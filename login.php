<?php
session_start(); // Iniciamos sesión para "recordar" al usuario
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    // Buscamos al usuario por su correo
    $sql = "SELECT id_usuario, nombre_completo, password_hash FROM usuarios WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($usuario = $resultado->fetch_assoc()) {
        // password_verify compara la contraseña escrita con el hash de la base de datos
        if (password_verify($password, $usuario['password_hash'])) {
            // Guardamos el nombre en sesión para mostrarlo en el header
            $_SESSION['usuario'] = $usuario['nombre_completo']; 
            
            // --- NUEVA LÍNEA IMPORTANTE ---
            // Guardamos el ID del usuario para poder asociar las compras futuras a este usuario
            $_SESSION['usuario_id'] = $usuario['id_usuario']; 
            
            echo "¡Bienvenido, " . $usuario['nombre_completo'] . "! <br> <a href='Tienda.php'>Ir a la tienda</a>";
        } else {
            echo "Contraseña incorrecta. <a href='Tienda.php'>Intentar de nuevo</a>";
        }
    } else {
        echo "Usuario no registrado. <a href='Tienda.php'>Registrarse</a>";
    }
}
?>