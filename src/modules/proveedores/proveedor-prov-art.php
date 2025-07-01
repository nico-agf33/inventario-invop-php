<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'prov-art';



include __DIR__ . '/proveedores-tabs.php';
?>

<?php if ($action === 'prov-art'): ?>
<div style="display: flex; height: calc(100vh - 160px); gap: 1em;">

  <div style="flex: 1; overflow-y: auto; border-right: 1px solid #ccc; padding-right: 1em;">
    <h4>Proveedores activos</h4>
    <ul id="listaProveedoresActivos" style="list-style: none; padding: 0;"></ul>
  </div>

  <div style="flex: 2; overflow-y: auto; border-right: 1px solid #ccc; padding: 0 1em;">
    <h4>Artículos relacionados</h4>
    <ul id="listaArticulosRelacionados" style="list-style: none; padding: 0;"></ul>
    <div id="formEdicionRelacionContainer"></div>
  </div>

  <div style="flex: 1; overflow-y: auto; padding-left: 1em;">
    <h4>Artículos no relacionados</h4>
    <ul id="listaArticulosNoRelacionados" style="list-style: none; padding: 0;"></ul>
  </div>
</div>

<script>
let proveedorSeleccionado = null;

async function cargarProveedoresActivos() {
  const lista = document.getElementById('listaProveedoresActivos');
  lista.innerHTML = '';
  const res = await fetch('http://localhost:5000/Proveedor/activos');
  const data = await res.json();
  data.forEach(p => {
    const li = document.createElement('li');
    li.textContent = `#${p.idProveedor} - ${p.nombreProveedor}`;
    li.style.cursor = 'pointer';
    li.className = 'articulo-item'; 
    li.dataset.id = p.idProveedor;  
    if (p.idProveedor === proveedorSeleccionado) {
      li.classList.add('selected');
    }
    li.onclick = () => seleccionarProveedor(p.idProveedor);
    lista.appendChild(li);
  });
}

async function seleccionarProveedor(idProv) {
  proveedorSeleccionado = idProv;
  cerrarFormEdicion();
  cargarRelacionados(idProv);
  cargarNoRelacionados(idProv);
  const lista = document.getElementById('listaProveedoresActivos');
  lista.querySelectorAll('li').forEach(li => {
    li.classList.remove('selected');
    if (parseInt(li.dataset.id) === idProv) {
      li.classList.add('selected');
    }
  });
}

async function cargarRelacionados(idProv) {
  const lista = document.getElementById('listaArticulosRelacionados');
  lista.innerHTML = '';
  const res = await fetch(`http://localhost:5000/Proveedor/articulos-proveedor/${idProv}`);
  const data = await res.json();
  data.forEach(art => {
    const li = document.createElement('li');
    li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
    li.style.cursor = 'pointer';
    li.onclick = () => mostrarFormularioEdicion(art);

    const btnEliminar = document.createElement('button');
    btnEliminar.textContent = 'Eliminar';
    btnEliminar.style.marginLeft = '1em';
    btnEliminar.onclick = async () => {
      if (!confirm("¿Confirmar baja de relación proveedor-artículo?")) return;
      try {
        const res = await fetch('http://localhost:5000/api/ProveedorArticulo/baja-prov-art', {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ idProveedor: proveedorSeleccionado, idArticulo: art.idArticulo })
        });
        if (res.ok) {
          alert("Relación eliminada correctamente");
          cargarRelacionados(proveedorSeleccionado);
          cargarNoRelacionados(proveedorSeleccionado);
          cerrarFormEdicion();
        } else {
          const msg = await res.json();
          alert("Error al eliminar: " + (msg.error || msg));
        }
      } catch (err) {
        alert("Error de red: " + err.message);
      }
    };

    li.appendChild(btnEliminar);
    lista.appendChild(li);
  });
}

function mostrarFormularioEdicion(art) {
  const contenedor = document.getElementById('formEdicionRelacionContainer');
  contenedor.innerHTML = '';

  const form = document.createElement('form');
  form.innerHTML = `
    <h4>Editar relación con artículo #${art.idArticulo} - ${art.nombreArticulo}</h4>
    <p>Proveedor predeterminado: <strong>${art.predeterminado ? 'Sí' : 'No'}</strong></p>
    <label>Precio Unitario:</label><br>
    <input type="number" name="precioUnitario" min="0.01" max="999999" step="0.01" value="${art.precioUnitario || ''}" required><br>
    <label>Tiempo de Entrega (días):</label><br>
    <input type="number" name="tiempoEntregaDias" min="1" max="999999" value="${art.tiempoEntregaDias || ''}" required><br>
    <label>Costo de Pedido:</label><br>
    <input type="number" name="costoPedido" min="0.01" max="999999" step="0.01" value="${art.costoPedido || ''}" required><br><br>
    <button type="submit">Guardar cambios</button>
    <button type="button" onclick="cerrarFormEdicion()">Cancelar</button>
  `;

  form.onsubmit = async function (e) {
    e.preventDefault();

    const payload = {
      idProveedor: proveedorSeleccionado,
      idArticulo: art.idArticulo,
      precioUnitario: parseFloat(form.precioUnitario.value),
      tiempoEntregaDias: parseInt(form.tiempoEntregaDias.value),
      costoPedido: parseFloat(form.costoPedido.value),
      predeterminado: art.predeterminado,
      fechaFinProveedorArticulo: art.fechaFinProveedorArticulo
    };

    const url = art.precioUnitario != null ? 'mod-prov-art' : ''; 
    if (!url) {
      try {
        const res = await fetch('http://localhost:5000/api/ProveedorArticulo', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        if (res.ok) {
          alert("Relación creada correctamente");
          cargarRelacionados(proveedorSeleccionado);
          cargarNoRelacionados(proveedorSeleccionado);
          cerrarFormEdicion();
        } else {
          const msg = await res.text();
          alert("Error al crear relación: " + msg);
        }
      } catch (err) {
        alert("Error de red: " + err.message);
      }
    } else {
      try {
        const res = await fetch('http://localhost:5000/api/ProveedorArticulo/mod-prov-art', {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        if (res.ok) {
          alert('Relación actualizada correctamente');
          cerrarFormEdicion();
          cargarRelacionados(proveedorSeleccionado);
        } else {
          const msg = await res.text();
          alert("Error al actualizar: " + msg);
        }
      } catch (err) {
        alert("Error de red: " + err.message);
      }
    }
  };

  contenedor.appendChild(form);
}

function cerrarFormEdicion() {
  document.getElementById('formEdicionRelacionContainer').innerHTML = '';
}

let articuloSeleccionado = null; 

async function cargarNoRelacionados(idProv) {
  const lista = document.getElementById('listaArticulosNoRelacionados');
  lista.innerHTML = '';

  const res = await fetch(`http://localhost:5000/api/ProveedorArticulo/art-no-relacionados/${idProv}`);
  const data = await res.json();

  data.forEach(art => {
    const li = document.createElement('li');
    li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
    li.className = 'articulo-item';
    li.dataset.id = art.idArticulo;

    if (art.idArticulo === articuloSeleccionado) {
      li.classList.add('selected');
    }

    li.onclick = () => {
      document.querySelectorAll('#listaArticulosNoRelacionados .articulo-item').forEach(el => {
        el.classList.remove('selected');
      });

      li.classList.add('selected');
      articuloSeleccionado = art.idArticulo;

      mostrarFormularioEdicion({
        idArticulo: art.idArticulo,
        nombreArticulo: art.nombreArticulo,
        precioUnitario: null,
        tiempoEntregaDias: null,
        costoPedido: null,
        predeterminado: false,
        fechaFinProveedorArticulo: null
      });
    };

    lista.appendChild(li);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  cargarProveedoresActivos();
});
</script>
<?php endif; ?>