<?php
// =========================================================
//  CONFIGURACIÓN GENERAL
//  Cambia estos valores al subir la página a un hosting.
// =========================================================

// Lee una variable de entorno (Vercel) o usa el valor local por defecto
function env($clave, $defecto = null) {
    $v = getenv($clave);
    if ($v === false || $v === '') $v = $_ENV[$clave] ?? $_SERVER[$clave] ?? null;
    return ($v === null || $v === '') ? $defecto : $v;
}

// ¿Está corriendo en Vercel?
define('EN_VERCEL', env('VERCEL') !== null);

// --- Base de datos ---
//   Local (Laragon): root sin contraseña.
//   Nube (Vercel):   se configuran en Settings > Environment Variables.
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', (int)env('DB_PORT', 3306));
define('DB_NAME', env('DB_NAME', 'agendafacil'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_SSL',  filter_var(env('DB_SSL', 'false'), FILTER_VALIDATE_BOOLEAN));   // Aiven exige SSL

// --- Ruta base del sitio ---
//   php -S localhost:8000 router.php   -> '/'
//   C:\laragon\www\PAGINAWEB            -> '/PAGINAWEB/'
//   Hosting (raíz del dominio)          -> '/'
define('BASE_URL', '/');

define('NOMBRE_SITIO', 'AgendaFácil');
define('CIUDAD', 'Santa Cruz de la Sierra');

// --- Modo demostración ---
// true: el enlace para recuperar contraseña se muestra en pantalla (no hay correo).
// En producción, cámbialo a false y envía el enlace por correo.
define('MODO_DEMO', filter_var(env('MODO_DEMO', 'true'), FILTER_VALIDATE_BOOLEAN));

// --- Reservas ---
define('INTERVALO_MIN', 30);          // cada cuántos minutos se ofrece un horario
define('DIAS_MAX_RESERVA', 60);       // con cuántos días de anticipación se puede reservar

// --- Seguridad ---
define('MAX_INTENTOS_LOGIN', 5);      // intentos fallidos permitidos...
define('MINUTOS_BLOQUEO', 15);        // ...en esta cantidad de minutos
define('DIAS_TOKEN_API', 30);         // duración del token de la app móvil

// --- Fotos subidas por los negocios ---
define('MAX_FOTO_MB', 2);
define('CARPETA_FOTOS', 'imagenes/negocios/');

date_default_timezone_set('America/La_Paz');

// --- Chatbot (Telegram / WhatsApp) ---
define('TELEGRAM_TOKEN',   env('TELEGRAM_TOKEN', ''));     // lo da @BotFather
define('TELEGRAM_SECRET',  env('TELEGRAM_SECRET', ''));    // texto secreto inventado por vos
define('TELEGRAM_USUARIO', env('TELEGRAM_USUARIO', ''));   // ej: AgendaFacilSczBot (sin @)
define('WA_TOKEN',         env('WA_TOKEN', ''));           // token de acceso de Meta
define('WA_PHONE_ID',      env('WA_PHONE_ID', ''));        // Phone number ID de Meta
define('WA_VERIFY_TOKEN',  env('WA_VERIFY_TOKEN', ''));    // texto inventado para verificar el webhook
define('WA_APP_SECRET',    env('WA_APP_SECRET', ''));      // App secret (para validar la firma)
define('WA_NUMERO',        env('WA_NUMERO', ''));          // número del bot, ej: 15551234567
define('WA_API_VERSION',   env('WA_API_VERSION', 'v23.0'));
define('CLAVE_INSTALACION', env('CLAVE_INSTALACION', ''));  // protege instalar_bd.php y bot/configurar.php

