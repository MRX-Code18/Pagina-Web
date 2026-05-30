<?php
// --- SEGURIDAD ---
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: Tienda.php");
    exit();
}

include("conexion.php");

// --- DATOS PARA LAS TARJETAS DE RESUMEN ---
$total_pedidos    = $conexion->query("SELECT COUNT(*) as t FROM pedidos")->fetch_assoc()['t'];
$pedidos_pendient = $conexion->query("SELECT COUNT(*) as t FROM pedidos WHERE estado_pedido = 'Pendiente'")->fetch_assoc()['t'];
$total_productos  = $conexion->query("SELECT COUNT(*) as t FROM inventario")->fetch_assoc()['t'];
$total_proveed    = $conexion->query("SELECT COUNT(*) as t FROM proveedores")->fetch_assoc()['t'];
$total_usuarios   = $conexion->query("SELECT COUNT(*) as t FROM usuarios")->fetch_assoc()['t'];
$ventas_hoy       = $conexion->query("SELECT SUM(total) as t FROM pedidos WHERE DATE(fecha) = CURDATE()")->fetch_assoc()['t'] ?? 0;
$ventas_mes       = $conexion->query("SELECT SUM(total) as t FROM pedidos WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())")->fetch_assoc()['t'] ?? 0;

// --- ÚLTIMOS 5 PEDIDOS ---
$ultimos_pedidos = $conexion->query("
    SELECT p.id_pedido, p.total, p.fecha, p.estado_pedido, u.nombre_completo AS cliente
    FROM pedidos p
    LEFT JOIN usuarios u ON p.usuario_id = u.id_usuario
    ORDER BY p.fecha DESC
    LIMIT 5
");

// --- PRODUCTOS MÁS REGISTRADOS POR CATEGORÍA ---
$por_categoria = $conexion->query("
    SELECT categoria, COUNT(*) as total
    FROM inventario
    GROUP BY categoria
    ORDER BY total DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración — Carnicería</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 240px;
            background: #1a252f;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
        }

        .sidebar-logo {
            padding: 25px 20px;
            background: #141e27;
            text-align: center;
            border-bottom: 1px solid #2c3e50;
        }

        .sidebar-logo h2 {
            font-size: 18px;
            color: #e74c3c;
            letter-spacing: 1px;
        }

        .sidebar-logo p {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 4px;
        }

        .sidebar-user {
            padding: 15px 20px;
            background: #2c3e50;
            font-size: 13px;
            color: #bdc3c7;
            border-bottom: 1px solid #34495e;
        }

        .sidebar-user strong {
            display: block;
            color: white;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .sidebar nav {
            flex: 1;
            padding: 20px 0;
        }

        .nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #7f8c8d;
            padding: 10px 20px 5px;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #bdc3c7;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: #2c3e50;
            color: white;
            border-left: 3px solid #e74c3c;
        }

        .sidebar nav a .icon { font-size: 18px; width: 22px; text-align: center; }

        .sidebar-footer {
            padding: 15px 20px;
            border-top: 1px solid #2c3e50;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e74c3c;
            text-decoration: none;
            font-size: 13px;
        }

        .sidebar-footer a:hover { color: #c0392b; }

        /* ── CONTENIDO PRINCIPAL ── */
        .main {
            margin-left: 240px;
            padding: 30px;
            flex: 1;
            width: calc(100% - 240px);
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 { font-size: 24px; color: #2c3e50; }
        .page-header p  { color: #7f8c8d; font-size: 14px; margin-top: 4px; }

        /* ── TARJETAS KPI ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }

        .kpi {
            background: white;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            border-left: 4px solid #ccc;
        }

        .kpi.rojo    { border-left-color: #e74c3c; }
        .kpi.verde   { border-left-color: #27ae60; }
        .kpi.azul    { border-left-color: #2980b9; }
        .kpi.naranja { border-left-color: #e67e22; }
        .kpi.morado  { border-left-color: #8e44ad; }

        .kpi .kpi-icon  { font-size: 26px; }
        .kpi .kpi-num   { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .kpi .kpi-label { font-size: 12px; color: #7f8c8d; }

        /* ── DOS COLUMNAS ── */
        .dos-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .dos-col { grid-template-columns: 1fr; }
        }

        /* ── CARDS GENERALES ── */
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        .card h3 {
            font-size: 15px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ecf0f1;
        }

        /* ── TABLA ── */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; font-size: 13px; }
        th { color: #7f8c8d; font-weight: 600; border-bottom: 1px solid #ecf0f1; }
        td { border-bottom: 1px solid #f8f9fa; color: #2c3e50; }
        tr:last-child td { border-bottom: none; }

        /* ── BADGES ── */
        .badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-pendiente    { background: #fff3cd; color: #856404; }
        .badge-preparacion  { background: #cce5ff; color: #004085; }
        .badge-camino       { background: #d1ecf1; color: #0c5460; }
        .badge-entregado    { background: #d4edda; color: #155724; }

        /* ── ACCESOS RÁPIDOS ── */
        .accesos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 25px;
        }

        .acceso {
            background: white;
            border-radius: 10px;
            padding: 20px 15px;
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .acceso:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .acceso .acc-icon { font-size: 30px; margin-bottom: 8px; }
        .acceso .acc-label { font-size: 13px; font-weight: 600; }

        /* ── BARRA DE CATEGORÍAS ── */
        .barra-wrap { margin-bottom: 10px; }
        .barra-label { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px; }
        .barra-bg { background: #ecf0f1; border-radius: 20px; height: 10px; overflow: hidden; }
        .barra-fill { height: 100%; border-radius: 20px; background: #e74c3c; }
    </style>
</head>
<body>

<!-- ══════════════ SIDEBAR ══════════════ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <h2>🥩 CARNICERÍA</h2>
        <p>Panel de Administración</p>
    </div>

    <div class="sidebar-user">
        <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong>
        Administrador
    </div>

    <nav>
        <div class="nav-label">Principal</div>
        <a href="panel_admin.php" class="active">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="Tienda.php">
            <span class="icon">🏪</span> Ver Tienda
        </a>

        <div class="nav-label" style="margin-top:10px;">Gestión</div>
        <a href="admin_pedidos.php">
            <span class="icon">📦</span> Pedidos
            <?php if ($pedidos_pendient > 0): ?>
                <span style="background:#e74c3c; color:white; font-size:11px; padding:2px 7px; border-radius:20px; margin-left:auto;">
                    <?= $pedidos_pendient ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="admin_inventario.php">
            <span class="icon">🗃️</span> Inventario
        </a>
        <a href="admin_proveedores.php">
            <span class="icon">🚛</span> Proveedores
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php">
            <span>🚪</span> Cerrar sesión
        </a>
    </div>
</aside>

<!-- ══════════════ CONTENIDO ══════════════ -->
<main class="main">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Bienvenido, <?= htmlspecialchars($_SESSION['usuario']) ?>. Aquí tienes el resumen de hoy.</p>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid">
        <div class="kpi verde">
            <span class="kpi-icon">💰</span>
            <span class="kpi-num">$<?= number_format($ventas_hoy, 2) ?></span>
            <span class="kpi-label">Ventas de hoy</span>
        </div>
        <div class="kpi azul">
            <span class="kpi-icon">📅</span>
            <span class="kpi-num">$<?= number_format($ventas_mes, 2) ?></span>
            <span class="kpi-label">Ventas del mes</span>
        </div>
        <div class="kpi rojo">
            <span class="kpi-icon">⏳</span>
            <span class="kpi-num"><?= $pedidos_pendient ?></span>
            <span class="kpi-label">Pedidos pendientes</span>
        </div>
        <div class="kpi naranja">
            <span class="kpi-icon">📦</span>
            <span class="kpi-num"><?= $total_pedidos ?></span>
            <span class="kpi-label">Pedidos totales</span>
        </div>
        <div class="kpi morado">
            <span class="kpi-icon">🗃️</span>
            <span class="kpi-num"><?= $total_productos ?></span>
            <span class="kpi-label">Productos en inventario</span>
        </div>
        <div class="kpi azul">
            <span class="kpi-icon">👥</span>
            <span class="kpi-num"><?= $total_usuarios ?></span>
            <span class="kpi-label">Usuarios registrados</span>
        </div>
        <div class="kpi verde">
            <span class="kpi-icon">🚛</span>
            <span class="kpi-num"><?= $total_proveed ?></span>
            <span class="kpi-label">Proveedores</span>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="accesos">
        <a href="admin_pedidos.php" class="acceso">
            <div class="acc-icon">📦</div>
            <div class="acc-label">Ver Pedidos</div>
        </a>
        <a href="admin_inventario.php" class="acceso">
            <div class="acc-icon">➕</div>
            <div class="acc-label">Agregar Producto</div>
        </a>
        <a href="admin_proveedores.php" class="acceso">
            <div class="acc-icon">🚛</div>
            <div class="acc-label">Proveedores</div>
        </a>
        <a href="admin_pedidos.php?filtro=Pendiente" class="acceso">
            <div class="acc-icon">⏳</div>
            <div class="acc-label">Pendientes</div>
        </a>
        <a href="admin_pedidos.php?filtro=Entregado" class="acceso">
            <div class="acc-icon">✅</div>
            <div class="acc-label">Entregados</div>
        </a>
    </div>

    <!-- Tabla de últimos pedidos + categorías -->
    <div class="dos-col">

        <!-- Últimos pedidos -->
        <div class="card">
            <h3>📋 Últimos 5 pedidos</h3>
            <table>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
                <?php if ($ultimos_pedidos && $ultimos_pedidos->num_rows > 0):
                    while ($p = $ultimos_pedidos->fetch_assoc()):
                        $badge_class = match($p['estado_pedido']) {
                            'Pendiente'      => 'badge-pendiente',
                            'En preparación' => 'badge-preparacion',
                            'En camino'      => 'badge-camino',
                            'Entregado'      => 'badge-entregado',
                            default          => ''
                        };
                ?>
                <tr>
                    <td><strong>#<?= $p['id_pedido'] ?></strong></td>
                    <td><?= htmlspecialchars($p['cliente'] ?? 'Desconocido') ?></td>
                    <td>$<?= number_format($p['total'], 2) ?></td>
                    <td><span class="badge <?= $badge_class ?>"><?= $p['estado_pedido'] ?></span></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" style="color:#999; text-align:center; padding:20px;">Sin pedidos aún.</td></tr>
                <?php endif; ?>
            </table>
            <div style="margin-top:12px; text-align:right;">
                <a href="admin_pedidos.php" style="font-size:13px; color:#e74c3c; text-decoration:none;">
                    Ver todos los pedidos →
                </a>
            </div>
        </div>

        <!-- Inventario por categoría -->
        <div class="card">
            <h3>🗃️ Inventario por categoría</h3>
            <?php
            if ($por_categoria && $por_categoria->num_rows > 0):
                $cats = [];
                while ($c = $por_categoria->fetch_assoc()) $cats[] = $c;
                $max = $cats[0]['total'];
                foreach ($cats as $c):
                    $pct = $max > 0 ? round(($c['total'] / $max) * 100) : 0;
            ?>
            <div class="barra-wrap">
                <div class="barra-label">
                    <span><?= ucfirst(htmlspecialchars($c['categoria'])) ?></span>
                    <span><?= $c['total'] ?> producto<?= $c['total'] != 1 ? 's' : '' ?></span>
                </div>
                <div class="barra-bg">
                    <div class="barra-fill" style="width: <?= $pct ?>%;"></div>
                </div>
            </div>
            <?php endforeach; else: ?>
                <p style="color:#999; font-size:13px;">Sin productos en inventario.</p>
            <?php endif; ?>

            <div style="margin-top:20px; text-align:right;">
                <a href="admin_inventario.php" style="font-size:13px; color:#e74c3c; text-decoration:none;">
                    Ir al inventario →
                </a>
            </div>
        </div>

    </div>
</main>

</body>
</html>
