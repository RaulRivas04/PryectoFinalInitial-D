<?php
session_start();
include("../conexion.php");

// Comprobar si es admin
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Obtener todos los usuarios
$usuarios = $conn->query("
    SELECT id, nombre, email, rol, fecha_registro 
    FROM usuarios
    ORDER BY id DESC
");

include("../header.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="../../img/logo-web.png">

<title>Gestión de Usuarios - Admin</title>

<link rel="stylesheet" href="../../css/style.css">
<link rel="stylesheet" href="../../css/admin.css">

</head>
<body>

<main class="admin-usuarios">
    <h2>Gestión de usuarios</h2>

    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Fecha de registro</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($u = $usuarios->fetch_assoc()): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['nombre']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <span class="badge badge-rol"><?= htmlspecialchars($u['rol']) ?></span>
                </td>
                <td><?= date("d/m/Y", strtotime($u['fecha_registro'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>

    <a href="dashboard.php" class="btn">Volver al panel</a>
</main>

<?php include("../footer.php"); ?>

<script src="/initial-d/js/script.js"></script>
</body>
</html>
