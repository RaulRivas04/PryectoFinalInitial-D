<?php
session_start();
include("../conexion.php");

// PHPMailer (correo)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';


// FUNCIÓN: Enviar correo con estado + lista de productos
function enviarCorreoEstado($conn, $email, $nombre, $pedidoID, $estadoNuevo){

    // Obtener productos del pedido
    $sqlProd = $conn->query("
        SELECT p.nombre, pp.cantidad, pp.subtotal
        FROM pedidos_productos pp
        JOIN productos p ON p.id = pp.id_producto
        WHERE pp.id_pedido = $pedidoID
    ");

    $listaHTML = "";

    while($row = $sqlProd->fetch_assoc()){
        $listaHTML .= "
            <li>
                <strong>{$row['nombre']}</strong> — 
                Cantidad: {$row['cantidad']} — 
                Subtotal: ".number_format($row['subtotal'], 2, ',', '.')." €
            </li>";
    }

    if ($listaHTML === "") {
        $listaHTML = "<li>No se encontraron productos.</li>";
    }

    // Mensajes según estado
    switch($estadoNuevo){
        case "pendiente":
            $titulo = "Tu pedido está pendiente 🕒";
            $mensaje = "Hemos recibido tu pedido y está en cola de procesamiento.";
        break;

        case "confirmado":
            $titulo = "Tu pedido ha sido confirmado ✔";
            $mensaje = "Hemos verificado tu pago y estamos preparando tu compra.";
        break;

        case "enviado":
            $titulo = "Tu pedido ha sido enviado 🚚💨";
            $mensaje = "Tu paquete está en camino hacia tu dirección.";
        break;

        case "completado":
            $titulo = "Tu pedido ha sido completado 🎉";
            $mensaje = "Tu pedido ha sido entregado correctamente. Muchas gracias.";
        break;

        default:
            $titulo = "Actualización del pedido";
            $mensaje = "El estado de tu pedido ha cambiado.";
        break;
    }

    // Enviar el correo
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'initialdmotors@gmail.com';
        $mail->Password   = 'sbwq fblq incj yuhe';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->CharSet = "UTF-8";
        $mail->Encoding = "base64";

        $mail->setFrom('initialdmotors@gmail.com', 'Initial D Motors');
        $mail->addAddress($email, $nombre);

        $mail->isHTML(true);
        $mail->Subject = "$titulo - Pedido #$pedidoID";

        $mail->Body = "
            <div style='font-family:Arial; padding:20px; font-size:15px;'>
                <h2 style='color:#ffcf00;'>$titulo</h2>

                <p>Hola <strong>$nombre</strong>,</p>
                <p>$mensaje</p>

                <h3>Productos de tu pedido:</h3>
                <ul>
                    $listaHTML
                </ul>

                <div style='background:#f5f5f5; padding:12px; border-radius:8px;
                            text-align:center; font-size:18px; font-weight:bold;'>
                    Estado actual: $estadoNuevo
                </div>

                <br>
                <a href='https://initial-d.lovestoblog.com/php/usuario/usuario_panel.php?seccion=pedidos'
                   style='background:#ffcf00; color:#000; padding:12px 20px;
                   font-weight:bold; border-radius:8px; text-decoration:none;'>
                   Ver mi pedido
                </a>

                <br><br>
                <p>Gracias por confiar en <strong>Initial D Motors</strong>.</p>
            </div>
        ";

        $mail->send();

    } catch (Exception $e) {
        file_put_contents("mail_estado_error.txt", "ERROR: ".$mail->ErrorInfo."\n", FILE_APPEND);
    }
}


//  VERIFICAR ADMIN
if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin'){
    header("Location: ../login.php");
    exit();
}


// LISTA PEDIDOS
$pedidos = $conn->query("
    SELECT p.id, u.nombre AS usuario, p.fecha, p.total, p.estado
    FROM pedidos p
    JOIN usuarios u ON p.id_usuario = u.id
    ORDER BY p.fecha DESC
");


//  CAMBIO DE ESTADO
if(isset($_POST['cambiar_estado'])){
    $id_pedido = intval($_POST['id_pedido']);
    $nuevo_estado = $_POST['estado'];

    // Obtener email del usuario
    $info = $conn->query("
        SELECT u.email, u.nombre
        FROM pedidos p
        JOIN usuarios u ON u.id = p.id_usuario
        WHERE p.id = $id_pedido
        LIMIT 1
    ")->fetch_assoc();

    $email = $info['email'];
    $nombre = $info['nombre'];

    // Actualizar estado
    $conn->query("UPDATE pedidos SET estado='$nuevo_estado' WHERE id=$id_pedido");

    // Enviar correo personalizado con lista de productos
    enviarCorreoEstado($conn, $email, $nombre, $id_pedido, $nuevo_estado);

    header("Location: pedidos_admin.php");
    exit();
}


include("../header.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../img/logo-web.png">

    <title>Gestión de Pedidos - Admin</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/admin.css">
</head>
<body>

<main class="admin-pedidos">
    <h2>Gestión de Pedidos</h2>

    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>
        </thead>

        <tbody>
        <?php while($row = $pedidos->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['usuario']; ?></td>
            <td><?php echo $row['fecha']; ?></td>
            <td><?php echo $row['total']; ?> €</td>
            <td><?php echo $row['estado']; ?></td>

            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id_pedido" value="<?php echo $row['id']; ?>">

                    <select name="estado">
                        <option value="pendiente"   <?php if($row['estado']=='pendiente') echo 'selected'; ?>>Pendiente</option>
                        <option value="confirmado"  <?php if($row['estado']=='confirmado') echo 'selected'; ?>>Confirmado</option>
                        <option value="enviado"     <?php if($row['estado']=='enviado') echo 'selected'; ?>>Enviado</option>
                        <option value="completado"  <?php if($row['estado']=='completado') echo 'selected'; ?>>Completado</option>
                    </select>

                    <button type="submit" name="cambiar_estado">Actualizar</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>

    </table>
    </div>

    <a href="dashboard.php" class="btn">Volver al Panel</a>
</main>

<?php include("../footer.php"); ?>

<script src="/initial-d/js/script.js"></script>
</body>
</html>
