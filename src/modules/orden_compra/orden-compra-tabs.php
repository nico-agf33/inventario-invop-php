<?php
function activeTab($tabName, $currentAction) {
    return $tabName === $currentAction
        ? 'class="tab-link active-tab-module"'
        : 'class="tab-link"';
}

$action = $_GET['action'] ?? 'gestion-oc';
?>

<div style="margin-top: 1em; margin-bottom: 1em;">
    <a href="index.php?mod=ordenes&action=gestion-oc" <?= activeTab('gestion-oc', $action) ?>>Gestión de órdenes de compra</a> 
    <a href="index.php?mod=ordenes&action=mod-oc" <?= activeTab('mod-oc', $action) ?>>Modificar orden de compra</a> 
    <a href="index.php?mod=ordenes&action=lista-oc-x-art" <?= activeTab('lista-oc-x-art', $action) ?>>Lista de órdenes de compra por artículo</a>
</div>