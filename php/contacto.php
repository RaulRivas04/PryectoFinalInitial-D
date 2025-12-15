<?php
// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// Conexión a la base de datos
include("conexion.php");

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Variables iniciales
$nombre = "";
$email = "";
$mensaje = "";
$producto_interes = "";

// Cargar producto de sesión si existe
if (isset($_SESSION['producto_interes'])) {
    $producto_interes = htmlspecialchars($_SESSION['producto_interes']);
    unset($_SESSION['producto_interes']);
}

if (isset($_POST['producto_interes'])) {
    $producto_interes = htmlspecialchars($_POST['producto_interes']);
}

// Procesar formulario
$errores = [];
$exito = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si solo viene producto_interes, no procesar, solo mostrar el formulario
    if (isset($_POST['producto_interes']) && !isset($_POST['enviar'])) {
        $producto_interes = htmlspecialchars($_POST['producto_interes']);
    }
    elseif (isset($_POST['enviar'])) {
        // Formulario HTML completo
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');

        if ($nombre === '') $errores[] = "El nombre es obligatorio.";
        if ($email === '') $errores[] = "El correo es obligatorio.";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "El correo no es válido.";
        if ($mensaje === '') $errores[] = "El mensaje es obligatorio.";
        elseif (strlen($mensaje) > 2000) $errores[] = "El mensaje es demasiado largo (máx. 2000 caracteres).";

        if (empty($errores)) {
            // Guardar en BD
            $stmt = $conn->prepare("INSERT INTO mensajes_contacto (nombre, email, mensaje, producto, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $nombre, $email, $mensaje, $producto_interes);
            $stmt->execute();
            $stmt->close();

            // Enviar correo
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'initialdmotors@gmail.com';
                $mail->Password = 'sbwq fblq incj yuhe';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('initialdmotors@gmail.com', 'Initial D Motors');
                $mail->addAddress('initialdmotors@gmail.com');

                $mail->isHTML(true);
                $mail->Subject = 'Nuevo mensaje de contacto';
                $mail->Body = "<strong>Nombre:</strong> {$nombre}<br>
                               <strong>Email:</strong> {$email}<br>
                               <strong>Producto de interés:</strong> {$producto_interes}<br>
                               <strong>Mensaje:</strong> {$mensaje}";

                $mail->send();
                $exito = "¡Tu mensaje ha sido enviado correctamente! Nos pondremos en contacto contigo pronto.";
                $nombre = $email = $mensaje = "";
            } catch (Exception $e) {
                $errores[] = "No se pudo enviar el correo. Error: {$mail->ErrorInfo}";
            }
        }
    } else {
        // AJAX request
        header('Content-Type: application/json');
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');
        $producto_interes = trim($_POST['producto_interes'] ?? '');

        $errores = [];

        if (empty($nombre)) $errores[] = "El nombre es obligatorio.";
        if (empty($email)) $errores[] = "El correo es obligatorio.";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "El correo no es válido.";
        if (empty($mensaje)) $errores[] = "El mensaje es obligatorio.";

        if (empty($errores)) {
            // Guardar en BD
            $stmt = $conn->prepare("INSERT INTO mensajes_contacto (nombre, email, mensaje, producto, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $nombre, $email, $mensaje, $producto_interes);
            $stmt->execute();
            $stmt->close();

            // Enviar correo
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'initialdmotors@gmail.com';
                $mail->Password = 'sbwq fblq incj yuhe';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('initialdmotors@gmail.com', 'Initial D Motors');
                $mail->addAddress('initialdmotors@gmail.com');

                $mail->isHTML(true);
                $mail->Subject = 'Nuevo mensaje de contacto desde la web';
                $mail->Body = "<strong>Nombre:</strong> {$nombre}<br>
                               <strong>Email:</strong> {$email}<br>" .
                               (!empty($producto_interes) ? "<strong>Producto de interés:</strong> {$producto_interes}<br>" : "") .
                               "<strong>Mensaje:</strong> {$mensaje}";

                $mail->send();
                echo json_encode(['status' => 'success', 'message' => '¡Tu mensaje ha sido enviado correctamente! Nos pondremos en contacto contigo pronto.']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo enviar el correo. Inténtalo de nuevo.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => implode(' ', $errores)]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="../img/logo-web.png">

<title>Contacto - Initial D</title>

<!-- CSS -->
<link rel="stylesheet" href="/initial-d/css/style.css">
<link rel="stylesheet" href="/initial-d/css/acesso.css">
</head>
<body>

<?php include("header.php"); ?>

<main class="contacto" style="padding: 80px 20px;">
    <h2>Contacto</h2>
    <p>¿Tienes alguna duda o quieres más información? Rellena el formulario y te responderemos lo antes posible.</p>

    <?php 
    if (!empty($errores)) {
        echo "<p style='color:red; text-align:center;'>" . implode("<br>", $errores) . "</p>";
    }
    if ($exito) {
        echo "<p style='color:green; text-align:center;'>$exito</p>";
    }
    ?>

    <form method="POST">
        <input type="text" name="nombre" placeholder="Tu nombre" required value="<?php echo htmlspecialchars($nombre); ?>">
        <input type="email" name="email" placeholder="Tu correo electrónico" required value="<?php echo htmlspecialchars($email); ?>">
        <textarea name="mensaje" placeholder="Tu mensaje..." required><?php echo htmlspecialchars($mensaje); ?></textarea>

        <?php if ($producto_interes): ?>
            <input type="hidden" name="producto_interes" value="<?php echo $producto_interes; ?>">
            <p><strong>Producto de interés:</strong> <?php echo $producto_interes; ?></p>
        <?php endif; ?>

        <button type="submit" name="enviar" class="btn">Enviar mensaje</button>
    </form>
</main>

<?php include("footer.php"); ?>

<script src="/initial-d/js/script.js"></script>
</body>
</html>