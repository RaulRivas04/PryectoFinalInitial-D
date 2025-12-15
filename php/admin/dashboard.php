<?php
session_start();
include("../conexion.php");

// Comprobar si es admin
if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin'){
    header("Location: ../login.php");
    exit();
}

// Consultas para estadísticas simples
$result_productos = $conn->query("SELECT COUNT(*) AS total FROM productos");
$total_productos = $result_productos->fetch_assoc()['total'];

$result_pedidos = $conn->query("SELECT COUNT(*) AS total FROM pedidos");
$total_pedidos = $result_pedidos->fetch_assoc()['total'];

$result_usuarios = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
$total_usuarios = $result_usuarios->fetch_assoc()['total'];

include("../header.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../img/logo-web.png">

    <title>Dashboard Admin - Initial D</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/admin.css">
</head>
<body>

<main class="admin-dashboard">
    <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?> (Administrador)</h2>

    <section class="stats">
        <div class="stat">
            <h3>Total de productos</h3>
            <p><?php echo $total_productos; ?></p>
        </div>
        <div class="stat">
            <h3>Total de pedidos</h3>
            <p><?php echo $total_pedidos; ?></p>
        </div>
        <div class="stat">
            <h3>Total de usuarios</h3>
            <p><?php echo $total_usuarios; ?></p>
        </div>
    </section>

    <section class="admin-actions">
        <a href="productos_admin.php" class="btn">Gestionar productos</a>
        <a href="pedidos_admin.php" class="btn">Ver pedidos</a>
        <a href="usuarios_admin.php" class="btn">Gestionar usuarios</a>
        <a href="../logout.php" class="btn">Cerrar sesión</a>
    </section>
</main>

<?php include("../footer.php"); ?>

<script src="/initial-d/js/script.js"></script>
</body>
</html>