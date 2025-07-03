<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'generar';

include __DIR__ . '/../../tabs/ventas-tabs.php';
?>

<?php if ($action === 'generar'): ?>
<style>
  .venta-grid {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    gap: 1em;
    margin: 1em 0;
    min-height: 60vh;
  }
  .venta-column {
    border: 1px solid #ccc;
    padding: 1em;
    background-color: #f9f9f9;
    overflow-y: auto;
  }
  #modalResumenVenta {
    display: none;
    position: fixed;
    top: 10%;
    left: 12.5%;
    width: 75%;
    max-height: 80vh;
    background-color: white;
    border: 2px solid #333;
    padding: 1em;
    overflow-y: auto;
    z-index: 9999;
  }
</style>

<h2>Generar Venta</h2>
<div class="venta-grid">
  <div class="venta-column">
    <h4>Descripción de venta</h4>
    <textarea id="descripcionVenta" rows="8" style="width: 100%;"></textarea>
  </div>

  <div class="venta-column">
    <h4>Artículos disponibles</h4>
    <ul id="listaArticulosDisponibles" style="list-style: none; padding: 0;"></ul>
  </div>

  <div class="venta-column">
    <h4>Artículos seleccionados</h4>
    <ul id="articulosSeleccionados" style="list-style: none; padding: 0;"></ul>
  </div>
</div>

<div style="text-align: center; margin-top: 1em;">
  <button onclick="previsualizarVenta()">Registrar Venta</button>
</div>

<div id="modalResumenVenta" style="display:none; max-width: 600px; margin: auto; background: white; border: 2px solid #333; padding: 1em; z-index: 9999; position: fixed; top: 20%; left: 25%;">
  <h3 id="resumenDescripcion"></h3>
  <table border="1" cellspacing="0" cellpadding="6" style="width: 100%; margin-top: 1em;">
    <thead>
      <tr>
        <th>ID Artículo</th>
        <th>Nombre</th>
        <th>Cantidad</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody id="resumenDetallesVenta"></tbody>
  </table>
  <div id="totalVentaModal" style="margin-top: 1em; font-weight: bold; font-size: 1.1em;">
    Total: $0.000
  </div>

  <div id="advertenciasVentaModal" style="background-color: #cce5ff; border: 1px solid #004085; padding: 10px; color: #004085; border-radius: 4px; max-height: 150px; overflow-y: auto; margin-top: 1em;">

  </div>

  <div style="text-align: right; margin-top: 1em;">
    <button onclick="cerrarResumenVenta()">Cerrar</button>
    <button onclick="confirmarVenta()">Confirmar</button>
  </div>
</div>


<script>
let articulosDisponibles = [], articulosVenta = [];
let totalVentaConfirmar = 0;

async function cargarArticulosDisponibles() {
  try {
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
  } catch (err) {
    alert("❌ Error al cargar artículos: " + err.message);
  }
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
        <button onclick="verificarStock(${a.idArticulo}, ${a.cantidadArticulo})" style="margin-left: 10px;">Verificar stock</button>
      </div>
      <hr style="margin: 8px 0;">
    `;
    ul.appendChild(li);
  });
}

function actualizarCantidad(index, valor) {
  const cantidad = Number(valor);
  if (Number.isInteger(cantidad) && cantidad >= 1 && cantidad <= 999999) {
    articulosVenta[index].cantidadArticulo = cantidad;
  } else {
    alert("⚠️ Error: revisar cantidades ingresadas");
    renderizarArticulosSeleccionados();
  }
}

async function verificarStock(idArticulo, cantidadArticulo) {
  try {
    const res = await fetch(`http://localhost:5000/api/Ventas/validar-stock`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ idArticulo, cantidadArticulo })
    });

    const contentType = res.headers.get("content-type");
    const data = contentType && contentType.includes("application/json") ? await res.json() : await res.text();

    if (res.ok) {
      if (typeof data === 'boolean') {
        alert(data
          ? "✅ Stock suficiente disponible"
          : "⚠️ Stock insuficiente para este artículo");
      } else {
        alert("⚠️ Respuesta inesperada del servidor");
      }
    } else {
      const mensaje = data.mensaje || data.error || data || "❌ No se pudo validar el stock";
      alert("❌ Error: " + mensaje);
    }
  } catch (err) {
    alert("❌ Error de red al validar stock: " + err.message);
  }
}

async function previsualizarVenta() {
  const descripcion = document.getElementById('descripcionVenta').value.trim();
  if (!descripcion || articulosVenta.length === 0) {
    alert("⚠️ Completar descripción de venta y seleccionar al menos un artículo");
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
      const venta = data.venta;
      const resultado = data.resultado;

      document.getElementById('resumenDescripcion').textContent = venta.descripcionVenta || descripcion;

      const tbody = document.getElementById('resumenDetallesVenta');
      tbody.innerHTML = '';

      venta.detalles.forEach(d => {
        const art = articulosDisponibles.find(a => a.idArticulo === d.idArticulo);
        const nombre = art ? art.nombreArticulo : '❓No encontrado';

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${d.idArticulo}</td>
          <td>${nombre}</td>
          <td>${d.cantidadArticulo}</td>
          <td style="text-align: right;">$${d.subtotalVenta.toFixed(3)}</td>
        `;
        tbody.appendChild(tr);
      });

      totalVentaConfirmar = venta.totalVenta ?? 0;
      document.getElementById('totalVentaModal').textContent = `Total: $${totalVentaConfirmar.toFixed(3)}`;

      articulosVenta = venta.detalles;

      const advContainer = document.getElementById('advertenciasVentaModal');
      if (resultado.advertencias && resultado.advertencias.length > 0) {
        advContainer.innerHTML = `
          <div style="background-color: #e0f0ff; border: 1px solid #87bfff; padding: 1em; margin-top: 1em;">
            <h4 style="margin-top:0;">Avisos:</h4>
            <ul style="margin:0; padding-left: 1.2em;">
              ${resultado.advertencias.map(msg => `<li>${msg}</li>`).join('')}
            </ul>
          </div>
        `;
      } else {
        advContainer.innerHTML = '';
      }

      document.getElementById('modalResumenVenta').style.display = 'block';

    } else {
      const mensaje = data.mensaje || data.error || "❌ No se pudo previsualizar la venta";
      alert("❌ Error: " + mensaje);
    }
  } catch (err) {
    alert("❌ Error de red al previsualizar venta: " + err.message);
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
      let advertencias = data.advertencias || [];
      if (advertencias.length > 0) {
        alert("⚠️ Avisos:\n" + advertencias.join("\n"));
      }
      alert(data.mensaje || "✅ Venta registrada correctamente");

      cerrarResumenVenta();
      document.getElementById('descripcionVenta').value = '';
      articulosVenta = [];
      totalVentaConfirmar = 0;
      renderizarArticulosSeleccionados();
      document.getElementById('totalVentaModal').textContent = 'Total: $0.000';
    } else {
      const mensaje = data.mensaje || data.error || "❌ No se pudo confirmar la venta";
      alert("❌ Error: " + mensaje);
    }
  } catch (err) {
    alert("❌ Error de red al confirmar venta: " + err.message);
  }
}

document.addEventListener('DOMContentLoaded', cargarArticulosDisponibles);
</script>
<?php endif; ?>