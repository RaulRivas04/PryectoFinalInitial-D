<?php
require_once '../vendor/autoload.php';
include("conexion.php");
session_start();

$client = new Google_Client();
$client->setClientId("11683329459-bbqhtq7ih0m6is2ifdnojn7t2lsatfd1.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("https://initial-d.lovestoblog.com/php/login_google.php");

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
$client->setAccessToken($token);

$google_oauth = new Google_Service_Oauth2($client);
$userInfo = $google_oauth->userinfo->get();

$email = $conn->real_escape_string($userInfo->email);
$nombre = $conn->real_escape_string($userInfo->name);
$google_id = $conn->real_escape_string($userInfo->id);

// Ver si existe el usuario
$sql = "SELECT * FROM usuarios WHERE email='$email'";
$resultado = $conn->query($sql);

if ($resultado->num_rows == 0) {
    // Crear usuario automático
    $sql_crear = "INSERT INTO usuarios (nombre, email, password, rol, fecha_registro, google_id)
                  VALUES ('$nombre', '$email', '', 'usuario', NOW(), '$google_id')";
    $conn->query($sql_crear);
}

// Obtener usuario actualizado
$user = $conn->query("SELECT * FROM usuarios WHERE email='$email'")->fetch_assoc();

// Crear sesión
$_SESSION['id'] = $user['id'];
$_SESSION['nombre'] = $user['nombre'];
$_SESSION['rol'] = $user['rol'];

// Redirigir según rol
if ($user['rol'] == "admin") {
    header("Location: admin/dashboard.php");
} else {
    header("Location: ../index.php");
}
exit();
