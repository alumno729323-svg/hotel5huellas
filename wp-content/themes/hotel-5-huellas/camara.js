/* Cámara: botones con el nombre de TUS mascotas (solo con sesión iniciada).
   Archivo independiente: no modifica app.js. */
(function () {
  'use strict';
  var C = window.HotelCfg || {};
  if (!C.user || !C.user.logged) return;

  var caja = document.querySelector('#camara .pet');
  var foto = document.getElementById('petFoto');
  var info = document.getElementById('petInfo');
  var vacio = document.getElementById('petVacio');
  var genericos = document.getElementById('petBtns');
  if (!caja || !foto || !info || !genericos) return;

  var PIXEL = foto.getAttribute('src');
  var ESP = { perro: '🐶 Perro', gato: '🐱 Gato', pajaro: '🐦 Pájaro', conejo: '🐰 Conejo', hamster: '🐹 Hámster', otro: '🐾 Otro' };
  var CON_FOTO = ['perro', 'gato', 'pajaro', 'conejo'];
  var ESTADOS = {
    perro: ['comiendo su pienso', 'jugando con la pelota', 'echando la siesta', 'dando un paseo por el jardín', 'moviendo la cola'],
    gato: ['tomando el sol en la ventana', 'persiguiendo una pluma', 'ronroneando', 'echando la siesta', 'inspeccionando su cama'],
    pajaro: ['cantando en su percha', 'dándose un baño', 'picoteando semillas', 'estirando las alas', 'saltando de rama en rama'],
    conejo: ['mordisqueando heno', 'dando saltitos', 'descansando en su casita', 'comiendo zanahoria', 'estirándose al sol'],
    hamster: ['corriendo en su rueda', 'llenando los mofletes', 'escondido en su casita', 'royendo una pipa', 'trepando por el tubo'],
    otro: ['descansando tranquilo', 'explorando su habitación', 'comiendo con ganas', 'jugando un rato', 'echando la siesta']
  };

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
  function emoji(e) { return (ESP[e] || ESP.otro).split(' ')[0]; }
  function azar(a) { return a[Math.floor(Math.random() * a.length)]; }

  function api(path, params) {
    var url = C.root + path;
    if (params) url += (url.indexOf('?') > -1 ? '&' : '?') + new URLSearchParams(params).toString();
    return fetch(url, { headers: { 'X-WP-Nonce': C.nonce }, credentials: 'same-origin' }).then(function (r) {
      return r.json().catch(function () { return null; }).then(function (d) {
        if (!r.ok) throw new Error((d && d.message) || ('Error ' + r.status));
        return d;
      });
    });
  }
  function fotoDe(tipo) {
    return api('animal', { tipo: tipo }).then(function (d) {
      return new Promise(function (res, rej) {
        var im = new Image();
        im.onload = function () { res(d.url); };
        im.onerror = function () { rej(new Error('No se pudo cargar la foto')); };
        im.src = d.url;
      });
    });
  }

  function ver(tipo, nombre) {
    var marco = foto.parentNode;
    var estado = azar(ESTADOS[tipo] || ESTADOS.otro);
    var h = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    function ok(conFoto) {
      info.innerHTML = esc(nombre) + ' está <strong>' + esc(estado) + '</strong>. ¡Se encuentra genial! 🐾<span class="hora">' + (conFoto ? 'Foto de las ' : 'Actualizado a las ') + h + '</span>';
    }
    if (CON_FOTO.indexOf(tipo) < 0) {            // hámster / otro: no hay cámara de fotos, solo el parte
      foto.src = PIXEL; marco.classList.remove('cargada');
      if (vacio) vacio.textContent = emoji(tipo);
      return ok(false);
    }
    if (vacio) vacio.textContent = '📸';
    info.textContent = 'Pidiendo foto a la cámara… 📸';
    fotoDe(tipo).then(function (u) {
      foto.src = u; marco.classList.add('cargada'); ok(true);
    }).catch(function (e) { info.textContent = 'La cámara de ' + nombre + ' no responde ahora mismo (' + e.message + '). Prueba otra vez.'; });
  }

  var cont = document.createElement('div');
  cont.id = 'petMias';
  cont.className = 'pet-mias';
  caja.insertBefore(cont, genericos);
  cont.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-tipo]');
    if (b) ver(b.dataset.tipo, b.dataset.nombre);
  });

  function pintar(ms) {
    cont.innerHTML = ms.length
      ? '<p class="nota pet-mias-t">Tus mascotas</p><div class="pet-btns">' + ms.map(function (m) {
          return '<button class="btn btn-mini btn-amarillo" type="button" data-tipo="' + esc(m.especie) + '" data-nombre="' + esc(m.nombre) + '">' + emoji(m.especie) + ' ' + esc(m.nombre) + '</button>';
        }).join('') + '</div><p class="nota pet-mias-t">…o mira a cualquier huésped:</p>'
      : '<p class="nota">Guarda a tu mascota en <a href="#cuenta">Mi cuenta → Mis mascotas</a> y aquí aparecerá con su nombre.</p>';
  }
  function cargar() {
    api('mascotas').then(function (a) { pintar(Array.isArray(a) ? a : []); }).catch(function () {});
  }
  cargar();

  // si añades o quitas una mascota en "Mi cuenta", la cámara se actualiza sola
  var lista = document.getElementById('petList');
  if (lista && window.MutationObserver) {
    var t = 0;
    new MutationObserver(function () { clearTimeout(t); t = setTimeout(cargar, 400); }).observe(lista, { childList: true });
  }
})();
