<?php
use Dompdf\Dompdf;
use Dompdf\Options;

require __DIR__ . '/../vendor/autoload.php';
include("conexion.php");

/**
 * Genera FACTURA PDF profesional para pedidos
 * Compatible con productos múltiples
 */
function generarFacturaPDF($pedidoID, $total, $cliente, $direccion, $metodoPago)
{
    global $conn;

    try {

        /* ============================================================
           1) OBTENER PRODUCTOS DEL PEDIDO DESDE BD
        ============================================================ */
        $sql = $conn->query("
            SELECT p.nombre, pp.cantidad, pp.subtotal
            FROM pedidos_productos pp
            JOIN productos p ON p.id = pp.id_producto
            WHERE pp.id_pedido = $pedidoID
        ");

        $tablaProductos = "";

        while ($row = $sql->fetch_assoc()) {
            $tablaProductos .= "
                <tr>
                    <td>{$row['nombre']}</td>
                    <td style='text-align:center;'>{$row['cantidad']}</td>
                    <td style='text-align:right;'>".number_format($row['subtotal'],2,",",".")." €</td>
                </tr>
            ";
        }

        if ($tablaProductos === "") {
            $tablaProductos = "<tr><td colspan='3'>No se encontraron productos.</td></tr>";
        }


        /* ============================================================
           2) GENERAR PDF PROFESIONAL
        ============================================================ */
        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = "
        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 14px;
                color: #222;
            }
            h1 {
                background: black;
                color: #ffcf00;
                padding: 15px;
                text-align: center;
                border-radius: 6px;
            }
            h2 { margin-top: 25px; color: #111; }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            table th {
                background: #eee;
                padding: 10px;
                border-bottom: 2px solid #ccc;
            }
            table td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }
            .box {
                margin-top: 12px;
                padding: 12px;
                background: #f7f7f7;
                border-radius: 8px;
            }
            .total-final {
                margin-top: 20px;
                font-size: 18px;
                padding: 12px;
                background: #ffcf00;
                color: #000;
                font-weight: bold;
                text-align: right;
                border-radius: 8px;
            }
            .footer {
                margin-top: 40px;
                text-align: center;
                font-size: 12px;
                color: #555;
            }
        </style>

        <h1>Factura - Initial D Motors</h1>

        <h2>Información del pedido</h2>
        <div class='box'>
            <p><strong>Nº Pedido:</strong> $pedidoID</p>
            <p><strong>Cliente:</strong> $cliente</p>
            <p><strong>Dirección de envío:</strong> $direccion</p>
            <p><strong>Método de pago:</strong> $metodoPago</p>
            <p><strong>Fecha:</strong> ".date("d/m/Y H:i")."</p>
        </div>

        <h2>Productos adquiridos</h2>

        <table>
            <tr>
                <th style='text-align:left;'>Producto</th>
                <th style='text-align:center;'>Cantidad</th>
                <th style='text-align:right;'>Subtotal</th>
            </tr>
            $tablaProductos
        </table>

        <div class='total-final'>
            Total pagado: ".number_format($total,2,",",".")." €
        </div>

        <div class='footer'>
            Documento generado automáticamente por Initial D Motors.<br>
            Gracias por confiar en nosotros.
        </div>
        ";

        $dompdf->loadHtml($html);
        $dompdf->setPaper("A4", "portrait");
        $dompdf->render();

        return $dompdf->output();

    } catch (Exception $e) {
        return null;
    }
}
?>
