<?php
// config/conexion.php
$host = "localhost";
$user = "root";          // Cambia si usas otro usuario
$password = "";          // Cambia si tienes contraseña en MySQL
$database = "carniceria"; // Asegúrate de que coincida con el nombre de tu BD

$conexion = mysqli_connect($host, $user, $password, $database);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8");
?>