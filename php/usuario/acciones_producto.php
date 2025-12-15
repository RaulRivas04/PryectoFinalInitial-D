<?php
session_start();
include("../conexion.php");

// Verificar sesión
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id'];

// Acción y producto
$id_producto = $_POST['id_producto'] ?? null;
$accion      = $_POST['accion'] ?? $_GET['accion'] ?? '';

/* FUNCIÓN: VOLVER A LA PÁGINA CORRECTA */
function redirigir_anterior($mensaje = "Acción realizada correctamente.", $pagina = null) {

    // Si viene del carrito
    if (isset($_GET['volver']) && $_GET['volver'] === "carrito") {
        $referer = "../usuario/usuario_panel.php?seccion=carrito";
    } else {
        $referer = $pagina ?? ($_SERVER['HTTP_REFERER'] ?? '../../productos.php');
    }

    $_SESSION['mensaje'] = $mensaje;

    header("Location: $referer");
    exit();
}

/* SWITCH DE ACCIONES */
switch ($accion) {


/* COMPRAR (1 unidad) */
case 'comprar':

    if ($id_producto) {

        $res = $conn->query("SELECT stock FROM productos WHERE id=$id_producto");
        $producto = $res->fetch_assoc();

        if ($producto && $producto['stock'] > 0) {

            $conn->query("UPDATE productos SET stock = stock - 1 WHERE id=$id_producto");

            if ($producto['stock'] - 1 <= 0) {
                $conn->query("UPDATE productos SET estado='vendido' WHERE id=$id_producto");
            }

            $_SESSION['mensaje'] = "Compra realizada correctamente.";
            $_SESSION['tipo_mensaje'] = "ok";

        } else {
            $_SESSION['mensaje'] = "Este producto está agotado.";
            $_SESSION['tipo_mensaje'] = "error";
        }
    }

    redirigir_anterior($_SESSION['mensaje']);
    break;



/* AÑADIR AL CARRITO */
case 'carrito':

    if ($id_producto) {

        $check = $conn->query("SELECT id FROM carrito WHERE id_usuario=$id_usuario AND id_producto=$id_producto");

        if ($check->num_rows > 0) {
            $_SESSION['mensaje'] = "Este producto ya está en tu carrito.";
            $_SESSION['tipo_mensaje'] = "error";

        } else {

            // Verificar stock antes de añadir
            $res = $conn->query("SELECT stock FROM productos WHERE id=$id_producto");
            $producto = $res->fetch_assoc();

            if ($producto['stock'] <= 0) {
                $_SESSION['mensaje'] = "No hay stock disponible.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $conn->query("INSERT INTO carrito (id_usuario, id_producto, cantidad)
                              VALUES ($id_usuario, $id_producto, 1)");
                $_SESSION['mensaje'] = "Producto añadido al carrito.";
                $_SESSION['tipo_mensaje'] = "ok";
            }
        }
    }

    redirigir_anterior($_SESSION['mensaje']);
    break;



/* SUMAR CANTIDAD — NO PASA DEL STOCK */
case 'sumar':

    $id_carrito = intval($_GET['id']);

    // Obtener cantidad actual y stock máximo
    $sql = "SELECT c.cantidad, p.stock 
            FROM carrito c
            JOIN productos p ON c.id_producto = p.id
            WHERE c.id=$id_carrito AND c.id_usuario=$id_usuario";

    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    if ($row) {

        $cantidad_actual = $row['cantidad'];
        $stock_maximo    = $row['stock'];

        if ($cantidad_actual < $stock_maximo) {

            $conn->query("UPDATE carrito 
                          SET cantidad = cantidad + 1 
                          WHERE id=$id_carrito AND id_usuario=$id_usuario");

            $_SESSION['mensaje'] = "Cantidad actualizada.";
            $_SESSION['tipo_mensaje'] = "ok";

        } else {

            $_SESSION['mensaje'] = "No puedes añadir más unidades. Stock máximo alcanzado.";
            $_SESSION['tipo_mensaje'] = "error";
        }
    }

    redirigir_anterior($_SESSION['mensaje']);
    break;



/* RESTAR — NUNCA MENOS DE 1 */
case 'restar':

    $id_carrito = intval($_GET['id']);

    $res = $conn->query("SELECT cantidad FROM carrito WHERE id=$id_carrito AND id_usuario=$id_usuario");
    $row = $res->fetch_assoc();

    if ($row && $row['cantidad'] > 1) {

        $conn->query("UPDATE carrito SET cantidad = cantidad - 1 
                      WHERE id=$id_carrito AND id_usuario=$id_usuario");

        $_SESSION['mensaje'] = "Cantidad actualizada.";
        $_SESSION['tipo_mensaje'] = "ok";

    } else {

        $_SESSION['mensaje'] = "La cantidad mínima es 1.";
        $_SESSION['tipo_mensaje'] = "error";
    }

    redirigir_anterior($_SESSION['mensaje']);
    break;



/* QUITAR DEL CARRITO */
case 'quitar_carrito':

    $id = intval($_GET['id']);
    $conn->query("DELETE FROM carrito WHERE id=$id AND id_usuario=$id_usuario");

    $_SESSION['mensaje'] = "Producto eliminado del carrito.";
    $_SESSION['tipo_mensaje'] = "error";

    redirigir_anterior($_SESSION['mensaje']);
    break;



/* FAVORITO (toggle) */
case 'favorito':

    if ($id_producto) {

        $check = $conn->query("SELECT id FROM favoritos WHERE id_usuario=$id_usuario AND id_producto=$id_producto");

        if ($check->num_rows > 0) {
            $conn->query("DELETE FROM favoritos WHERE id_usuario=$id_usuario AND id_producto=$id_producto");
            $_SESSION['mensaje'] = "Producto eliminado de favoritos.";
            $_SESSION['tipo_mensaje'] = "error";

        } else {
            $conn->query("INSERT INTO favoritos (id_usuario, id_producto) 
                          VALUES ($id_usuario, $id_producto)");
            $_SESSION['mensaje'] = "Producto añadido a favoritos.";
            $_SESSION['tipo_mensaje'] = "ok";
        }
    }

    redirigir_anterior($_SESSION['mensaje']);
    break;



/* QUITAR FAVORITO DESDE PANEL */
case 'quitar_favorito':

    $id = intval($_GET['id']);
    $conn->query("DELETE FROM favoritos WHERE id=$id AND id_usuario=$id_usuario");

    $_SESSION['mensaje'] = "Favorito eliminado.";
    $_SESSION['tipo_mensaje'] = "error";

    redirigir_anterior($_SESSION['mensaje']);
    break;



/* RESERVA */
case 'reservar':

    if ($id_producto) {

        $check = $conn->query("SELECT * FROM reservas WHERE id_producto=$id_producto LIMIT 1");

        if ($check->num_rows > 0) {

            $row = $check->fetch_assoc();

            if ($row['id_usuario'] == $id_usuario) {
                $conn->query("DELETE FROM reservas WHERE id_producto=$id_producto AND id_usuario=$id_usuario");
                $_SESSION['mensaje'] = "Reserva cancelada.";
                $_SESSION['tipo_mensaje'] = "error";

            } else {
                $_SESSION['mensaje'] = "Este producto ya está reservado por otro usuario.";
                $_SESSION['tipo_mensaje'] = "error";
            }

        } else {
            $conn->query("INSERT INTO reservas (id_usuario, id_producto)
                          VALUES ($id_usuario, $id_producto)");
            $_SESSION['mensaje'] = "Producto reservado.";
            $_SESSION['tipo_mensaje'] = "ok";
        }
    }

    redirigir_anterior($_SESSION['mensaje']);
    break;



/* QUITAR RESERVA */
case 'quitar_reserva':

    $id = intval($_GET['id']);
    $conn->query("DELETE FROM reservas WHERE id=$id AND id_usuario=$id_usuario");

    $_SESSION['mensaje'] = "Reserva cancelada.";
    $_SESSION['tipo_mensaje'] = "error";

    redirigir_anterior($_SESSION['mensaje']);
    break;



/* DEFAULT */
default:
    header("Location: ../usuario/usuario_panel.php");
    exit();
}

?>
