(function () {
  'use strict';
  var C = window.HotelCfg || { root: '', nonce: '', user: {}, planes: {}, extras: {} };
  C.user = C.user || {};
  var $ = function (s, el) { return (el || document).querySelector(s); };
  var $$ = function (s, el) { return Array.prototype.slice.call((el || document).querySelectorAll(s)); };
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var eur = function (n) { return Number(n).toLocaleString('es-ES', { maximumFractionDigits: 2 }) + ' €'; };
  var stars = function (n) { return '★'.repeat(n) + '☆'.repeat(5 - n); };
  var ESP = { perro: '🐶 Perro', gato: '🐱 Gato', pajaro: '🐦 Pájaro', conejo: '🐰 Conejo', hamster: '🐹 Hámster', otro: '🐾 Otro' };
  var emoji = function (e) { return (ESP[e] || ESP.otro).split(' ')[0]; };
  var nombreEsp = function (e) { return (ESP[e] || ESP.otro).split(' ').slice(1).join(' '); };

  document.documentElement.classList.add('js');

  /* ── API ── */
  function api(path, opt) {
    opt = opt || {};
    var url = C.root + path;
    if (opt.params) {
      var qs = new URLSearchParams(opt.params).toString();
      url += (url.indexOf('?') > -1 ? '&' : '?') + qs;
    }
    var init = { method: opt.method || 'GET', headers: { 'X-WP-Nonce': C.nonce }, credentials: 'same-origin' };
    if (opt.body) { init.headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(opt.body); }
    return fetch(url, init).then(function (res) {
      return res.json().catch(function () { return null; }).then(function (data) {
        if (!res.ok) throw new Error((data && data.message) || ('Error ' + res.status));
        return data;
      });
    }, function () { throw new Error('Sin conexión con el servidor'); });
  }

  var toastT;
  function toast(msg) {
    var t = $('#toast'); if (!t) return;
    t.textContent = msg; t.classList.add('show');
    clearTimeout(toastT); toastT = setTimeout(function () { t.classList.remove('show'); }, 3400);
  }
  function setMsg(el, txt, ok) { if (!el) return; el.textContent = txt || ''; el.className = 'msg ' + (txt ? (ok ? 'ok' : 'err') : ''); }
  function aviso(txt) { try { sessionStorage.setItem('h5_aviso', txt); } catch (e) {} }
  try {
    var av = sessionStorage.getItem('h5_aviso');
    if (av) { sessionStorage.removeItem('h5_aviso'); setTimeout(function () { toast(av); }, 400); }
  } catch (e) {}

  /* ── Menú móvil, enlace activo y animación al entrar ── */
  var menu = $('#menu'), burger = $('#burger');
  if (burger) {
    burger.addEventListener('click', function () {
      var abierto = menu.classList.toggle('open');
      burger.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
    $$('a', menu).forEach(function (a) { a.addEventListener('click', function () { menu.classList.remove('open'); burger.setAttribute('aria-expanded', 'false'); }); });
  }
  var secciones = $$('#menu a').map(function (a) { return { a: a, el: $(a.getAttribute('href')) }; }).filter(function (x) { return x.el; });
  var tick = false;
  function marcarActivo() {
    tick = false;
    var actual = null;
    secciones.forEach(function (x) { if (x.el.getBoundingClientRect().top <= 140) actual = x; });
    secciones.forEach(function (x) { x.a.classList.toggle('on', x === actual); });
  }
  window.addEventListener('scroll', function () { if (!tick) { tick = true; requestAnimationFrame(marcarActivo); } }, { passive: true });
  marcarActivo();

  var apar = $$('.card,.paso,.stats .stat,.cal-wrap,#formReserva,.pet,.info,#formContacto,.faq details,.resena-form,.resumen');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } });
    }, { threshold: 0.08 });
    apar.forEach(function (el) { el.classList.add('aparece'); io.observe(el); });
  }

  /* ── Menú de usuario y "Mi cuenta" ── */
  var drop = $('#userDrop'), userBtn = $('#userBtn');
  function cerrarDrop() { if (drop) { drop.hidden = true; userBtn.setAttribute('aria-expanded', 'false'); } }
  if (userBtn) {
    userBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      drop.hidden = !drop.hidden; userBtn.setAttribute('aria-expanded', drop.hidden ? 'false' : 'true');
    });
    document.addEventListener('click', function (e) { if (drop && !drop.contains(e.target)) cerrarDrop(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarDrop(); });
  }
  function tabCuenta(t) {
    $$('.acc-tab').forEach(function (b) { b.classList.toggle('on', b.dataset.tab === t); });
    $$('.acc-pane').forEach(function (p) { p.hidden = p.dataset.pane !== t; });
  }
  $$('.acc-tab').forEach(function (b) { b.addEventListener('click', function () { tabCuenta(b.dataset.tab); }); });
  $$('#userDrop a[data-tab]').forEach(function (a) { a.addEventListener('click', function () { tabCuenta(a.dataset.tab); cerrarDrop(); }); });
  if ($('#linkPanel')) $('#linkPanel').addEventListener('click', cerrarDrop);
  if ($('#btnLogout')) $('#btnLogout').addEventListener('click', function () {
    api('auth/logout', { method: 'POST' }).then(function () { aviso('Sesión cerrada. ¡Hasta pronto! 🐾'); location.reload(); })
      .catch(function (e) { toast(e.message); });
  });

  /* ── Entrar / crear cuenta ── */
  var dlg = $('#authDlg');
  function cerrarAuth() { if (dlg.close) dlg.close(); else dlg.removeAttribute('open'); }
  function cambiarTab(t) {
    $$('.tab', dlg).forEach(function (b) { b.classList.toggle('on', b.dataset.tab === t); });
    $('#formLogin').hidden = t !== 'login';
    $('#formRegistro').hidden = t !== 'registro';
    $('#authTitulo').textContent = t === 'login' ? 'Bienvenido de nuevo' : 'Crea tu cuenta';
    setMsg($('#authMsg'), '');
    $$('input', dlg).forEach(function (i) { i.classList.remove('malo'); });
    var foco = $(t === 'login' ? '#lUsuario' : '#gNombre'); if (foco) setTimeout(function () { foco.focus(); }, 30);
  }
  function abrirAuth(tab) {
    if (!dlg) return;
    if (dlg.showModal) { if (!dlg.open) dlg.showModal(); } else dlg.setAttribute('open', '');
    cambiarTab(tab || 'login');
  }
  if (dlg) {
    if ($('#btnAuth')) $('#btnAuth').addEventListener('click', function () { abrirAuth('login'); });
    $$('.abrir-auth').forEach(function (b) { b.addEventListener('click', function () { abrirAuth('registro'); }); });
    $('#authClose').addEventListener('click', cerrarAuth);
    dlg.addEventListener('click', function (e) { if (e.target === dlg) cerrarAuth(); });
    $$('.tab', dlg).forEach(function (b) { b.addEventListener('click', function () { cambiarTab(b.dataset.tab); }); });
    $$('.cambiar', dlg).forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); cambiarTab(a.dataset.tab); }); });
    $$('.eye', dlg).forEach(function (b) {
      b.addEventListener('click', function () { var i = $('#' + b.dataset.for); i.type = i.type === 'password' ? 'text' : 'password'; });
    });

    var fuerza = function (p) {
      var n = 0;
      if (p.length >= 8) n++;
      if (/[A-Z]/.test(p) && /[a-z]/.test(p)) n++;
      if (/\d/.test(p)) n++;
      if (/[^A-Za-z0-9]/.test(p)) n++;
      if (p.length < 8) n = Math.min(n, 1);
      return n;
    };
    var NIVELES = ['Escribe una contraseña', 'Muy débil', 'Aceptable', 'Buena', 'Excelente'];
    $('#gClave').addEventListener('input', function () {
      var n = this.value ? fuerza(this.value) : 0;
      if (this.value && n === 0) n = 1;
      $('#pwMeter').className = 'meter' + (n ? ' n' + n : '');
      $('#pwTxt').textContent = this.value ? 'Seguridad: ' + NIVELES[n] : 'Usa letras, números y un símbolo.';
    });

    var marcar = function (sel, mal) { $(sel).classList.toggle('malo', !!mal); return !!mal; };
    var enviar = function (form, ruta, body, ok) {
      var btn = $('button[type=submit]', form); btn.disabled = true; setMsg($('#authMsg'), '');
      api(ruta, { method: 'POST', body: body }).then(function (d) {
        setMsg($('#authMsg'), '¡Listo! Entrando…', true);
        aviso(ok(d)); location.reload(); // se recarga para tener la sesión (y el nonce) nuevos
      }).catch(function (e) { setMsg($('#authMsg'), e.message); btn.disabled = false; });
    };

    $('#formLogin').addEventListener('submit', function (e) {
      e.preventDefault();
      var m1 = marcar('#lUsuario', !$('#lUsuario').value.trim()), m2 = marcar('#lClave', !$('#lClave').value);
      if (m1 || m2) { setMsg($('#authMsg'), 'Escribe tu usuario y tu contraseña.'); return; }
      enviar(e.target, 'auth/login', { usuario: $('#lUsuario').value.trim(), clave: $('#lClave').value, recordar: $('#lRecordar').checked },
        function (d) { return '¡Hola, ' + d.name + '! 🐾'; });
    });

    $('#formRegistro').addEventListener('submit', function (e) {
      e.preventDefault();
      var malos = [
        marcar('#gUsuario', !/^[A-Za-z0-9_.-]{3,30}$/.test($('#gUsuario').value.trim())),
        marcar('#gEmail', !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($('#gEmail').value.trim())),
        marcar('#gClave', $('#gClave').value.length < 8),
        marcar('#gClave2', $('#gClave2').value !== $('#gClave').value)
      ];
      if (malos[0]) return setMsg($('#authMsg'), 'Usuario: 3-30 caracteres (letras, números, . _ -).');
      if (malos[1]) return setMsg($('#authMsg'), 'Escribe un email válido.');
      if (malos[2]) return setMsg($('#authMsg'), 'La contraseña debe tener al menos 8 caracteres.');
      if (malos[3]) return setMsg($('#authMsg'), 'Las contraseñas no coinciden.');
      enviar(e.target, 'auth/registro', { nombre: $('#gNombre').value, usuario: $('#gUsuario').value.trim(), email: $('#gEmail').value.trim(), clave: $('#gClave').value },
        function (d) { return '¡Cuenta creada! Bienvenido/a, ' + d.name + ' 🎉'; });
    });
  }

  /* ── Fotos de animales ── */
  var TIPOS = ['perro', 'gato', 'pajaro', 'conejo'];
  var azar = function (a) { return a[Math.floor(Math.random() * a.length)]; };
  function fotoDe(tipo) {
    return api('animal', { params: { tipo: tipo } }).then(function (d) {
      return new Promise(function (res, rej) {
        var im = new Image();
        im.onload = function () { res(d.url); };
        im.onerror = function () { rej(new Error('No se pudo cargar la foto')); };
        im.src = d.url;
      });
    });
  }

  var hero = $('#heroFoto');
  function heroOtra(tipo) { fotoDe(tipo || 'perro').then(function (u) { hero.src = u; }).catch(function () {}); }
  if (hero) {
    heroOtra('perro');
    $('#heroOtra').addEventListener('click', function () { heroOtra(azar(TIPOS)); });
  }

  var ESTADOS = {
    perro: ['comiendo su pienso', 'jugando con la pelota', 'echando la siesta', 'dando un paseo por el jardín', 'moviendo la cola'],
    gato: ['tomando el sol en la ventana', 'persiguiendo una pluma', 'ronroneando', 'echando la siesta', 'inspeccionando su cama'],
    pajaro: ['cantando en su percha', 'dándose un baño', 'picoteando semillas', 'estirando las alas', 'saltando de rama en rama'],
    conejo: ['mordisqueando heno', 'dando saltitos', 'descansando en su casita', 'comiendo zanahoria', 'estirándose al sol']
  };
  var pf = $('#petFoto');
  $$('#petBtns button').forEach(function (b) {
    b.addEventListener('click', function () {
      var tipo = b.dataset.tipo, info = $('#petInfo'), marco = pf.parentNode;
      info.textContent = 'Pidiendo foto a la cámara… 📸';
      fotoDe(tipo).then(function (u) {
        pf.src = u; marco.classList.add('cargada');
        var h = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        info.innerHTML = 'Tu ' + esc(nombreEsp(tipo).toLowerCase()) + ' está <strong>' + esc(azar(ESTADOS[tipo])) + '</strong>. ¡Se encuentra genial! 🐾<span class="hora">Foto de las ' + h + '</span>';
      }).catch(function (e) { info.textContent = 'La cámara de este animal no responde ahora mismo (' + e.message + '). Prueba con otro.'; });
    });
  });

  function pintarGaleria() {
    var g = $('#galeriaGrid'); if (!g) return;
    var tipos = ['perro', 'gato', 'pajaro', 'conejo', azar(TIPOS), azar(TIPOS)].sort(function () { return Math.random() - 0.5; });
    g.innerHTML = tipos.map(function (t) { return '<div class="caja gal-item" data-t="' + t + '">' + emoji(t) + '<span>' + esc(nombreEsp(t)) + '</span></div>'; }).join('');
    $$('.gal-item', g).forEach(function (el) {
      fotoDe(el.dataset.t).then(function (u) {
        var im = document.createElement('img'); im.src = u; im.alt = 'Huésped: ' + nombreEsp(el.dataset.t); el.insertBefore(im, el.firstChild);
      }).catch(function () {});
    });
  }
  if ($('#galeriaOtra')) { $('#galeriaOtra').addEventListener('click', pintarGaleria); pintarGaleria(); }

  /* ── Tiempo (Open-Meteo), moneda (Frankfurter) y cifras ── */
  var WX = { 0: ['☀️', 'Despejado'], 1: ['🌤️', 'Casi despejado'], 2: ['⛅', 'Parcialmente nublado'], 3: ['☁️', 'Nublado'], 45: ['🌫️', 'Niebla'], 48: ['🌫️', 'Niebla'], 51: ['🌦️', 'Llovizna'], 61: ['🌧️', 'Lluvia'], 63: ['🌧️', 'Lluvia'], 65: ['🌧️', 'Lluvia fuerte'], 71: ['🌨️', 'Nieve'], 80: ['🌦️', 'Chubascos'], 95: ['⛈️', 'Tormenta'] };
  fetch('https://api.open-meteo.com/v1/forecast?latitude=40.4168&longitude=-3.7038&current=temperature_2m,weather_code&timezone=auto')
    .then(function (r) { return r.json(); }).then(function (d) {
      var c = d.current, w = WX[c.weather_code] || ['🌡️', 'Tiempo en Madrid'];
      $('#wxIcon').textContent = w[0]; $('#wxTemp').textContent = Math.round(c.temperature_2m) + '°C'; $('#wxDesc').textContent = w[1] + ' · Madrid';
    }).catch(function () { $('#wxDesc').textContent = 'Madrid'; });

  fetch('https://api.frankfurter.dev/v1/latest?base=EUR&symbols=USD,GBP')
    .then(function (r) { return r.json(); }).then(function (d) {
      $$('.usd').forEach(function (el) {
        var e = Number(el.dataset.eur);
        el.textContent = '≈ $' + (e * d.rates.USD).toFixed(0) + ' · £' + (e * d.rates.GBP).toFixed(0);
      });
      $('#monedaNota').textContent = 'Cambio orientativo del día (' + d.date + '). Se cobra en euros.';
    }).catch(function () {});

  function cargarStats() {
    if (!$('#stReservas')) return;
    api('estadisticas').then(function (d) {
      $('#stReservas').textContent = d.reservas;
      $('#stResenas').textContent = d.resenas;
      $('#stMedia').textContent = d.resenas ? d.media.toFixed(1) + ' ★' : '–';
    }).catch(function () {});
  }

  /* ── Calendario y reserva ── */
  var cal = { y: 0, m: 0, desde: null, hasta: null, llenos: {} };
  var hoy = new Date(); var hoyS = iso(hoy);
  cal.y = hoy.getFullYear(); cal.m = hoy.getMonth();
  function iso(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
  function addDias(s, n) { var d = new Date(s + 'T12:00:00'); d.setDate(d.getDate() + n); return iso(d); }
  function noches() { return cal.desde && cal.hasta ? Math.round((new Date(cal.hasta + 'T12:00:00') - new Date(cal.desde + 'T12:00:00')) / 864e5) : 0; }

  function renderCal() {
    var box = $('#cal'); if (!box) return;
    var primero = new Date(cal.y, cal.m, 1);
    $('#calTitle').textContent = primero.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
    var html = ['L', 'M', 'X', 'J', 'V', 'S', 'D'].map(function (d) { return '<div class="dw">' + d + '</div>'; }).join('');
    var offset = (primero.getDay() + 6) % 7;
    for (var i = 0; i < offset; i++) html += '<button class="vacio" disabled tabindex="-1"></button>';
    var dias = new Date(cal.y, cal.m + 1, 0).getDate();
    for (var d = 1; d <= dias; d++) {
      var s = cal.y + '-' + String(cal.m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
      var cls = [];
      if (cal.llenos[s]) cls.push('lleno');
      if (cal.desde && cal.hasta && s > cal.desde && s < cal.hasta) cls.push('rango');
      if (s === cal.desde || s === cal.hasta) cls.push('ext');
      html += '<button type="button" data-d="' + s + '" class="' + cls.join(' ') + '"' + (s < hoyS ? ' disabled' : '') + '>' + d + '</button>';
    }
    box.innerHTML = html;
    var r = $('#selRango');
    if (cal.desde && cal.hasta) r.textContent = 'Del ' + cal.desde + ' al ' + cal.hasta + ' · ' + noches() + (noches() === 1 ? ' noche' : ' noches');
    else if (cal.desde) r.textContent = 'Entrada: ' + cal.desde + '. Ahora elige el día de salida.';
    else r.textContent = 'Elige día de entrada y de salida.';
    $('#fDesde').value = cal.desde || ''; $('#fHasta').value = cal.hasta || '';
    actualizarTotal();
  }

  function rangoLibre(d, h) {
    for (var s = d; s < h; s = addDias(s, 1)) if (cal.llenos[s]) return false;
    return true;
  }

  if ($('#cal')) {
    $('#cal').addEventListener('click', function (e) {
      var b = e.target.closest('button[data-d]'); if (!b || b.disabled) return;
      var s = b.dataset.d;
      if (!cal.desde || (cal.desde && cal.hasta)) { cal.desde = s; cal.hasta = null; }
      else if (s > cal.desde) {
        if (!rangoLibre(cal.desde, s)) { toast('Hay días completos en ese rango. Prueba otras fechas.'); cal.desde = null; cal.hasta = null; }
        else cal.hasta = s;
      } else { cal.desde = s; cal.hasta = null; }
      renderCal();
    });
    $('#calPrev').addEventListener('click', function () { cal.m--; if (cal.m < 0) { cal.m = 11; cal.y--; } renderCal(); });
    $('#calNext').addEventListener('click', function () { cal.m++; if (cal.m > 11) { cal.m = 0; cal.y++; } renderCal(); });
  }

  function cargarDisponibilidad() {
    var plan = $('#rPlan').value;
    api('disponibilidad', { params: { plan: plan, desde: hoyS, dias: 180 } }).then(function (d) {
      cal.llenos = {}; d.llenos.forEach(function (x) { cal.llenos[x] = 1; });
      if (cal.desde && cal.hasta && !rangoLibre(cal.desde, cal.hasta)) { cal.desde = cal.hasta = null; toast('Esas fechas están completas en esta habitación.'); }
      renderCal();
    }).catch(function () { renderCal(); });
  }

  function extrasMarcados() { return $$('#extrasChecks input:checked').map(function (i) { return i.value; }); }

  function actualizarTotal() {
    var box = $('#totalBox'); var n = noches(); var p = C.planes[$('#rPlan').value];
    if (!n || !p) { box.textContent = 'Elige fechas para ver el precio.'; return; }
    var li = ['<li>' + esc(p.nombre) + ' × ' + n + ' → ' + eur(p.precio * n) + '</li>'];
    var total = p.precio * n;
    extrasMarcados().forEach(function (id) {
      var e = C.extras[id]; if (!e) return;
      var imp = e.tipo === 'dia' ? e.precio * n : e.precio; total += imp;
      li.push('<li>' + esc(e.nombre) + (e.tipo === 'dia' ? ' × ' + n : '') + ' → ' + eur(imp) + '</li>');
    });
    box.innerHTML = '<ul>' + li.join('') + '</ul><span class="t">Total: ' + eur(total) + '</span>';
  }

  function elegirPlan(id) {
    $('#rPlan').value = id;
    $$('.room').forEach(function (r) { r.classList.toggle('sel', r.dataset.plan === id); });
    cargarDisponibilidad();
  }
  if ($('#rPlan')) {
    $('#rPlan').addEventListener('change', function () { elegirPlan(this.value); });
    $('#extrasChecks').addEventListener('change', actualizarTotal);
    $$('.elegir').forEach(function (b) {
      b.addEventListener('click', function () { elegirPlan(b.dataset.plan); $('#reservar').scrollIntoView({ behavior: 'smooth' }); });
    });
    elegirPlan($('#rPlan').value);
  }

  if ($('#rMiMascota')) {
    $('#rMiMascota').addEventListener('change', function () {
      var m = misMascotas.filter(function (x) { return x.id === this.value; }, this)[0];
      if (m) { $('#rMascota').value = m.nombre; $('#rEspecie').value = m.especie; }
    });
  }

  if ($('#formReserva')) {
    $('#formReserva').addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = $('#resMsg'); setMsg(msg, '');
      if (!cal.desde || !cal.hasta) { setMsg(msg, 'Elige las fechas en el calendario.'); return; }
      var btn = $('#btnReservar'); btn.disabled = true;
      api('reservas', { method: 'POST', body: {
        nombre: $('#rNombre').value, email: $('#rEmail').value, mascota: $('#rMascota').value,
        especie: $('#rEspecie').value, plan: $('#rPlan').value, desde: cal.desde, hasta: cal.hasta, extras: extrasMarcados()
      } }).then(function (d) {
        setMsg(msg, d.mensaje + ' Total: ' + eur(d.total), true);
        toast('¡Reserva enviada! 🐾');
        cal.desde = cal.hasta = null; $$('#extrasChecks input').forEach(function (i) { i.checked = false; });
        cargarDisponibilidad(); cargarUltimas(); cargarMias(); cargarStats();
      }).catch(function (err) { setMsg(msg, err.message); }).then(function () { btn.disabled = false; });
    });
  }

  /* ── Listas de reservas ── */
  function itemReserva(r, staff) {
    var ex = r.extras && r.extras.length ? '<p>Extras: ' + esc(r.extras.join(', ')) + '</p>' : '';
    var h = '<article class="caja item"><h4>' + emoji(r.especie) + ' ' + esc(r.mascota) + ' <span class="badge ' + esc(r.estado) + '">' + esc(r.estado) + '</span></h4>' +
      '<p>' + esc(nombreEsp(r.especie)) + ' · Habitación ' + esc(r.plan) + '</p><p>' + esc(r.desde) + ' → ' + esc(r.hasta) + '</p>' + ex +
      '<p>Dueño: ' + esc(r.dueno) + (staff && r.email ? ' · ' + esc(r.email) : '') + '</p>';
    if (staff) {
      h += '<p><strong>' + eur(r.total) + '</strong> · creada ' + esc(r.creada) + '</p><div class="acciones">' +
        ['pendiente', 'confirmada', 'cancelada'].map(function (s) {
          return '<button class="btn btn-mini ' + (r.estado === s ? 'btn-amarillo' : 'btn-claro') + '" data-id="' + r.id + '" data-estado="' + s + '">' + s + '</button>';
        }).join('') + '</div>';
    }
    return h + '</article>';
  }
  function lista(el, arr, fn, vacio) {
    if (!el) return;
    el.innerHTML = arr.length ? arr.map(fn).join('') : '<p class="vacio-txt">' + vacio + '</p>';
  }

  function cargarUltimas() {
    var el = $('#ultimas'); if (!el) return;
    api('reservas').then(function (a) { lista(el, a, function (r) { return itemReserva(r, false); }, 'Todavía no hay reservas. ¡Sé el primero!'); })
      .catch(function (e) { el.innerHTML = '<p class="vacio-txt">' + esc(e.message) + '</p>'; });
  }
  function cargarMias() {
    if (!C.user.logged) return;
    api('mis-reservas').then(function (a) { lista($('#misList'), a, function (r) { return itemReserva(r, false); }, 'Aún no tienes reservas. ¡Haz la primera!'); }).catch(function () {});
  }

  /* ── Mis mascotas ── */
  var misMascotas = [];
  function pintarMascotas() {
    lista($('#petList'), misMascotas, function (m) {
      return '<article class="caja item mascota-card"><span class="ico">' + emoji(m.especie) + '</span><div><strong>' + esc(m.nombre) + '</strong><span>' + esc(nombreEsp(m.especie)) + '</span></div>' +
        '<button class="btn btn-mini" type="button" data-usar="' + esc(m.id) + '">Reservar</button>' +
        '<button class="btn btn-mini btn-claro" type="button" data-del="' + esc(m.id) + '">Quitar</button></article>';
    }, 'Aún no has guardado mascotas.');
    var sel = $('#rMiMascota'); if (!sel) return;
    sel.innerHTML = '<option value="">— Elegir —</option>' + misMascotas.map(function (m) { return '<option value="' + esc(m.id) + '">' + emoji(m.especie) + ' ' + esc(m.nombre) + '</option>'; }).join('');
    $('#miMascotaWrap').hidden = !misMascotas.length;
  }
  function cargarMascotas() {
    if (!C.user.logged) return;
    api('mascotas').then(function (a) { misMascotas = a; pintarMascotas(); }).catch(function () {});
  }
  if ($('#formMascota')) {
    $('#formMascota').addEventListener('submit', function (e) {
      e.preventDefault(); var msg = $('#mascotaMsg'); setMsg(msg, '');
      api('mascotas', { method: 'POST', body: { nombre: $('#mNombre').value, especie: $('#mEspecie').value } }).then(function (d) {
        misMascotas = d.mascotas; pintarMascotas(); $('#mNombre').value = ''; setMsg(msg, 'Mascota guardada 🐾', true);
      }).catch(function (err) { setMsg(msg, err.message); });
    });
    $('#petList').addEventListener('click', function (e) {
      var del = e.target.closest('[data-del]'), usar = e.target.closest('[data-usar]');
      if (del) {
        api('mascotas/' + del.dataset.del, { method: 'DELETE' }).then(function (d) { misMascotas = d.mascotas; pintarMascotas(); toast('Mascota eliminada'); })
          .catch(function (err) { toast(err.message); });
      } else if (usar) {
        var sel = $('#rMiMascota'); sel.value = usar.dataset.usar; sel.dispatchEvent(new Event('change'));
        $('#reservar').scrollIntoView({ behavior: 'smooth' });
      }
    });
  }

  /* ── Mi perfil ── */
  if ($('#formPerfil')) {
    $('#formPerfil').addEventListener('submit', function (e) {
      e.preventDefault(); var msg = $('#perfilMsg'); setMsg(msg, '');
      api('perfil', { method: 'POST', body: { nombre: $('#pNombre').value, clave_actual: $('#pActual').value, clave_nueva: $('#pNueva').value } }).then(function (d) {
        if (d.clave) { aviso('Contraseña actualizada ✅'); location.reload(); return; }
        $('#chipNombre').textContent = d.name; $('#avatar').textContent = d.name.charAt(0).toUpperCase();
        setMsg(msg, 'Perfil guardado ✅', true);
      }).catch(function (err) { setMsg(msg, err.message); });
    });
  }

  /* ── Reseñas ── */
  var valor = 0;
  function pintarStars() { $$('#starPick .star').forEach(function (s) { var v = +s.dataset.v; s.textContent = v <= valor ? '★' : '☆'; s.classList.toggle('on', v === valor); }); }
  function cargarResenas() {
    var res = $('#revResumen'); if (!res) return;
    api('resenas').then(function (d) {
      res.innerHTML = d.total
        ? '<div class="media">' + d.media.toFixed(1) + '</div><div class="estrellas">' + stars(Math.round(d.media)) + '</div><p>' + d.total + (d.total === 1 ? ' reseña' : ' reseñas') + '</p>'
        : '<p>Aún no hay reseñas.<br>¡Escribe la primera!</p>';
      lista($('#revList'), d.items, function (r) {
        return '<article class="caja item"><div class="estrellas">' + stars(r.rating) + '</div><p>«' + esc(r.texto) + '»</p><p><strong>' + esc(r.autor) + '</strong> · ' + esc(r.fecha) + (r.mia ? ' · <span class="badge">tuya</span>' : '') + '</p></article>';
      }, '');
      var mia = d.items.filter(function (r) { return r.mia; })[0];
      if (mia && !valor) { valor = mia.rating; $('#revTexto').value = mia.texto; pintarStars(); }
    }).catch(function (e) { res.textContent = e.message; });
  }
  if ($('#starPick')) {
    $('#starPick').addEventListener('click', function (e) { var b = e.target.closest('.star'); if (b) { valor = +b.dataset.v; pintarStars(); } });
    pintarStars();
    $('#formResena').addEventListener('submit', function (e) {
      e.preventDefault(); var msg = $('#revMsg');
      if (!valor) { setMsg(msg, 'Elige de 1 a 5 estrellas.'); return; }
      api('resenas', { method: 'POST', body: { rating: valor, texto: $('#revTexto').value } }).then(function (d) {
        setMsg(msg, d.actualizada ? 'Reseña actualizada ✅' : '¡Gracias por tu reseña! 🐾', true); cargarResenas(); cargarStats();
      }).catch(function (err) { setMsg(msg, err.message); });
    });
  }

  /* ── Contacto ── */
  if ($('#formContacto')) {
    $('#formContacto').addEventListener('submit', function (e) {
      e.preventDefault(); var msg = $('#contMsg'); setMsg(msg, '');
      api('contacto', { method: 'POST', body: {
        nombre: $('#cNombre').value, email: $('#cEmail').value, asunto: $('#cAsunto').value, mensaje: $('#cMensaje').value, web: $('#cWeb').value
      } }).then(function (d) {
        setMsg(msg, d.mensaje || 'Enviado', true); toast('Mensaje enviado ✉️');
        $('#cAsunto').value = ''; $('#cMensaje').value = '';
        if (C.user.staff) cargarPanel();
      }).catch(function (err) { setMsg(msg, err.message); });
    });
  }

  /* ── Panel del personal ── */
  var panel = { reservas: [], mensajes: [], filtro: 'todas' };
  function pintarPanel() {
    var r = panel.reservas, c = function (e) { return r.filter(function (x) { return x.estado === e; }).length; };
    $('#panelStats').innerHTML =
      '<div class="caja stat"><b>' + c('pendiente') + '</b><span>pendientes</span></div>' +
      '<div class="caja stat"><b>' + c('confirmada') + '</b><span>confirmadas</span></div>' +
      '<div class="caja stat"><b>' + c('cancelada') + '</b><span>canceladas</span></div>' +
      '<div class="caja stat"><b>' + panel.mensajes.length + '</b><span>mensajes</span></div>';
    $$('#panelFiltro button').forEach(function (b) {
      b.classList.toggle('btn-amarillo', b.dataset.f === panel.filtro); b.classList.toggle('btn-claro', b.dataset.f !== panel.filtro);
    });
    var vis = panel.filtro === 'todas' ? r : r.filter(function (x) { return x.estado === panel.filtro; });
    lista($('#panelRes'), vis, function (x) { return itemReserva(x, true); }, 'No hay reservas en este filtro.');
    lista($('#panelMsg'), panel.mensajes, function (m) {
      return '<article class="caja item"><h4>' + esc(m.asunto) + '</h4><p>' + esc(m.texto) + '</p><p><strong>' + esc(m.nombre) + '</strong> · ' + esc(m.email) + ' · ' + esc(m.fecha) + '</p></article>';
    }, 'No hay mensajes.');
  }
  function cargarPanel() {
    if (!C.user.staff) return;
    api('panel').then(function (d) { panel.reservas = d.reservas; panel.mensajes = d.mensajes; pintarPanel(); }).catch(function (e) { toast(e.message); });
  }
  if ($('#panelRes')) {
    $('#panelFiltro').addEventListener('click', function (e) {
      var b = e.target.closest('button[data-f]'); if (!b) return; panel.filtro = b.dataset.f; pintarPanel();
    });
    $('#panelRes').addEventListener('click', function (e) {
      var b = e.target.closest('button[data-estado]'); if (!b) return;
      api('reservas/' + b.dataset.id + '/estado', { method: 'POST', body: { estado: b.dataset.estado } })
        .then(function () { toast('Estado actualizado'); cargarPanel(); cargarUltimas(); cargarDisponibilidad(); })
        .catch(function (err) { toast(err.message); });
    });
  }

  /* ── Inicio ── */
  cargarStats(); cargarUltimas(); cargarResenas(); cargarMias(); cargarMascotas(); cargarPanel();
})();