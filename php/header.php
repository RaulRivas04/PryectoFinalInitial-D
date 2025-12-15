<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos
include("conexion.php");

// OBTENER NOMBRE DEL USUARIO LOGUEADO
$nombreUsuarioHeader = null;

if (isset($_SESSION['id'])) {
    $idUser = $_SESSION['id'];
    $stmtH = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ? LIMIT 1");
    $stmtH->bind_param("i", $idUser);
    $stmtH->execute();
    $resultH = $stmtH->get_result();

    if ($resultH->num_rows > 0) {
        $nombreUsuarioHeader = $resultH->fetch_assoc()['nombre'];
    }
    $stmtH->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="./img/logo-web.png">
<title>Initial D</title>

<!-- Tipografía -->
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

<!-- Iconos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- CSS externo -->
<link rel="stylesheet" href="/INITIAL-D/css/style.css">
</head>
<body>

<header>
    <nav class="navbar">

        <!-- LOGO -->
        <div class="logo-container">
            <a href="/initial-d/index.php" class="logo-main">Initial D</a>

            <!-- USUARIO -->
            <?php if ($nombreUsuarioHeader): ?>
            <div class="user-dropdown">

                <!-- Visible -->
                <div class="user-trigger">
                    <i class="fas fa-user-circle"></i>
                    <span><?= htmlspecialchars($nombreUsuarioHeader) ?></span>
                    <i class="fas fa-caret-down caret-icon"></i>
                </div>

                <!-- Menú desplegable -->
                <div class="user-menu">

                    <a href="/initial-d/php/usuario/usuario_panel.php">
                        <i class="fas fa-user"></i> Mi cuenta
                    </a>

                    <a href="/initial-d/php/usuario/usuario_panel.php?seccion=pedidos">
                        <i class="fas fa-box"></i> Mis pedidos
                    </a>

                    <a href="/initial-d/php/logout.php" class="logout-option">
                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                    </a>

                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- BOTÓN CAMBIO DE TEMA -->
        <button id="theme-toggle" aria-label="Cambiar tema">
            <i class="fas fa-moon"></i>
        </button>

        <!-- MENÚ GENERAL (DERECHA) -->
        <ul class="nav-links">

            <li><a href="/initial-d/index.php"><i class="fas fa-home"></i><span class="link-text">Inicio</span></a></li>
            <li><a href="/initial-d/php/productos.php"><i class="fas fa-car"></i><span class="link-text">Productos</span></a></li>
            <li><a href="/initial-d/php/contacto.php"><i class="fas fa-user"></i><span class="link-text">Contacto</span></a></li>

            <?php if (isset($_SESSION['id'])): ?>

                <?php
                $id_usuario = $_SESSION['id'];
                $count_carrito = $conn->query("SELECT COUNT(*) AS c FROM carrito WHERE id_usuario=$id_usuario")->fetch_assoc()['c'];
                $count_fav     = $conn->query("SELECT COUNT(*) AS f FROM favoritos WHERE id_usuario=$id_usuario")->fetch_assoc()['f'];
                $count_res     = $conn->query("SELECT COUNT(*) AS r FROM reservas WHERE id_usuario=$id_usuario")->fetch_assoc()['r'];
                ?>

                <?php if ($_SESSION['rol'] === 'admin'): ?>

                    <li><a href="/initial-d/php/admin/dashboard.php"><i class="fas fa-user-shield"></i><span class="link-text">Admin</span></a></li>

                <?php else: ?>

                    <li>
                        <a href="/initial-d/php/usuario/usuario_panel.php?seccion=carrito">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="link-text">Carrito</span>
                            <span class="count"><?= $count_carrito ?></span>
                        </a>
                    </li>

                    <li>
                        <a href="/initial-d/php/usuario/usuario_panel.php?seccion=favoritos">
                            <i class="fas fa-heart"></i>
                            <span class="link-text">Favoritos</span>
                            <span class="count"><?= $count_fav ?></span>
                        </a>
                    </li>

                    <li>
                        <a href="/initial-d/php/usuario/usuario_panel.php?seccion=reservas">
                            <i class="fas fa-calendar-check"></i>
                            <span class="link-text">Reservas</span>
                            <span class="count"><?= $count_res ?></span>
                        </a>
                    </li>

                    <li>
                        <a href="/initial-d/php/usuario/usuario_panel.php?seccion=pedidos" class="show-text-desktop"><i class="fas fa-box"></i><span class="link-text">Pedidos</span></a>
                    </li>

                <?php endif; ?>

            <?php else: ?>

                <li><a href="/initial-d/php/login.php"><i class="fas fa-sign-in-alt"></i><span class="link-text">Iniciar sesión</span></a></li>
                <li><a href="/initial-d/php/registro.php"><i class="fas fa-user-plus"></i><span class="link-text">Registrarse</span></a></li>

            <?php endif; ?>

        </ul>

        <!-- BURGER MENU -->
        <div class="burger">
            <div></div>
            <div></div>
            <div></div>
        </div>

    </nav>
</header>
