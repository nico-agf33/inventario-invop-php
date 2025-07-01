<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'restaurar-susp';

include __DIR__ . '/../../tabs/proveedores-tabs.php';
?>

<?php if ($action === 'restaurar-susp'): ?>
  <h3>Proveedores Suspendidos</h3>
  <ul id="listaProveedoresSuspendidos" style="list-style:none; padding:0;"></ul>

  <script>
    async function cargarProveedoresSuspendidos() {
      const lista = document.getElementById('listaProveedoresSuspendidos');
      lista.innerHTML = '';
      try {
        const res = await fetch('http://localhost:5000/Proveedor/suspendidos');
        if (!res.ok) throw new Error('Error al cargar proveedores suspendidos');
        const data = await res.json();

        if (data.length === 0) {
          lista.innerHTML = '<li>No hay proveedores suspendidos</li>';
          return;
        }

        data.forEach(prov => {
          const li = document.createElement('li');
          li.style.display = 'flex';
          li.style.alignItems = 'center';
          li.style.justifyContent = 'space-between';
          li.style.padding = '8px 0';
          li.style.borderBottom = '1px solid #ccc';

          const texto = document.createElement('span');
          texto.textContent = `#${prov.idProveedor} - ${prov.nombreProveedor}`;

          const btnRestaurar = document.createElement('button');
          btnRestaurar.title = 'Restaurar proveedor';
          btnRestaurar.style.cursor = 'pointer';
          btnRestaurar.style.border = 'none';
          btnRestaurar.style.background = 'transparent';
          btnRestaurar.style.color = 'green';
          btnRestaurar.style.fontSize = '20px';

          // Icono flecha arriba (unicode) - podés cambiar por svg o icono que uses
          btnRestaurar.innerHTML = '&#x25B2;';

          btnRestaurar.onclick = async () => {
            if (!confirm(`¿Restaurar proveedor ${prov.nombreProveedor}?`)) return;

            try {
              const res = await fetch(`http://localhost:5000/Proveedor/restaurar/${prov.idProveedor}`, {
                method: 'PUT'
              });
              if (res.ok) {
                alert('Proveedor restaurado correctamente');
                cargarProveedoresSuspendidos();
              } else {
                alert('Error al restaurar proveedor');
              }
            } catch (err) {
              alert('Error de red: ' + err.message);
            }
          };

          li.appendChild(texto);
          li.appendChild(btnRestaurar);
          lista.appendChild(li);
        });
      } catch (err) {
        alert('Error: ' + err.message);
      }
    }

    document.addEventListener('DOMContentLoaded', cargarProveedoresSuspendidos);
  </script>
<?php endif; ?>