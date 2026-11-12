<?php
require __DIR__ . '/../../config/conexion.php';

$u = requiereLogin(['cliente']);
$idCita = (int)($_GET['cita'] ?? $_POST['cita'] ?? 0);

$cita = null;
foreach (listarCitas($pdo, ['usuario_id' => $u['id']]) as $c) {
    if ((int)$c['id'] === $idCita) $cita = $c;
}
if (!$cita || $cita['estado'] !== 'completada' || $cita['calificacion']) {
    flash('Esa cita no se puede calificar.', 'error');
    redirigir('negocios/citas/mis_citas.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = calificarCita($pdo, $u['id'], $idCita, $_POST['estrellas'] ?? 0, $_POST['comentario'] ?? '');
    if ($r['ok']) {
        flash('¡Gracias por tu opinión!');
        redirigir('negocios/citas/mis_citas.php');
    }
    $error = $r['error'];
}

$titulo = 'Calificar cita';
require __DIR__ . '/../../includes/header.php';
?>

<section class="seccion-form">
    <div class="form-caja">
        <span class="icono-cuadro icono-grande"><?= iconoCategoria($cita['categoria']) ?></span>
        <h1>¿Cómo te fue en <?= e($cita['negocio']) ?>?</h1>
        <p class="form-sub"><?= e($cita['servicio']) ?> · <?= date('d/m/Y', strtotime($cita['fecha'])) ?></p>

        <?php if ($error): ?><div class="alerta alerta-error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" class="formulario">
            <?= csrfCampo() ?>
            <input type="hidden" name="cita" value="<?= $idCita ?>">
            <div class="selector-estrellas" role="radiogroup" aria-label="Calificación">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="estrellas" id="e<?= $i ?>" value="<?= $i ?>" required>
                    <label for="e<?= $i ?>" title="<?= $i ?> estrellas"><?= icono('star') ?></label>
                <?php endfor; ?>
            </div>
            <label>Comentario (opcional)
                <textarea name="comentario" rows="3" maxlength="500" placeholder="Contanos tu experiencia"></textarea>
            </label>
            <button class="btn btn-primario btn-bloque btn-grande">Enviar calificación</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
