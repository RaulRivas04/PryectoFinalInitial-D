<?php
session_start();
include("conexion.php");

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = hash('sha256', $_POST['password']);

    $sql = "SELECT * FROM usuarios WHERE email='$email' AND password='$password'";
    $resultado = $conn->query($sql);

    if ($resultado->num_rows == 1) {
        $usuario = $resultado->fetch_assoc();
        $_SESSION['id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['rol'] = $usuario['rol'];

        // Redirigir según rol
        if($usuario['rol'] == 'admin'){
            header("Location: admin/dashboard.php");
        } else {
            header("Location: ../index.php");
        }
        exit();
    } else {
        $mensaje = "Email o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="../img/logo-web.png">

<title>Login - Initial D</title>
<link rel="stylesheet" href="/initial-d/css/style.css">
<link rel="stylesheet" href="/initial-d/css/acesso.css">

</head>
<body>

<?php include("header.php"); ?>

<main class="login-form">
    <h2>Iniciar sesión</h2>
    <?php if(!empty($mensaje)) echo "<p class='error'>$mensaje</p>"; ?>
    
    <form method="POST" action="">
        <label for="email">Email:</label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

        <label for="password">Contraseña:</label>

        <div class="password-wrapper">
            <input type="password" name="password" id="password" required>

            <!-- ICONO SVG -->
            <span class="toggle-pass" id="togglePass">
                <!-- Ojo abierto -->
                <svg id="eyeOpen" viewBox="0 0 24 24">
                    <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z"/>
                </svg>

                <!-- Ojo cerrado -->
                <svg id="eyeClosed" viewBox="0 0 24 24" style="display:none;">
                    <path d="M12 6c4.97 0 9.25 3.11 11 7-1.06 2.38-3 4.5-5.55 5.74L20 21l-1.41 1.41-3.1-3.1A11.52 11.52 0 0112 20c-4.97 0-9.25-3.11-11-7a11.79 11.79 0 013.55-4.74L2 4l1.41-1.41 3.1 3.1A11.52 11.52 0 0112 6zm0 2a5 5 0 00-5 5c0 .87.22 1.69.6 2.4l6.8-6.8A4.94 4.94 0 0012 8zm5 5a5 5 0 00-5-5c-.87 0-1.69.22-2.4.6l6.8 6.8c.38-.71.6-1.53.6-2.4z"/>
                </svg>
            </span>
        </div>

        <button type="submit">Entrar</button>
    </form>

    <p class="registro">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>

    <a href="login_google.php" class="google-circle-btn">
        <img class="google-circle-icon" src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google">
    </a>

</main>

<?php include("footer.php"); ?>


<script src="/initial-d/js/script.js"></script>


</body>
</html>