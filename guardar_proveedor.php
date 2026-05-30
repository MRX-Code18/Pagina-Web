<?php
// --- SEGURIDAD ---
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: Tienda.php");
    exit();
}

include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validación de campos obligatorios
    if (empty(trim($_POST['nombre']))) {
        header("Location: admin_proveedores.php?status=error&msg=El nombre es obligatorio");
        exit();
    }

    $nombre    = trim($_POST['nombre']);
    $telefono  = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    $stmt = $conexion->prepare(
        "INSERT INTO proveedores (nombre, telefono, direccion) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("sss", $nombre, $telefono, $direccion);

    if ($stmt->execute()) {
        header("Location: admin_proveedores.php?status=success&msg=Proveedor registrado correctamente");
    } else {
        header("Location: admin_proveedores.php?status=error&msg=No se pudo registrar: " . $conexion->error);
    }

    $stmt->close();
} else {
    // Si alguien entra directo a este archivo sin el formulario, lo mandamos de regreso
    header("Location: admin_proveedores.php");
}

exit();
?>
