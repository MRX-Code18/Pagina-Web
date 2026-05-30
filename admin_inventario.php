<?php
// --- INICIO DE SEGURIDAD (EL PORTERO) ---
session_start();
// Si el usuario no ha iniciado sesión, lo mandamos a la tienda
if (!isset($_SESSION['usuario'])) {
    header("Location: Tienda.php");
    exit();
}
// Incluimos la conexión para poder mostrar la tabla de inventario
include("conexion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Inventario</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 10px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { background: #27ae60; color: white; padding: 10px; border: none; width: 100%; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #2c3e50; color: white; }
    </style>
</head>
<body>

<div class="container">
    <a href="Tienda.php">← Volver a la Tienda</a>
    <h2>Registro de Inventario</h2>
    <input type="text" id="prod-nombre" placeholder="Nombre del Producto">
    <input type="number" id="prod-precio" placeholder="Precio de Compra">
    <select id="prod-cat">
        <option value="puerco">Puerco</option>
        <option value="pollo">Pollo</option>
        <option value="res">Res</option>
    </select>

    <select id="prod-prov">
        <option value="">Selecciona un proveedor</option>
        <?php
        // Traemos los proveedores desde la base de datos
        $res = $conexion->query("SELECT nombre FROM proveedores");
        while($p = $res->fetch_assoc()) {
            echo "<option value='{$p['nombre']}'>{$p['nombre']}</option>";
        }
        ?>
    </select>
    <button onclick="guardarProducto()">Registrar Producto</button>

    <hr style="margin: 30px 0;">

    <h3>Productos Registrados</h3>
    <table>
        <tr><th>Producto</th><th>Precio</th><th>Categoría</th><th>Proveedor</th></tr>
        <?php
        // --- VISUALIZACIÓN: Leemos de la base de datos ---
        $query = "SELECT * FROM inventario ORDER BY fecha_registro DESC";
        $resultado = $conexion->query($query);
        while($fila = $resultado->fetch_assoc()) {
            echo "<tr>
                    <td>{$fila['nombre_producto']}</td>
                    <td>\${$fila['precio_compra']}</td>
                    <td>{$fila['categoria']}</td>
                    <td>{$fila['proveedor']}</td>
                  </tr>";
        }
        ?>
    </table>
</div>

<script>
function guardarProducto() {
    const data = {
        nombre: document.getElementById('prod-nombre').value,
        precio: document.getElementById('prod-precio').value,
        categoria: document.getElementById('prod-cat').value,
        proveedor: document.getElementById('prod-prov').value // Esto ahora toma el valor seleccionado del select
    };

    fetch('gestionar_inventario.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if(data.status === "success") location.reload();
    });
}
</script>
</body>
</html>