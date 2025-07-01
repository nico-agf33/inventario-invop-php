<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'lista-vent-x-art';

include __DIR__ . '/../../tabs/ventas-tabs.php';
?>

<?php if ($action === 'lista-vent-x-art'): ?>
<style>
  .selected-row { background-color: #c8f7c5; font-weight: bold; }
  .articulo-item { padding: 0.5em; cursor: pointer; border-bottom: 1px solid #ccc; }
</style>

<div style="margin: 1em 0; padding: 1em; border: 1px solid #ccc; border-radius: 8px; background-color: #f9f9f9;">
  <div style="display: flex; height: calc(100vh - 180px);">
    <div style="width: 35%; padding: 1em; border-right: 1px solid #ccc; overflow-y: auto;">
      <h3>Artículos activos</h3>
      <ul id="listaArticulosActivos" style="list-style: none; padding: 0;"></ul>
    </div>

    <div style="width: 65%; padding: 1em; overflow-y: auto;">
      <h3>Ventas del artículo</h3>
      <div id="mensajeSinVentas" style="color: #777; font-style: italic; margin-bottom: 1em;"></div>
      <table border="1" cellspacing="0" cellpadding="6" style="width: 100%; text-align: left;">
        <thead>
          <tr>
            <th>N° Venta</th>
            <th>Cantidad Vendida</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody id="tablaVentasArticulo"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
let articuloSeleccionado = null;

async function cargarArticulosActivos() {
  const res = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
  const data = await res.json();
  const lista = document.getElementById('listaArticulosActivos');
  lista.innerHTML = '';

  data.forEach(art => {
    const li = document.createElement('li');
    li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
    li.className = 'articulo-item';
    li.onclick = () => seleccionarArticulo(art.idArticulo, li);
    lista.appendChild(li);
  });
}

async function seleccionarArticulo(idArticulo, liElemento) {
  articuloSeleccionado = idArticulo;

  document.querySelectorAll('.articulo-item').forEach(li => li.classList.remove('selected-row'));
  liElemento.classList.add('selected-row');

  const res = await fetch(`http://localhost:5000/api/Ventas/art-vent/${idArticulo}`);
  const data = await res.json();

  const tabla = document.getElementById('tablaVentasArticulo');
  tabla.innerHTML = '';
  const mensaje = document.getElementById('mensajeSinVentas');

  if (data.length === 0) {
    mensaje.textContent = 'Este artículo no tiene ventas registradas.';
  } else {
    mensaje.textContent = '';
    data.forEach(venta => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${venta.nVenta}</td>
        <td>${venta.cantidadVendida}</td>
        <td>$${venta.subtotal.toFixed(2)}</td>
      `;
      tabla.appendChild(tr);
    });
  }
}

document.addEventListener('DOMContentLoaded', cargarArticulosActivos);
</script>
<?php endif; ?>