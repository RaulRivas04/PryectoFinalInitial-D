<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();
require "../vendor/autoload.php"; 
include("conexion.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* VALIDAR SESIÓN */
if (!isset($_SESSION['email'])) {
    echo json_encode(["ok" => false, "msg" => "Debes iniciar sesión."]);
    exit();
}

$emailDestino = $_SESSION['email'];

// VALIDAR TELÉFONO
$telefono = trim($_POST['telefono'] ?? '');

if ($telefono === "") {
    echo json_encode(["ok" => false, "msg" => "El teléfono es obligatorio."]);
    exit();
}

if (!ctype_digit($telefono)) {
    echo json_encode(["ok" => false, "msg" => "El teléfono solo debe contener números."]);
    exit();
}

if (strlen($telefono) !== 9) {
    echo json_encode(["ok" => false, "msg" => "El teléfono debe tener 9 dígitos."]);
    exit();
}

// GENERAR CÓDIGO OTP Y CADUCIDAD (90 segundos)
$codigo = rand(100000, 999999);

$_SESSION['bizum_otp']        = $codigo;
$_SESSION['bizum_otp_expira'] = time() + 90;
$_SESSION['bizum_otp_tel']    = $telefono;

/* ENVIAR CORREO CON CÓDIGO */
try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = "smtp.gmail.com";
    $mail->SMTPAuth   = true;
    $mail->Username   = 'initialdmotors@gmail.com';
    $mail->Password   = 'sbwq fblq incj yuhe';
    $mail->SMTPSecure = "tls";
    $mail->Port       = 587;
    $mail->CharSet    = "UTF-8";

    $mail->setFrom("initialdmotors@gmail.com", "Initial D Motors");
    $mail->addAddress($emailDestino);

    $mail->Subject = "Código Bizum - Initial D Motors";
    $mail->Body    = "
        Tu código Bizum es: $codigo

        ⚠ Caduca en 90 segundos.
    ";

    $mail->send();

    echo json_encode([
        "ok"  => true,
        "msg" => "Código enviado correctamente al correo."
    ]);

} catch (Exception $e) {

    echo json_encode([
        "ok"  => false,
        "msg" => "Error al enviar el correo: " . $mail->ErrorInfo
    ]);
}

exit();
