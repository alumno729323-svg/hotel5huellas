<?php
if (!defined('ABSPATH')) exit;
$u       = function_exists('h5_user_info') ? h5_user_info() : ['logged' => false, 'name' => '', 'rol' => '', 'staff' => false];
$planes  = function_exists('h5_planes') ? h5_planes() : [];
$extras  = function_exists('h5_extras') ? h5_extras() : [];
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="top">
  <a class="logo" href="#inicio">🐾 <span>Hotel 5 Huellas</span></a>
  <nav class="menu" aria-label="Principal">
    <a href="#servicios">Servicios</a>
    <a href="#habitaciones">Habitaciones</a>
    <a href="#reservar">Reservar</a>
    <a href="#mascota">Mi mascota</a>
    <a href="#resenas">Reseñas</a>
    <a href="#contacto">Contacto</a>
  </nav>
  <div class="cuenta">
    <button id="btnAuth" class="btn btn-mini" type="button" <?php echo $u['logged'] ? 'hidden' : ''; ?>>👤 Entrar</button>
    <span id="chipUser" class="chip" <?php echo $u['logged'] ? '' : 'hidden'; ?>>
      <span id="chipNombre">👤 <?php echo esc_html($u['name']); ?></span>
      <small id="chipRol"><?php echo esc_html($u['rol']); ?></small>
      <button id="btnLogout" class="btn btn-mini btn-claro" type="button">Salir</button>
    </span>
  </div>
</header>

<main id="inicio">

<!-- HERO -->
<section class="hero">
  <div class="hero-txt">
    <p class="etiqueta">🐶 🐱 🦊 🦆 &nbsp;Hotel para mascotas</p>
    <h1>Tu mejor amigo, <em>de vacaciones</em> con nosotros</h1>
    <p class="lead">Reserva su plaza, mira cómo está cuando quieras y viaja tranquilo. Atención 24 h, mimos incluidos.</p>
    <div class="hero-btns">
      <a class="btn" href="#reservar">Reservar ahora</a>
      <a class="btn btn-claro" href="#mascota">Ver cómo está mi mascota</a>
    </div>
  </div>
  <div class="hero-foto">
    <div class="blob"><img id="heroFoto" alt="Una mascota feliz" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="></div>
    <button id="heroOtra" class="btn btn-mini btn-amarillo" type="button">🎲 Otra foto</button>
    <div class="caja sticker" aria-live="polite">
      <span id="wxIcon">⛅</span>
      <div><strong id="wxTemp">--°C</strong><small id="wxDesc">Tiempo en Madrid…</small></div>
    </div>
  </div>
</section>

<!-- PASOS -->
<section class="pasos">
  <div class="caja paso"><b>1</b><h3>Crea tu cuenta</h3><p>Entra para guardar tus reservas y dejar reseñas.</p></div>
  <div class="caja paso"><b>2</b><h3>Elige fechas</h3><p>Mira el calendario y escoge habitación y extras.</p></div>
  <div class="caja paso"><b>3</b><h3>Relájate</h3><p>Pide una foto de tu mascota cuando quieras.</p></div>
</section>

<!-- SERVICIOS -->
<section id="servicios" class="seccion">
  <h2>Qué incluye la estancia</h2>
  <div class="grid4">
    <article class="caja card"><span class="ico">🏠</span><h3>Alojamiento</h3><p>Habitaciones limpias, cómodas y climatizadas.</p></article>
    <article class="caja card"><span class="ico">🍖</span><h3>Alimentación</h3><p>Su comida habitual o menú del hotel, sin coste.</p></article>
    <article class="caja card"><span class="ico">🎾</span><h3>Juegos y paseos</h3><p>Dos salidas al día con nuestros cuidadores.</p></article>
    <article class="caja card"><span class="ico">📷</span><h3>Fotos diarias</h3><p>Una foto cada día para que veas su sonrisa.</p></article>
  </div>

  <h2 class="sub">Servicios extra <small>(elígelos al reservar)</small></h2>
  <div id="extrasGrid" class="grid3">
    <?php foreach ($extras as $e) : ?>
      <article class="caja card extra" data-id="<?php echo esc_attr($e['id']); ?>">
        <span class="ico"><?php echo esc_html($e['emoji']); ?></span>
        <h3><?php echo esc_html($e['nombre']); ?></h3>
        <p class="precio"><?php echo esc_html(number_format($e['precio'], 0, ',', '.')); ?> €
          <small><?php echo $e['tipo'] === 'dia' ? 'por noche' : 'una vez'; ?></small></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- HABITACIONES -->
<section id="habitaciones" class="seccion alt">
  <h2>Nuestras habitaciones</h2>
  <div id="roomsGrid" class="grid3">
    <?php foreach ($planes as $p) : ?>
      <article class="caja card room" data-plan="<?php echo esc_attr($p['id']); ?>">
        <span class="ico"><?php echo esc_html($p['emoji']); ?></span>
        <h3><?php echo esc_html($p['nombre']); ?></h3>
        <p><?php echo esc_html($p['desc']); ?></p>
        <p class="precio"><?php echo esc_html($p['precio']); ?> € <small>por noche</small>
          <small class="usd" data-eur="<?php echo esc_attr($p['precio']); ?>"></small></p>
        <button class="btn btn-mini elegir" type="button" data-plan="<?php echo esc_attr($p['id']); ?>">Elegir</button>
      </article>
    <?php endforeach; ?>
  </div>
  <p id="monedaNota" class="nota"></p>
</section>

<!-- RESERVAR -->
<section id="reservar" class="seccion">
  <h2>Reserva en 1 minuto</h2>
  <div class="reserva">
    <div class="caja cal-wrap">
      <div class="cal-head">
        <button id="calPrev" class="btn btn-mini btn-claro" type="button" aria-label="Mes anterior">‹</button>
        <strong id="calTitle">Mes</strong>
        <button id="calNext" class="btn btn-mini btn-claro" type="button" aria-label="Mes siguiente">›</button>
      </div>
      <div id="cal" class="cal"></div>
      <p id="selRango" class="nota">Elige día de entrada y de salida.</p>
      <p class="leyenda"><span class="dot libre"></span>Libre <span class="dot lleno"></span>Completo <span class="dot sel"></span>Tu estancia</p>
    </div>

    <form id="formReserva" class="caja form" novalidate>
      <label>Tu nombre<input name="nombre" id="rNombre" maxlength="60" required value="<?php echo esc_attr($u['name']); ?>"></label>
      <label>Email <small>(opcional)</small><input name="email" id="rEmail" type="email" value="<?php echo $u['logged'] ? esc_attr(wp_get_current_user()->user_email) : ''; ?>"></label>
      <div class="fila">
        <label>Mascota<input name="mascota" id="rMascota" maxlength="40" required></label>
        <label>Especie
          <select name="especie" id="rEspecie">
            <option value="perro">🐶 Perro</option><option value="gato">🐱 Gato</option>
            <option value="zorro">🦊 Zorro</option><option value="pato">🦆 Pato</option>
            <option value="otro">🐾 Otro</option>
          </select>
        </label>
      </div>
      <label>Habitación
        <select name="plan" id="rPlan">
          <?php foreach ($planes as $p) : ?>
            <option value="<?php echo esc_attr($p['id']); ?>"><?php echo esc_html($p['nombre'] . ' · ' . $p['precio'] . ' €/noche'); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <fieldset id="extrasChecks">
        <legend>Servicios extra</legend>
        <?php foreach ($extras as $e) : ?>
          <label class="check">
            <input type="checkbox" name="extra" value="<?php echo esc_attr($e['id']); ?>">
            <span><?php echo esc_html($e['emoji'] . ' ' . $e['nombre']); ?> <small><?php echo esc_html($e['precio']); ?> €<?php echo $e['tipo'] === 'dia' ? '/noche' : ''; ?></small></span>
          </label>
        <?php endforeach; ?>
      </fieldset>
      <input type="hidden" id="fDesde" name="desde"><input type="hidden" id="fHasta" name="hasta">
      <div id="totalBox" class="total">Elige fechas para ver el precio.</div>
      <button class="btn" type="submit" id="btnReservar">Enviar reserva 🐾</button>
      <p id="resMsg" class="msg" role="status"></p>
    </form>
  </div>
</section>

<!-- MI MASCOTA -->
<section id="mascota" class="seccion alt">
  <h2>¿Cómo está mi mascota?</h2>
  <div class="caja pet">
    <div class="pet-btns" id="petBtns">
      <button class="btn btn-mini btn-claro" type="button" data-tipo="perro">🐶 Perro</button>
      <button class="btn btn-mini btn-claro" type="button" data-tipo="gato">🐱 Gato</button>
      <button class="btn btn-mini btn-claro" type="button" data-tipo="zorro">🦊 Zorro</button>
      <button class="btn btn-mini btn-claro" type="button" data-tipo="pato">🦆 Pato</button>
    </div>
    <div class="pet-vista">
      <img id="petFoto" alt="Foto de tu mascota" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==">
      <div id="petInfo" class="pet-info">Pulsa un animal para pedir una foto en directo 📸</div>
    </div>
    <p class="nota">Demo del servicio de cámara: las fotos vienen de APIs públicas de animales.</p>
  </div>
</section>

<!-- MIS RESERVAS (solo con sesión) -->
<section id="misReservas" class="seccion" <?php echo $u['logged'] ? '' : 'hidden'; ?>>
  <h2>Mis reservas</h2>
  <div id="misList" class="lista"></div>
</section>

<!-- RESEÑAS -->
<section id="resenas" class="seccion">
  <h2>Lo que dicen nuestros clientes</h2>
  <div class="resenas">
    <div class="caja resumen" id="revResumen">Cargando…</div>
    <div id="revList" class="lista"></div>
  </div>
  <div class="caja form resena-form">
    <div id="revLogin" <?php echo $u['logged'] ? 'hidden' : ''; ?>>
      <p>Para dejar tu reseña necesitas una cuenta.</p>
      <button class="btn abrir-auth" type="button">Entrar o crear cuenta</button>
    </div>
    <form id="formResena" <?php echo $u['logged'] ? '' : 'hidden'; ?> novalidate>
      <h3>Deja tu opinión</h3>
      <div id="starPick" class="stars" role="radiogroup" aria-label="Valoración">
        <?php for ($i = 1; $i <= 5; $i++) : ?><button type="button" class="star" data-v="<?php echo $i; ?>" aria-label="<?php echo $i; ?> estrellas">☆</button><?php endfor; ?>
      </div>
      <label>Tu reseña<textarea id="revTexto" rows="3" maxlength="400" placeholder="Cuéntanos cómo fue la estancia…"></textarea></label>
      <button class="btn" type="submit">Publicar reseña</button>
      <p id="revMsg" class="msg" role="status"></p>
    </form>
  </div>
</section>

<!-- ÚLTIMAS RESERVAS -->
<section class="seccion alt">
  <h2>Últimas reservas</h2>
  <div id="ultimas" class="lista"></div>
</section>

<!-- CONTACTO -->
<section id="contacto" class="seccion">
  <h2>Contáctanos</h2>
  <div class="contacto">
    <div class="caja info">
      <h3>Hotel 5 Huellas</h3>
      <p>📍 Calle de las Huellas, 5</p>
      <p>📞 600 000 000</p>
      <p>🕘 Recepción 24 h</p>
      <p class="nota">¿Dudas sobre vacunas, alimentación o medicación? Escríbenos y te contestamos enseguida.</p>
    </div>
    <form id="formContacto" class="caja form" novalidate>
      <div class="fila">
        <label>Nombre<input name="nombre" id="cNombre" maxlength="60" required value="<?php echo esc_attr($u['name']); ?>"></label>
        <label>Email<input name="email" id="cEmail" type="email" required></label>
      </div>
      <label>Asunto<input name="asunto" id="cAsunto" maxlength="80" required></label>
      <label>Mensaje<textarea name="mensaje" id="cMensaje" rows="4" maxlength="800" required></textarea></label>
      <input type="text" name="web" id="cWeb" class="trampa" tabindex="-1" autocomplete="off" aria-hidden="true">
      <button class="btn" type="submit">Enviar mensaje ✉️</button>
      <p id="contMsg" class="msg" role="status"></p>
    </form>
  </div>
</section>

<!-- PANEL DEL PERSONAL (solo cuidador/admin) -->
<section id="panel" class="seccion alt" <?php echo !empty($u['staff']) ? '' : 'hidden'; ?>>
  <h2>Panel del personal</h2>
  <h3 class="sub2">Reservas</h3>
  <div id="panelRes" class="lista"></div>
  <h3 class="sub2">Mensajes de contacto</h3>
  <div id="panelMsg" class="lista"></div>
</section>

<!-- FAQ -->
<section class="seccion">
  <h2>Preguntas frecuentes</h2>
  <div class="faq">
    <details class="caja"><summary>¿Qué animales aceptáis?</summary><p>Perros, gatos y otras mascotas pequeñas. Si dudas, escríbenos.</p></details>
    <details class="caja"><summary>¿Puedo ver a mi mascota durante la estancia?</summary><p>Sí, en «¿Cómo está mi mascota?» y con la videollamada diaria opcional.</p></details>
    <details class="caja"><summary>¿Cómo cancelo una reserva?</summary><p>Escríbenos desde Contacto y el personal la cancelará.</p></details>
    <details class="caja"><summary>¿Qué roles hay en la web?</summary><p>Dueño (reserva y opina), Cuidador (gestiona reservas y mensajes) y Administrador.</p></details>
  </div>
</section>

</main>

<footer class="pie">🐾 Hotel 5 Huellas · Proyecto de clase · Despliegue automático con Docker + GitHub Actions</footer>

<!-- LOGIN / REGISTRO -->
<dialog id="authDlg" class="caja dlg">
  <button id="authClose" class="cerrar" type="button" aria-label="Cerrar">✕</button>
  <div class="tabs">
    <button class="tab on" type="button" data-tab="login">Entrar</button>
    <button class="tab" type="button" data-tab="registro">Crear cuenta</button>
  </div>
  <form id="formLogin" class="form" novalidate>
    <label>Usuario o email<input name="usuario" autocomplete="username" required></label>
    <label>Contraseña<input name="clave" type="password" autocomplete="current-password" required></label>
    <button class="btn" type="submit">Entrar</button>
  </form>
  <form id="formRegistro" class="form" hidden novalidate>
    <label>Tu nombre<input name="nombre" maxlength="40" autocomplete="name"></label>
    <label>Usuario<input name="usuario" autocomplete="username" required></label>
    <label>Email<input name="email" type="email" autocomplete="email" required></label>
    <label>Contraseña <small>(mín. 8)</small><input name="clave" type="password" autocomplete="new-password" required></label>
    <button class="btn" type="submit">Crear cuenta</button>
  </form>
  <p id="authMsg" class="msg" role="alert"></p>
</dialog>

<div id="toast" class="toast" role="status" aria-live="polite"></div>

<?php wp_footer(); ?>
</body>
</html>