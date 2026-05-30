<?php
// --- SEGURIDAD ---
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: Tienda.php");
    exit();
}

include("conexion.php");

// --- ACTUALIZAR ESTADO DEL PEDIDO (cuando llega la petición POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'], $_POST['nuevo_estado'])) {
    $estados_validos = ['Pendiente', 'En preparación', 'En camino', 'Entregado'];
    $pedido_id   = intval($_POST['pedido_id']);
    $nuevo_estado = $_POST['nuevo_estado'];

    if (in_array($nuevo_estado, $estados_validos)) {
        $stmt = $conexion->prepare("UPDATE pedidos SET estado_pedido = ? WHERE id_pedido = ?");
        $stmt->bind_param("si", $nuevo_estado, $pedido_id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_pedidos.php?status=success&msg=Estado actualizado correctamente");
    } else {
        header("Location: admin_pedidos.php?status=error&msg=Estado no válido");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Pedidos</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 10px; }
        a { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: middle; }
        th { background-color: #2c3e50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        select { padding: 6px 10px; border-radius: 5px; border: 1px solid #ccc; }
        button { padding: 6px 14px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-guardar { background: #27ae60; color: white; }
        .btn-guardar:hover { background: #219150; }
        .alerta { padding: 12px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; }
        .alerta.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alerta.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Badges de estado */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 13px; font-weight: bold; display: inline-block; }
        .badge-pendiente    { background: #fff3cd; color: #856404; }
        .badge-preparacion  { background: #cce5ff; color: #004085; }
        .badge-camino       { background: #d1ecf1; color: #0c5460; }
        .badge-entregado    { background: #d4edda; color: #155724; }

        /* Sección de detalle colapsable */
        .detalle-btn { background: #ecf0f1; color: #2c3e50; font-size: 13px; }
        .detalle-btn:hover { background: #bdc3c7; }
        .detalle-tabla { display: none; margin-top: 8px; width: 100%; border-collapse: collapse; }
        .detalle-tabla td { border: 1px solid #eee; padding: 6px 10px; font-size: 13px; }
        .detalle-tabla th { background: #ecf0f1; padding: 6px 10px; font-size: 13px; }

        .resumen { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .tarjeta { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px;
                   padding: 15px 20px; flex: 1; min-width: 150px; text-align: center; }
        .tarjeta .numero { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .tarjeta .etiqueta { font-size: 13px; color: #666; margin-top: 4px; }
    </style>
</head>
<body>
<div class="container">
    <a href="Tienda.php">← Volver a la Tienda</a>
    <h2>Administrar Pedidos</h2>

    <?php
    // Mensaje de feedback
    if (!empty($_GET['status']) && !empty($_GET['msg'])):
        $tipo = $_GET['status'] === 'success' ? 'success' : 'error';
        $msg  = htmlspecialchars($_GET['msg']);
    ?>
        <div class="alerta <?= $tipo ?>"><?= $msg ?></div>
    <?php endif; ?>

    <?php
    // --- TARJETAS DE RESUMEN ---
    $totales = $conexion->query("
        SELECT estado_pedido, COUNT(*) as total
        FROM pedidos
        GROUP BY estado_pedido
    ");
    $resumen = ['Pendiente' => 0, 'En preparación' => 0, 'En camino' => 0, 'Entregado' => 0];
    while ($r = $totales->fetch_assoc()) {
        if (isset($resumen[$r['estado_pedido']])) {
            $resumen[$r['estado_pedido']] = $r['total'];
        }
    }
    ?>
    <div class="resumen">
        <div class="tarjeta">
            <div class="numero"><?= $resumen['Pendiente'] ?></div>
            <div class="etiqueta">⏳ Pendientes</div>
        </div>
        <div class="tarjeta">
            <div class="numero"><?= $resumen['En preparación'] ?></div>
            <div class="etiqueta">🔪 En preparación</div>
        </div>
        <div class="tarjeta">
            <div class="numero"><?= $resumen['En camino'] ?></div>
            <div class="etiqueta">🚗 En camino</div>
        </div>
        <div class="tarjeta">
            <div class="numero"><?= $resumen['Entregado'] ?></div>
            <div class="etiqueta">✅ Entregados</div>
        </div>
    </div>

    <?php
    // --- FILTRO POR ESTADO ---
    $filtro = $_GET['filtro'] ?? 'todos';
    $where  = $filtro !== 'todos' ? "WHERE p.estado_pedido = '" . $conexion->real_escape_string($filtro) . "'" : '';
    ?>

    <!-- Filtros rápidos -->
    <div style="margin-bottom: 15px; display: flex; gap: 8px; flex-wrap: wrap;">
        <?php foreach (['todos' => 'Todos', 'Pendiente' => '⏳ Pendiente', 'En preparación' => '🔪 En preparación', 'En camino' => '🚗 En camino', 'Entregado' => '✅ Entregado'] as $val => $label): ?>
            <a href="?filtro=<?= urlencode($val) ?>" style="
                padding: 7px 14px; border-radius: 20px; text-decoration: none; font-size: 13px;
                background: <?= $filtro === $val ? '#2c3e50' : '#ecf0f1' ?>;
                color: <?= $filtro === $val ? 'white' : '#2c3e50' ?>;
            "><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <table>
        <tr>
            <th># Pedido</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Estado actual</th>
            <th>Cambiar estado</th>
            <th>Detalle</th>
        </tr>
        <?php
        // Traemos pedidos con el nombre del usuario (JOIN)
        $sql = "
            SELECT p.id_pedido, p.total, p.fecha, p.estado_pedido,
                   u.nombre_completo AS cliente
            FROM pedidos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id_usuario
            $where
            ORDER BY p.fecha DESC
        ";
        $pedidos = $conexion->query($sql);

        if ($pedidos && $pedidos->num_rows > 0):
            while ($ped = $pedidos->fetch_assoc()):
                // Badge de color según estado
                $badge_class = match($ped['estado_pedido']) {
                    'Pendiente'      => 'badge-pendiente',
                    'En preparación' => 'badge-preparacion',
                    'En camino'      => 'badge-camino',
                    'Entregado'      => 'badge-entregado',
                    default          => ''
                };
        ?>
        <tr>
            <td><strong>#<?= $ped['id_pedido'] ?></strong></td>
            <td><?= htmlspecialchars($ped['cliente'] ?? 'Desconocido') ?></td>
            <td><?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?></td>
            <td>$<?= number_format($ped['total'], 2) ?></td>
            <td>
                <span class="badge <?= $badge_class ?>">
                    <?= htmlspecialchars($ped['estado_pedido']) ?>
                </span>
            </td>
            <td>
                <!-- Formulario inline para cambiar el estado sin salir de la página -->
                <form method="POST" style="display:flex; gap:6px; align-items:center;">
                    <input type="hidden" name="pedido_id" value="<?= $ped['id_pedido'] ?>">
                    <select name="nuevo_estado">
                        <option value="Pendiente"      <?= $ped['estado_pedido'] === 'Pendiente'      ? 'selected' : '' ?>>⏳ Pendiente</option>
                        <option value="En preparación" <?= $ped['estado_pedido'] === 'En preparación' ? 'selected' : '' ?>>🔪 En preparación</option>
                        <option value="En camino"      <?= $ped['estado_pedido'] === 'En camino'      ? 'selected' : '' ?>>🚗 En camino</option>
                        <option value="Entregado"      <?= $ped['estado_pedido'] === 'Entregado'      ? 'selected' : '' ?>>✅ Entregado</option>
                    </select>
                    <button type="submit" class="btn-guardar">Guardar</button>
                </form>
            </td>
            <td>
                <!-- Botón para ver el detalle del pedido -->
                <button class="detalle-btn" onclick="toggleDetalle(<?= $ped['id_pedido'] ?>)">
                    Ver productos
                </button>
                <table class="detalle-tabla" id="detalle-<?= $ped['id_pedido'] ?>">
                    <tr><th>Producto</th><th>Cant.</th><th>Precio unit.</th><th>Subtotal</th></tr>
                    <?php
                    $det = $conexion->prepare(
                        "SELECT producto_nombre, cantidad, precio_unitario FROM detalle_pedidos WHERE pedido_id = ?"
                    );
                    $det->bind_param("i", $ped['id_pedido']);
                    $det->execute();
                    $det_res = $det->get_result();
                    while ($d = $det_res->fetch_assoc()):
                        $subtotal = $d['cantidad'] * $d['precio_unitario'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($d['producto_nombre']) ?></td>
                        <td><?= $d['cantidad'] ?></td>
                        <td>$<?= number_format($d['precio_unitario'], 2) ?></td>
                        <td>$<?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php endwhile; $det->close(); ?>
                </table>
            </td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
        <tr>
            <td colspan="7" style="text-align:center; color:#999; padding: 30px;">
                No hay pedidos <?= $filtro !== 'todos' ? 'con estado "' . htmlspecialchars($filtro) . '"' : 'registrados aún' ?>.
            </td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<script>
function toggleDetalle(id) {
    const tabla = document.getElementById('detalle-' + id);
    tabla.style.display = tabla.style.display === 'table' ? 'none' : 'table';
}
</script>
</body>
</html>
