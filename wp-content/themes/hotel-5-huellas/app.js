(function () {
  'use strict';
  var C = window.HotelCfg || { root: '', nonce: '', user: {}, planes: {}, extras: {} };
  var $ = function (s, el) { return (el || document).querySelector(s); };
  var $$ = function (s, el) { return Array.prototype.slice.call((el || document).querySelectorAll(s)); };
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var eur = function (n) { return Number(n).toLocaleString('es-ES', { maximumFractionDigits: 2 }) + ' €'; };
  var stars = function (n) { return '★'.repeat(n) + '☆'.repeat(5 - n); };

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
    clearTimeout(toastT); toastT = setTimeout(function () { t.classList.remove('show'); }, 3200);
  }
  function setMsg(el, txt, ok) { if (!el) return; el.textContent = txt || ''; el.className = 'msg ' + (txt ? (ok ? 'ok' : 'err') : ''); }

  /* ── Login / registro ── */
  var dlg = $('#authDlg');
  function abrirAuth(tab) {
    if (!dlg) return;
    cambiarTab(tab || 'login');
    setMsg($('#authMsg'), '');
    if (dlg.showModal) dlg.showModal(); else dlg.setAttribute('open', '');
  }
  function cambiarTab(t) {
    $$('.tab').forEach(function (b) { b.classList.toggle('on', b.dataset.tab === t); });
    $('#formLogin').hidden = t !== 'login';
    $('#formRegistro').hidden = t !== 'registro';
  }
  function datosForm(f) {
    var o = {}; new FormData(f).forEach(function (v, k) { o[k] = v; }); return o;
  }
  function enviarAuth(form, ruta) {
    var btn = $('button[type=submit]', form); btn.disabled = true;
    setMsg($('#authMsg'), '');
    api(ruta, { method: 'POST', body: datosForm(form) }).then(function () {
      setMsg($('#authMsg'), '¡Listo! Entrando…', true);
      location.reload(); // recarga para obtener la sesión nueva (nonce nuevo)
    }).catch(function (e) {
      setMsg($('#authMsg'), e.message); btn.disabled = false;
    });
  }
  if ($('#btnAuth')) $('#btnAuth').addEventListener('click', function () { abrirAuth('login'); });
  $$('.abrir-auth').forEach(function (b) { b.addEventListener('click', function () { abrirAuth('registro'); }); });
  if ($('#authClose')) $('#authClose').addEventListener('click', function () { dlg.close ? dlg.close() : dlg.removeAttribute('open'); });
  $$('.tab').forEach(function (b) { b.addEventListener('click', function () { cambiarTab(b.dataset.tab); }); });
  if ($('#formLogin')) $('#formLogin').addEventListener('submit', function (e) { e.preventDefault(); enviarAuth(e.target, 'auth/login'); });
  if ($('#formRegistro')) $('#formRegistro').addEventListener('submit', function (e) { e.preventDefault(); enviarAuth(e.target, 'auth/registro'); });
  if ($('#btnLogout')) $('#btnLogout').addEventListener('click', function () {
    api('auth/logout', { method: 'POST' }).then(function () { location.reload(); }).catch(function (e) { toast(e.message); });
  });

  /* ── Fotos de animales ── */
  var TIPOS = ['perro', 'gato', 'zorro', 'pato'];
  function cargarFoto(tipo, img, onOk) {
    return api('animal', { params: { tipo: tipo } }).then(function (d) {
      img.onload = function () { if (onOk) onOk(d); };
      img.src = d.url;
    });
  }
  var hero = $('#heroFoto');
  function heroOtra(tipo) {
    cargarFoto(tipo || 'perro', hero).catch(function () {});
  }
  if (hero) {
    heroOtra('perro');
    $('#heroOtra').addEventListener('click', function () { heroOtra(TIPOS[Math.floor(Math.random() * TIPOS.length)]); });
  }

  var ESTADOS = {
    perro: ['comiendo su pienso', 'jugando con la pelota', 'echando la siesta', 'dando un paseo por el jardín', 'moviendo la cola'],
    gato: ['tomando el sol en la ventana', 'persiguiendo una pluma', 'ronroneando', 'echando la siesta', 'inspeccionando su cama'],
    zorro: ['explorando su rincón', 'dando saltitos', 'jugando con una pelota', 'descansando', 'comiendo con ganas'],
    pato: ['nadando en su estanque', 'chapoteando', 'tomando el sol', 'paseando en fila', 'comiendo migas']
  };
  var pf = $('#petFoto');
  $$('#petBtns button').forEach(function (b) {
    b.addEventListener('click', function () {
      var tipo = b.dataset.tipo; var info = $('#petInfo');
      info.textContent = 'Pidiendo foto a la cámara… 📸';
      cargarFoto(tipo, pf, function () {
        var l = ESTADOS[tipo];
        var h = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        info.innerHTML = 'Tu ' + esc(tipo) + ' está <strong>' + esc(l[Math.floor(Math.random() * l.length)]) + '</strong>. ¡Se encuentra genial! 🐾<span class="hora">Foto de las ' + h + '</span>';
      }).catch(function (e) { info.textContent = e.message; });
    });
  });

  /* ── Tiempo (Open-Meteo) y moneda (Frankfurter) ── */
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
      var pasado = s < hoyS;
      if (cal.llenos[s]) cls.push('lleno');
      if (cal.desde && cal.hasta && s > cal.desde && s < cal.hasta) cls.push('rango');
      if (s === cal.desde || s === cal.hasta) cls.push('ext');
      html += '<button type="button" data-d="' + s + '" class="' + cls.join(' ') + '"' + (pasado ? ' disabled' : '') + '>' + d + '</button>';
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
        cargarDisponibilidad(); cargarUltimas(); cargarMias();
      }).catch(function (err) { setMsg(msg, err.message); }).then(function () { btn.disabled = false; });
    });
  }

  /* ── Listas de reservas ── */
  function itemReserva(r, staff) {
    var ex = r.extras && r.extras.length ? '<p>Extras: ' + esc(r.extras.join(', ')) + '</p>' : '';
    var h = '<article class="caja item"><h4>' + esc(r.mascota) + ' <span class="badge ' + esc(r.estado) + '">' + esc(r.estado) + '</span></h4>' +
      '<p>' + esc(r.especie) + ' · Habitación ' + esc(r.plan) + '</p><p>' + esc(r.desde) + ' → ' + esc(r.hasta) + '</p>' + ex +
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
    el.innerHTML = arr.length ? arr.map(fn).join('') : '<p class="vacio-txt">' + vacio + '</p>';
  }

  function cargarUltimas() {
    var el = $('#ultimas'); if (!el) return;
    api('reservas').then(function (a) { lista(el, a, function (r) { return itemReserva(r, false); }, 'Todavía no hay reservas. ¡Sé el primero!'); })
      .catch(function (e) { el.innerHTML = '<p class="vacio-txt">' + esc(e.message) + '</p>'; });
  }
  function cargarMias() {
    if (!C.user.logged) return;
    var el = $('#misList');
    api('mis-reservas').then(function (a) { lista(el, a, function (r) { return itemReserva(r, false); }, 'Aún no tienes reservas.'); }).catch(function () {});
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
        setMsg(msg, d.actualizada ? 'Reseña actualizada ✅' : '¡Gracias por tu reseña! 🐾', true); cargarResenas();
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
  function cargarPanel() {
    if (!C.user.staff) return;
    api('panel').then(function (d) {
      lista($('#panelRes'), d.reservas, function (r) { return itemReserva(r, true); }, 'No hay reservas.');
      lista($('#panelMsg'), d.mensajes, function (m) {
        return '<article class="caja item"><h4>' + esc(m.asunto) + '</h4><p>' + esc(m.texto) + '</p><p><strong>' + esc(m.nombre) + '</strong> · ' + esc(m.email) + ' · ' + esc(m.fecha) + '</p></article>';
      }, 'No hay mensajes.');
    }).catch(function (e) { toast(e.message); });
  }
  if ($('#panelRes')) {
    $('#panelRes').addEventListener('click', function (e) {
      var b = e.target.closest('button[data-estado]'); if (!b) return;
      api('reservas/' + b.dataset.id + '/estado', { method: 'POST', body: { estado: b.dataset.estado } })
        .then(function () { toast('Estado actualizado'); cargarPanel(); cargarUltimas(); cargarDisponibilidad(); })
        .catch(function (err) { toast(err.message); });
    });
  }

  /* ── Inicio ── */
  cargarUltimas(); cargarResenas(); cargarMias(); cargarPanel();
})();