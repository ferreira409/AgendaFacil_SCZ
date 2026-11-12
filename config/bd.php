<?php
// =========================================================
//  Conexión PDO a MySQL (la usan la web y la API)
// =========================================================
require_once __DIR__ . '/config.php';

// Certificado de la base en la nube: se pega completo en la variable DB_CA_PEM
// (Aiven > tu servicio > CA certificate). Se guarda en /tmp porque Vercel es de solo lectura.
function rutaCertificadoBD() {
    $pem = env('DB_CA_PEM');
    if ($pem) {
        $pem  = trim(str_replace(['\\n', "\r"], ["\n", ''], $pem));
        $ruta = sys_get_temp_dir() . '/agendafacil-ca-' . md5($pem) . '.pem';   // si cambia el certificado, cambia el archivo
        if (!is_file($ruta)) file_put_contents($ruta, $pem . "\n");
        return $ruta;
    }
    foreach (['/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/certs/ca-certificates.crt'] as $r) {
        if (is_file($r)) return $r;
    }
    return null;
}

function conectarBD() {
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (DB_SSL && ($ca = rutaCertificadoBD())) {
        $opciones[PDO::MYSQL_ATTR_SSL_CA] = $ca;
    }
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        $opciones
    );
    // Misma zona horaria en MySQL que en PHP (Bolivia, UTC-4)
    $pdo->exec("SET time_zone = '-04:00'");
    return $pdo;
}
