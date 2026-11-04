<?php
// =========================================================
//  IMÁGENES DEL SITIO (URLs externas de Unsplash)
//  Para cambiar una imagen, reemplaza la URL aquí.
//  También puedes usar una imagen local, ej: 'imagenes/mi-foto.jpg'
// =========================================================

function unsplash($id, $ancho = 900) {
    return "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$ancho}&q=80";
}

// Fotos de Wikimedia Commons (libres, con crédito al autor en el footer)
function wikimedia($ruta, $ancho = 1280) {
    $archivo = basename($ruta);
    return "https://upload.wikimedia.org/wikipedia/commons/thumb/{$ruta}/{$ancho}px-{$archivo}";
}

// ---------- FOTOS DE SANTA CRUZ DE LA SIERRA ----------
$IMG_SCZ = [
    'plaza'     => ['titulo' => 'Plaza 24 de Septiembre', 'autor' => 'Parallelepiped09', 'licencia' => 'CC BY-SA 4.0',
                    'url' => wikimedia('b/b5/Plaza_24_de_Septiembre%2C_Santa_Cruz_de_la_Sierra.jpg')],
    'cristo'    => ['titulo' => 'Cristo Redentor', 'autor' => 'rserrate', 'licencia' => 'CC BY-SA 3.0',
                    'url' => wikimedia('2/2b/Cristo_redentor_Santa_Cruz_de_la_Sierra.jpg')],
    'equipetrol'=> ['titulo' => 'Equipetrol', 'autor' => 'Parallelepiped09', 'licencia' => 'CC BY-SA 4.0',
                    'url' => wikimedia('3/3c/Skyline_of_the_Equipetrol_Neighborhood.jpg')],
    'noche'     => ['titulo' => 'Santa Cruz de noche', 'autor' => 'Cinemalejandro', 'licencia' => 'CC BY-SA 3.0',
                    'url' => wikimedia('d/d1/Santa_Cruz_de_la_Sierra_por_la_noche.jpg')],
    'catedral'  => ['titulo' => 'Catedral de noche', 'autor' => 'Marek Grote', 'licencia' => 'CC BY-SA 3.0',
                    'url' => wikimedia('3/35/La_Catedral_de_Santa_Cruz_de_la_Sierra_de_Noche.jpg')],
    'lomas'     => ['titulo' => 'Lomas de Arena', 'autor' => 'Gabriel Millos', 'licencia' => 'CC BY-SA 2.0',
                    'url' => wikimedia('9/9f/Lomas_de_Arena_-_Santa_Cruz%2C_Bolivia.jpg')],
    'botanico'  => ['titulo' => 'Jardín Botánico', 'autor' => 'Parallelepiped09', 'licencia' => 'CC BY-SA 4.0',
                    'url' => wikimedia('2/2a/Jard%C3%ADn_Bot%C3%A1nico_de_Santa_Cruz.jpg')],
    'biblioteca'=> ['titulo' => 'Biblioteca Municipal', 'autor' => 'María Elizabeth García Durán', 'licencia' => 'CC BY-SA 4.0',
                    'url' => wikimedia('4/4e/Biblioteca_Municipal_de_Santa_Cruz.jpg')],
    'skyline'   => ['titulo' => 'Skyline cruceño', 'autor' => 'Parallelepiped09', 'licencia' => 'CC BY-SA 4.0',
                    'url' => wikimedia('9/99/Santa_Cruz_de_la_Sierra_Skyline.jpg')],
    'atardecer' => ['titulo' => 'Atardecer en Equipetrol', 'autor' => 'Ruditaly', 'licencia' => 'CC BY-SA 4.0',
                    'url' => wikimedia('a/a7/Atardecer_en_Equipetrol_Norte_-_Santa_Cruz_de_la_Sierra%2C_Bolivia.jpg')],
];

function imgScz($clave) {
    global $IMG_SCZ;
    return $IMG_SCZ[$clave]['url'] ?? '';
}

// ---------- Imagen principal según el ROL (todas de Santa Cruz) ----------
// (portada del inicio, login/registro y banner del panel)
$IMG_ROL = [
    'visitante' => imgScz('plaza'),       // Plaza 24 de Septiembre
    'cliente'   => imgScz('cristo'),      // Cristo Redentor
    'negocio'   => imgScz('equipetrol'),  // Zona empresarial de Equipetrol
    'admin'     => imgScz('noche'),       // La ciudad de noche
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

// ---------- ÍCONOS (Font Awesome 6) ----------
function icono($nombre, $estilo = 'solid') {
    return '<i class="fa-' . $estilo . ' fa-' . $nombre . '" aria-hidden="true"></i>';
}

$ICONO_CATEGORIA = [
    'Barbería'    => 'scissors',
    'Peluquería'  => 'wand-magic-sparkles',
    'Spa'         => 'spa',
    'Manicure'    => 'hand-sparkles',
    'Odontología' => 'tooth',
    'Consultorio' => 'stethoscope',
    'Otro'        => 'store',
];

function nombreIconoCategoria($categoria) {
    global $ICONO_CATEGORIA;
    return $ICONO_CATEGORIA[$categoria] ?? 'store';
}

function iconoCategoria($categoria) {
    return icono(nombreIconoCategoria($categoria));
}

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

// Foto del negocio: la que subió el vendedor o, si no tiene, la de su categoría
function imgNegocio(array $n) {
    return !empty($n['foto']) ? url($n['foto']) : imgCategoria($n['categoria']);
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
