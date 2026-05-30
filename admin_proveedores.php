<?php
session_start();
include("conexion.php");
if (!isset($_SESSION['usuario'])) { header("Location: Tienda.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Proveedores</title>
</head>
<body>
    <h2>Registrar Proveedor</h2>
    <form action="guardar_proveedor.php" method="POST">
        <input type="text" name="nombre" placeholder="Nombre del Proveedor" required>
        <input type="text" name="telefono" placeholder="Teléfono">
        <button type="submit">Guardar</button>
    </form>
</body>
</html>