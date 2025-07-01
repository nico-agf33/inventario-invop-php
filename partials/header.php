<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Gestión de Inventarios</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
    $mod = $_GET['mod'] ?? '';
?>
<nav>
    <a href="index.php?mod=articulos" class="<?= $mod === 'articulos' ? 'activo-nav' : '' ?>">Gestión de Artículos</a>
    <a href="index.php?mod=proveedores" class="<?= $mod === 'proveedores' ? 'activo-nav' : '' ?>">Gestión de Proveedores</a>
    <a href="index.php?mod=ordenes" class="<?= $mod === 'ordenes' ? 'activo-nav' : '' ?>">Órdenes de Compra</a>
    <a href="index.php?mod=ventas" class="<?= $mod === 'ventas' ? 'activo-nav' : '' ?>">Ventas</a>
</nav>