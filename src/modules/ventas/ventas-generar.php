<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'generar';

include __DIR__ . '/../../tabs/ventas-tabs.php';
?>

<?php if ($action === 'generar'): ?>

<h2>Generar Venta</h2>
<div class="contenedor-ventas-generar">
  <div class="columna">
    <h4>Descripción de venta</h4>
    <textarea id="descripcionVenta" rows="8" class="textarea-base"></textarea>
  </div>

  <div class="columna">
    <h4>Artículos disponibles</h4>
    <ul id="listaArticulosDisponibles" class="lista-articulos"></ul>
  </div>

  <div class="columna">
    <h4>Artículos seleccionados</h4>
    <ul id="articulosSeleccionados" class="lista-articulos"></ul>
  </div>
</div>

<div class="acciones-ventas">
  <button class="boton-accion" onclick="previsualizarVenta()">Registrar Venta</button>
</div>

<div id="modalResumenVenta" class="modal">
  <div class="modal-content"></div>
  <span class="close" onclick="cerrarResumenVenta()">&times;</span>
  <table class="tabla-base">
    <thead>
      <tr>
        <th>ID Artículo</th>
        <th>Cantidad</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody id="resumenDetallesVenta"></tbody>
  </table>
  <div id="totalVentaModal" class="total-venta-modal">
    Total: $0.000
  </div>
  <div class="acciones-modal">
    <button class="boton-accion" onclick="cerrarResumenVenta()">Cerrar</button>
    <button class="boton-accion" onclick="confirmarVenta()">Confirmar</button>
  </div>
</div>

<script>
let articulosDisponibles = [], articulosVenta = [];
let totalVentaConfirmar = 0;  

async function cargarArticulosDisponibles() {
  const res = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
  const data = await res.json();
  articulosDisponibles = data;
  const ul = document.getElementById('listaArticulosDisponibles');
  ul.innerHTML = '';

  data.forEach(art => {
    const li = document.createElement('li');
    li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
    li.style.cursor = 'pointer';
    li.onclick = () => seleccionarArticulo(art);
    ul.appendChild(li);
  });
}

function seleccionarArticulo(articulo) {
  if (articulosVenta.find(a => a.idArticulo === articulo.idArticulo)) return;

  articulosVenta.push({ idArticulo: articulo.idArticulo, cantidadArticulo: 1 });
  renderizarArticulosSeleccionados();
}

function eliminarArticulo(index) {
  articulosVenta.splice(index, 1);
  renderizarArticulosSeleccionados();
}

function renderizarArticulosSeleccionados() {
  const ul = document.getElementById('articulosSeleccionados');
  ul.innerHTML = '';

  articulosVenta.forEach((a, i) => {
    const li = document.createElement('li');
    li.innerHTML = `
      <div>
        <strong>ID:</strong> ${a.idArticulo}<br>
        <label>Cantidad: </label>
        <input type="number" value="${a.cantidadArticulo}" min="1" max="999999" step="1" onchange="actualizarCantidad(${i}, this.value)">
        <button onclick="eliminarArticulo(${i})" style="margin-left: 10px;">Quitar</button>
      </div><hr style="margin: 8px 0;">
    `;
    ul.appendChild(li);
  });
}

function actualizarCantidad(index, valor) {
  const cantidad = Number(valor);

  if (
    Number.isInteger(cantidad) &&
    cantidad >= 1 &&
    cantidad <= 999999
  ) {
    articulosVenta[index].cantidadArticulo = cantidad;
  } else {
    alert("Error: revisar cantidades ingresadas");
    renderizarArticulosSeleccionados();
  }
}

async function previsualizarVenta() {
  const descripcion = document.getElementById('descripcionVenta').value.trim();
  if (!descripcion || articulosVenta.length === 0) {
    alert("Completar descripción de venta y seleccionar al menos un artículo");
    return;
  }

  const dto = {
    descripcionVenta: descripcion,
    detalles: articulosVenta
  };

  try {
    const res = await fetch('http://localhost:5000/api/Ventas/visualizar-venta', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dto)
    });

    const data = await res.json();

    if (res.ok) {
      document.getElementById('resumenDescripcion').textContent = data.descripcionVenta;
      const tbody = document.getElementById('resumenDetallesVenta');
      tbody.innerHTML = '';

      data.detalles.forEach(d => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${d.idArticulo}</td>
          <td>${d.cantidadArticulo}</td>
          <td>$${d.subtotalVenta.toFixed(3)}</td>
        `;
        tbody.appendChild(tr);
      });

      totalVentaConfirmar = data.totalVenta ?? 0;
      document.getElementById('totalVentaModal').textContent = `Total: $${totalVentaConfirmar.toFixed(3)}`;

      articulosVenta = data.detalles;
      document.getElementById('modalResumenVenta').style.display = 'block';
    } else {
      alert("Error: " + (data.mensaje || "No se pudo previsualizar la venta"));
    }
  } catch (err) {
    alert("Error de red: " + err.message);
  }
}

function cerrarResumenVenta() {
  document.getElementById('modalResumenVenta').style.display = 'none';
}

async function confirmarVenta() {
  const dto = {
    descripcionVenta: document.getElementById('descripcionVenta').value.trim(),
    detalles: articulosVenta,
    totalVenta: totalVentaConfirmar
  };

  try {
    const res = await fetch('http://localhost:5000/api/Ventas/crear-venta', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dto)
    });

    const data = await res.json();

    if (res.ok) {
      alert(data.mensaje || "Venta registrada correctamente");
      cerrarResumenVenta();
      document.getElementById('descripcionVenta').value = '';
      articulosVenta = [];
      totalVentaConfirmar = 0;
      renderizarArticulosSeleccionados();
      document.getElementById('totalVentaModal').textContent = 'Total: $0.000';
    } else {
      alert("Error: " + (data.error || "No se pudo confirmar la venta"));
    }
  } catch (err) {
    alert("Error de red: " + err.message);
  }
}

document.addEventListener('DOMContentLoaded', cargarArticulosDisponibles);
</script>
<?php endif; ?>