<?php
// Uso: $titulo = "Mi página"; require __DIR__ . '/../includes/header.php';
$u = usuarioActual();
$titulo = $titulo ?? NOMBRE_SITIO;
$paginaActual = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reservá citas en barberías, spas, clínicas y más negocios de Santa Cruz de la Sierra.">
    <meta name="theme-color" content="#00843d">
    <title><?= e($titulo) ?> | <?= NOMBRE_SITIO ?></title>
    <!-- App instalable (PWA) -->
    <link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('assets/iconos/favicon-32.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('assets/iconos/apple-touch-icon.png') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="AgendaFácil">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= url('assets/estilo.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/diseno.css') ?>">
</head>
<body>

<div class="franja-cruceña" aria-hidden="true"></div>
<header class="navbar">
    <div class="contenedor nav-contenido">
        <a href="<?= url('index.php') ?>" class="logo">
            <span class="logo-icono"><?= icono('calendar-check') ?></span>
            <?= NOMBRE_SITIO ?> <span class="logo-scz">SCZ</span>
        </a>

        <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú"><?= icono('bars') ?></button>

        <nav class="nav-links" id="navLinks">
            <a href="<?= url('index.php') ?>" class="<?= $paginaActual === 'index.php' && strpos($_SERVER['SCRIPT_NAME'], '/admin/') === false && strpos($_SERVER['SCRIPT_NAME'], '/negocios/') === false ? 'activo' : '' ?>">Inicio</a>
            <a href="<?= url('negocios/index.php') ?>" class="<?= strpos($_SERVER['SCRIPT_NAME'], '/negocios/') !== false && strpos($_SERVER['SCRIPT_NAME'], '/citas/') === false ? 'activo' : '' ?>">Negocios</a>
            <a href="<?= url('encuesta.php') ?>" class="<?= $paginaActual === 'encuesta.php' ? 'activo' : '' ?>">Opiná</a>

            <?php if (!$u): ?>
                <a href="<?= url('auth/login.php') ?>" class="btn btn-borde btn-chico">Iniciar sesión</a>
                <a href="<?= url('auth/registro.php') ?>" class="btn btn-primario btn-chico">Crear cuenta</a>
            <?php else: ?>
                <?php if ($u['rol'] === 'cliente'): ?>
                    <a href="<?= url('negocios/citas/mis_citas.php') ?>" class="<?= $paginaActual === 'mis_citas.php' ? 'activo' : '' ?>">Mis citas</a>
                <?php else: ?>
                    <a href="<?= url('admin/index.php') ?>" class="<?= strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false ? 'activo' : '' ?>">Panel</a>
                <?php endif; ?>
                <a href="<?= url('cuenta/perfil.php') ?>" class="usuario-nav" title="Mi perfil">
                    <?= avatarHtml($u) ?>
                    <span class="usuario-datos">
                        <strong><?= e(explode(' ', $u['nombre'])[0]) ?></strong>
                        <small><?= e(etiquetaRol($u['rol'])) ?></small>
                    </span>
                </a>
                <form method="POST" action="<?= url('auth/logout.php') ?>" class="form-salir">
                    <?= csrfCampo() ?>
                    <button class="btn btn-borde btn-chico"><?= icono('right-from-bracket') ?> Salir</button>
                </form>
            <?php endif; ?>
        </nav>
    </div>
</header>
