<?php
session_start(); // Iniciamos la sesión para poder destruirla
session_unset(); // Borramos todas las variables de sesión
session_destroy(); // Destruimos la sesión completamente
header("Location: Tienda.php"); // Redirigimos al usuario a la tienda
exit(); // Terminamos la ejecución
?>