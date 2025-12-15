<?php
session_start();
include("../conexion.php");

// Comprobar sesión
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id'];
$nombre_usuario = $_SESSION['nombre'] ?? "Usuario";

// Sección seleccionada
$seccion = $_GET['seccion'] ?? "resumen";

// Contadores
$cont_carrito = $conn->query("SELECT COUNT(*) AS total FROM carrito WHERE id_usuario=$id_usuario")->fetch_assoc()['total'];
$cont_favs    = $conn->query("SELECT COUNT(*) AS total FROM favoritos WHERE id_usuario=$id_usuario")->fetch_assoc()['total'];
$cont_res     = $conn->query("SELECT COUNT(*) AS total FROM reservas WHERE id_usuario=$id_usuario")->fetch_assoc()['total'];
$cont_ped     = $conn->query("SELECT COUNT(*) AS total FROM pedidos WHERE id_usuario=$id_usuario")->fetch_assoc()['total'];

// FUNCIÓN MENSAJE TOAST
function mensaje() {
    if (isset($_SESSION['mensaje'])) {
        $tipo  = $_SESSION['tipo_mensaje'] ?? 'ok';
        $clase = ($tipo === 'error') ? 'toast-error' : 'toast-ok';

        echo "<div class='toast $clase'>{$_SESSION['mensaje']}</div>";

        unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="../../img/logo-web.png">

<title>Panel de Usuario</title>

<link rel="stylesheet" href="../../css/style.css">
<link rel="stylesheet" href="../../css/usuario.css">

<!-- ICONOS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>
<body>

<?php include("../header.php"); ?>

<div class="panel-layout">

    <!-- MENÚ LATERAL -->
    <aside class="panel-menu">
        <h3><i class="fa-solid fa-user"></i> <?= $nombre_usuario ?></h3>

        <a href="?seccion=resumen"    class="<?= $seccion=='resumen' ? 'active':'' ?>"><i class="fa-solid fa-chart-line"></i> Resumen</a>
        <a href="?seccion=carrito"    class="<?= $seccion=='carrito' ? 'active':'' ?>"><i class="fa-solid fa-cart-shopping"></i> Carrito</a>
        <a href="?seccion=favoritos"  class="<?= $seccion=='favoritos' ? 'active':'' ?>"><i class="fa-solid fa-heart"></i> Favoritos</a>
        <a href="?seccion=reservas"   class="<?= $seccion=='reservas' ? 'active':'' ?>"><i class="fa-solid fa-bookmark"></i> Reservas</a>
        <a href="?seccion=pedidos"    class="<?= $seccion=='pedidos' ? 'active':'' ?>"><i class="fa-solid fa-box"></i> Pedidos</a>
        <a href="?seccion=ajustes"    class="<?= $seccion=='ajustes' ? 'active':'' ?>"><i class="fa-solid fa-gear"></i> Ajustes</a>

        <hr>

        <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </aside>

    <!-- CONTENIDO CENTRAL -->
    <main class="panel-content">
        <h2>Panel de Usuario</h2>

        <?php mensaje(); ?>

        <!-- SECCIÓN: RESUMEN -->
        <?php if ($seccion == "resumen"): ?>
        <section class="resumen-grid">

            <div class="resumen-card">
                <i class="fa-solid fa-cart-shopping"></i>
                <h3><?= $cont_carrito ?></h3>
                <p>En carrito</p>
            </div>

            <div class="resumen-card">
                <i class="fa-solid fa-heart"></i>
                <h3><?= $cont_favs ?></h3>
                <p>Favoritos</p>
            </div>

            <div class="resumen-card">
                <i class="fa-solid fa-bookmark"></i>
                <h3><?= $cont_res ?></h3>
                <p>Reservas</p>
            </div>

            <div class="resumen-card">
                <i class="fa-solid fa-box"></i>
                <h3><?= $cont_ped ?></h3>
                <p>Pedidos</p>
            </div>

        </section>
        <?php endif; ?>


<!-- SECCIÓN CARRITO -->
<?php if ($seccion=="carrito"): ?>
<h3>Carrito</h3>

<?php
$sql = "SELECT 
            c.id AS id_carrito, 
            c.cantidad,
            p.id AS id_producto,
            p.nombre,
            p.precio,
            p.imagen
        FROM carrito c
        JOIN productos p ON c.id_producto = p.id
        WHERE c.id_usuario = $id_usuario";

$res = $conn->query($sql);

$total_general = 0;
?>

<div class="lista-productos">

<?php if ($res->num_rows > 0): ?>

    <?php while ($row = $res->fetch_assoc()): ?>

    <?php
        $subtotal = $row['precio'] * $row['cantidad'];
        $total_general += $subtotal;
    ?>

    <div class="item carrito-item">
        <img src="../../img/<?= $row['imagen'] ?>" alt="">

        <div class="carrito-info">
            <h4><?= htmlspecialchars($row['nombre']) ?></h4>

            <p class="precio-unit">
                Precio unitario: 
                <strong><?= number_format($row['precio'],2,",",".") ?> €</strong>
            </p>

            <!-- Cantidad -->
            <div class="cantidad-box">

                <a href="acciones_producto.php?accion=restar&id=<?= $row['id_carrito'] ?>" 
                   class="qty-btn">−</a>

                <span class="qty-number"><?= $row['cantidad'] ?></span>

                <a href="acciones_producto.php?accion=sumar&id=<?= $row['id_carrito'] ?>" 
                   class="qty-btn">+</a>

            </div>

            <p class="subtotal">
                Subtotal: 
                <strong><?= number_format($subtotal,2,",",".") ?> €</strong>
            </p>

            <a href="#"
               class="btn-danger quitar-btn"
               onclick="confirmarAccion(
                   'acciones_producto.php?accion=quitar_carrito&id=<?= $row['id_carrito'] ?>',
                   '¿Quitar del carrito?',
                   'Este producto será eliminado del carrito.'
               ); return false;">
                Quitar
            </a>
        </div>
    </div>

    <?php endwhile; ?>

<?php else: ?>

    <p>No hay productos en el carrito.</p>

<?php endif; ?>

</div>


<!-- TOTAL GENERAL -->
<?php if ($total_general > 0): ?>
<div class="carrito-total-box">
    <h3>Total del pedido:
        <strong><?= number_format($total_general,2,",",".") ?> €</strong>
    </h3>

<form action="../checkout.php" method="POST">
    <input type="hidden" name="checkout_carrito" value="1">
    <button type="submit" class="btn btn-primario">
        Tramitar compra
    </button>
</form>

</div>
<?php endif; ?>

<?php endif; ?>


        <!-- FAVORITOS -->
        <?php if ($seccion=="favoritos"): ?>
        <h3>Favoritos</h3>

        <?php
        $sql = "SELECT f.id AS id_fav, p.* FROM favoritos f
                JOIN productos p ON f.id_producto = p.id
                WHERE f.id_usuario = $id_usuario";
        $res = $conn->query($sql);
        ?>

        <div class="lista-productos">
        <?php if ($res->num_rows > 0): ?>
            <?php while ($row = $res->fetch_assoc()): ?>
            <div class="item">
                <img src="../../img/<?= $row['imagen'] ?>" alt="">
                <div>
                    <h4><?= $row['nombre'] ?></h4>
                    <p><?= number_format($row['precio'],2,",",".") ?> €</p>

                    <a href="../producto_detalle.php?id=<?= $row['id'] ?>" class="btn">Ver</a>

                    <a href="#"
                       class="btn-danger"
                       onclick="confirmarAccion(
                           'acciones_producto.php?accion=quitar_favorito&id=<?= $row['id_fav'] ?>',
                           '¿Eliminar de favoritos?',
                           'Este producto será eliminado de favoritos.'
                       ); return false;">
                        Quitar
                    </a>

                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No tienes favoritos.</p>
        <?php endif; ?>
        </div>
        <?php endif; ?>


        <!-- RESERVAS -->
        <?php if ($seccion=="reservas"): ?>
        <h3>Reservas</h3>

        <?php
        $sql = "SELECT r.id AS id_reserva, p.* FROM reservas r
                JOIN productos p ON r.id_producto = p.id
                WHERE r.id_usuario = $id_usuario";
        $res = $conn->query($sql);
        ?>

        <div class="lista-productos">
        <?php if ($res->num_rows > 0): ?>
            <?php while ($row = $res->fetch_assoc()): ?>
            <div class="item">
                <img src="../../img/<?= $row['imagen'] ?>" alt="">
                <div>
                    <h4><?= $row['nombre'] ?></h4>
                    <p><?= number_format($row['precio'],2,",",".") ?> €</p>

                    <a href="../producto_detalle.php?id=<?= $row['id'] ?>" class="btn">Ver</a>

                    <a href="#"
                       class="btn-danger"
                       onclick="confirmarAccion(
                           'acciones_producto.php?accion=quitar_reserva&id=<?= $row['id_reserva'] ?>',
                           '¿Cancelar reserva?',
                           'Este producto dejará de estar reservado para ti.'
                       ); return false;">
                        Cancelar
                    </a>

                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No tienes reservas activas.</p>
        <?php endif; ?>
        </div>
        <?php endif; ?>


<!-- PEDIDOS -->
<?php if ($seccion=="pedidos"): ?>
<h3>Pedidos realizados</h3>

<?php
// Primero obtenemos todos los pedidos
$sqlPedidos = "
    SELECT id, fecha, estado, total
    FROM pedidos 
    WHERE id_usuario = $id_usuario
    ORDER BY fecha DESC
";
$resPedidos = $conn->query($sqlPedidos);
?>

<div class="lista-pedidos">
<?php if ($resPedidos->num_rows > 0): ?>
    <?php while ($pedido = $resPedidos->fetch_assoc()): ?>

        <?php
            $id_pedido = $pedido['id'];
            $estado = $pedido['estado'];
            $fechaPedido = $pedido['fecha'];
            $total_pedido = $pedido['total'];

            // Obtener todos los productos de este pedido
            $sqlProductos = "
                SELECT pp.cantidad, pp.subtotal,
                       p.nombre, p.precio, p.imagen
                FROM pedidos_productos pp
                JOIN productos p ON p.id = pp.id_producto
                WHERE pp.id_pedido = $id_pedido
            ";
            $resProductos = $conn->query($sqlProductos);

            // FECHA ESTIMADA SEGÚN ESTADO
            $estimada = "";
            switch($estado){
                case "pendiente":
                    $estimada = "Fecha estimada disponible cuando el pago sea confirmado.";
                    break;
                case "confirmado":
                    $estimada = "Entrega estimada el <strong>" . date("j M", strtotime($fechaPedido . " +3 days")) . "</strong><br>
                                 Entrega rápida: <strong>" . date("j M", strtotime($fechaPedido . " +1 day")) . "</strong>";
                    break;
                case "enviado":
                    $estimada = "Tu pedido llegará el <strong>" . date("j M", strtotime($fechaPedido . " +2 days")) . "</strong><br>
                                 Entrega express: <strong>" . date("j M", strtotime($fechaPedido . " +1 day")) . "</strong>";
                    break;
                case "completado":
                    $estimada = "<strong>Pedido entregado correctamente 🎉</strong>";
                    break;
            }

            // Mapeo estado = paso de progreso
            $pasos = [
                "pendiente"  => 1,
                "confirmado" => 2,
                "enviado"    => 3,
                "completado" => 4
            ];
            $progreso = $pasos[$estado] ?? 1;
        ?>

        <div class="pedido-box">
            <div class="pedido-header">
                <h4>Pedido #<?= $id_pedido ?></h4>
                <small>Fecha: <?= $fechaPedido ?></small>
            </div>

            <!-- LISTA DE PRODUCTOS DEL PEDIDO -->
            <div class="pedido-productos-lista">
                <?php while ($prod = $resProductos->fetch_assoc()): ?>
                    <?php
                        $cantidad = $prod['cantidad'];
                        $subtotal = $prod['subtotal'];
                        $precio_unitario = $prod['precio'];
                        $iva_producto = $subtotal * 0.21;
                        $total_producto = $subtotal + $iva_producto;
                    ?>
                    
                    <div class="pedido-producto-item">
                        <img src="../../img/<?= $prod['imagen'] ?>" alt="">
                        <div class="producto-detalles">
                            <h5><?= htmlspecialchars($prod['nombre']) ?></h5>
                            <p><strong>Cantidad:</strong> <?= $cantidad ?> unidad(es)</p>
                            <p><strong>Precio unitario:</strong> <?= number_format($precio_unitario,2,",",".") ?> €</p>
                            <p><strong>Subtotal:</strong> <?= number_format($subtotal,2,",",".") ?> €</p>
                            <p><strong>IVA (21%):</strong> <?= number_format($iva_producto,2,",",".") ?> €</p>
                            <p class="total-producto"><strong>Total:</strong> <?= number_format($total_producto,2,",",".") ?> €</p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if ($estimada !== ""): ?>
            <div class="pedido-fecha-estimada">
                <?= $estimada ?>
            </div>
            <?php endif; ?>

            <!-- TOTAL DEL PEDIDO -->
            <div class="pedido-total-resumen">
                <p><strong>Total del pedido completo:</strong> <?= number_format($total_pedido,2,",",".") ?> € (IVA incluido)</p>
            </div>

            <!-- BARRA PROGRESO ESTADO -->
            <div class="estado-tracker">
                <div class="paso <?= $progreso >= 1 ? 'activo':'' ?>">
                    <div class="punto"></div>
                    <span>Pedido creado</span>
                </div>

                <div class="linea <?= $progreso >= 2 ? 'activo':'' ?>"></div>

                <div class="paso <?= $progreso >= 2 ? 'activo':'' ?>">
                    <div class="punto"></div>
                    <span>Pago confirmado</span>
                </div>

                <div class="linea <?= $progreso >= 3 ? 'activo':'' ?>"></div>

                <div class="paso <?= $progreso >= 3 ? 'activo':'' ?>">
                    <div class="punto"></div>
                    <span>Pedido enviado</span>
                </div>

                <div class="linea <?= $progreso >= 4 ? 'activo':'' ?>"></div>

                <div class="paso <?= $progreso >= 4 ? 'activo':'' ?>">
                    <div class="punto"></div>
                    <span>Entregado</span>
                </div>
            </div>

        </div>

    <?php endwhile; ?>
<?php else: ?>
    <p>No has hecho pedidos aún.</p>
<?php endif; ?>
</div>

<?php endif; ?>




<!-- AJUSTES DE CUENTA -->
<?php if ($seccion == "ajustes"): ?>
<h3>Ajustes de Cuenta</h3>

<div class="ajustes-box">

<form action="actualizar_datos.php" method="POST" class="ajuste-form">
        <input type="hidden" name="actualizar_cuenta" value="1">

        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre_usuario) ?>">

        <label>Nuevo email:</label>
        <input type="email" name="email" placeholder="Opcional">

<label>Nueva contraseña:</label>
<div class="password-wrapper">
    <input type="password" name="password" id="pass1">
    <span class="toggle-pass" onclick="togglePassword('pass1', this)">
        <i class="fa-solid fa-eye"></i>
    </span>
</div>

<label>Repetir contraseña:</label>
<div class="password-wrapper">
    <input type="password" name="password2" id="pass2">
    <span class="toggle-pass" onclick="togglePassword('pass2', this)">
        <i class="fa-solid fa-eye"></i>
    </span>
</div>

<button class="btn">Actualizar</button>

    </form>
</div>
<?php endif; ?>


    </main>
</div>


<!-- MODAL -->
<div id="modalConfirm" class="modal-confirm hidden">
    <div class="modal-content">
        <h3 id="modalTitle">¿Estás seguro?</h3>
        <p id="modalText">Esta acción no se puede deshacer.</p>

        <div class="modal-buttons">
            <button class="btn-cancelar" onclick="closeModal()">Cancelar</button>
            <a id="modalAceptar" href="#" class="btn-confirmar">Sí, continuar</a>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>

<script src="/initial-d/js/script.js"></script>

</body>
</html>
