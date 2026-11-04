<?php
// =========================================================
//  LÓGICA DEL SISTEMA (la usan la web y la API)
//  Aquí no se imprime HTML: solo se consulta y valida.
// =========================================================
require_once __DIR__ . '/config.php';

// ---------------------------------------------------------
//  Listas fijas
// ---------------------------------------------------------
const CATEGORIAS = ['Barbería', 'Peluquería', 'Spa', 'Manicure', 'Odontología', 'Consultorio', 'Otro'];

const ZONAS = ['Centro', 'Equipetrol', 'Norte', 'Urubó', 'Las Palmas', 'Sur', 'Plan 3000',
               'Villa 1ro de Mayo', 'Pampa de la Isla', 'Satélite Norte', 'Otra'];

const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
              5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

const ESTADOS = ['pendiente', 'confirmada', 'cancelada', 'completada'];

// ---------------------------------------------------------
//  Utilidades
// ---------------------------------------------------------
function e($texto) {
    return htmlspecialchars((string)($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

function url($ruta = '') {
    return BASE_URL . ltrim($ruta, '/');
}

function ipCliente() {
    // En Vercel la IP real del visitante llega en la cabecera X-Forwarded-For
    if (EN_VERCEL && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function aMinutos($hora) {              // '09:30' o '09:30:00' -> 570
    [$h, $m] = array_map('intval', explode(':', $hora));
    return $h * 60 + $m;
}

function aHora($minutos) {              // 570 -> '09:30'
    return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
}

function fechaValida($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', (string)$fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

function horaValida($hora) {
    return (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string)$hora);
}

// Número boliviano para WhatsApp: deja solo dígitos y agrega 591 si falta
function normalizarWhatsapp($numero) {
    $n = preg_replace('/\D/', '', (string)$numero);
    if ($n === '') return '';
    if (strlen($n) === 8) $n = '591' . $n;
    return $n;
}

function enlaceWhatsapp($numero, $mensaje) {
    $n = normalizarWhatsapp($numero);
    return $n ? 'https://wa.me/' . $n . '?text=' . rawurlencode($mensaje) : '';
}

// ---------------------------------------------------------
//  Usuarios y login
// ---------------------------------------------------------
function demasiadosIntentos(PDO $pdo, $email, $ip) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM intentos_login
                         WHERE email = ? AND ip = ? AND creado_en > (NOW() - INTERVAL ? MINUTE)");
    $st->execute([$email, $ip, MINUTOS_BLOQUEO]);
    return (int)$st->fetchColumn() >= MAX_INTENTOS_LOGIN;
}

// Devuelve ['ok' => true, 'usuario' => [...]] o ['ok' => false, 'error' => '...']
function intentarLogin(PDO $pdo, $email, $password, $ip) {
    $email = strtolower(trim($email));

    if (demasiadosIntentos($pdo, $email, $ip)) {
        return ['ok' => false, 'error' => 'Demasiados intentos fallidos. Esperá ' . MINUTOS_BLOQUEO . ' minutos e intentá de nuevo.'];
    }

    $st = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $st->execute([$email]);
    $u = $st->fetch();

    if (!$u || !password_verify($password, $u['password'])) {
        $pdo->prepare("INSERT INTO intentos_login (email, ip) VALUES (?, ?)")->execute([$email, $ip]);
        return ['ok' => false, 'error' => 'Correo o contraseña incorrectos.'];
    }
    if (!$u['activo']) {
        return ['ok' => false, 'error' => 'Tu cuenta está desactivada. Contactá al administrador.'];
    }

    // Login correcto: se borran los intentos fallidos
    $pdo->prepare("DELETE FROM intentos_login WHERE email = ? AND ip = ?")->execute([$email, $ip]);

    if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
        $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?")
            ->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    return ['ok' => true, 'usuario' => datosPublicosUsuario($u)];
}

function datosPublicosUsuario($u) {
    return [
        'id'       => (int)$u['id'],
        'nombre'   => $u['nombre'],
        'email'    => $u['email'],
        'telefono' => $u['telefono'],
        'rol'      => $u['rol'],
    ];
}

function validarPassword($password) {
    if (strlen($password) < 8) return 'La contraseña debe tener al menos 8 caracteres.';
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password))
        return 'La contraseña debe tener letras y números.';
    return null;
}

// Registro de cliente o de negocio. Devuelve ['ok'=>true,'id'=>..] o ['ok'=>false,'errores'=>[...]]
function registrarUsuario(PDO $pdo, array $d, $tipo = 'cliente') {
    $tipo = $tipo === 'negocio' ? 'negocio' : 'cliente';
    $d = array_map(fn($v) => is_string($v) ? trim($v) : $v, $d);
    $email = strtolower($d['email'] ?? '');
    $errores = [];

    if (($d['nombre'] ?? '') === '' || mb_strlen($d['nombre']) > 100) $errores[] = 'El nombre es obligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))                   $errores[] = 'El correo no es válido.';
    if ($err = validarPassword($d['password'] ?? ''))                 $errores[] = $err;
    if (isset($d['password2']) && $d['password'] !== $d['password2']) $errores[] = 'Las contraseñas no coinciden.';
    if ($tipo === 'negocio') {
        if (($d['negocio'] ?? '') === '')                          $errores[] = 'Indicá el nombre de tu negocio.';
        if (!in_array($d['categoria'] ?? '', CATEGORIAS, true))    $errores[] = 'Elegí una categoría válida.';
        if (!in_array($d['zona'] ?? '', ZONAS, true))              $errores[] = 'Elegí una zona válida.';
    }

    if (!$errores) {
        $st = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $st->execute([$email]);
        if ($st->fetch()) $errores[] = 'Ese correo ya está registrado.';
    }
    if ($errores) return ['ok' => false, 'errores' => $errores];

    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO usuarios (nombre, email, telefono, password, rol) VALUES (?,?,?,?,?)")
            ->execute([$d['nombre'], $email, $d['telefono'] ?? '', password_hash($d['password'], PASSWORD_DEFAULT), $tipo]);
        $idUsuario = (int)$pdo->lastInsertId();

        if ($tipo === 'negocio') {
            $pdo->prepare("INSERT INTO negocios (usuario_id, nombre, categoria, icono, zona, direccion, telefono, whatsapp)
                           VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$idUsuario, $d['negocio'], $d['categoria'], nombreIconoCategoria($d['categoria']),
                           $d['zona'], $d['direccion'] ?? '', $d['telefono'] ?? '', normalizarWhatsapp($d['telefono'] ?? '')]);
            $idNegocio = (int)$pdo->lastInsertId();

            // Valores iniciales: un servicio y horario lunes a sábado 09:00 - 18:00
            $pdo->prepare("INSERT INTO servicios (negocio_id, nombre, duracion_min, precio) VALUES (?, 'Servicio general', 30, 0)")
                ->execute([$idNegocio]);
            $h = $pdo->prepare("INSERT INTO horarios (negocio_id, dia, abre, cierra) VALUES (?, ?, '09:00', '18:00')");
            for ($dia = 1; $dia <= 6; $dia++) $h->execute([$idNegocio, $dia]);
        }
        $pdo->commit();
        return ['ok' => true, 'id' => $idUsuario];
    } catch (Exception $ex) {
        $pdo->rollBack();
        return ['ok' => false, 'errores' => ['No se pudo registrar. Intentá de nuevo.']];
    }
}

function actualizarPerfil(PDO $pdo, $idUsuario, array $d) {
    $nombre = trim($d['nombre'] ?? '');
    $email  = strtolower(trim($d['email'] ?? ''));
    $tel    = trim($d['telefono'] ?? '');
    $errores = [];
    if ($nombre === '')                               $errores[] = 'El nombre es obligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errores[] = 'El correo no es válido.';
    if (!$errores) {
        $st = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ?");
        $st->execute([$email, $idUsuario]);
        if ($st->fetch()) $errores[] = 'Ese correo ya lo usa otra cuenta.';
    }
    if ($errores) return ['ok' => false, 'errores' => $errores];
    $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ? WHERE id = ?")
        ->execute([$nombre, $email, $tel, $idUsuario]);
    return ['ok' => true];
}

function cambiarPassword(PDO $pdo, $idUsuario, $actual, $nueva, $nueva2) {
    $st = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $st->execute([$idUsuario]);
    $hash = $st->fetchColumn();
    if (!$hash || !password_verify($actual, $hash)) return ['ok' => false, 'errores' => ['La contraseña actual no es correcta.']];
    if ($err = validarPassword($nueva))             return ['ok' => false, 'errores' => [$err]];
    if ($nueva !== $nueva2)                         return ['ok' => false, 'errores' => ['Las contraseñas nuevas no coinciden.']];
    $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([password_hash($nueva, PASSWORD_DEFAULT), $idUsuario]);
    $pdo->prepare("DELETE FROM api_tokens WHERE usuario_id = ?")->execute([$idUsuario]); // cierra sesiones de la app
    return ['ok' => true];
}

// ---------------------------------------------------------
//  Recuperar contraseña (token de un solo uso, 1 hora)
// ---------------------------------------------------------
function crearTokenRecuperacion(PDO $pdo, $email) {
    $st = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND activo = 1");
    $st->execute([strtolower(trim($email))]);
    $id = $st->fetchColumn();
    if (!$id) return null;

    $token = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = ?")->execute([$id]);
    $pdo->prepare("INSERT INTO password_resets (usuario_id, token_hash, expira) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)")
        ->execute([$id, hash('sha256', $token)]);
    return $token;
}

function usuarioPorTokenRecuperacion(PDO $pdo, $token) {
    if (!preg_match('/^[a-f0-9]{64}$/', (string)$token)) return null;
    $st = $pdo->prepare("SELECT usuario_id FROM password_resets
                         WHERE token_hash = ? AND usado = 0 AND expira > NOW()");
    $st->execute([hash('sha256', $token)]);
    return $st->fetchColumn() ?: null;
}

function restablecerPassword(PDO $pdo, $token, $nueva, $nueva2) {
    $id = usuarioPorTokenRecuperacion($pdo, $token);
    if (!$id)                          return ['ok' => false, 'errores' => ['El enlace no es válido o ya venció.']];
    if ($err = validarPassword($nueva)) return ['ok' => false, 'errores' => [$err]];
    if ($nueva !== $nueva2)            return ['ok' => false, 'errores' => ['Las contraseñas no coinciden.']];

    $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([password_hash($nueva, PASSWORD_DEFAULT), $id]);
    $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM api_tokens WHERE usuario_id = ?")->execute([$id]);
    return ['ok' => true];
}

// ---------------------------------------------------------
//  Tokens de la API
// ---------------------------------------------------------
function crearTokenApi(PDO $pdo, $idUsuario) {
    $token = bin2hex(random_bytes(32));
    $pdo->prepare("INSERT INTO api_tokens (usuario_id, token_hash, expira) VALUES (?, ?, NOW() + INTERVAL ? DAY)")
        ->execute([$idUsuario, hash('sha256', $token), DIAS_TOKEN_API]);
    $pdo->exec("DELETE FROM api_tokens WHERE expira < NOW()");
    return $token;
}

function usuarioPorTokenApi(PDO $pdo, $token) {
    if (!preg_match('/^[a-f0-9]{64}$/', (string)$token)) return null;
    $st = $pdo->prepare("SELECT u.* FROM api_tokens t JOIN usuarios u ON u.id = t.usuario_id
                         WHERE t.token_hash = ? AND t.expira > NOW() AND u.activo = 1");
    $st->execute([hash('sha256', $token)]);
    $u = $st->fetch();
    return $u ? datosPublicosUsuario($u) : null;
}

function borrarTokenApi(PDO $pdo, $token) {
    $pdo->prepare("DELETE FROM api_tokens WHERE token_hash = ?")->execute([hash('sha256', (string)$token)]);
}

// ---------------------------------------------------------
//  Negocios
// ---------------------------------------------------------
// Filtros: q (texto), categoria, zona
function listarNegocios(PDO $pdo, array $f = [], $soloActivos = true) {
    $sql = "SELECT n.*,
                   (SELECT MIN(precio) FROM servicios s WHERE s.negocio_id = n.id) AS desde,
                   (SELECT COUNT(*)    FROM servicios s WHERE s.negocio_id = n.id) AS total_servicios,
                   (SELECT ROUND(AVG(estrellas), 1) FROM calificaciones c WHERE c.negocio_id = n.id) AS promedio,
                   (SELECT COUNT(*)    FROM calificaciones c WHERE c.negocio_id = n.id) AS total_calificaciones
            FROM negocios n WHERE 1 = 1";
    $p = [];
    if ($soloActivos) $sql .= " AND n.activo = 1";
    if (!empty($f['q'])) {
        $sql .= " AND (n.nombre LIKE ? OR n.descripcion LIKE ? OR n.direccion LIKE ?
                       OR EXISTS (SELECT 1 FROM servicios s WHERE s.negocio_id = n.id AND s.nombre LIKE ?))";
        $q = '%' . $f['q'] . '%';
        array_push($p, $q, $q, $q, $q);
    }
    if (!empty($f['categoria'])) { $sql .= " AND n.categoria = ?"; $p[] = $f['categoria']; }
    if (!empty($f['zona']))      { $sql .= " AND n.zona = ?";      $p[] = $f['zona']; }
    $sql .= " ORDER BY promedio IS NULL, promedio DESC, n.nombre";

    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

function obtenerNegocio(PDO $pdo, $id, $soloActivo = true) {
    $st = $pdo->prepare("SELECT * FROM negocios WHERE id = ?" . ($soloActivo ? " AND activo = 1" : ""));
    $st->execute([(int)$id]);
    $n = $st->fetch();
    if (!$n) return null;

    $st = $pdo->prepare("SELECT * FROM servicios WHERE negocio_id = ? ORDER BY precio, nombre");
    $st->execute([$n['id']]);
    $n['servicios'] = $st->fetchAll();

    $st = $pdo->prepare("SELECT dia, TIME_FORMAT(abre,'%H:%i') abre, TIME_FORMAT(cierra,'%H:%i') cierra
                         FROM horarios WHERE negocio_id = ? ORDER BY dia");
    $st->execute([$n['id']]);
    $n['horarios'] = $st->fetchAll();

    $st = $pdo->prepare("SELECT ROUND(AVG(estrellas),1) promedio, COUNT(*) total FROM calificaciones WHERE negocio_id = ?");
    $st->execute([$n['id']]);
    $n += $st->fetch();

    $st = $pdo->prepare("SELECT c.estrellas, c.comentario, c.creado_en, u.nombre
                         FROM calificaciones c JOIN usuarios u ON u.id = c.usuario_id
                         WHERE c.negocio_id = ? ORDER BY c.creado_en DESC LIMIT 10");
    $st->execute([$n['id']]);
    $n['resenas'] = $st->fetchAll();
    return $n;
}

// Negocios que puede administrar un usuario (vendedor: los suyos; admin: todos)
function negociosDeUsuario(PDO $pdo, array $u) {
    if ($u['rol'] === 'admin') return $pdo->query("SELECT * FROM negocios ORDER BY nombre")->fetchAll();
    $st = $pdo->prepare("SELECT * FROM negocios WHERE usuario_id = ? ORDER BY nombre");
    $st->execute([$u['id']]);
    return $st->fetchAll();
}

function puedeAdministrarNegocio(PDO $pdo, array $u, $idNegocio) {
    return in_array((int)$idNegocio, array_map('intval', array_column(negociosDeUsuario($pdo, $u), 'id')), true);
}

function guardarDatosNegocio(PDO $pdo, $idNegocio, array $d) {
    $d = array_map(fn($v) => is_string($v) ? trim($v) : $v, $d);
    $errores = [];
    if (($d['nombre'] ?? '') === '')                          $errores[] = 'El nombre del negocio es obligatorio.';
    if (!in_array($d['categoria'] ?? '', CATEGORIAS, true))   $errores[] = 'Categoría no válida.';
    if (!in_array($d['zona'] ?? '', ZONAS, true))             $errores[] = 'Zona no válida.';
    if ($errores) return ['ok' => false, 'errores' => $errores];

    $pdo->prepare("UPDATE negocios SET nombre=?, categoria=?, icono=?, zona=?, direccion=?, telefono=?, whatsapp=?, descripcion=?
                   WHERE id = ?")
        ->execute([$d['nombre'], $d['categoria'], nombreIconoCategoria($d['categoria']), $d['zona'],
                   $d['direccion'] ?? '', $d['telefono'] ?? '', normalizarWhatsapp($d['whatsapp'] ?? ''),
                   mb_substr($d['descripcion'] ?? '', 0, 1000), (int)$idNegocio]);
    return ['ok' => true];
}

// $dias = [1 => ['abierto'=>1,'abre'=>'09:00','cierra'=>'18:00'], ...]
function guardarHorario(PDO $pdo, $idNegocio, array $dias) {
    $errores = [];
    foreach (DIAS as $num => $nombre) {
        $d = $dias[$num] ?? [];
        if (!empty($d['abierto'])) {
            if (!horaValida($d['abre'] ?? '') || !horaValida($d['cierra'] ?? '')) $errores[] = "$nombre: hora no válida.";
            elseif (aMinutos($d['cierra']) <= aMinutos($d['abre']))              $errores[] = "$nombre: la hora de cierre debe ser después de la de apertura.";
        }
    }
    if ($errores) return ['ok' => false, 'errores' => $errores];

    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM horarios WHERE negocio_id = ?")->execute([$idNegocio]);
    $ins = $pdo->prepare("INSERT INTO horarios (negocio_id, dia, abre, cierra) VALUES (?,?,?,?)");
    foreach (DIAS as $num => $nombre) {
        $d = $dias[$num] ?? [];
        if (!empty($d['abierto'])) $ins->execute([$idNegocio, $num, $d['abre'], $d['cierra']]);
    }
    $pdo->commit();
    return ['ok' => true];
}

// Sube la foto del negocio. Devuelve ['ok'=>true,'ruta'=>...] o error.
function subirFotoNegocio(PDO $pdo, $idNegocio, array $archivo) {
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return ['ok' => false, 'errores' => ['No se pudo subir la imagen.']];
    if ($archivo['size'] > MAX_FOTO_MB * 1024 * 1024)               return ['ok' => false, 'errores' => ['La imagen pesa más de ' . MAX_FOTO_MB . ' MB.']];

    $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime  = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!isset($tipos[$mime]) || !getimagesize($archivo['tmp_name'])) return ['ok' => false, 'errores' => ['Solo se aceptan imágenes JPG, PNG o WEBP.']];

    // La foto se guarda en la base de datos (en Vercel no se pueden guardar archivos)
    asegurarTablaFotos($pdo);
    $pdo->prepare("INSERT INTO fotos (negocio_id, mime, datos) VALUES (?, ?, ?)")
        ->execute([(int)$idNegocio, $mime, file_get_contents($archivo['tmp_name'])]);
    $idFoto = (int)$pdo->lastInsertId();

    // Borra la foto anterior (de la base o, si era vieja, de la carpeta local)
    $st = $pdo->prepare("SELECT foto FROM negocios WHERE id = ?");
    $st->execute([$idNegocio]);
    $anterior = (string)$st->fetchColumn();
    if (preg_match('/^foto\.php\?id=(\d+)$/', $anterior, $m)) {
        $pdo->prepare("DELETE FROM fotos WHERE id = ? AND negocio_id = ?")->execute([(int)$m[1], (int)$idNegocio]);
    } elseif ($anterior && strpos($anterior, CARPETA_FOTOS) === 0 && is_writable(__DIR__ . '/../' . $anterior)) {
        @unlink(__DIR__ . '/../' . $anterior);
    }

    $ruta = 'foto.php?id=' . $idFoto;
    $pdo->prepare("UPDATE negocios SET foto = ? WHERE id = ?")->execute([$ruta, $idNegocio]);
    return ['ok' => true, 'ruta' => $ruta];
}

// ---------------------------------------------------------
//  Disponibilidad y citas
// ---------------------------------------------------------
// Horario de un día concreto, o null si está cerrado (día sin horario o día libre)
function horarioDelDia(PDO $pdo, $idNegocio, $fecha) {
    $st = $pdo->prepare("SELECT 1 FROM dias_libres WHERE negocio_id = ? AND fecha = ?");
    $st->execute([$idNegocio, $fecha]);
    if ($st->fetch()) return null;

    $st = $pdo->prepare("SELECT abre, cierra FROM horarios WHERE negocio_id = ? AND dia = ?");
    $st->execute([$idNegocio, (int)date('N', strtotime($fecha))]);
    return $st->fetch() ?: null;
}

// Horas libres para un servicio de $duracion minutos. Tiene en cuenta:
//  - horario de ese día y días libres
//  - que el servicio termine antes del cierre
//  - que NO se cruce con otra cita (pendiente o confirmada)
//  - que no sea una hora que ya pasó
function horasDisponibles(PDO $pdo, $idNegocio, $fecha, $duracion) {
    if (!fechaValida($fecha) || $fecha < date('Y-m-d')) return [];
    if ($fecha > date('Y-m-d', strtotime('+' . DIAS_MAX_RESERVA . ' days'))) return [];

    $horario = horarioDelDia($pdo, $idNegocio, $fecha);
    if (!$horario) return [];

    $st = $pdo->prepare("SELECT hora, hora_fin FROM citas
                         WHERE negocio_id = ? AND fecha = ? AND estado IN ('pendiente','confirmada')");
    $st->execute([$idNegocio, $fecha]);
    $ocupadas = array_map(fn($c) => [aMinutos($c['hora']), aMinutos($c['hora_fin'])], $st->fetchAll());

    $ahora  = ($fecha === date('Y-m-d')) ? aMinutos(date('H:i')) : -1;
    $libres = [];
    for ($ini = aMinutos($horario['abre']); $ini + $duracion <= aMinutos($horario['cierra']); $ini += INTERVALO_MIN) {
        if ($ini <= $ahora) continue;
        $fin = $ini + $duracion;
        $choca = false;
        foreach ($ocupadas as [$oIni, $oFin]) {
            if ($ini < $oFin && $fin > $oIni) { $choca = true; break; }   // se cruzan
        }
        if (!$choca) $libres[] = aHora($ini);
    }
    return $libres;
}

// Crea la cita de forma segura (dos personas no pueden tomar la misma hora)
function crearCita(PDO $pdo, $idUsuario, $idNegocio, $idServicio, $fecha, $hora, $notas = '') {
    if (!fechaValida($fecha) || !horaValida($hora)) return ['ok' => false, 'error' => 'Fecha u hora no válida.'];

    $st = $pdo->prepare("SELECT s.duracion_min FROM servicios s JOIN negocios n ON n.id = s.negocio_id
                         WHERE s.id = ? AND n.id = ? AND n.activo = 1");
    $st->execute([(int)$idServicio, (int)$idNegocio]);
    $duracion = $st->fetchColumn();
    if (!$duracion) return ['ok' => false, 'error' => 'El servicio no pertenece a ese negocio.'];

    $pdo->beginTransaction();
    try {
        // Bloquea el negocio mientras se reserva: evita que dos personas tomen la misma hora a la vez
        $pdo->prepare("SELECT id FROM negocios WHERE id = ? FOR UPDATE")->execute([(int)$idNegocio]);

        if (!in_array($hora, horasDisponibles($pdo, $idNegocio, $fecha, (int)$duracion), true)) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Ese horario ya no está disponible. Elegí otro.'];
        }
        $pdo->prepare("INSERT INTO citas (usuario_id, negocio_id, servicio_id, fecha, hora, hora_fin, notas)
                       VALUES (?,?,?,?,?,?,?)")
            ->execute([(int)$idUsuario, (int)$idNegocio, (int)$idServicio, $fecha, $hora,
                       aHora(aMinutos($hora) + (int)$duracion), mb_substr(trim((string)$notas), 0, 255)]);
        $id = (int)$pdo->lastInsertId();
        $pdo->commit();
        return ['ok' => true, 'id' => $id];
    } catch (Exception $ex) {
        $pdo->rollBack();
        return ['ok' => false, 'error' => 'No se pudo guardar la cita.'];
    }
}

// Cambia el estado respetando quién puede hacer qué
function cambiarEstadoCita(PDO $pdo, $idCita, $nuevo, array $u) {
    if (!in_array($nuevo, ESTADOS, true)) return ['ok' => false, 'error' => 'Estado no válido.'];

    $st = $pdo->prepare("SELECT * FROM citas WHERE id = ?");
    $st->execute([(int)$idCita]);
    $c = $st->fetch();
    if (!$c) return ['ok' => false, 'error' => 'La cita no existe.'];

    // Permisos
    if ($u['rol'] === 'cliente') {
        if ((int)$c['usuario_id'] !== (int)$u['id'] || $nuevo !== 'cancelada')
            return ['ok' => false, 'error' => 'No tenés permiso para esa acción.'];
    } elseif (!puedeAdministrarNegocio($pdo, $u, $c['negocio_id'])) {
        return ['ok' => false, 'error' => 'No tenés permiso sobre esa cita.'];
    }

    // Transiciones permitidas
    $permitidas = [
        'pendiente'  => ['confirmada', 'cancelada'],
        'confirmada' => ['completada', 'cancelada'],
    ];
    if (!in_array($nuevo, $permitidas[$c['estado']] ?? [], true))
        return ['ok' => false, 'error' => 'No se puede pasar de "' . $c['estado'] . '" a "' . $nuevo . '".'];

    $pdo->prepare("UPDATE citas SET estado = ? WHERE id = ?")->execute([$nuevo, $c['id']]);
    return ['ok' => true];
}

// Citas con todos los datos. $filtro: ['usuario_id'] o ['negocios' => [ids]], + estado/fecha opcionales
function listarCitas(PDO $pdo, array $filtro) {
    $sql = "SELECT c.*, TIME_FORMAT(c.hora,'%H:%i') hora, TIME_FORMAT(c.hora_fin,'%H:%i') hora_fin,
                   n.nombre AS negocio, n.categoria, n.direccion, n.whatsapp AS negocio_whatsapp,
                   s.nombre AS servicio, s.precio, s.duracion_min,
                   u.nombre AS cliente, u.telefono AS cliente_telefono,
                   (SELECT estrellas FROM calificaciones k WHERE k.cita_id = c.id) AS calificacion
            FROM citas c
            JOIN negocios n  ON n.id = c.negocio_id
            JOIN servicios s ON s.id = c.servicio_id
            JOIN usuarios u  ON u.id = c.usuario_id
            WHERE 1 = 1";
    $p = [];
    if (isset($filtro['usuario_id'])) { $sql .= " AND c.usuario_id = ?"; $p[] = (int)$filtro['usuario_id']; }
    if (isset($filtro['negocios'])) {
        $ids = array_map('intval', $filtro['negocios']) ?: [0];
        $sql .= " AND c.negocio_id IN (" . implode(',', $ids) . ")";
    }
    if (!empty($filtro['estado']) && in_array($filtro['estado'], ESTADOS, true)) { $sql .= " AND c.estado = ?"; $p[] = $filtro['estado']; }
    if (!empty($filtro['fecha']) && fechaValida($filtro['fecha']))               { $sql .= " AND c.fecha = ?";  $p[] = $filtro['fecha']; }
    $sql .= " ORDER BY c.fecha DESC, c.hora ASC";
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

function calificarCita(PDO $pdo, $idUsuario, $idCita, $estrellas, $comentario = '') {
    $estrellas = (int)$estrellas;
    if ($estrellas < 1 || $estrellas > 5) return ['ok' => false, 'error' => 'Elegí de 1 a 5 estrellas.'];

    $st = $pdo->prepare("SELECT * FROM citas WHERE id = ? AND usuario_id = ?");
    $st->execute([(int)$idCita, (int)$idUsuario]);
    $c = $st->fetch();
    if (!$c)                         return ['ok' => false, 'error' => 'La cita no existe.'];
    if ($c['estado'] !== 'completada') return ['ok' => false, 'error' => 'Solo podés calificar citas completadas.'];

    $st = $pdo->prepare("SELECT 1 FROM calificaciones WHERE cita_id = ?");
    $st->execute([$c['id']]);
    if ($st->fetch()) return ['ok' => false, 'error' => 'Ya calificaste esta cita.'];

    $pdo->prepare("INSERT INTO calificaciones (cita_id, usuario_id, negocio_id, estrellas, comentario) VALUES (?,?,?,?,?)")
        ->execute([$c['id'], $idUsuario, $c['negocio_id'], $estrellas, mb_substr(trim((string)$comentario), 0, 500)]);
    return ['ok' => true];
}

// Mensaje de recordatorio listo para WhatsApp
function mensajeRecordatorio(array $c) {
    return "Hola {$c['cliente']}, te recordamos tu cita en {$c['negocio']}: {$c['servicio']} el "
         . date('d/m/Y', strtotime($c['fecha'])) . " a las {$c['hora']}. Dirección: {$c['direccion']}. ¡Te esperamos!";
}

// ---------------------------------------------------------
//  Encuesta: crea la tabla automáticamente si todavía no existe
// ---------------------------------------------------------
function asegurarTablaFotos(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS fotos (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        negocio_id  INT NOT NULL,
        mime        VARCHAR(30) NOT NULL,
        datos       MEDIUMBLOB NOT NULL,
        creado_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_negocio (negocio_id)
    ) ENGINE=InnoDB");
}

function asegurarTablaEncuesta(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS encuestas (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        general     TINYINT NOT NULL,
        facilidad   TINYINT NOT NULL,
        diseno      TINYINT NOT NULL,
        usaria      ENUM('si','tal_vez','no') NOT NULL,
        perfil      ENUM('cliente','negocio','estudiante','otro') NOT NULL,
        dispositivo ENUM('celular','computadora','tablet') NOT NULL,
        gusto       VARCHAR(500),
        mejora      VARCHAR(500),
        ip_hash     CHAR(64) NOT NULL,
        creado_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_hash, creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
