<?php
// =========================================================
//  IMÁGENES DEL SITIO (URLs externas de Unsplash)
//  Para cambiar una imagen, reemplaza la URL aquí.
//  También puedes usar una imagen local, ej: 'imagenes/mi-foto.jpg'
// =========================================================

function unsplash($id, $ancho = 900) {
    return "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$ancho}&q=80";
}

// ---------- Imagen principal según el ROL ----------
// (portada del inicio, login/registro y banner del panel)
$IMG_ROL = [
    'visitante' => unsplash('1512941937669-90a1b58e7e9c'),  // persona usando el celular
    'cliente'   => unsplash('1560066984-138dadb4c035'),     // cliente en un salón
    'negocio'   => unsplash('1600880292203-757bb62b4baf'),  // dueña de negocio con laptop
    'admin'     => unsplash('1551288049-bebda4e38f71'),     // panel con estadísticas
];

// ---------- Foto de perfil (avatar) según el ROL ----------
$AVATAR_ROL = [
    'cliente' => unsplash('1494790108377-be9c29b29330', 200),
    'negocio' => unsplash('1507003211169-0a1dd7228f2d', 200),
    'admin'   => unsplash('1500648767791-00dcc994a43e', 200),
];

// ---------- Portada de cada categoría de negocio ----------
$IMG_CATEGORIA = [
    'Barbería'    => unsplash('1503951914875-452162b0f3f1', 600),
    'Peluquería'  => unsplash('1560066984-138dadb4c035', 600),
    'Spa'         => unsplash('1544161515-4ab6ce6db874', 600),
    'Manicure'    => unsplash('1604654894610-df63bc536371', 600),
    'Odontología' => unsplash('1606811971618-4486d14f3f99', 600),
    'Consultorio' => unsplash('1519494026892-80bbd2d6fd0d', 600),
    'Otro'        => unsplash('1441986300917-64674bd600d8', 600),
];

// Textos de cada rol
$ETIQUETA_ROL = ['cliente' => 'Cliente', 'negocio' => 'Vendedor', 'admin' => 'Administrador'];

// ---------- Funciones para usar en las páginas ----------
function imgRol($rol) {
    global $IMG_ROL;
    return $IMG_ROL[$rol] ?? $IMG_ROL['visitante'];
}

function avatarRol($rol) {
    global $AVATAR_ROL;
    return $AVATAR_ROL[$rol] ?? $AVATAR_ROL['cliente'];
}

function imgCategoria($categoria) {
    global $IMG_CATEGORIA;
    return $IMG_CATEGORIA[$categoria] ?? $IMG_CATEGORIA['Otro'];
}

function etiquetaRol($rol) {
    global $ETIQUETA_ROL;
    return $ETIQUETA_ROL[$rol] ?? 'Visitante';
}

// Avatar <img> con respaldo: si la imagen no carga, muestra la inicial
function avatarHtml($usuario, $clase = 'avatar') {
    $inicial = mb_strtoupper(mb_substr($usuario['nombre'], 0, 1));
    return '<span class="' . $clase . ' rol-' . e($usuario['rol']) . '" data-inicial="' . e($inicial) . '">'
         . '<img src="' . e(avatarRol($usuario['rol'])) . '" alt="' . e($usuario['nombre']) . '" loading="lazy" '
         . 'onerror="this.remove()"></span>';
}

// Banner con imagen de fondo según el rol (panel y mis citas)
function bannerRol($usuario, $titulo, $subtitulo = '') {
    $rol = $usuario['rol'];
    echo '<div class="banner-rol rol-' . e($rol) . '" style="--img:url(\'' . e(imgRol($rol)) . '\')">'
       . '<div class="banner-contenido">'
       . avatarHtml($usuario, 'avatar avatar-grande')
       . '<div><span class="banner-etiqueta">' . e(etiquetaRol($rol)) . '</span>'
       . '<h1>' . e($titulo) . '</h1>'
       . ($subtitulo ? '<p>' . e($subtitulo) . '</p>' : '')
       . '</div></div></div>';
}
