<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'lista-oc-x-art';

include __DIR__ . '/../../tabs/orden-compra-tabs.php';
?>

<?php if ($action === 'lista-oc-x-art'): ?>
<h2>Órdenes de Compra por Artículo</h2>

<div style="height: calc(100vh - 250px); border: 1px solid #ccc; border-radius: 6px; padding: 1em; background-color: #fdfdfd; box-shadow: 0 0 10px rgba(0,0,0,0.05); margin-top: 1em; margin-bottom: 1em; display: flex; gap: 1em; overflow: hidden;">

  <div style="flex: 1; display: flex; flex-direction: column; border-right: 1px solid #ccc; padding-right: 1em;">
    <h4>Artículos</h4>
    <ul id="listaArticulos" style="list-style: none; padding: 0; overflow-y: auto; flex: 1; margin: 0;"></ul>
  </div>

  <div style="flex: 1; display: flex; flex-direction: column; border-right: 1px solid #ccc; padding-right: 1em;">
    <h4>Órdenes del artículo</h4>
    <div id="ordenesPorArticulo" style="overflow-y: auto; flex: 1;"></div>
  </div>

  <div style="flex: 1; display: flex; flex-direction: column;">
    <h4>Detalle Orden-Artículo</h4>
    <div id="detalleOrdenArticulo" class="detalle-orden" style="overflow-y: auto; flex: 1;"></div>
  </div>

</div>

<style>
.orden-item {
  cursor: pointer;
  padding: 8px;
  border: 1px solid #ccc;
  margin-bottom: 8px;
  background-color: #f9f9f9;
}
.orden-item.selected {
  border: 2px solid green;
  background-color: #e6ffe6;
}
.detalle-orden {
  background-color: #f4f4f4;
  border: 1px solid #ccc;
  padding: 1em;
}
</style>

<script>
let articuloSeleccionado = null;
let ordenSeleccionada = null;

async function cargarArticulos() {
  try {
    const res = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
    const articulos = await res.json();
    const ul = document.getElementById('listaArticulos');
    ul.innerHTML = '';

    articulos.forEach(art => {
      const li = document.createElement('li');
      li.textContent = art.nombreArticulo;
      li.className = 'articulo-item';
      li.onclick = () => seleccionarArticulo(art.idArticulo, li);
      ul.appendChild(li);
    });
  } catch (err) {
    console.error('Error al cargar artículos:', err);
  }
}

async function seleccionarArticulo(idArticulo, liElement) {
  articuloSeleccionado = idArticulo;

  document.querySelectorAll('#listaArticulos .articulo-item')
    .forEach(el => el.classList.remove('selected'));
  liElement.classList.add('selected');

  document.getElementById('ordenesPorArticulo').innerHTML = '';
  document.getElementById('detalleOrdenArticulo').innerHTML = '';

  try {
    const res = await fetch(`http://localhost:5000/OrdenCompra/articulo/ordenes/${idArticulo}`);
    const ordenes = await res.json();

    if (!ordenes.length) {
      document.getElementById('ordenesPorArticulo').innerText = 'Sin órdenes para este artículo.';
      return;
    }

    ordenes.forEach(oc => {
      const div = document.createElement('div');
      div.className = 'orden-item';
      div.innerHTML = `
        <strong>N°:</strong> ${oc.nOrdenCompra}<br>
        <strong>Fecha:</strong> ${new Date(oc.fechaOrden).toLocaleDateString()}<br>
        <strong>Proveedor:</strong> ${oc.proveedor}<br>
        <strong>Estado:</strong> ${oc.ordenEstado}<br>
        <strong>Total:</strong> $${oc.totalPagar.toFixed(2)}
      `;
      div.onclick = () => seleccionarOrden(oc.nOrdenCompra, div);
      document.getElementById('ordenesPorArticulo').appendChild(div);
    });
  } catch (err) {
    console.error('Error al cargar órdenes:', err);
  }
}

async function seleccionarOrden(nOrdenCompra, divElement) {
  ordenSeleccionada = nOrdenCompra;

  document.querySelectorAll('#ordenesPorArticulo .orden-item')
    .forEach(el => el.classList.remove('selected'));
  divElement.classList.add('selected');

  try {
    const res = await fetch(`http://localhost:5000/OrdenCompra/ordenCompra/${nOrdenCompra}/articulo/${articuloSeleccionado}`);
    const detalle = await res.json();

    const contenedor = document.getElementById('detalleOrdenArticulo');
    contenedor.innerHTML = `
      <p><strong>ID Artículo:</strong> ${detalle.idArticulo}</p>
      <p><strong>Nombre:</strong> ${detalle.nombreArticulo}</p>
      <p><strong>Cantidad:</strong> ${detalle.cantidad}</p>
      <p><strong>Precio Unitario:</strong> $${detalle.precioUnitario.toFixed(3)}</p>
      <p><strong>Subtotal:</strong> $${detalle.subTotal.toFixed(3)}</p>
    `;
  } catch (err) {
    console.error('Error al cargar detalle orden-artículo:', err);
  }
}

document.addEventListener('DOMContentLoaded', cargarArticulos);
</script>
<?php endif; ?>