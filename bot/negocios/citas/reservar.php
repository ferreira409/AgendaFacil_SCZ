<?php
require __DIR__ . '/../../config/conexion.php';

$u = requiereLogin(['cliente']);

// ---------- Negocio y servicio ----------
$n = obtenerNegocio($pdo, (int)($_GET['negocio'] ?? $_POST['negocio'] ?? 0));
if (!$n) {
    flash('Elegí un negocio para reservar.', 'error');
    redirigir('negocios/index.php');
}
$servicios = array_column($n['servicios'], null, 'id');
$idServicio = (int)($_GET['servicio'] ?? $_POST['servicio'] ?? 0);
if (!isset($servicios[$idServicio])) $idServicio = (int)array_key_first($servicios);
$servicio = $servicios[$idServicio] ?? null;

// ---------- Fecha ----------
$hoy    = date('Y-m-d');
$maximo = date('Y-m-d', strtotime('+' . DIAS_MAX_RESERVA . ' days'));
$fecha  = $_GET['fecha'] ?? $_POST['fecha'] ?? $hoy;
if (!fechaValida($fecha) || $fecha < $hoy || $fecha > $maximo) $fecha = $hoy;

$errores = [];

// ---------- Guardar ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $servicio) {
    $r = crearCita($pdo, $u['id'], $n['id'], $idServicio, $fecha, $_POST['hora'] ?? '', $_POST['notas'] ?? '');
    if ($r['ok']) {
        flash('¡Cita reservada! El negocio la confirmará pronto.');
        redirigir('negocios/citas/mis_citas.php');
    }
    $errores[] = $r['error'];
}

$libres  = $servicio ? horasDisponibles($pdo, $n['id'], $fecha, (int)$servicio['duracion_min']) : [];
$horario = horarioDelDia($pdo, $n['id'], $fecha);

// Próximos 14 días para elegir rápido
$proximos = [];
for ($i = 0; $i < 14; $i++) {
    $f = date('Y-m-d', strtotime("+$i days"));
    $proximos[] = ['fecha' => $f, 'abierto' => horarioDelDia($pdo, $n['id'], $f) !== null];
}
$diasCortos = [1 => 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

function enlaceReserva($n, $servicio, $fecha) {
    return url('negocios/citas/reservar.php?' . http_build_query(['negocio' => $n, 'servicio' => $servicio, 'fecha' => $fecha]));
}

$titulo = 'Reservar en ' . $n['nombre'];
require __DIR__ . '/../../includes/header.php';
?>

<section class="seccion seccion-gris">
    <div class="contenedor reserva-grid">

        <!-- Resumen del negocio -->
        <aside class="tarjeta negocio-info">
            <div class="negocio-info-foto" style="--foto:url('<?= e(imgNegocio($n)) ?>')"></div>
            <div class="negocio-info-cuerpo">
                <span class="categoria"><?= iconoCategoria($n['categoria']) ?> <?= e($n['categoria']) ?></span>
                <h2><?= e($n['nombre']) ?></h2>
                <p class="direccion"><?= icono('location-dot') ?> <?= e($n['direccion']) ?>, <?= e($n['zona']) ?></p>
                <?= estrellasHtml($n['promedio'], $n['total']) ?>

                <?php if ($servicio): ?>
                <div class="resumen-reserva">
                    <h3>Tu reserva</h3>
                    <p><?= icono('scissors') ?> <?= e($servicio['nombre']) ?></p>
                    <p><?= icono('clock', 'regular') ?> <?= (int)$servicio['duracion_min'] ?> minutos</p>
                    <p><?= icono('calendar', 'regular') ?> <?= DIAS[(int)date('N', strtotime($fecha))] ?> <?= date('d/m/Y', strtotime($fecha)) ?></p>
                    <p class="resumen-precio">Total: <strong>Bs <?= number_format($servicio['precio'], 2) ?></strong></p>
                    <small>Se paga en el local.</small>
                </div>
                <?php endif; ?>
                <a href="<?= url('negocios/ver.php?id=' . $n['id']) ?>" class="enlace-chico"><?= icono('arrow-left') ?> Ver el negocio</a>
            </div>
        </aside>

        <!-- Formulario -->
        <div class="form-caja form-ancho">
            <h1>Reservar cita</h1>
            <p class="form-sub">Elegí el servicio, el día y la hora</p>

            <?php mostrarErrores($errores); ?>

            <?php if (!$servicios): ?>
                <div class="alerta alerta-error">Este negocio aún no tiene servicios disponibles.</div>
            <?php else: ?>

            <!-- 1. Servicio -->
            <div class="paso-reserva">
                <span class="paso-reserva-num">1</span>
                <div class="paso-reserva-cuerpo">
                    <h3>Servicio</h3>
                    <div class="opciones-servicio">
                        <?php foreach ($servicios as $s): ?>
                            <a href="<?= enlaceReserva($n['id'], $s['id'], $fecha) ?>" class="opcion-servicio <?= (int)$s['id'] === $idServicio ? 'activo' : '' ?>">
                                <strong><?= e($s['nombre']) ?></strong>
                                <small><?= (int)$s['duracion_min'] ?> min · Bs <?= number_format($s['precio'], 0) ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 2. Día -->
            <div class="paso-reserva">
                <span class="paso-reserva-num">2</span>
                <div class="paso-reserva-cuerpo">
                    <h3>Día</h3>
                    <div class="dias-rapidos">
                        <?php foreach ($proximos as $p): $ts = strtotime($p['fecha']); ?>
                            <a href="<?= enlaceReserva($n['id'], $idServicio, $p['fecha']) ?>"
                               class="dia-opcion <?= $p['fecha'] === $fecha ? 'activo' : '' ?> <?= $p['abierto'] ? '' : 'cerrado' ?>">
                                <small><?= $diasCortos[(int)date('N', $ts)] ?></small>
                                <strong><?= date('d', $ts) ?></strong>
                                <small><?= $p['abierto'] ? date('m', $ts) . '/' . date('y', $ts) : 'Cerrado' ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <label class="otra-fecha">Otra fecha:
                        <input type="date" id="fechaCita" value="<?= e($fecha) ?>" min="<?= $hoy ?>" max="<?= $maximo ?>"
                               data-url="<?= e(url('negocios/citas/reservar.php?' . http_build_query(['negocio' => $n['id'], 'servicio' => $idServicio]))) ?>">
                    </label>
                </div>
            </div>

            <!-- 3. Hora -->
            <form method="POST" class="formulario">
                <?= csrfCampo() ?>
                <input type="hidden" name="negocio" value="<?= $n['id'] ?>">
                <input type="hidden" name="servicio" value="<?= $idServicio ?>">
                <input type="hidden" name="fecha" value="<?= e($fecha) ?>">

                <div class="paso-reserva">
                    <span class="paso-reserva-num">3</span>
                    <div class="paso-reserva-cuerpo">
                        <h3>Hora
                            <?php if ($horario): ?><small class="horario-dia">Atención: <?= substr($horario['abre'], 0, 5) ?> – <?= substr($horario['cierra'], 0, 5) ?></small><?php endif; ?>
                        </h3>
                        <?php if (!$horario): ?>
                            <p class="vacio-linea"><?= icono('store-slash') ?> El negocio no atiende este día. Elegí otro.</p>
                        <?php elseif (!$libres): ?>
                            <p class="vacio-linea"><?= icono('calendar-xmark', 'regular') ?> No quedan horarios libres para este servicio. Probá otro día.</p>
                        <?php else: ?>
                            <div class="horas">
                                <?php foreach ($libres as $h): ?>
                                    <label class="hora-opcion">
                                        <input type="radio" name="hora" value="<?= $h ?>" required <?= ($_POST['hora'] ?? '') === $h ? 'checked' : '' ?>>
                                        <span><?= $h ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <label>Notas para el negocio (opcional)
                    <textarea name="notas" rows="2" maxlength="255" placeholder="Ej: corte con máquina 2 a los costados"><?= e($_POST['notas'] ?? '') ?></textarea>
                </label>

                <button type="submit" class="btn btn-primario btn-bloque btn-grande" <?= !$libres ? 'disabled' : '' ?>>
                    <?= icono('calendar-check') ?> Confirmar reserva
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
