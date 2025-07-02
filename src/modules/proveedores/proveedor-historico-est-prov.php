<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'historico-est-prov';

include __DIR__ . '/../../tabs/proveedores-tabs.php';
?>

<?php if ($action === 'historico-est-prov'): ?>
  <div style="display: flex; height: calc(100vh - 160px); gap: 1em;">

  <div style="flex: 1; overflow-y: auto; border-right: 1px solid #ccc; padding-right: 1em;">
    <h4>Proveedores</h4>
    <ul id="listaProveedores" style="list-style: none; padding: 0; margin: 0;"></ul>
  </div>

  <div style="flex: 2; overflow-y: auto; padding-left: 1em;">
    <h4>Histórico de Estados</h4>
    <table id="tablaHistorico" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th style="border-bottom: 1px solid #999; padding: 8px; text-align: left;">Estado</th>
          <th style="border-bottom: 1px solid #999; padding: 8px; text-align: left;">Fecha Inicio</th>
          <th style="border-bottom: 1px solid #999; padding: 8px; text-align: left;">Fecha Fin</th>
        </tr>
      </thead>
      <tbody>

      </tbody>
    </table>
  </div>

</div>

<script>
let proveedorSeleccionado = null;

async function cargarProveedores() {
  const lista = document.getElementById('listaProveedores');
  lista.innerHTML = '';
  try {
    const res = await fetch('http://localhost:5000/Proveedor/proveedores-sist');
    if (!res.ok) throw new Error('Error cargando proveedores');
    const data = await res.json();
    data.forEach(p => {
      const li = document.createElement('li');
      li.textContent = `#${p.idProveedor} - ${p.nombreProveedor}`;
      li.style.cursor = 'pointer';
      li.classList.add('articulo-item'); 

      li.onclick = () => seleccionarProveedor(p.idProveedor, li);

      lista.appendChild(li);
    });
  } catch (err) {
    alert('Error al cargar proveedores: ' + err.message);
  }
}

async function seleccionarProveedor(idProveedor, elementoLi) {
  proveedorSeleccionado = idProveedor;
  document.querySelectorAll('#listaProveedores li').forEach(li => li.classList.remove('selected'));
  elementoLi.classList.add('selected');
  await cargarHistorico(idProveedor);
}

async function cargarHistorico(idProveedor) {
  const tbody = document.querySelector('#tablaHistorico tbody');
  tbody.innerHTML = '';

  try {
    const res = await fetch(`http://localhost:5000/Proveedor/${idProveedor}/historial`);
    if (!res.ok) throw new Error('Error al obtener histórico');
    const data = await res.json();

    if (data.length === 0) {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td colspan="3" style="padding: 8px; text-align:center;">No hay historial para este proveedor</td>`;
      tbody.appendChild(tr);
      return;
    }

    data.forEach(est => {
      const tr = document.createElement('tr');

      const fechaInicio = est.fechaIEstadoProveedor ? new Date(est.fechaIEstadoProveedor).toLocaleDateString() : '';
      const fechaFin = est.fechaFEstadoProveedor ? new Date(est.fechaFEstadoProveedor).toLocaleDateString() : '';

      tr.innerHTML = `
        <td style="padding: 8px; border-bottom: 1px solid #ddd;">${est.nombreEstado}</td>
        <td style="padding: 8px; border-bottom: 1px solid #ddd;">${fechaInicio}</td>
        <td style="padding: 8px; border-bottom: 1px solid #ddd;">${fechaFin}</td>
      `;
      tbody.appendChild(tr);
    });
  } catch (err) {
    alert('Error al cargar histórico: ' + err.message);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  cargarProveedores();
});
</script>
<?php endif; ?>