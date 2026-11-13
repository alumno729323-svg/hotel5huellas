<?php
if (!defined('ABSPATH')) exit;
$u       = function_exists('h5_user_info') ? h5_user_info() : ['logged' => false, 'name' => '', 'email' => '', 'rol' => '', 'staff' => false];
$planes  = function_exists('h5_planes') ? h5_planes() : [];
$extras  = function_exists('h5_extras') ? h5_extras() : [];
$inicial = $u['logged'] ? (function_exists('mb_strtoupper') ? mb_strtoupper(mb_substr($u['name'], 0, 1)) : strtoupper(substr($u['name'], 0, 1))) : '';
$especies = ['perro' => '🐶 Perro', 'gato' => '🐱 Gato', 'pajaro' => '🐦 Pájaro', 'conejo' => '🐰 Conejo', 'hamster' => '🐹 Hámster', 'otro' => '🐾 Otro'];
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<!-- ═══════════ CABECERA ═══════════ -->
<header class="top" id="top">
  <a class="logo" href="#inicio">🐾 <span>Hotel 5 Huellas</span></a>

  <button id="burger" class="burger" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="menu">☰</button>

  <nav id="menu" class="menu" aria-label="Principal">
    <a href="#servicios">Servicios</a>
    <a href="#habitaciones">Habitaciones</a>
    <a href="#reservar">Reservar</a>
    <a href="#camara">Cámara</a>
    <a href="#galeria">Galería</a>
    <a href="#resenas">Reseñas</a>
    <a href="#contacto">Contacto</a>
  </nav>

  <div class="cuenta">
    <button id="btnAuth" class="btn btn-mini" type="button" <?php echo $u['logged'] ? 'hidden' : ''; ?>>👤 Entrar</button>

    <div id="userMenu" class="usermenu" <?php echo $u['logged'] ? '' : 'hidden'; ?>>
      <button id="userBtn" class="chip" type="button" aria-haspopup="true" aria-expanded="false">
        <span class="avatar" id="avatar"><?php echo esc_html($inicial); ?></span>
        <span id="chipNombre"><?php echo esc_html($u['name']); ?></span>
        <small id="chipRol"><?php echo esc_html($u['rol']); ?></small>
        <span aria-hidden="true">▾</span>
      </button>
      <div id="userDrop" class="drop caja" hidden>
        <a href="#cuenta" data-tab="reservas">📅 Mis reservas</a>
        <a href="#cuenta" data-tab="mascotas">🐾 Mis mascotas</a>
        <a href="#cuenta" data-tab="perfil">⚙️ Mi perfil</a>
        <a href="#panel" id="linkPanel" <?php echo !empty($u['staff']) ? '' : 'hidden'; ?>>🛠️ Panel del personal</a>
        <button id="btnLogout" type="button">🚪 Cerrar sesión</button>
      </div>
    </div>
  </div>
</header>

<main id="inicio">

<!-- ═══════════ PORTADA ═══════════ -->
<section class="hero">
  <div class="hero-txt">
    <p class="etiqueta">🐶 🐱 🐦 🐰 &nbsp;Hotel para mascotas</p>
    <h1>Tu mejor amigo, <em>de vacaciones</em> con nosotros</h1>
    <p class="lead">Reserva su plaza, mira cómo está cuando quieras y viaja tranquilo. Atención 24 h, mimos incluidos.</p>
    <div class="hero-btns">
      <a class="btn" href="#reservar">Reservar ahora</a>
      <a class="btn btn-claro" href="#camara">Ver cómo está mi mascota</a>
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

<section class="stats" aria-label="El hotel en cifras">
  <div class="caja stat"><b id="stReservas">–</b><span>reservas hechas</span></div>
  <div class="caja stat"><b id="stMedia">–</b><span>valoración media</span></div>
  <div class="caja stat"><b id="stResenas">–</b><span>reseñas</span></div>
  <div class="caja stat"><b>24 h</b><span>atención y cámara</span></div>
</section>

<!-- ═══════════ CÓMO FUNCIONA ═══════════ -->
<section class="pasos" aria-label="Cómo funciona">
  <div class="caja paso"><b>1</b><h3>Crea tu cuenta</h3><p>Guarda a tus mascotas, tus reservas y tus reseñas.</p></div>
  <div class="caja paso"><b>2</b><h3>Elige fechas</h3><p>Mira el calendario y escoge habitación y extras.</p></div>
  <div class="caja paso"><b>3</b><h3>Relájate</h3><p>Pide una foto de tu mascota cuando quieras.</p></div>
</section>

<!-- ═══════════ SERVICIOS ═══════════ -->
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

<!-- ═══════════ HABITACIONES ═══════════ -->
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

<!-- ═══════════ RESERVAR ═══════════ -->
<section id="reservar" class="seccion">
  <h2>Reserva en 1 minuto</h2>
  <div class="reserva">
    <div class="caja cal-wrap">
      <h3 class="paso-t"><b>1</b> Elige las fechas</h3>
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
      <h3 class="paso-t"><b>2</b> Cuéntanos de tu mascota</h3>
      <label id="miMascotaWrap" hidden>Usar una de mis mascotas
        <select id="rMiMascota"><option value="">— Elegir —</option></select>
      </label>
      <label>Tu nombre<input name="nombre" id="rNombre" maxlength="60" required value="<?php echo esc_attr($u['name']); ?>"></label>
      <label>Email <small>(opcional)</small><input name="email" id="rEmail" type="email" value="<?php echo $u['logged'] ? esc_attr($u['email']) : ''; ?>"></label>
      <div class="fila">
        <label>Mascota<input name="mascota" id="rMascota" maxlength="40" required></label>
        <label>Especie
          <select name="especie" id="rEspecie">
            <?php foreach ($especies as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?>
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
      <h3 class="paso-t"><b>3</b> Servicios extra</h3>
      <fieldset id="extrasChecks">
        <legend class="sr">Servicios extra</legend>
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

<!-- ═══════════ CÁMARA ═══════════ -->
<section id="camara" class="seccion alt">
  <h2>¿Cómo está mi mascota?</h2>
  <div class="caja pet">
    <div class="pet-btns" id="petBtns">
      <button class="btn btn-mini btn-claro" type="button" data-tipo="perro">🐶 Perro</button>
      <button class="btn btn-mini btn-claro" type="button" data-tipo="gato">🐱 Gato</button>
      <button class="btn btn-mini btn-claro" type="button" data-tipo="pajaro">🐦 Pájaro</button>
      <button class="btn btn-mini btn-claro" type="button" data-tipo="conejo">🐰 Conejo</button>
    </div>
    <div class="pet-vista">
      <div class="pet-marco">
        <img id="petFoto" alt="Foto de tu mascota" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==">
        <span id="petVacio" class="pet-vacio">📸</span>
      </div>
      <div id="petInfo" class="pet-info">Pulsa un animal para pedir una foto en directo 📸</div>
    </div>
    <p class="nota">Demo del servicio de cámara: las fotos vienen de APIs públicas de animales.</p>
  </div>
</section>

<!-- ═══════════ GALERÍA ═══════════ -->
<section id="galeria" class="seccion">
  <h2>Nuestros huéspedes de hoy</h2>
  <div id="galeriaGrid" class="galeria"></div>
  <p class="centro"><button id="galeriaOtra" class="btn btn-amarillo" type="button">🔄 Ver otros huéspedes</button></p>
</section>

<!-- ═══════════ RESEÑAS ═══════════ -->
<section id="resenas" class="seccion alt">
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

<!-- ═══════════ MI CUENTA (solo con sesión) ═══════════ -->
<section id="cuenta" class="seccion" <?php echo $u['logged'] ? '' : 'hidden'; ?>>
  <h2>Mi cuenta</h2>
  <div class="caja cuenta-box">
    <div class="acc-tabs" role="tablist">
      <button class="acc-tab on" type="button" role="tab" data-tab="reservas">📅 Mis reservas</button>
      <button class="acc-tab" type="button" role="tab" data-tab="mascotas">🐾 Mis mascotas</button>
      <button class="acc-tab" type="button" role="tab" data-tab="perfil">⚙️ Mi perfil</button>
    </div>

    <div class="acc-pane" id="accReservas" data-pane="reservas">
      <div id="misList" class="lista"></div>
    </div>

    <div class="acc-pane" id="accMascotas" data-pane="mascotas" hidden>
      <form id="formMascota" class="fila-form" novalidate>
        <label>Nombre<input id="mNombre" maxlength="40" required placeholder="Ej.: Luna"></label>
        <label>Especie
          <select id="mEspecie"><?php foreach ($especies as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?></select>
        </label>
        <button class="btn" type="submit">Añadir</button>
      </form>
      <p id="mascotaMsg" class="msg" role="status"></p>
      <div id="petList" class="lista"></div>
    </div>

    <div class="acc-pane" id="accPerfil" data-pane="perfil" hidden>
      <form id="formPerfil" class="form perfil-form" novalidate>
        <label>Nombre que se muestra<input id="pNombre" maxlength="40" required value="<?php echo esc_attr($u['name']); ?>"></label>
        <p class="nota izq">Email: <strong><?php echo esc_html($u['email']); ?></strong> · Rol: <strong><?php echo esc_html($u['rol']); ?></strong></p>
        <h3>Cambiar contraseña <small>(opcional)</small></h3>
        <label>Contraseña actual<input id="pActual" type="password" autocomplete="current-password"></label>
        <label>Contraseña nueva <small>(mín. 8)</small><input id="pNueva" type="password" autocomplete="new-password"></label>
        <button class="btn" type="submit">Guardar cambios</button>
        <p id="perfilMsg" class="msg" role="status"></p>
      </form>
    </div>
  </div>
</section>

<!-- ═══════════ PANEL DEL PERSONAL (solo cuidador/admin) ═══════════ -->
<section id="panel" class="seccion alt" <?php echo !empty($u['staff']) ? '' : 'hidden'; ?>>
  <h2>Panel del personal</h2>
  <div id="panelStats" class="panel-stats"></div>
  <div id="panelFiltro" class="filtros">
    <button class="btn btn-mini btn-amarillo" type="button" data-f="todas">Todas</button>
    <button class="btn btn-mini btn-claro" type="button" data-f="pendiente">Pendientes</button>
    <button class="btn btn-mini btn-claro" type="button" data-f="confirmada">Confirmadas</button>
    <button class="btn btn-mini btn-claro" type="button" data-f="cancelada">Canceladas</button>
  </div>
  <h3 class="sub2">Reservas</h3>
  <div id="panelRes" class="lista"></div>
  <h3 class="sub2">Mensajes de contacto</h3>
  <div id="panelMsg" class="lista"></div>
</section>

<!-- ═══════════ ACTIVIDAD ═══════════ -->
<section class="seccion" id="actividad">
  <h2>Últimas reservas</h2>
  <div id="ultimas" class="lista"></div>
</section>

<!-- ═══════════ FAQ ═══════════ -->
<section class="seccion alt" id="faq">
  <h2>Preguntas frecuentes</h2>
  <div class="faq">
    <details class="caja"><summary>¿Qué animales aceptáis?</summary><p>Perros, gatos, pájaros, conejos, hámsters y otras mascotas pequeñas. Si dudas, escríbenos.</p></details>
    <details class="caja"><summary>¿Puedo ver a mi mascota durante la estancia?</summary><p>Sí, en «¿Cómo está mi mascota?» y con la videollamada diaria opcional.</p></details>
    <details class="caja"><summary>¿Cómo cancelo una reserva?</summary><p>Escríbenos desde Contacto y el personal la cancelará.</p></details>
    <details class="caja"><summary>¿Qué roles hay en la web?</summary><p>Dueño (reserva, guarda mascotas y opina), Cuidador (gestiona reservas y mensajes) y Administrador.</p></details>
  </div>
</section>

<!-- ═══════════ CONTACTO ═══════════ -->
<section id="contacto" class="seccion">
  <h2>Contáctanos</h2>
  <div class="contacto">
    <div class="caja info">
      <h3>Hotel 5 Huellas</h3>
      <p>📍 Calle de las Huellas, 5</p>
      <p>📞 600 000 000</p>
      <p>🕘 Recepción 24 h</p>
      <p class="nota izq">¿Dudas sobre vacunas, alimentación o medicación? Escríbenos y te contestamos enseguida.</p>
    </div>
    <form id="formContacto" class="caja form" novalidate>
      <div class="fila">
        <label>Nombre<input name="nombre" id="cNombre" maxlength="60" required value="<?php echo esc_attr($u['name']); ?>"></label>
        <label>Email<input name="email" id="cEmail" type="email" required value="<?php echo $u['logged'] ? esc_attr($u['email']) : ''; ?>"></label>
      </div>
      <label>Asunto<input name="asunto" id="cAsunto" maxlength="80" required></label>
      <label>Mensaje<textarea name="mensaje" id="cMensaje" rows="4" maxlength="800" required></textarea></label>
      <input type="text" name="web" id="cWeb" class="trampa" tabindex="-1" autocomplete="off" aria-hidden="true">
      <button class="btn" type="submit">Enviar mensaje ✉️</button>
      <p id="contMsg" class="msg" role="status"></p>
    </form>
  </div>
</section>

</main>

<footer class="pie">
  <div class="pie-grid">
    <div><strong>🐾 Hotel 5 Huellas</strong><p>Proyecto de clase · Despliegue automático con Docker + GitHub Actions.</p></div>
    <div><strong>Enlaces</strong><p><a href="#servicios">Servicios</a> · <a href="#habitaciones">Habitaciones</a> · <a href="#reservar">Reservar</a> · <a href="#contacto">Contacto</a></p></div>
  </div>
  <a class="subir" href="#top" aria-label="Subir">↑</a>
</footer>

<!-- ═══════════ ENTRAR / CREAR CUENTA ═══════════ -->
<dialog id="authDlg" class="dlg caja" aria-labelledby="authTitulo">
  <button id="authClose" class="cerrar" type="button" aria-label="Cerrar">✕</button>
  <div class="dlg-grid">
    <aside class="dlg-side">
      <div class="dlg-emoji">🐶🐱🐦🐰</div>
      <h3>Tu cuenta 5 Huellas</h3>
      <ul>
        <li>📅 Reservas en un clic</li>
        <li>🐾 Guarda a tus mascotas</li>
        <li>⭐ Deja tu reseña</li>
        <li>📸 Pide fotos cuando quieras</li>
      </ul>
    </aside>
    <div class="dlg-main">
      <h2 id="authTitulo">Bienvenido</h2>
      <div class="tabs" role="tablist">
        <button class="tab on" type="button" role="tab" data-tab="login">Entrar</button>
        <button class="tab" type="button" role="tab" data-tab="registro">Crear cuenta</button>
      </div>

      <form id="formLogin" class="form" novalidate>
        <label>Usuario o email<input name="usuario" id="lUsuario" autocomplete="username" required></label>
        <label>Contraseña
          <span class="pw"><input name="clave" id="lClave" type="password" autocomplete="current-password" required>
            <button type="button" class="eye" data-for="lClave" aria-label="Mostrar u ocultar contraseña">👁️</button></span>
        </label>
        <label class="check"><input type="checkbox" name="recordar" id="lRecordar" checked> <span>Mantener la sesión iniciada</span></label>
        <button class="btn" type="submit">Entrar</button>
        <p class="nota">¿Aún no tienes cuenta? <a href="#" class="cambiar" data-tab="registro">Créala gratis</a></p>
      </form>

      <form id="formRegistro" class="form" hidden novalidate>
        <label>Tu nombre<input name="nombre" id="gNombre" maxlength="40" autocomplete="name"></label>
        <div class="fila">
          <label>Usuario<input name="usuario" id="gUsuario" autocomplete="username" required></label>
          <label>Email<input name="email" id="gEmail" type="email" autocomplete="email" required></label>
        </div>
        <label>Contraseña <small>(mín. 8)</small>
          <span class="pw"><input name="clave" id="gClave" type="password" autocomplete="new-password" required>
            <button type="button" class="eye" data-for="gClave" aria-label="Mostrar u ocultar contraseña">👁️</button></span>
        </label>
        <div class="meter" id="pwMeter" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
        <small id="pwTxt" class="nota izq">Usa letras, números y un símbolo.</small>
        <label>Repite la contraseña<input id="gClave2" type="password" autocomplete="new-password" required></label>
        <button class="btn" type="submit">Crear cuenta</button>
        <p class="nota">¿Ya tienes cuenta? <a href="#" class="cambiar" data-tab="login">Entra aquí</a></p>
      </form>

      <p id="authMsg" class="msg" role="alert"></p>
    </div>
  </div>
</dialog>

<div id="toast" class="toast" role="status" aria-live="polite"></div>

<?php wp_footer(); ?>
</body>
</html>