<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['id'])) {
    die("Debes iniciar sesión para realizar un pedido.");
}

$id_usuario = $_SESSION['id'];

// OBTENER PRODUCTOS DEL CARRITO
$sql = $conn->prepare("
    SELECT c.id_producto, c.cantidad, p.precio 
    FROM carrito c 
    INNER JOIN productos p ON c.id_producto = p.id
    WHERE c.id_usuario = ?
");
$sql->bind_param("i", $id_usuario);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows == 0) {
    die("Tu carrito está vacío.");
}

// CALCULAR TOTAL
$total = 0;
$items = [];

while ($row = $result->fetch_assoc()) {
    $subtotal = $row['precio'] * $row['cantidad'];
    $total += $subtotal;

    $items[] = [
        'id_producto' => $row['id_producto'],
        'cantidad' => $row['cantidad'],
        'subtotal' => $subtotal
    ];
}

// CREAR PEDIDO
$sqlPedido = $conn->prepare("
    INSERT INTO pedidos (id_usuario, total, estado)
    VALUES (?, ?, 'pendiente')
");
$sqlPedido->bind_param("id", $id_usuario, $total);
$sqlPedido->execute();

$id_pedido = $conn->insert_id;

// INSERTAR PRODUCTOS Y ACTUALIZAR VENTAS
$sqlDetalle = $conn->prepare("
    INSERT INTO pedidos_productos (id_pedido, id_producto, cantidad, subtotal)
    VALUES (?, ?, ?, ?)
");

foreach ($items as $item) {

    // Grabar productos del pedido
    $sqlDetalle->bind_param(
        "iiid",
        $id_pedido,
        $item['id_producto'],
        $item['cantidad'],
        $item['subtotal']
    );
    $sqlDetalle->execute();

    // Sumar ventas al producto
    $conn->query("
        UPDATE productos 
        SET ventas = ventas + {$item['cantidad']}
        WHERE id = {$item['id_producto']}
    ");

    // Activar top ventas automáticamente (>= 5 ventas)
    $conn->query("
        UPDATE productos
        SET top_ventas = 1
        WHERE id = {$item['id_producto']} AND ventas >= 5
    ");
}

// VACIAR CARRITO
$conn->query("DELETE FROM carrito WHERE id_usuario = $id_usuario");

echo "Pedido realizado correctamente. Número de pedido: " . $id_pedido;
?>
