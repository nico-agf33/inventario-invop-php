    <?php
    use GuzzleHttp\Client;

    $action = $_GET['action'] ?? 'abm-art';

    include __DIR__ . '/maestro-articulos-tabs.php';
    ?>

    <style>
    .modal-content select {
    position: relative;
    z-index: 1000;
    }
    </style>

    <?php if ($action === 'abm-art'): ?>

    <div id="modalCrearArticulo" class="modal" style="display:none;">
    <div class="modal-content" style="
        max-width: 600px;
        margin: 5% auto;
        background-color: white;
        padding: 20px;
        position: relative;
        border-radius: 8px;
        overflow: visible;
    ">
        <span class="close" onclick="
        document.getElementById('modalCrearArticulo').style.display='none';
        document.getElementById('idArticulo').value = '';
        document.querySelector('#modalCrearArticulo h3').textContent = 'Crear nuevo artículo';
        const form = document.getElementById('formCrearArticulo');
        form.querySelector('input[type=submit]').value = 'Guardar artículo';
        form.reset();
        document.getElementById('modeloInv').dispatchEvent(new Event('change'));
        ">&times;</span>
        <h3>Crear nuevo artículo</h3>
        <form id="formCrearArticulo">
            <input type="hidden" name="idArticulo" id="idArticulo">
            <label>Nombre: <input type="text" name="nombreArticulo" required></label><br><br>
            <label>Descripción: <input type="text" name="descripcion" required></label><br><br>

            <label>Modelo Inventario:
                <select name="modeloInv" id="modeloInv" required></select>
            </label><br><br>

            <label>Categoría Artículo:
                <select name="categoriaArt" id="categoriaArt" required></select>
            </label><br><br>

            <label>Demanda Diaria:
                <input type="number" name="demandaDiaria" min="1" max="999999" required>
            </label><br><br>

            <label>Costo Almacén:
                <input type="number" name="costoAlmacen" step="0.01" min="0.01" max="999999" required>
            </label><br><br>

            <label>Tiempo Revisión:
                <input type="number" name="tiempoRevision" id="tiempoRevision" min="1" max="999999" disabled required>
            </label><br><br>

            <label>Stock máx.:
                <input type="number" name="stockMax" id="stockMax" min="1" max="999999" required>
            </label><br><br>

            <input type="submit" value="Guardar artículo">
        </form>
    </div>
    </div>

    <div id="modalProveedores" class="modal" style="display:none;">
    <div class="modal-content" style="
        max-width: 90vw;
        width: 600px;
        background-color: white;
        padding: 1em;
        overflow-x: auto;
        position: relative;
        border-radius: 8px;
    ">
    <span class="close" onclick="cerrarModalProveedores()" style="
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 24px;
        cursor: pointer;
        color: #aaa;
    ">&times;</span>
        
        <h3 style="margin-top: 0; margin-bottom: 20px; font-family: Arial, sans-serif;">Proveedores del Artículo</h3>
        
        <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;">
        <thead style="background-color: #f2f2f2;">
            <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Precio Unitario</th>
            <th>Costo Pedido</th>
            <th>Stock máx.</th>
            <th>Tiempo Entrega (días)</th>
            <th>Predeterminado</th>
            </tr>
        </thead>
        <tbody id="proveedoresBody">
        </tbody>
        </table>
    </div>
    </div>

    <style>
    #modalCrearArticulo {
        position: fixed !important;
        top: 0; left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0,0,0,0.4);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    #modalCrearArticulo .modal-content {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        max-width: 500px;
        width: 95%;
        position: relative;
        z-index: 10000;
    }
    </style>

    <script>
    window.addEventListener('DOMContentLoaded', async () => {
        const modeloSelect = document.getElementById('modeloInv');
        const categoriaSelect = document.getElementById('categoriaArt');
        const tiempoInput = document.getElementById('tiempoRevision');

        try {
            const modelos = await fetch('http://localhost:5000/MaestroArticulos/modeloInventario/lista-modelos').then(r => r.json());
            modelos.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = m.nombreModInv;
                modeloSelect.appendChild(opt);
            });

            const categorias = await fetch('http://localhost:5000/MaestroArticulos/modeloInventario/lista-categorias').then(r => r.json());
            categorias.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.nombreCatArt;
                categoriaSelect.appendChild(opt);
            });

            modeloSelect.addEventListener('change', () => {
                const selectedId = parseInt(modeloSelect.value);
                tiempoInput.disabled = (selectedId !== 2); 
            });

        } catch (e) {
            alert("error cargando modelos o categorías: " + e);
        }
    });

    document.getElementById('formCrearArticulo').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const modeloId = parseInt(form.modeloInv.value);
        const categoriaId = parseInt(form.categoriaArt.value);
        const idArticulo = document.getElementById('idArticulo').value;

        const data = {
            nombreArticulo: form.nombreArticulo.value.trim(),
            descripcion: form.descripcion.value.trim(),
            modeloInv: modeloId,
            categoriaArt: categoriaId,
            demandaDiaria: parseInt(form.demandaDiaria.value),
            stockMax: parseInt(form.stockMax.value),
            costoAlmacen: parseFloat(form.costoAlmacen.value),
            tiempoRevision: modeloId === 2 ? parseInt(form.tiempoRevision.value) : 0,
            idMaster: 1
        };

        if (
            data.demandaDiaria < 1 || data.demandaDiaria > 999999 ||
            data.stockMax < 1 || data.stockMax > 999999 ||
            data.costoAlmacen <= 0 || data.costoAlmacen > 999999 ||
            (modeloId === 2 && (data.tiempoRevision < 1 || data.tiempoRevision > 999999))
        ) {
            alert("Verificar valores numéricos ingresados ");
            return;
        }

        if (idArticulo) {
            data.idArticulo = parseInt(idArticulo);
            try {
                const resp = await fetch('http://localhost:5000/MaestroArticulos/articulo/UpdateArticulo', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });

                if (resp.ok) {
                    alert("Artículo modificado con éxito ");
                    location.reload();
                } else {
                    const msg = await resp.text();
                    alert("Error al modificar el artículo: " + msg);
                }
            } catch (err) {
                alert("Error de red: " + err);
            }
        } else {
            try {
                const resp = await fetch('http://localhost:5000/MaestroArticulos/articulo/CreateArticulo', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });

                if (resp.ok) {
                    alert("Artículo creado con éxito ");
                    location.reload();
                } else {
                    const msg = await resp.text();
                    alert("Error al crear el artículo: " + msg);
                }
            } catch (err) {
                alert("Error de red o conexión: " + err);
            }
        }
    });

    function editarArticulo(articulo) {
        document.getElementById('modalCrearArticulo').style.display = 'block';

        const form = document.getElementById('formCrearArticulo');
        form.nombreArticulo.value = articulo.nombreArticulo || '';
        form.descripcion.value = articulo.descripcion || '';
        form.demandaDiaria.value = articulo.demandaDiaria || '';
        form.costoAlmacen.value = articulo.costoAlmacen || '';
        form.tiempoRevision.value = articulo.tiempoRevision || '';
        form.stockMax.value = articulo.stockMax || '';
        document.getElementById('idArticulo').value = articulo.idArticulo;
        document.getElementById('modeloInv').value = articulo.modeloInvId || '';
        document.getElementById('categoriaArt').value = articulo.categoriaArtId || '';

        document.getElementById('tiempoRevision').disabled = (parseInt(articulo.modeloInvId) !== 2);

        document.querySelector('#modalCrearArticulo h3').textContent = 'Editar Artículo';
        form.querySelector('input[type="submit"]').value = 'Guardar Cambios';

        form.setAttribute('data-modo', 'editar');
    }

    async function eliminarArticulo(idArticulo) {
        if (!confirm('¿Confirma que desea eliminar este artículo? Esta acción no se puede deshacer ')) {
            return;
        }

        try {
            const resp = await fetch(`http://localhost:5000/MaestroArticulos/articulo/DeleteArticulo/${idArticulo}`, {
                method: 'DELETE'
            });

            if (resp.ok) {
                alert('Artículo eliminado con éxito ');
                location.reload();
            } else {
                const msg = await resp.text();
                alert('Error al eliminar el artículo: ' + msg);
            }
        } catch (err) {
            alert('Error de red o conexión: ' + err);
        }
    }

    async function verProveedores(idArticulo) {
        const modal = document.getElementById('modalProveedores');
        const tbody = document.getElementById('proveedoresBody');

        tbody.innerHTML = '';

        try {
            const response = await fetch(`http://localhost:5000/MaestroArticulos/articulosLista/proveedores/${idArticulo}`);
            if (!response.ok) throw new Error('Error al obtener proveedores');

            const proveedores = await response.json();

            if (proveedores.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">No hay proveedores asociados</td></tr>';
            } else {
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
                        <td style="white-space: nowrap; background-color: #fff; text-align: center;">
                            ${p.predeterminado ? 'Sí' : 'No'}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            modal.style.display = 'block';
        } catch (err) {
            alert('Error cargando proveedores: ' + err.message);
        }
    }

    function cerrarModalProveedores() {
        document.getElementById('modalProveedores').style.display = 'none';
    }

    async function calcularParametros() {
        if (!confirm("¿Confirma que desea recalcular parámetros?")) return;

        try {
            const resp = await fetch('http://localhost:5000/MaestroArticulos/modeloInventario/calc-mod-inv');

            if (!resp.ok) {
                const msg = await resp.text();
                alert("Error en el cálculo: " + msg);
                return;
            }

            const contentType = resp.headers.get('content-type');

            if (contentType && contentType.includes('application/json')) {
                const datos = await resp.json();

                if (Array.isArray(datos) && datos.length === 0) {
                    alert("No se encontraron artículos para calcular, o los existentes no poseen proveedor predeterminado");
                } else {
                    alert("Parámetros de inventario calculados exitosamente.");
                }

            } else {
                const texto = await resp.text();
                alert("Resultado: " + texto);
            }

            location.reload();

        } catch (err) {
            alert("Error de red: " + err.message);
        }
    }

    function abrirModalCrearArticulo() {
        const modal = document.getElementById('modalCrearArticulo');
        const form = document.getElementById('formCrearArticulo');

        modal.style.display = 'block';

        document.getElementById('idArticulo').value = '';
        document.querySelector('#modalCrearArticulo h3').textContent = 'Crear nuevo artículo';
        form.querySelector('input[type=submit]').value = 'Guardar artículo';
        form.reset();

        const modeloSelect = document.getElementById('modeloInv');
        modeloSelect.selectedIndex = 0; 
        modeloSelect.dispatchEvent(new Event('change')); 
    }
    </script>

        <?php
        $client = new Client(['base_uri' => 'http://localhost:5000']); 

        try {
            $response = $client->get('/MaestroArticulos/articulos/list-art-datos'); 
            $articulos = json_decode($response->getBody(), true);
        } catch (Exception $e) {
            $articulos = [];
            echo "<p style='color:red;'>Error al obtener artículos: " . $e->getMessage() . "</p>";
        }
        ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1em;">
        <h2 style="margin: 0;">Lista de Artículos Activos</h2>
        <div style="display: flex; gap: 1em;">
            <button class="boton-accion" onclick="abrirModalCrearArticulo()" style="display: flex; align-items: center; gap: 0.5em; padding: 0.5em 1em;">
                ＋<span style="font-size: 1.2em;"></span>
                <span>Añadir artículo</span>
            </button>

            <button onclick="calcularParametros()" style="display: flex; align-items: center; gap: 0.5em; padding: 0.5em 1em;">
                <span style="font-size: 1.2em;">&#128425;</span>
                <span>Calcular parámetros de stock</span>
            </button>
        </div>
    </div>

    <div style="max-height: 60vh; overflow-y: auto; border: 1px solid #ccc;">
        <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: #eee; position: sticky; top: 0; z-index: 1;">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Prov. predeterminado</th>
                    <th>Modelo Inv.</th>
                    <th>Categoría</th>
                    <th>Demanda Diaria</th>
                    <th>Costo Almacén</th>
                    <th>Tiempo Revisión</th>
                    <th>Stock Actual</th>
                    <th>Stock Máx.</th>
                    <th>Stock de Seguridad</th>
                    <th>Punto de Pedido</th>
                    <th>CGI</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($articulos)): ?>
                    <tr><td colspan="14" style="text-align:center;">No hay artículos activos.</td></tr>
                <?php else: ?>
                    <?php foreach ($articulos as $art): ?>
                        <tr>
                            <td><?= htmlspecialchars($art['idArticulo']) ?></td>
                            <td><?= htmlspecialchars($art['nombreArticulo']) ?></td>
                            <td><?= htmlspecialchars($art['descripcion'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['proveedor'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['modeloInv'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['categoriaArt'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['demandaDiaria'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['costoAlmacen'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['tiempoRevision'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['stockActual'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['stockMax'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['stockSeguridad'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['puntoPedido'] ?? '') ?></td>
                            <td><?= htmlspecialchars($art['cgi'] ?? '') ?></td>
                            <td>
                                <button onclick="editarArticulo(<?= htmlspecialchars(json_encode($art)) ?>)" title="Editar" style="margin-right:4px;">✏️</button>
                                <button onclick="eliminarArticulo(<?= $art['idArticulo'] ?>)" title="Eliminar" style="margin-right:4px; color:red;">❌</button>
                                <button onclick="verProveedores(<?= $art['idArticulo'] ?>)" title="Ver Proveedores">👤</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>