const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

let tasas = { EUR: 1 };
let destino = null;
let hoteles = [];
let hotelElegido = '';
const sugerencias = {};

// Fechas: no se puede elegir el pasado
const hoy = new Date().toISOString().slice(0, 10);
$('entrada').min = hoy;
$('salida').min = hoy;

// Mapa: Leaflet + OpenStreetMap
const mapa = L.map('mapa').setView([40.2, -3.7], 5);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(mapa);
const capaMarcadores = L.layerGroup().addTo(mapa);

// Construye la URL de nuestra API (vale con enlaces permanentes y sin ellos)
function apiUrl(base, params = {}) {
  const url = new URL(base, window.location.href);
  Object.entries(params).forEach(([k, v]) => {
    if (v !== '' && v !== null && v !== undefined) url.searchParams.set(k, v);
  });
  return url.toString();
}

function noches() {
  const a = $('entrada').value;
  const b = $('salida').value;
  if (!a || !b) return 0;
  const n = Math.round((new Date(b) - new Date(a)) / 86400000);
  return n > 0 ? n : 0;
}

function precio(eur) {
  const elegida = $('moneda').value;
  const moneda = tasas[elegida] ? elegida : 'EUR';
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: moneda, maximumFractionDigits: 0 })
    .format(eur * tasas[moneda]);
}

// API pública: cambio de moneda (Frankfurter)
async function cargarTasas() {
  try {
    const r = await fetch('https://api.frankfurter.dev/v1/latest?base=EUR&symbols=USD,GBP');
    const d = await r.json();
    tasas = { EUR: 1, ...d.rates };
  } catch (err) { /* si falla, se muestra todo en euros */ }
}

// API pública: sugerencias de destino (Open-Meteo Geocoding)
let temporizador;
$('destino').addEventListener('input', () => {
  clearTimeout(temporizador);
  const q = $('destino').value.trim();
  if (q.length < 2) return;
  temporizador = setTimeout(async () => {
    try {
      const r = await fetch('https://geocoding-api.open-meteo.com/v1/search?count=5&language=es&name=' + encodeURIComponent(q));
      const d = await r.json();
      const lista = $('sugerencias');
      lista.innerHTML = '';
      (d.results || []).forEach(c => {
        sugerencias[c.name.toLowerCase()] = { nombre: c.name, lat: c.latitude, lng: c.longitude };
        const o = document.createElement('option');
        o.value = c.name;
        o.label = [c.admin1, c.country].filter(Boolean).join(', ');
        lista.appendChild(o);
      });
    } catch (err) { /* sin sugerencias */ }
  }, 300);
});

async function resolverDestino(texto) {
  if (!texto) return null;
  const guardado = sugerencias[texto.toLowerCase()];
  if (guardado) return guardado;
  try {
    const r = await fetch('https://geocoding-api.open-meteo.com/v1/search?count=1&language=es&name=' + encodeURIComponent(texto));
    const d = await r.json();
    const c = (d.results || [])[0];
    if (c) return { nombre: c.name, lat: c.latitude, lng: c.longitude };
  } catch (err) { /* se usa solo el texto */ }
  return { nombre: texto, lat: null, lng: null };
}

// API pública: tiempo del destino (Open-Meteo)
const CLIMA = [[0, '☀️ Despejado'], [3, '⛅ Nuboso'], [48, '🌫️ Niebla'], [67, '🌧️ Lluvia'], [77, '❄️ Nieve'], [82, '🌦️ Chubascos'], [86, '❄️ Nieve'], [99, '⛈️ Tormenta']];
const textoClima = code => (CLIMA.find(([max]) => code <= max) || [0, '🌡️'])[1];

async function cargarClima() {
  const caja = $('tiempo');
  if (!destino || destino.lat === null) { caja.textContent = ''; return; }
  try {
    const url = `https://api.open-meteo.com/v1/forecast?latitude=${destino.lat}&longitude=${destino.lng}&current=temperature_2m,weather_code`;
    const d = await (await fetch(url)).json();
    caja.innerHTML = `<strong>Tiempo en ${esc(destino.nombre)}</strong><br>${textoClima(d.current.weather_code)} · ${Math.round(d.current.temperature_2m)} °C`;
  } catch (err) { caja.textContent = ''; }
}

// API propia: hoteles con filtros
async function cargarHoteles() {
  const max = Number($('precio-max').value);
  const params = {
    ciudad: destino ? destino.nombre : $('destino').value.trim(),
    precio_max: max < 300 ? max : '',
    estrellas_min: $('estrellas').value,
    mascotas: $('mascotas').checked ? '1' : '',
  };
  try {
    const r = await fetch(apiUrl(HotelCfg.hotelesUrl, params));
    hoteles = await r.json();
  } catch (err) { hoteles = []; }
  pintarHoteles();
}

function pintarHoteles() {
  const lista = $('lista-hoteles');
  const n = noches();
  capaMarcadores.clearLayers();
  $('titulo-resultados').textContent = hoteles.length + (hoteles.length === 1 ? ' hotel encontrado' : ' hoteles encontrados');
  if (!hoteles.length) {
    lista.innerHTML = '<p>No hay hoteles con esos filtros.</p>';
    return;
  }
  lista.innerHTML = hoteles.map(h => `
    <article class="hotel">
      <img src="https://picsum.photos/seed/hotel${h.id}/320/220" alt="Foto de ${esc(h.nombre)}" loading="lazy">
      <div>
        <h3>${esc(h.nombre)}</h3>
        <p>${'★'.repeat(h.estrellas)} · ${esc(h.ciudad)}${h.mascotas ? ' · 🐾 Admite mascotas' : ''}</p>
      </div>
      <div class="precio">
        <strong>${precio(h.precio)}</strong>
        <small>por noche</small>
        ${n ? `<small>${n} noche${n > 1 ? 's' : ''}: ${precio(h.precio * n)}</small>` : ''}
        <button type="button" data-hotel="${esc(h.nombre)}">Reservar</button>
      </div>
    </article>`).join('');

  const puntos = [];
  hoteles.forEach(h => {
    if (!h.lat || !h.lng) return;
    puntos.push([h.lat, h.lng]);
    L.marker([h.lat, h.lng])
      .bindPopup(`<strong>${esc(h.nombre)}</strong><br>${precio(h.precio)} / noche`)
      .addTo(capaMarcadores);
  });
  if (puntos.length) mapa.fitBounds(puntos, { padding: [30, 30], maxZoom: 14 });
}

// Eventos del buscador y de los filtros
$('buscador').addEventListener('submit', async e => {
  e.preventDefault();
  destino = await resolverDestino($('destino').value.trim());
  cargarClima();
  cargarHoteles();
});
$('precio-max').addEventListener('input', () => {
  const v = Number($('precio-max').value);
  $('precio-valor').textContent = v >= 300 ? 'sin límite' : v + ' €';
});
$('precio-max').addEventListener('change', cargarHoteles);
$('estrellas').addEventListener('change', cargarHoteles);
$('mascotas').addEventListener('change', cargarHoteles);
$('moneda').addEventListener('change', pintarHoteles);
$('entrada').addEventListener('change', pintarHoteles);
$('salida').addEventListener('change', pintarHoteles);

// Elegir hotel
$('lista-hoteles').addEventListener('click', e => {
  const b = e.target.closest('button[data-hotel]');
  if (!b) return;
  hotelElegido = b.dataset.hotel;
  $('hotel').value = hotelElegido;
  $('form-reserva').scrollIntoView({ behavior: 'smooth' });
});

// API propia: reservas
async function cargarReservas() {
  const lista = $('lista');
  try {
    const reservas = await (await fetch(apiUrl(HotelCfg.reservasUrl))).json();
    lista.innerHTML = reservas
      .map(r => `<li>${esc(r.hotel || 'Hotel')} · ${esc(r.mascota)} · ${esc(r.entrada)} → ${esc(r.salida)}</li>`)
      .join('');
  } catch (err) { lista.innerHTML = ''; }
}

$('form-reserva').addEventListener('submit', async e => {
  e.preventDefault();
  const msg = $('mensaje');
  if (!hotelElegido) { msg.textContent = 'Elige un hotel pulsando «Reservar» en la lista.'; return; }
  if (!noches()) { msg.textContent = 'Elige fechas de entrada y salida válidas arriba.'; return; }
  const datos = {
    dueno: $('dueno').value,
    hotel: hotelElegido,
    mascota: $('mascota').value,
    entrada: $('entrada').value,
    salida: $('salida').value,
  };
  try {
    const res = await fetch(HotelCfg.reservasUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(datos),
    });
    const cuerpo = await res.json();
    if (!res.ok) { msg.textContent = cuerpo.message || 'No se pudo reservar'; return; }
    msg.textContent = '¡Reserva realizada!';
    $('dueno').value = '';
    cargarReservas();
  } catch (err) { msg.textContent = 'Error de conexión'; }
});

// API pública: fotos de mascotas
const FOTOS = {
  perro: async () => (await (await fetch('https://dog.ceo/api/breeds/image/random')).json()).message,
  gato: async () => (await (await fetch('https://api.thecatapi.com/v1/images/search')).json())[0].url,
};
document.querySelectorAll('.selector button').forEach(btn => {
  btn.addEventListener('click', async () => {
    try {
      const img = $('foto-mascota');
      img.src = await FOTOS[btn.dataset.tipo]();
      img.hidden = false;
    } catch (err) { /* la API pública puede fallar a veces */ }
  });
});

// Arranque
(async function iniciar() {
  await cargarTasas();
  await cargarHoteles();
  cargarReservas();
})();