<?php
require __DIR__ . '/../config/conexion.php';

$filtros = [
    'q'         => trim($_GET['q'] ?? ''),
    'categoria' => in_array($_GET['categoria'] ?? '', CATEGORIAS, true) ? $_GET['categoria'] : '',
    'zona'      => in_array($_GET['zona'] ?? '', ZONAS, true) ? $_GET['zona'] : '',
];
$negocios = listarNegocios($pdo, $filtros);
$hayFiltros = $filtros['q'] || $filtros['categoria'] || $filtros['zona'];

$titulo = 'Buscar negocios';
require __DIR__ . '/../includes/header.php';
?>

<section class="encabezado-bandera">
    <div class="contenedor">
        <h1>Negocios en Santa Cruz</h1>
        <p>Encontrá dónde reservar tu próxima cita</p>

        <form method="GET" class="buscador">
            <label class="buscador-campo buscador-texto">
                <?= icono('magnifying-glass') ?>
                <input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Nombre, servicio o dirección...">
            </label>
            <label class="buscador-campo">
                <?= icono('tags') ?>
                <select name="categoria"><?= opcionesSelect(CATEGORIAS, $filtros['categoria'], 'Todas las categorías') ?></select>
            </label>
            <label class="buscador-campo">
                <?= icono('map-location-dot') ?>
                <select name="zona"><?= opcionesSelect(ZONAS, $filtros['zona'], 'Todas las zonas') ?></select>
            </label>
            <button class="btn btn-primario"><?= icono('magnifying-glass') ?> Buscar</button>
        </form>
    </div>
</section>

<section class="seccion seccion-gris seccion-pegada">
    <div class="contenedor">
        <div class="resultado-info">
            <span><strong><?= count($negocios) ?></strong> <?= count($negocios) === 1 ? 'negocio encontrado' : 'negocios encontrados' ?></span>
            <?php if ($hayFiltros): ?>
                <a href="<?= url('negocios/index.php') ?>" class="enlace-chico"><?= icono('xmark') ?> Quitar filtros</a>
            <?php endif; ?>
        </div>

        <?php if (!$negocios): ?>
            <div class="vacio-caja tarjeta">
                <span class="icono-cuadro icono-grande"><?= icono('store-slash') ?></span>
                <h3>No encontramos negocios con esos filtros</h3>
                <p>Probá con otra categoría o zona.</p>
            </div>
        <?php else: ?>
            <div class="grid-4">
                <?php foreach ($negocios as $n) include __DIR__ . '/../includes/tarjeta_negocio.php'; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
