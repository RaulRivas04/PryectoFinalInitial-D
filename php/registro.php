<?php
session_start();
include("conexion.php");

$mensaje = '';
$tipo_mensaje = 'error';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = $conn->real_escape_string($_POST['nombre']);
    $email = $conn->real_escape_string($_POST['email']);
    $password_plain = $_POST['password'];
    $password_repeat = $_POST['password2'];

    // Validación (extra seguridad)
    if ($password_plain !== $password_repeat) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = 'error';
    } else {

        $password = hash('sha256', $password_plain);

        // Comprobar si email existe
        $check = $conn->query("SELECT id FROM usuarios WHERE email='$email'");
        if($check->num_rows > 0){
            $mensaje = "Este email ya está registrado.";
            $tipo_mensaje = 'error';
        } else {
            $conn->query("INSERT INTO usuarios (nombre, email, password) VALUES ('$nombre','$email','$password')");
            $mensaje = "Registro exitoso. Ahora puedes iniciar sesión.";
            $tipo_mensaje = 'success';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="../img/logo-web.png">

<title>Registro - Initial D</title>
<link rel="stylesheet" href="/initial-d/css/style.css">
<link rel="stylesheet" href="/initial-d/css/acesso.css">
</head>
<body>

<?php include("header.php"); ?>

<main class="registro-form">
    <h2>Registrarse</h2>
    <?php if(!empty($mensaje)) echo "<p class='$tipo_mensaje'>$mensaje</p>"; ?>

    <form method="POST" action="" id="formRegistro">

        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">

        <label for="email">Email:</label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

        <label for="password">Contraseña:</label>

        <!-- CONTRASEÑA -->
        <div class="password-wrapper">
            <input type="password" name="password" id="password" required>

            <span class="toggle-pass" id="togglePass1">
                <svg id="eyeOpen1" viewBox="0 0 24 24">
                    <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z"/>
                </svg>
                <svg id="eyeClosed1" viewBox="0 0 24 24" style="display:none;">
                    <path d="M12 6c4.97 0 9.25 3.11 11 7-1.06 2.38-3 4.5-5.55 5.74L20 21l-1.41 1.41-3.1-3.1A11.52 11.52 0 0112 20c-4.97 0-9.25-3.11-11-7a11.79 11.79 0 013.55-4.74L2 4l1.41-1.41 3.1 3.1A11.52 11.52 0 0112 6zm0 2a5 5 0 00-5 5c0 .87.22 1.69.6 2.4l6.8-6.8A4.94 4.94 0 0012 8zm5 5a5 5 0 00-5-5c-.87 0-1.69.22-2.4.6l6.8 6.8c.38-.71.6-1.53.6-2.4z"/>
                </svg>
            </span>
        </div>

        <!-- INDICADOR DE FUERZA -->
        <div class="strength-container">
            <div id="strengthBar"></div>
            <p id="strengthText"></p>
        </div>

        <!-- REPETIR CONTRASEÑA -->
        <label for="password2">Repetir contraseña:</label>

        <div class="password-wrapper">
            <input type="password" name="password2" id="password2" required>

            <span class="toggle-pass" id="togglePass2">
                <svg id="eyeOpen2" viewBox="0 0 24 24">
                    <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z"/>
                </svg>
                <svg id="eyeClosed2" viewBox="0 0 24 24" style="display:none;">
                    <path d="M12 6c4.97 0 9.25 3.11 11 7-1.06 2.38-3 4.5-5.55 5.74L20 21l-1.41 1.41-3.1-3.1A11.52 11.52 0 0112 20c-4.97 0-9.25-3.11-11-7a11.79 11.79 0 013.55-4.74L2 4l1.41-1.41 3.1 3.1A11.52 11.52 0 0112 6zm0 2a5 5 0 00-5 5c0 .87.22 1.69.6 2.4l6.8-6.8A4.94 4.94 0 0012 8zm5 5a5 5 0 00-5-5c-.87 0-1.69.22-2.4.6l6.8 6.8c.38-.71.6-1.53.6-2.4z"/>
                </svg>
            </span>
        </div>

        <p id="passError" class="pass-error">Las contraseñas no coinciden.</p>

        <button type="submit">Registrarse</button>
    </form>

    <p class="login-link">¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>
</main>

<?php include("footer.php"); ?>

<script src="/initial-d/js/script.js"></script>

</body>
</html>
