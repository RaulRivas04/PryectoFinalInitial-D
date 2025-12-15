<?php
session_start();
include("../conexion.php");

// Si no hay sesión = fuera
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id'];

// Solo procesa si viene por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nuevo_nombre = trim($_POST['nombre']);
    $nuevo_email  = trim($_POST['email']);
    $pass1        = trim($_POST['password']);
    $pass2        = trim($_POST['password2']);

    // VALIDACIONES
    if ($nuevo_nombre === "") {
        $_SESSION['mensaje'] = "El nombre no puede estar vacío.";
        $_SESSION['tipo_mensaje'] = "error";
        header("Location: usuario_panel.php?seccion=ajustes");
        exit();
    }

    // VALIDAR CONTRASEÑAS
    if ($pass1 !== "" || $pass2 !== "") {

        if ($pass1 !== $pass2) {
            $_SESSION['mensaje'] = "Las contraseñas no coinciden.";
            $_SESSION['tipo_mensaje'] = "error";
            header("Location: usuario_panel.php?seccion=ajustes");
            exit();
        }

        // Actualizar contraseña
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $conn->query("UPDATE usuarios SET password='$hash' WHERE id=$id_usuario");
    }

    // Actualizar nombre
    $conn->query("UPDATE usuarios SET nombre='$nuevo_nombre' WHERE id=$id_usuario");
    $_SESSION['nombre'] = $nuevo_nombre;

    // Actualizar email si no está vacío
    if ($nuevo_email !== "") {
        $conn->query("UPDATE usuarios SET email='$nuevo_email' WHERE id=$id_usuario");
    }

    $_SESSION['mensaje'] = "Datos actualizados correctamente.";
    $_SESSION['tipo_mensaje'] = "ok";

    header("Location: usuario_panel.php?seccion=ajustes");
    exit();
}

// Si alguien entra sin POST
header("Location: usuario_panel.php?seccion=ajustes");
exit();
