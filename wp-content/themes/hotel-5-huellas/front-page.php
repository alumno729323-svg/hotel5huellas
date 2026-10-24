<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hotel 5 Huellas</title>
  <?php wp_head(); ?>
</head>
<body>
  <header class="hero">
    <h1>🐾 Hotel 5 Huellas</h1>
    <p>Encuentra el hotel perfecto, donde tu mascota también es bienvenida</p>
    <form id="buscador" class="buscador" autocomplete="off">
      <label>Destino
        <input type="text" id="destino" list="sugerencias" placeholder="Madrid, Barcelona...">
      </label>
      <datalist id="sugerencias"></datalist>
      <label>Entrada <input type="date" id="entrada"></label>
      <label>Salida <input type="date" id="salida"></label>
      <label class="check"><input type="checkbox" id="mascotas"> 🐾 Admite mascotas</label>
      <button type="submit">Buscar</button>
    </form>
  </header>

  <main class="contenido">
    <aside class="filtros">
      <h2>Filtros</h2>
      <label>Precio máximo por noche: <output id="precio-valor">sin límite</output>
        <input type="range" id="precio-max" min="30" max="300" step="5" value="300">
      </label>
      <label>Estrellas mínimas
        <select id="estrellas">
          <option value="0">Todas</option>
          <option value="2">2 ★ o más</option>
          <option value="3">3 ★ o más</option>
          <option value="4">4 ★ o más</option>
          <option value="5">5 ★</option>
        </select>
      </label>
      <label>Moneda
        <select id="moneda">
          <option value="EUR">Euro (€)</option>
          <option value="USD">Dólar ($)</option>
          <option value="GBP">Libra (£)</option>
        </select>
      </label>
      <div id="tiempo" class="tiempo"></div>
    </aside>

    <section class="resultados">
      <h2 id="titulo-resultados">Hoteles</h2>
      <div id="lista-hoteles"></div>
    </section>

    <div id="mapa"></div>
  </main>

  <section class="bloque">
    <h2>Reserva</h2>
    <form id="form-reserva">
      <input type="text" id="hotel" placeholder="Pulsa «Reservar» en un hotel" readonly>
      <input type="text" id="dueno" placeholder="Tu nombre" required>
      <select id="mascota">
        <option value="Perro">Perro</option>
        <option value="Gato">Gato</option>
        <option value="Ave">Ave</option>
        <option value="Otro">Otro</option>
      </select>
      <button type="submit">Reservar</button>
    </form>
    <p id="mensaje"></p>
    <h3>Reservas actuales</h3>
    <ul id="lista"></ul>
  </section>

  <section class="bloque">
    <h2>Selecciona tu mascota</h2>
    <div class="selector">
      <button type="button" data-tipo="perro">🐶 Perro</button>
      <button type="button" data-tipo="gato">🐱 Gato</button>
    </div>
    <img id="foto-mascota" alt="Foto de mascota" hidden>
  </section>

  <footer>
    <p>Hotel 5 Huellas: seguridad y confianza, tranquilidad total y reserva sin complicaciones.</p>
  </footer>
  <?php wp_footer(); ?>
</body>
</html>