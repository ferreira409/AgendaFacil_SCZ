<?php
// =========================================================
//  Globito del asistente virtual (chat de la página)
//  Se incluye desde includes/footer.php. No se muestra en el panel.
// =========================================================
if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) return;
$uChat = usuarioActual();
?>
<div class="chatbot" id="chatbot"
     data-url="<?= e(url('bot/web.php')) ?>"
     data-csrf="<?= e(csrfToken()) ?>"
     data-usuario="<?= e($uChat['id'] ?? 'visitante') ?>">

    <button type="button" class="chat-burbuja" id="chatAbrir" aria-label="Abrir el asistente virtual" aria-expanded="false" aria-controls="chatVentana">
        <span class="chat-burbuja-icono chat-icono-abrir"><?= icono('comment-dots') ?></span>
        <span class="chat-burbuja-icono chat-icono-cerrar"><?= icono('xmark') ?></span>
        <span class="chat-punto" aria-hidden="true"></span>
    </button>

    <div class="chat-tooltip" id="chatTooltip" hidden>
        ¿Te ayudo a reservar? 👋
        <button type="button" class="chat-tooltip-cerrar" id="chatTooltipCerrar" aria-label="Cerrar"><?= icono('xmark') ?></button>
    </div>

    <section class="chat-ventana" id="chatVentana" role="dialog" aria-label="Asistente de <?= e(NOMBRE_SITIO) ?>" hidden>
        <header class="chat-cabecera">
            <span class="chat-avatar"><?= icono('robot') ?></span>
            <div class="chat-cabecera-texto">
                <strong>Asistente <?= e(NOMBRE_SITIO) ?></strong>
                <small><span class="chat-en-linea"></span> Responde al instante</small>
            </div>
            <button type="button" class="chat-accion" id="chatReiniciar" title="Empezar de nuevo" aria-label="Empezar de nuevo"><?= icono('rotate-right') ?></button>
            <button type="button" class="chat-accion" id="chatCerrar" title="Cerrar" aria-label="Cerrar el chat"><?= icono('xmark') ?></button>
        </header>

        <div class="chat-mensajes" id="chatMensajes" aria-live="polite"></div>

        <form class="chat-form" id="chatForm" autocomplete="off">
            <input type="text" id="chatTexto" name="mensaje" maxlength="300" placeholder="Escribí tu mensaje…" aria-label="Mensaje">
            <button type="submit" class="chat-enviar" aria-label="Enviar"><?= icono('paper-plane') ?></button>
        </form>

        <?php if (TELEGRAM_USUARIO || WA_NUMERO): ?>
        <div class="chat-canales">
            También en:
            <?php if (TELEGRAM_USUARIO): ?>
                <a href="https://t.me/<?= e(TELEGRAM_USUARIO) ?>" target="_blank" rel="noopener"><?= icono('telegram', 'brands') ?> Telegram</a>
            <?php endif; ?>
            <?php if (WA_NUMERO): ?>
                <a href="https://wa.me/<?= e(preg_replace('/\D/', '', WA_NUMERO)) ?>?text=Hola" target="_blank" rel="noopener"><?= icono('whatsapp', 'brands') ?> WhatsApp</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
</div>
<link rel="stylesheet" href="<?= url('assets/chatbot.css') ?>">
<script src="<?= url('js/chatbot.js') ?>" defer></script>
