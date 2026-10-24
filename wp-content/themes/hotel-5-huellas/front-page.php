<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hotel 5 Huellas</title>
  <?php wp_head(); ?>
</head>
<body>
  <nav class="barra">
    <a class="logo" href="#">🐾 Hotel 5 Huellas</a>
    <label class="moneda">Moneda
      <select id="moneda">
        <option value="EUR">EUR €</option>
        <option value="USD">USD $</option>
        <option value="GBP">GBP £</option>
      </select>
    </label>
  </nav>

  <header class="hero">
    <h1>Compara hoteles donde tu mascota también es bienvenida</h1>
    <p>Encuentra el mejor precio entre varias agencias en un solo sitio</p>
    <form id="buscador" class="buscador" autocomplete="off">
      <label class="campo"><span>Destino</span>
        <input type="text" id="destino" list="sugerencias" placeholder="¿A dónde vas?">
      </label>
      <datalist id="sugerencias"></datalist>
      <label class="campo"><span>Entrada</span><input type="date" id="entrada"></label>
      <label class="campo"><span>Salida</span><input type="date" id="salida"></label>
      <label class="campo check"><input type="checkbox" id="mascotas"><span>🐾 Admite mascotas</span></label>
      <button type="submit" class="buscar">Buscar</button>
    </form>
    <div class="populares">
      Destinos populares:
      <button type="button" class="chip" data-destino="Madrid">Madrid</button>
      <button type="button" class="chip" data-destino="Barcelona">Barcelona</button>
      <button type="button" class="chip" data-destino="Valencia">Valencia</button>
      <button type="button" class="chip" data-destino="Sevilla">Sevilla</button>
      <button type="button" class="chip" data-destino="Toledo">Toledo</button>
    </div>
  </header>

  <main class="contenido">
    <aside class="filtros">
      <h2>Filtros</h2>
      <label>Precio máximo por noche: <output id="precio-valor">sin límite</output>
        <input type="range" id="precio-max" min="30" max="300" step="5" value="300">
      </label>
      <label>Estrellas
        <select id="estrellas">
          <option value="0">Todas</option>
          <option value="2">2 ★ o más</option>
          <option value="3">3 ★ o más</option>
          <option value="4">4 ★ o más</option>
          <option value="5">5 ★</option>
        </select>
      </label>
      <label>Valoración
        <select id="valoracion">
          <option value="0">Cualquiera</option>
          <option value="7">Bueno 7+</option>
          <option value="8">Muy bueno 8+</option>
          <option value="9">Excepcional 9+</option>
        </select>
      </label>
      <div id="tiempo" class="tiempo"></div>
    </aside>

    <section class="resultados">
      <div class="cabecera-resultados">
        <h2 id="titulo-resultados">Hoteles</h2>
        <div class="acciones">
          <label>Ordenar por
            <select id="orden">
              <option value="recomendados">Recomendados</option>
              <option value="precio">Precio más bajo</option>
              <option value="valoracion">Mejor valorados</option>
              <option value="estrellas">Más estrellas</option>
            </select>
          </label>
          <button type="button" id="ver-mapa" class="secundario">🗺️ Mapa</button>
        </div>
      </div>
      <div id="lista-hoteles"></div>
      <p class="nota-pie">Las valoraciones y los precios de las agencias son datos de ejemplo para la práctica.</p>
    </section>

    <div id="mapa"></div>
  </main>

  <section class="bloque">
    <h2>Reserva</h2>
    <form id="form-reserva">
      <input type="text" id="hotel" placeholder="Pulsa «Ver oferta» en un hotel" readonly>
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