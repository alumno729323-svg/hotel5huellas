/* Guía de bienvenida paso a paso — sin dependencias.
   Se abre sola en la primera visita y se puede repetir con el botón "❓ Guía". */
(function () {
  'use strict';

  var KEY = 'h5_tour_v1';
  var cfg = window.HotelCfg || {};
  var user = cfg.user || {};

  function guardado() { try { return localStorage.getItem(KEY) === '1'; } catch (e) { return false; } }
  function guardar() { try { localStorage.setItem(KEY, '1'); } catch (e) {} }

  /* ── Pasos ── (sel: selector o lista de selectores alternativos; el primero visible se usa) */
  function construirPasos() {
    var logged = !!user.logged;
    var p = [
      { centro: true, emoji: '🐾', t: '¡Bienvenido a Hotel 5 Huellas!',
        x: 'Te enseñamos la página en menos de un minuto. Puedes salir cuando quieras y repetir la guía con el botón “Guía” de arriba.' },
      { sel: ['#menu', '#burger'], t: 'Menú de navegación',
        x: 'Desde aquí saltas a cualquier parte de la página: servicios, habitaciones, reservar, cámara, galería, reseñas y contacto.' },
      { sel: '#extrasGrid', t: 'Servicios extra',
        x: 'Paseos, baño, videollamada, chequeo veterinario, spa o taxi. Los eliges más tarde al reservar.' },
      { sel: '#roomsGrid', t: 'Elige la habitación',
        x: 'Cozy, Confort o Suite. Cada una tiene un precio por noche y un número de plazas limitado.' },
      { sel: '#cal', t: 'Paso 1 · Elige las fechas',
        x: 'Pulsa el día de entrada y luego el de salida. Los días en rosa están completos y tus noches se marcan en amarillo.' },
      { sel: '#formReserva', t: 'Paso 2 · Cuéntanos de tu mascota',
        x: 'Rellena tu nombre, el de tu mascota, su especie y la habitación. Si tienes sesión iniciada, puedes usar una de tus mascotas guardadas.' },
      { sel: '#totalBox', t: 'Paso 3 · Mira el precio',
        x: 'Aquí aparece el desglose al momento. Cuando todo esté bien, pulsa “Enviar reserva”. Máximo 30 noches por reserva.' },
      { sel: '#petBtns', t: 'Cámara en directo',
        x: 'Pulsa un animal y pedimos una foto en directo para ver cómo están nuestros huéspedes.' },
      { sel: '#revResumen', t: 'Opiniones de otros clientes',
        x: 'Aquí ves la valoración media y las reseñas. Cuando hayas estado con nosotros, tú también podrás dejar la tuya.' }
    ];
    if (logged) {
      p.push({ sel: '#userBtn', t: 'Tu cuenta',
        x: 'Desde tu nombre abres tus reservas, tus mascotas y tu perfil.' + (user.staff ? ' Como personal tienes además el Panel del personal.' : '') });
    } else {
      p.push({ sel: '#btnAuth', t: 'Entrar o crear cuenta',
        x: 'Crea una cuenta para guardar a tus mascotas, ver tus reservas y dejar reseñas. Sin cuenta también puedes reservar.' });
    }
    p.push({ centro: true, emoji: '🎉', t: '¡Listo!',
      x: 'Ya sabes cómo funciona todo. Si quieres repasar algo, la guía te espera en el botón “Guía”.' });
    return p;
  }

  /* ── Estado ── */
  var pasos, i, activo = false;
  var elBloqueo, elFoco, elPop, previoFoco, destino = null, raf = 0;

  function visible(el) {
    if (!el || el.hidden) return false;
    var r = el.getBoundingClientRect();
    if (r.width < 2 || r.height < 2) return false;
    if (r.right <= 0 || r.left >= window.innerWidth) return false;
    return getComputedStyle(el).visibility !== 'hidden';
  }
  function buscar(sel) {
    var lista = Array.isArray(sel) ? sel : [sel];
    for (var k = 0; k < lista.length; k++) {
      var el = document.querySelector(lista[k]);
      if (visible(el)) return el;
    }
    return null;
  }

  function crear() {
    elBloqueo = document.createElement('div');
    elBloqueo.className = 'h5t-bloqueo';
    elFoco = document.createElement('div');
    elFoco.className = 'h5t-foco';
    elFoco.setAttribute('aria-hidden', 'true');
    elPop = document.createElement('div');
    elPop.className = 'h5t-pop';
    elPop.setAttribute('role', 'dialog');
    elPop.setAttribute('aria-modal', 'true');
    elPop.setAttribute('aria-labelledby', 'h5t-titulo');
    elPop.setAttribute('aria-describedby', 'h5t-texto');
    document.body.appendChild(elBloqueo);
    document.body.appendChild(elFoco);
    document.body.appendChild(elPop);
  }

  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  function pintarPop(paso, n, total) {
    var puntos = '';
    for (var k = 0; k < total; k++) puntos += '<i class="' + (k === n ? 'on' : (k < n ? 'hecho' : '')) + '"></i>';
    var ultimo = n === total - 1;
    elPop.className = 'h5t-pop' + (paso.centro ? ' h5t-centro' : '');
    elPop.innerHTML =
      '<div class="h5t-prog"><span class="h5t-puntos" aria-hidden="true">' + puntos + '</span><span>' + (n + 1) + ' de ' + total + '</span></div>' +
      (paso.emoji ? '<div class="h5t-emoji" aria-hidden="true">' + paso.emoji + '</div>' : '') +
      '<h3 id="h5t-titulo">' + esc(paso.t) + '</h3>' +
      '<p id="h5t-texto">' + esc(paso.x) + '</p>' +
      '<div class="h5t-pie">' +
        (ultimo ? '' : '<button type="button" class="h5t-saltar" data-a="saltar">Saltar guía</button>') +
        '<div class="h5t-acciones">' +
          (n > 0 ? '<button type="button" class="btn btn-claro" data-a="atras">← Atrás</button>' : '') +
          '<button type="button" class="btn" data-a="sig">' + (ultimo ? '¡Empezar! 🐾' : (n === 0 ? 'Empezar →' : 'Siguiente →')) + '</button>' +
        '</div>' +
      '</div>';
    var b = elPop.querySelector('[data-a="sig"]');
    if (b) b.focus({ preventScroll: true });
  }

  function colocar() {
    raf = 0;
    if (!activo) return;
    var paso = pasos[i];
    if (paso.centro || !destino) {
      elFoco.style.display = 'none';
      elBloqueo.classList.add('h5t-fondo');
      return;
    }
    elBloqueo.classList.remove('h5t-fondo');
    var r = destino.getBoundingClientRect();
    var pad = 8, vw = window.innerWidth, vh = window.innerHeight;
    var top = Math.max(r.top - pad, 4), left = Math.max(r.left - pad, 4);
    var w = Math.min(r.width + pad * 2, vw - left - 4);
    var h = Math.min(r.bottom + pad, vh - 4) - top;
    elFoco.style.display = 'block';
    elFoco.style.top = top + 'px';
    elFoco.style.left = left + 'px';
    elFoco.style.width = w + 'px';
    elFoco.style.height = Math.max(h, 24) + 'px';

    if (vw <= 640) { elPop.style.left = ''; elPop.style.top = ''; return; }

    var ph = elPop.offsetHeight, pw = elPop.offsetWidth, hueco = 14;
    var debajo = vh - (top + h) - hueco, encima = top - hueco;
    var y, x = Math.min(Math.max(left + w / 2 - pw / 2, 12), vw - pw - 12);
    if (debajo >= ph) y = top + h + hueco;
    else if (encima >= ph) y = top - hueco - ph;
    else {
      // el objetivo es muy alto: la tarjeta va a un lado si cabe, y si no abajo
      y = Math.min(Math.max(top, 12), vh - ph - 12);
      if (vw - (left + w) - hueco >= pw + 12) x = left + w + hueco;
      else if (left - hueco >= pw + 12) x = left - hueco - pw;
      else y = vh - ph - 12;
    }
    elPop.style.top = Math.max(y, 12) + 'px';
    elPop.style.left = x + 'px';
  }
  function pedirColocar() { if (!raf) raf = requestAnimationFrame(colocar); }

  function mostrar(n, dir) {
    // salta pasos cuyo objetivo no existe o no se ve
    while (n >= 0 && n < pasos.length && !pasos[n].centro && !buscar(pasos[n].sel)) n += dir;
    if (n < 0) n = 0;
    if (n >= pasos.length) return cerrar(true);
    i = n;
    var paso = pasos[i];
    destino = paso.centro ? null : buscar(paso.sel);
    pintarPop(paso, i, pasos.length);

    if (destino) {
      var html = document.documentElement, prev = html.style.scrollBehavior;
      html.style.scrollBehavior = 'auto';
      var hh = destino.getBoundingClientRect().height, vh = window.innerHeight;
      var alto = hh > vh * 0.55 || (window.innerWidth <= 640 && hh > vh * 0.3);
      destino.scrollIntoView({ block: alto ? 'start' : 'center', inline: 'nearest' });
      html.style.scrollBehavior = prev;
    }
    elPop.style.left = ''; elPop.style.top = '';
    colocar();
    setTimeout(colocar, 60);
  }

  function cerrar(completo) {
    if (!activo) return;
    activo = false;
    guardar();
    window.removeEventListener('resize', pedirColocar);
    window.removeEventListener('scroll', pedirColocar, true);
    document.removeEventListener('keydown', teclas, true);
    [elBloqueo, elFoco, elPop].forEach(function (e) { if (e && e.parentNode) e.parentNode.removeChild(e); });
    if (previoFoco && previoFoco.focus) { try { previoFoco.focus({ preventScroll: true }); } catch (e) {} }
    if (completo) window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function teclas(e) {
    if (!activo) return;
    if (e.key === 'Escape') { e.preventDefault(); cerrar(false); }
    else if (e.key === 'ArrowRight') { e.preventDefault(); mostrar(i + 1, 1); }
    else if (e.key === 'ArrowLeft') { e.preventDefault(); mostrar(i - 1, -1); }
    else if (e.key === 'Tab') {
      var f = elPop.querySelectorAll('button');
      if (!f.length) return;
      var a = f[0], z = f[f.length - 1];
      if (e.shiftKey && document.activeElement === a) { e.preventDefault(); z.focus(); }
      else if (!e.shiftKey && document.activeElement === z) { e.preventDefault(); a.focus(); }
      else if (!elPop.contains(document.activeElement)) { e.preventDefault(); a.focus(); }
    }
  }

  function empezar() {
    if (activo) return;
    pasos = construirPasos();
    previoFoco = document.activeElement;
    activo = true;
    crear();
    elPop.addEventListener('click', function (e) {
      var b = e.target.closest('[data-a]');
      if (!b) return;
      var a = b.getAttribute('data-a');
      if (a === 'sig') { if (i >= pasos.length - 1) cerrar(true); else mostrar(i + 1, 1); }
      else if (a === 'atras') mostrar(i - 1, -1);
      else if (a === 'saltar') cerrar(false);
    });
    window.addEventListener('resize', pedirColocar);
    window.addEventListener('scroll', pedirColocar, true);
    document.addEventListener('keydown', teclas, true);
    mostrar(0, 1);
  }

  /* ── Botón "Guía" en la cabecera ── */
  function botonAyuda() {
    var cont = document.querySelector('.cuenta');
    if (!cont || document.getElementById('h5t-btn')) return;
    var b = document.createElement('button');
    b.type = 'button';
    b.id = 'h5t-btn';
    b.className = 'btn btn-mini btn-claro';
    b.setAttribute('aria-label', 'Ver la guía de la página');
    b.innerHTML = '❓ <span>Guía</span>';
    b.addEventListener('click', empezar);
    cont.insertBefore(b, cont.firstChild);
  }

  function iniciar() {
    botonAyuda();
    if (!guardado()) setTimeout(function () {
      // no interrumpir si ya hay un diálogo abierto (p. ej. el de entrar)
      var abierto = document.querySelector('dialog[open]');
      if (!abierto) empezar();
    }, 900);
  }

  window.H5Tour = { empezar: empezar, reiniciar: function () { try { localStorage.removeItem(KEY); } catch (e) {} empezar(); } };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
  else iniciar();
})();
