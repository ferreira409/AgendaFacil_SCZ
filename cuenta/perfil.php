<?php
require __DIR__ . '/../config/conexion.php';

$u = requiereLogin();
$errores = [];
$erroresPass = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['accion'] ?? '') === 'datos') {
        $r = actualizarPerfil($pdo, $u['id'], $_POST);
        if ($r['ok']) {
            $st = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $st->execute([$u['id']]);
            $_SESSION['usuario'] = datosPublicosUsuario($st->fetch());
            flash('Tus datos se guardaron.');
            redirigir('cuenta/perfil.php');
        }
        $errores = $r['errores'];
    } elseif (($_POST['accion'] ?? '') === 'password') {
        $r = cambiarPassword($pdo, $u['id'], $_POST['actual'] ?? '', $_POST['nueva'] ?? '', $_POST['nueva2'] ?? '');
        if ($r['ok']) {
            flash('Tu contraseña fue cambiada.');
            redirigir('cuenta/perfil.php');
        }
        $erroresPass = $r['errores'];
    }
}

$st = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$st->execute([$u['id']]);
$datos = $st->fetch();

$titulo = 'Mi perfil';
require __DIR__ . '/../includes/header.php';
?>

<section class="seccion">
    <div class="contenedor contenedor-angosto">
        <?php bannerRol($u, 'Mi perfil', 'Miembro desde ' . date('d/m/Y', strtotime($datos['creado_en']))); ?>
        <?php mostrarFlash(); ?>

        <div class="grid-2">
            <form method="POST" class="tarjeta formulario caja-form">
                <?= csrfCampo() ?>
                <input type="hidden" name="accion" value="datos">
                <h2><?= icono('id-card', 'regular') ?> Datos personales</h2>
                <?php mostrarErrores($errores); ?>
                <label>Nombre completo
                    <input type="text" name="nombre" value="<?= e($datos['nombre']) ?>" required maxlength="100">
                </label>
                <label>Correo electrónico
                    <input type="email" name="email" value="<?= e($datos['email']) ?>" required>
                </label>
                <label>Celular
                    <input type="tel" name="telefono" value="<?= e($datos['telefono']) ?>">
                </label>
                <button class="btn btn-primario"><?= icono('floppy-disk', 'regular') ?> Guardar cambios</button>
            </form>

            <form method="POST" class="tarjeta formulario caja-form">
                <?= csrfCampo() ?>
                <input type="hidden" name="accion" value="password">
                <h2><?= icono('lock') ?> Cambiar contraseña</h2>
                <?php mostrarErrores($erroresPass); ?>
                <label>Contraseña actual
                    <input type="password" name="actual" required autocomplete="current-password">
                </label>
                <label>Nueva contraseña
                    <input type="password" name="nueva" minlength="8" required autocomplete="new-password">
                </label>
                <label>Repetir nueva contraseña
                    <input type="password" name="nueva2" minlength="8" required autocomplete="new-password">
                </label>
                <small class="ayuda"><?= icono('circle-info') ?> Mínimo 8 caracteres, con letras y números.</small>
                <button class="btn btn-borde"><?= icono('key') ?> Cambiar contraseña</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
