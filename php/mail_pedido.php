<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; 
include("conexion.php");

use Dompdf\Dompdf;
use Dompdf\Options;


/*
  ENVÍA EMAIL + PDF con todos los productos del pedido
 */
function enviarCorreoPedido($emailDestino, $nombreUsuario, $pedidoID, $producto, $total, $direccion, $metodoPago) {
    global $conn;

    // OBTENER PRODUCTOS REALES DEL PEDIDO
    $sqlProd = $conn->query("
        SELECT p.nombre, p.imagen, pp.cantidad, pp.subtotal 
        FROM pedidos_productos pp
        JOIN productos p ON p.id = pp.id_producto
        WHERE pp.id_pedido = $pedidoID
    ");

    $listaProductos = [];
    $tablaPDF = "";
    $htmlListaEmail = "";

    while ($row = $sqlProd->fetch_assoc()) {
        $listaProductos[] = $row;

        $tablaPDF .= "
            <tr>
                <td>{$row['nombre']}</td>
                <td style='text-align:center;'>{$row['cantidad']}</td>
                <td style='text-align:right;'>".number_format($row['subtotal'],2,",",".")." €</td>
            </tr>
        ";

        $htmlListaEmail .= "
            <li><strong>{$row['nombre']}</strong> — Cantidad: {$row['cantidad']} — 
            Subtotal: ".number_format($row['subtotal'],2,",",".")." €</li>
        ";
    }

    if (empty($tablaPDF)) {
        $tablaPDF = "<tr><td colspan='3'>No se encontraron productos.</td></tr>";
    }

    // GENERAR PDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);

    $htmlPDF = "
        <style>
            body { font-family: 'Arial'; }
            .header {
                text-align:center; padding:20px; background:black;
                color:#ffcf00; font-size:28px; font-weight:bold;
            }
            .box {
                background:#f5f5f5; padding:18px; margin-top:20px;
                border-radius:10px;
            }
            h2 { color:#222; margin-top:30px; }
            table {
                width:100%; border-collapse:collapse; margin-top:10px;
                font-size:15px;
            }
            table th, table td {
                padding:10px; border-bottom:1px solid #ccc;
            }
            .totales {
                margin-top:20px;
                background:white; padding:12px;
                border-radius:10px; border:1px solid #ddd;
                font-size:17px;
            }
            .footer {
                margin-top:40px; font-size:12px;
                text-align:center; color:#777;
            }
        </style>

        <div class='header'>INITIAL D MOTORS</div>

        <h2>Factura / Resumen del Pedido</h2>

        <div class='box'>
            <strong>Nº Pedido:</strong> $pedidoID<br>
            <strong>Cliente:</strong> $nombreUsuario<br>
            <strong>Método de pago:</strong> $metodoPago<br>
            <strong>Fecha:</strong> ".date('d/m/Y H:i')."
        </div>

        <h2>Productos adquiridos</h2>

        <table>
            <tr>
                <th style='text-align:left;'>Producto</th>
                <th style='text-align:center;'>Cantidad</th>
                <th style='text-align:right;'>Subtotal</th>
            </tr>

            $tablaPDF
        </table>

        <h2>Dirección de envío</h2>
        <div class='box'>
            $direccion
        </div>

        <div class='totales'>
            <strong>Total pagado:</strong> $total €
        </div>

        <div class='footer'>
            Documento generado automáticamente por Initial D Motors.<br>
            Gracias por confiar en nosotros.
        </div>
    ";

    $dompdf->loadHtml($htmlPDF);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Crear carpeta PDF si no existe
    $pdfFolder = __DIR__ . "/../pdf";
    if (!is_dir($pdfFolder)) mkdir($pdfFolder, 0777, true);

    $pdfFile = $pdfFolder . "/pedido_$pedidoID.pdf";
    file_put_contents($pdfFile, $dompdf->output());

    // EMAIL PARA EL USUARIO (con PDF adjunto)
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'initialdmotors@gmail.com';
        $mail->Password   = 'sbwq fblq incj yuhe';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';

        $mail->setFrom('initialdmotors@gmail.com', 'Initial D Motors');
        $mail->addAddress($emailDestino, $nombreUsuario);

        // Adjuntar factura PDF
        $mail->addAttachment($pdfFile);

        $mail->isHTML(true);
        $mail->Subject = "Tu pedido #$pedidoID ha sido procesado correctamente ✔";

        $mail->Body = "
            <div style='font-family:Arial; padding:20px'>
                <h2 style='color:#ffcf00'>¡Gracias por tu compra, $nombreUsuario! 🚗🔥</h2>

                <p>Tu pedido se ha procesado correctamente.</p>

                <h3>Productos comprados:</h3>
                <ul>
                    $htmlListaEmail
                </ul>

                <p><strong>Total pagado:</strong> $total €</p>

                <br>
                <a href='https://initial-d.lovestoblog.com/php/usuario/usuario_panel.php?seccion=pedidos'
                   style='background:#ffcf00;color:#000;padding:12px 20px;
                   font-weight:bold;border-radius:8px;text-decoration:none;'>
                   Ver mi pedido
                </a>

                <br><br>
                <p>Hemos adjuntado la factura en PDF.</p>

                <p>Gracias por confiar en <strong>Initial D Motors</strong>.</p>
            </div>
        ";

        $mail->send();

    } catch (Exception $e) {
        file_put_contents("mail_error_log.txt", "USUARIO: ".$mail->ErrorInfo."\n", FILE_APPEND);
    }

    // EMAIL PARA EL ADMIN
    try {
        $mailAdmin = new PHPMailer(true);

        $mailAdmin->isSMTP();
        $mailAdmin->Host       = 'smtp.gmail.com';
        $mailAdmin->SMTPAuth   = true;
        $mailAdmin->Username   = 'initialdmotors@gmail.com';
        $mailAdmin->Password   = 'sbwq fblq incj yuhe';
        $mailAdmin->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailAdmin->Port       = 587;
        $mailAdmin->CharSet    = 'UTF-8';

        $mailAdmin->setFrom('initialdmotors@gmail.com', 'Initial D – Sistema');
        $mailAdmin->addAddress('initialdmotors@gmail.com', 'ADMIN');

        $mailAdmin->isHTML(true);
        $mailAdmin->Subject = "📢 NUEVA COMPRA — Pedido #$pedidoID";

        $mailAdmin->Body = "
            <div style='font-family:Arial; padding:20px'>
                <h2 style='color:#ff0000;'>Nueva compra registrada 🛍</h2>

                <p><strong>Cliente:</strong> $nombreUsuario</p>
                <p><strong>Email:</strong> $emailDestino</p>
                <p><strong>Total pagado:</strong> $total €</p>
                <p><strong>Método de pago:</strong> $metodoPago</p>

                <h3>Productos:</h3>
                <ul>
                    $htmlListaEmail
                </ul>

                <p>Consulta el panel de administración para más detalles.</p>
            </div>
        ";

        $mailAdmin->send();

    } catch (Exception $e) {
        file_put_contents("mail_error_log.txt", "ADMIN: ".$mailAdmin->ErrorInfo."\n", FILE_APPEND);
    }

    return true;
}
