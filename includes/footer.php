<footer class="footer footer-scz">
    <div class="franja-cruceña" aria-hidden="true"></div>
    <div class="contenedor footer-grid">
        <div>
            <p class="footer-logo"><?= icono('calendar-check') ?> <?= NOMBRE_SITIO ?> <span class="logo-scz">SCZ</span></p>
            <p><?= icono('location-dot') ?> <?= CIUDAD ?>, Bolivia</p>
            <p>© <?= date("Y") ?> · Proyecto universitario (simulación)</p>
        </div>
        <div class="footer-links">
            <a href="<?= url('negocios/index.php') ?>">Buscar negocios</a>
            <a href="<?= url('auth/registro.php?tipo=negocio') ?>">Registrar mi negocio</a>
            <a href="<?= url('encuesta.php') ?>">Dejá tu opinión (encuesta)</a>
            <a href="<?= url('api/') ?>">API para desarrolladores</a>
            <?php if (defined('TELEGRAM_USUARIO') && TELEGRAM_USUARIO): ?>
                <a href="https://t.me/<?= e(TELEGRAM_USUARIO) ?>" target="_blank" rel="noopener"><?= icono('telegram', 'brands') ?> Reservá por Telegram</a>
            <?php endif; ?>
            <?php if (defined('WA_NUMERO') && WA_NUMERO): ?>
                <a href="https://wa.me/<?= e(preg_replace('/\D/', '', WA_NUMERO)) ?>?text=Hola" target="_blank" rel="noopener"><?= icono('whatsapp', 'brands') ?> Reservá por WhatsApp</a>
            <?php endif; ?>
        </div>
        <details class="creditos">
            <summary><?= icono('camera') ?> Créditos de las fotos</summary>
            <ul>
                <?php global $IMG_SCZ; foreach ($IMG_SCZ as $f): ?>
                    <li><?= e($f['titulo']) ?> — <?= e($f['autor']) ?>, <?= e($f['licencia']) ?>, vía Wikimedia Commons</li>
                <?php endforeach; ?>
            </ul>
        </details>
    </div>
</footer>

<?php
// Botón flotante: clientes y visitantes -> buscar negocios; negocio/admin -> panel
$uFab = usuarioActual();
$ocultarFab = strpos($_SERVER['SCRIPT_NAME'], 'reservar.php') !== false || strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
if (!$ocultarFab):
    if (!$uFab || $uFab['rol'] === 'cliente'): ?>
    <a href="<?= url('negocios/index.php') ?>" class="boton-flotante" title="Reservar una cita">
        <?= icono('calendar-plus') ?> <span class="fab-texto">Reservar cita</span>
    </a>
<?php else: ?>
    <a href="<?= url('admin/index.php') ?>" class="boton-flotante" title="Ir al panel">
        <?= icono('table-columns') ?> <span class="fab-texto">Ir al panel</span>
    </a>
<?php endif; endif; ?>

<!-- Aviso para instalar la app -->
<div class="aviso-app" id="avisoApp" hidden>
    <img src="<?= url('assets/iconos/icono-192.png') ?>" alt="" width="44" height="44">
    <div class="aviso-app-texto">
        <strong>Instalá AgendaFácil</strong>
        <small id="avisoAppTexto">Tené tus citas a un toque, como una app.</small>
    </div>
    <button class="btn btn-primario btn-chico" id="btnInstalar">Instalar</button>
    <button class="aviso-app-cerrar" id="cerrarAvisoApp" aria-label="Cerrar"><?= icono('xmark') ?></button>
</div>

<?php require __DIR__ . '/chatbot.php'; // asistente virtual ?>

<button class="btn-arriba" id="btnArriba" aria-label="Volver arriba"><?= icono('arrow-up') ?></button>

<?= $scriptsExtra ?? '' ?>
<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= url('js/app.js') ?>"></script>
</body>
</html>
