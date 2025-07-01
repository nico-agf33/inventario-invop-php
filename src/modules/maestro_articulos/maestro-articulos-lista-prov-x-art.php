    <?php
    use GuzzleHttp\Client;

    $action = $_GET['action'] ?? 'lista-prov-x-art';

    include __DIR__ . '/maestro-articulos-tabs.php';
    ?>

    <style>
    .modal-content select {
    position: relative;
    z-index: 1000;
    }
    </style>

    <?php if ($action === 'lista-prov-x-art'): ?>
        <h2>Lista de Proveedores por Artículo</h2>

        <div style="display: flex; gap: 2em; margin-top: 1em; min-height: 400px;">
            <div style="flex: 1; border: 1px solid #ccc; padding: 1em; overflow-y: auto;">
                <h4>Artículos</h4>
                <ul id="listaArticulos" style="list-style: none; padding: 0; margin: 0;"></ul>
            </div>

            <div style="flex: 2; border: 1px solid #ccc; padding: 1em; overflow-y: auto;">
                <h4>Proveedores</h4>
                <div id="infoProveedores">
                    <p>Seleccionar un artículo para ver sus proveedores</p>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const listaArticulos = document.getElementById('listaArticulos');
            const infoProveedores = document.getElementById('infoProveedores');

            try {
                const resp = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
                if (!resp.ok) throw new Error('Error al obtener lista de artículos');
                const articulos = await resp.json();

                articulos.forEach(art => {
                    const li = document.createElement('li');
                    li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
                    li.style.cursor = 'pointer';
                    li.style.margin = '0.5em 0';
                    li.onclick = () => cargarProveedores(art.idArticulo, li);
                    listaArticulos.appendChild(li);
                });
            } catch (err) {
                listaArticulos.innerHTML = '<li>Error al cargar artículos</li>';
            }

            let articuloSeleccionado = null;

            async function cargarProveedores(idArticulo, elementoLi) {
                // Resaltar artículo seleccionado
                if (articuloSeleccionado) articuloSeleccionado.style.backgroundColor = '';
                elementoLi.style.backgroundColor = '#d4edda'; // verde claro
                articuloSeleccionado = elementoLi;

                infoProveedores.innerHTML = '<p>Cargando proveedores...</p>';

                try {
                    const resp = await fetch(`http://localhost:5000/MaestroArticulos/articulosLista/proveedores/${idArticulo}`);
                    if (!resp.ok) throw new Error('Error al obtener proveedores');
                    const proveedores = await resp.json();

                    if (proveedores.length === 0) {
                        infoProveedores.innerHTML = '<p>No hay proveedores asociados.</p>';
                        return;
                    }

                    const table = document.createElement('table');
                    table.border = "1";
                    table.cellPadding = "5";
                    table.cellSpacing = "0";
                    table.style.width = "100%";
                    table.style.borderCollapse = "collapse";

                    const thead = document.createElement('thead');
                    thead.innerHTML = `
                        <tr style="background-color: #eee;">
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Precio Unitario</th>
                            <th>Costo Pedido</th>
                            <th>Tiempo Entrega (días)</th>
                            <th>Predeterminado</th>
                        </tr>`;
                    table.appendChild(thead);

                    const tbody = document.createElement('tbody');

                    proveedores.forEach(p => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${p.nombreProveedor || ''}</td>
                            <td>${p.emailProveedor || ''}</td>
                            <td>${p.telProveedor || ''}</td>
                            <td>${p.direccionProveedor || ''}</td>
                            <td>${p.precioUnitario != null ? p.precioUnitario.toFixed(3) : ''}</td>
                            <td>${p.costoPedido != null ? p.costoPedido.toFixed(3) : ''}</td>
                            <td>${p.tiempoEntregaDias || ''}</td>
                            <td>${p.predeterminado ? 'Sí' : 'No'}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    table.appendChild(tbody);
                    infoProveedores.innerHTML = '';
                    infoProveedores.appendChild(table);

                } catch (err) {
                    infoProveedores.innerHTML = `<p style="color:red;">Error al cargar proveedores: ${err.message}</p>`;
                }
            }
        });
        </script>
    <?php endif; ?>