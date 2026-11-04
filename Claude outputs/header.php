<?php
// Uso: $titulo = "Mi página"; require __DIR__ . '/../includes/header.php';
$u = usuarioActual();
$titulo = $titulo ?? NOMBRE_SITIO;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | <?= NOMBRE_SITIO ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/estilo.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/diseno.css') ?>">
</head>
<body>

<header class="navbar">
    <div class="contenedor nav-contenido">
        <a href="<?= url('index.php') ?>" class="logo">📅 <?= NOMBRE_SITIO ?></a>

        <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">☰</button>

        <nav class="nav-links" id="navLinks">
            <a href="<?= url('index.php') ?>">Inicio</a>
            <a href="<?= url('index.php#negocios') ?>">Negocios</a>

            <?php if (!$u): ?>
                <a href="<?= url('index.php#como-funciona') ?>">¿Cómo funciona?</a>
                <a href="<?= url('auth/login.php') ?>" class="btn btn-borde">Iniciar sesión</a>
                <a href="<?= url('auth/registro.php') ?>" class="btn btn-primario">Registrarse</a>
            <?php else: ?>
                <?php if ($u['rol'] === 'cliente'): ?>
                    <a href="<?= url('negocios/citas/mis_citas.php') ?>">Mis citas</a>
                <?php else: ?>
                    <a href="<?= url('admin/index.php') ?>">Panel</a>
                <?php endif; ?>
                <span class="usuario-nav">👤 <?= e(explode(' ', $u['nombre'])[0]) ?></span>
                <a href="<?= url('auth/logout.php') ?>" class="btn btn-borde">Salir</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
