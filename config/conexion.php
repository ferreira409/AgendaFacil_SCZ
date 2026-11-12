<?php
// =========================================================
//  Arranque de las páginas web:
//  configuración + sesión segura + BD + funciones + CSRF
// =========================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/imagenes.php';
require_once __DIR__ . '/sesiones.php';

// --- Conexión ---
try {
    $pdo = conectarBD();
} catch (PDOException $ex) {
    http_response_code(500);
    $diag = '';
    if (EN_VERCEL) {
        // Diagnóstico: qué variables están cargadas (sin mostrar sus valores) y el error de MySQL
        $filas = '';
        foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_SSL', 'DB_CA_PEM'] as $v) {
            $val = env($v);
            $ok  = $val !== null;
            if ($v === 'DB_HOST' && $ok && preg_match('#[/:@\s]#', $val)) { $ok = false; $extra = ' (tiene / : @ o espacios: poné solo el host)'; }
            elseif ($v === 'DB_CA_PEM' && $ok && !str_contains($val, 'BEGIN CERTIFICATE')) { $ok = false; $extra = ' (no parece un certificado)'; }
            elseif ($v === 'DB_PORT' && $ok && !ctype_digit(trim($val))) { $ok = false; $extra = ' (tiene que ser solo un número)'; }
            else $extra = $ok ? '' : ' (falta)';
            $filas .= '<li>' . ($ok ? '✅' : '❌') . " <code>$v</code>$extra</li>";
        }
        $diag = "<ul style='font-family:sans-serif;line-height:1.7'>$filas</ul>
                 <p style='font-family:sans-serif'><b>Error de MySQL:</b></p>
                 <pre style='background:#f1f5f9;padding:12px;border-radius:8px;white-space:pre-wrap'>" . e($ex->getMessage()) . "</pre>
                 <p style='font-family:sans-serif;color:#64748b'>Si cambiaste variables, hacé Redeploy. Revisá que la base en Aiven diga <b>Running</b>.</p>";
    }
    die("<div style='max-width:640px;margin:40px auto;padding:0 16px'>
         <h2 style='font-family:sans-serif;color:#b91c1c'>Error de conexión a la base de datos</h2>
         <p style='font-family:sans-serif'>" . (EN_VERCEL ? "Revisá las variables de la base de datos en Vercel:" : "¿Iniciaste MySQL en Laragon e importaste <b>database.sql</b>?") . "</p>
         " . (EN_VERCEL ? $diag : "<pre>" . e($ex->getMessage()) . "</pre>") . "</div>");
}

// --- Sesión con cookies seguras ---
if (session_status() === PHP_SESSION_NONE) {
    session_set_save_handler(new SesionesBD($pdo), true);   // sesiones en MySQL (necesario en Vercel)
    session_set_cookie_params([
        'httponly' => true,                              // JavaScript no puede leer la cookie
        'samesite' => 'Lax',                             // protege contra envíos desde otros sitios
        'secure'   => !empty($_SERVER['HTTPS']),         // solo HTTPS cuando el hosting lo tenga
    ]);
    session_start();
}

// Si falta una tabla (base de datos vieja), mostrar un mensaje claro
set_exception_handler(function ($ex) {
    http_response_code(500);
    if ($ex instanceof PDOException && $ex->getCode() === '42S02') {
        die("<div style='font-family:sans-serif;max-width:560px;margin:80px auto'>
               <h2 style='color:#b91c1c'>La base de datos está desactualizada</h2>
               <p>Falta una tabla de la versión nueva. Importá de nuevo <b>database.sql</b> en la terminal:</p>
               <pre style='background:#f1f5f9;padding:12px;border-radius:8px'>mysql -u root -e \"source C:/PAGINAWEB/database.sql\"</pre>
               <p>Después recargá esta página.</p></div>");
    }
    error_log((string)$ex);
    die("<div style='font-family:sans-serif;text-align:center;margin:80px'><h2>Ocurrió un error</h2><p>Intentá de nuevo en unos segundos.</p></div>");
});

// ---------------------------------------------------------
//  CSRF: cada formulario POST lleva un código secreto
// ---------------------------------------------------------
function csrfToken() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrfCampo() {
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

// Se verifica automáticamente en TODAS las peticiones POST de la web
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrfToken(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        die("<div style='font-family:sans-serif;max-width:480px;margin:80px auto;text-align:center'>
               <h2>La página expiró</h2>
               <p>Por seguridad, volvé atrás, recargá la página e intentá de nuevo.</p>
               <a href='" . url('index.php') . "'>Ir al inicio</a></div>");
    }
}

// ---------------------------------------------------------
//  Funciones de las páginas
// ---------------------------------------------------------
function redirigir($ruta) {
    header('Location: ' . url($ruta));
    exit;
}

function usuarioActual() {
    return $_SESSION['usuario'] ?? null;
}

function iniciarSesion(array $usuario) {
    session_regenerate_id(true);
    $_SESSION['usuario'] = $usuario;
}

// Exige sesión (y opcionalmente uno de los roles indicados)
function requiereLogin($roles = []) {
    global $pdo;
    $u = usuarioActual();
    if (!$u) {
        flash('Iniciá sesión para continuar.', 'error');
        redirigir('auth/login.php');
    }
    // Si el admin desactivó la cuenta, se cierra la sesión
    $st = $pdo->prepare("SELECT activo FROM usuarios WHERE id = ?");
    $st->execute([$u['id']]);
    if (!$st->fetchColumn()) {
        $_SESSION = [];
        flash('Tu sesión fue cerrada.', 'error');
        redirigir('auth/login.php');
    }
    if ($roles && !in_array($u['rol'], (array)$roles, true)) {
        flash('No tenés permiso para ver esa página.', 'error');
        redirigir('index.php');
    }
    return $u;
}

// Mensajes que se muestran una sola vez
function flash($mensaje, $tipo = 'exito') {
    $_SESSION['flash'] = ['mensaje' => $mensaje, 'tipo' => $tipo];
}

function mostrarFlash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alerta alerta-' . e($f['tipo']) . '">' . e($f['mensaje']) . '</div>';
    }
}

function mostrarErrores(array $errores) {
    foreach ($errores as $err) echo '<div class="alerta alerta-error">' . e($err) . '</div>';
}

function badgeEstado($estado) {
    return '<span class="estado estado-' . e($estado) . '">' . e(ucfirst($estado)) . '</span>';
}

// Estrellas de calificación (Font Awesome)
function estrellasHtml($promedio, $total = null) {
    if ($promedio === null) return '<span class="estrellas sin-calificar">Sin calificaciones</span>';
    $html = '<span class="estrellas" title="' . e($promedio) . ' de 5">';
    for ($i = 1; $i <= 5; $i++) {
        if ($promedio >= $i)            $html .= icono('star');
        elseif ($promedio >= $i - 0.5)  $html .= icono('star-half-stroke');
        else                            $html .= icono('star', 'regular');
    }
    $html .= ' <b>' . e(number_format((float)$promedio, 1)) . '</b>';
    if ($total !== null) $html .= ' <small>(' . (int)$total . ')</small>';
    return $html . '</span>';
}

function opcionesSelect(array $lista, $seleccion = '', $placeholder = null) {
    $html = $placeholder !== null ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($lista as $item) {
        $html .= '<option' . ($item === $seleccion ? ' selected' : '') . '>' . e($item) . '</option>';
    }
    return $html;
}
