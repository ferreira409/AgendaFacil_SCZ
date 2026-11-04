<footer class="footer">
    <div class="contenedor">
        <p>© <?= date("Y") ?> <?= NOMBRE_SITIO ?> · Todos los derechos reservados</p>
    </div>
</footer>

<?php
// Botón flotante: clientes y visitantes -> reservar; negocio/admin -> panel
$uFab = usuarioActual();
if (!$uFab || $uFab['rol'] === 'cliente'):
    $enReserva = strpos($_SERVER['SCRIPT_NAME'], 'reservar.php') !== false;
    if (!$enReserva): ?>
    <a href="<?= url('index.php#negocios') ?>" class="fab" title="Reservar una cita">
        📅 <span class="fab-texto">Reservar cita</span>
    </a>
<?php endif; else: ?>
    <a href="<?= url('admin/index.php') ?>" class="fab" title="Ir al panel">
        📋 <span class="fab-texto">Mis citas</span>
    </a>
<?php endif; ?>

<button class="btn-arriba" id="btnArriba" aria-label="Volver arriba">↑</button>

<script src="<?= url('js/app.js') ?>"></script>
</body>
</html>
