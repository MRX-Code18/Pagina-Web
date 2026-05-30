<?php
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Estos nombres ('nombre', 'correo', etc.) deben ser IGUALES a los 'name' del formulario
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $curp = $_POST['curp'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nombre_completo, correo, curp_id, password_hash) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $correo, $curp, $password);

    if ($stmt->execute()) {
        echo "¡Registro exitoso! <a href='Tienda.php'>Volver al inicio</a>";
    } else {
        echo "Error al registrar: " . $conexion->error;
    }
}
?>