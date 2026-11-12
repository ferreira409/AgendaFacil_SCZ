<?php
require __DIR__ . '/../config/conexion.php';

$n = obtenerNegocio($pdo, (int)($_GET['id'] ?? 0));
if (!$n) { http_response_code(404); require __DIR__ . '/../404.php'; exit; }

$diasAbiertos = array_column($n['horarios'], null, 'dia');
$hoy = (int)date('N');
$mapa = 'https://maps.google.com/maps?q=' . rawurlencode($n['direccion'] . ', ' . $n['zona'] . ', ' . CIUDAD . ', Bolivia') . '&z=15&output=embed';
$wa = enlaceWhatsapp($n['whatsapp'], "Hola {$n['nombre']}, vi su negocio en " . NOMBRE_SITIO . " y quiero hacer una consulta.");

$titulo = $n['nombre'];
require __DIR__ . '/../includes/header.php';
?>

<section class="negocio-portada" style="--foto:url('<?= e(imgNegocio($n)) ?>')">
    <div class="contenedor negocio-portada-contenido">
        <span class="tn-categoria"><?= iconoCategoria($n['categoria']) ?> <?= e($n['categoria']) ?></span>
        <h1><?= e($n['nombre']) ?></h1>
        <p><?= icono('location-dot') ?> <?= e($n['direccion']) ?> · <?= e($n['zona']) ?></p>
        <?= estrellasHtml($n['promedio'], $n['total']) ?>
    </div>
</section>

<section class="seccion seccion-pegada">
    <div class="contenedor negocio-grid">
        <div class="negocio-principal">
            <?php if ($n['descripcion']): ?>
                <div class="tarjeta caja-form">
                    <h2>Sobre nosotros</h2>
                    <p><?= nl2br(e($n['descripcion'])) ?></p>
                </div>
            <?php endif; ?>

            <div class="tarjeta caja-form">
                <h2><?= icono('list-check') ?> Servicios</h2>
                <?php if (!$n['servicios']): ?>
                    <p class="vacio">Este negocio aún no cargó servicios.</p>
                <?php endif; ?>
                <ul class="lista-servicios lista-servicios-grande">
                    <?php foreach ($n['servicios'] as $s): ?>
                        <li>
                            <div>
                                <strong><?= e($s['nombre']) ?></strong>
                                <small><?= icono('clock', 'regular') ?> <?= (int)$s['duracion_min'] ?> min</small>
                            </div>
                            <div class="servicio-derecha">
                                <strong>Bs <?= number_format($s['precio'], 2) ?></strong>
                                <a href="<?= url('negocios/citas/reservar.php?negocio=' . $n['id'] . '&servicio=' . $s['id']) ?>" class="btn btn-primario btn-chico">Reservar</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="tarjeta caja-form">
                <h2><?= icono('star', 'regular') ?> Opiniones</h2>
                <?php if (!$n['resenas']): ?>
                    <p class="vacio">Todavía no hay opiniones. ¡Reservá y sé el primero en calificar!</p>
                <?php endif; ?>
                <?php foreach ($n['resenas'] as $r): ?>
                    <div class="resena">
                        <div class="resena-top">
                            <strong><?= e(explode(' ', $r['nombre'])[0]) ?></strong>
                            <?= estrellasHtml($r['estrellas']) ?>
                            <small><?= date('d/m/Y', strtotime($r['creado_en'])) ?></small>
                        </div>
                        <?php if ($r['comentario']): ?><p><?= e($r['comentario']) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <aside class="negocio-lateral">
            <div class="tarjeta caja-form">
                <a href="<?= url('negocios/citas/reservar.php?negocio=' . $n['id']) ?>" class="btn btn-primario btn-bloque btn-grande"><?= icono('calendar-plus') ?> Reservar cita</a>
                <?php if ($wa): ?>
                    <a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="btn btn-whatsapp btn-bloque"><?= icono('whatsapp', 'brands') ?> Escribir por WhatsApp</a>
                <?php endif; ?>
                <?php if ($n['telefono']): ?>
                    <a href="tel:<?= e($n['telefono']) ?>" class="btn btn-borde btn-bloque"><?= icono('phone') ?> <?= e($n['telefono']) ?></a>
                <?php endif; ?>
            </div>

            <div class="tarjeta caja-form">
                <h3><?= icono('clock', 'regular') ?> Horario de atención</h3>
                <ul class="lista-horario">
                    <?php foreach (DIAS as $num => $nombre): ?>
                        <li class="<?= $num === $hoy ? 'hoy' : '' ?>">
                            <span><?= $nombre ?></span>
                            <?php if (isset($diasAbiertos[$num])): ?>
                                <span><?= $diasAbiertos[$num]['abre'] ?> – <?= $diasAbiertos[$num]['cierra'] ?></span>
                            <?php else: ?>
                                <span class="cerrado">Cerrado</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="tarjeta mapa">
                <iframe src="<?= e($mapa) ?>" loading="lazy" title="Mapa de <?= e($n['nombre']) ?>" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
