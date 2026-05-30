<?php
session_start();
include("conexion.php");

// 1. Verificamos que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["status" => "error", "message" => "Debes iniciar sesión para comprar"]);
    exit;
}

// 2. Recibimos los datos del carrito que vienen desde JavaScript
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['productos'])) {
    echo json_encode(["status" => "error", "message" => "Datos inválidos"]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$total = $data['total'];
$productos = $data['productos'];

// 3. Guardamos el pedido principal
$sqlPedido = "INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)";
$stmt = $conexion->prepare($sqlPedido);
$stmt->bind_param("id", $usuario_id, $total);
$stmt->execute();
$pedido_id = $stmt->insert_id; // Obtenemos el ID del pedido recién creado

// 4. Guardamos cada producto del carrito en detalle_pedidos
$sqlDetalle = "INSERT INTO detalle_pedidos (pedido_id, producto_nombre, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
$stmtDetalle = $conexion->prepare($sqlDetalle);

foreach ($productos as $prod) {
    $cantidad = 1; // Por ahora asumimos 1 por producto
    $stmtDetalle->bind_param("isid", $pedido_id, $prod['nombre'], $cantidad, $prod['precio']);
    $stmtDetalle->execute();
}

echo json_encode(["status" => "success", "message" => "¡Pedido registrado con éxito!"]);
?>