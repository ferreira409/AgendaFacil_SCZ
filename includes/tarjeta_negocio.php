<?php // Tarjeta de un negocio. Necesita la variable $n (fila de listarNegocios) ?>
<article class="tarjeta tarjeta-negocio">
    <a href="<?= url('negocios/ver.php?id=' . $n['id']) ?>" class="tn-portada" style="--foto:url('<?= e(imgNegocio($n)) ?>')" aria-label="Ver <?= e($n['nombre']) ?>">
        <span class="tn-categoria"><?= e($n['categoria']) ?></span>
        <span class="tn-icono"><?= iconoCategoria($n['categoria']) ?></span>
    </a>
    <div class="tn-cuerpo">
        <h3><a href="<?= url('negocios/ver.php?id=' . $n['id']) ?>"><?= e($n['nombre']) ?></a></h3>
        <p class="direccion"><?= icono('location-dot') ?> <?= e($n['zona']) ?> · <?= e($n['direccion']) ?></p>
        <?= estrellasHtml($n['promedio'], $n['total_calificaciones']) ?>
        <div class="tn-info">
            <span><?= icono('list-check') ?> <?= (int)$n['total_servicios'] ?> servicios</span>
            <?php if ($n['desde'] !== null): ?>
                <span class="tn-precio">desde <strong>Bs <?= number_format($n['desde'], 0) ?></strong></span>
            <?php endif; ?>
        </div>
        <div class="tn-acciones">
            <a href="<?= url('negocios/ver.php?id=' . $n['id']) ?>" class="btn btn-borde btn-chico">Detalles</a>
            <a href="<?= url('negocios/citas/reservar.php?negocio=' . $n['id']) ?>" class="btn btn-primario btn-chico">Reservar</a>
        </div>
    </div>
</article>
