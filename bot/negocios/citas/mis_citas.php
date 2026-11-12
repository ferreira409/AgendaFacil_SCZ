<?php
require __DIR__ . '/../../config/conexion.php';

$u = requiereLogin(['cliente']);

// Cancelar una cita propia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar'])) {
    $r = cambiarEstadoCita($pdo, (int)$_POST['cancelar'], 'cancelada', $u);
    flash($r['ok'] ? 'La cita fue cancelada.' : $r['error'], $r['ok'] ? 'exito' : 'error');
    redirigir('negocios/citas/mis_citas.php');
}

$citas = listarCitas($pdo, ['usuario_id' => $u['id']]);
$hoy = date('Y-m-d');
$proximas = array_values(array_filter($citas, fn($c) => $c['fecha'] >= $hoy && in_array($c['estado'], ['pendiente', 'confirmada'])));
$historial = array_values(array_filter($citas, fn($c) => !in_array($c, $proximas, true)));
usort($proximas, fn($a, $b) => strcmp($a['fecha'] . $a['hora'], $b['fecha'] . $b['hora']));

function filaCita($c, $esProxima) {
    $wa = enlaceWhatsapp($c['negocio_whatsapp'], "Hola, tengo una cita en {$c['negocio']} el " . date('d/m/Y', strtotime($c['fecha'])) . " a las {$c['hora']} ({$c['servicio']}).");
    ?>
    <div class="tarjeta cita-fila">
        <span class="cita-icono"><?= iconoCategoria($c['categoria']) ?></span>
        <div class="cita-datos">
            <strong><?= e($c['negocio']) ?></strong>
            <p><?= e($c['servicio']) ?> · Bs <?= number_format($c['precio'], 2) ?></p>
            <small><?= icono('location-dot') ?> <?= e($c['direccion']) ?></small>
        </div>
        <div class="cita-cuando">
            <span><?= icono('calendar', 'regular') ?> <?= date('d/m/Y', strtotime($c['fecha'])) ?></span>
            <span><?= icono('clock', 'regular') ?> <?= $c['hora'] ?> – <?= $c['hora_fin'] ?></span>
        </div>
        <div class="cita-acciones">
            <?= badgeEstado($c['estado']) ?>
            <?php if ($esProxima): ?>
                <div class="cita-botones">
                    <?php if ($wa): ?>
                        <a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="mini mini-whatsapp"><?= icono('whatsapp', 'brands') ?> WhatsApp</a>
                    <?php endif; ?>
                    <form method="POST" onsubmit="return confirm('¿Cancelar esta cita?')">
                        <?= csrfCampo() ?>
                        <button name="cancelar" value="<?= $c['id'] ?>" class="mini mini-peligro"><?= icono('xmark') ?> Cancelar</button>
                    </form>
                </div>
            <?php elseif ($c['estado'] === 'completada' && !$c['calificacion']): ?>
                <a href="<?= url('negocios/citas/calificar.php?cita=' . $c['id']) ?>" class="mini mini-ok"><?= icono('star', 'regular') ?> Calificar</a>
            <?php elseif ($c['calificacion']): ?>
                <?= estrellasHtml($c['calificacion']) ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

$titulo = 'Mis citas';
require __DIR__ . '/../../includes/header.php';
?>

<section class="seccion">
    <div class="contenedor">
        <?php bannerRol($u, 'Mis citas', 'Revisá y administrá tus reservas'); ?>
        <?php mostrarFlash(); ?>

        <div class="encabezado-pagina">
            <h2><?= icono('calendar-days') ?> Próximas citas</h2>
            <a href="<?= url('negocios/index.php') ?>" class="btn btn-primario"><?= icono('plus') ?> Nueva cita</a>
        </div>

        <?php if (!$proximas): ?>
            <div class="tarjeta vacio-caja">
                <span class="icono-cuadro icono-grande"><?= icono('calendar-plus') ?></span>
                <h3>No tenés citas próximas</h3>
                <p>Buscá un negocio y reservá en segundos.</p>
            </div>
        <?php else: ?>
            <div class="lista-citas"><?php foreach ($proximas as $c) filaCita($c, true); ?></div>
        <?php endif; ?>

        <?php if ($historial): ?>
            <h2 class="subtitulo-lista"><?= icono('clock-rotate-left') ?> Historial</h2>
            <div class="lista-citas"><?php foreach ($historial as $c) filaCita($c, false); ?></div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
