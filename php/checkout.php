<?php
session_start();
include("conexion.php");
require_once "mail_pedido.php";

// Obligar a iniciar sesión
if (!isset($_SESSION['id'])) {
    $_SESSION['mensaje'] = "Debes iniciar sesión para completar la compra.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: login.php");
    exit();
}

$id_usuario      = (int)$_SESSION['id'];
$email_usuario   = $_SESSION['email']  ?? '';
$nombre_usuario  = $_SESSION['nombre'] ?? '';

/* CHECKOUT DESDE EL CARRITO (MÚLTIPLE) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_carrito'])) {

    $sqlCart = "
        SELECT c.id_producto, c.cantidad, p.nombre, p.imagen, 
               p.precio, p.precio_oferta, p.stock
        FROM carrito c
        JOIN productos p ON p.id = c.id_producto
        WHERE c.id_usuario = $id_usuario
    ";
    $resCart = $conn->query($sqlCart);

    if (!$resCart || $resCart->num_rows === 0) {
        $_SESSION['mensaje'] = "Tu carrito está vacío.";
        $_SESSION['tipo_mensaje'] = "error";
        header("Location: usuario/usuario_panel.php?seccion=carrito");
        exit();
    }

    // Construir array de productos para el checkout múltiple
    $multi = [];
    while ($row = $resCart->fetch_assoc()) {

        $precio_unit = (!empty($row['precio_oferta']) && $row['precio_oferta'] > 0)
            ? (float)$row['precio_oferta']
            : (float)$row['precio'];

        $multi[] = [
            'id_producto' => (int)$row['id_producto'],
            'nombre'      => $row['nombre'],
            'imagen'      => $row['imagen'],
            'cantidad'    => (int)$row['cantidad'],
            'precio_unit' => $precio_unit,
            'stock'       => (int)$row['stock']
        ];
    }

    $_SESSION['checkout_multi'] = $multi;
    $_SESSION['checkout_modo']  = "multi";

    header("Location: checkout.php");
    exit();
}

/*CHECKOUT INDIVIDUAL DESDE DETALLE DE PRODUCTO */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_producto']) && !isset($_POST['checkout_carrito'])) {

    $id_producto = (int)$_POST['id_producto'];
    $resProd = $conn->query("SELECT * FROM productos WHERE id = $id_producto LIMIT 1");

    if ($resProd && $resProd->num_rows > 0) {
        $producto = $resProd->fetch_assoc();

        $precio_unit = (!empty($producto['precio_oferta']) && $producto['precio_oferta'] > 0)
            ? (float)$producto['precio_oferta']
            : (float)$producto['precio'];

        $_SESSION['checkout'] = [
            'id_producto'   => (int)$producto['id'],
            'nombre'        => $producto['nombre'],
            'imagen'        => $producto['imagen'],
            'precio_unit'   => $precio_unit,
            'cantidad'      => 1,
            'stock_actual'  => (int)$producto['stock'],
        ];

        $_SESSION['checkout_modo'] = "single";

    } else {
        $_SESSION['mensaje'] = "Producto no encontrado.";
        $_SESSION['tipo_mensaje'] = "error";
        header("Location: productos.php");
        exit();
    }
}

/* CARGA DE MODO CHECKOUT (INDIVIDUAL O MULTI) */
$modo = $_SESSION['checkout_modo'] ?? null;

if ($modo === "single") {
    $checkout = $_SESSION['checkout'] ?? null;
} elseif ($modo === "multi") {
    $checkout = $_SESSION['checkout_multi'] ?? null;
} else {
    $checkout = null;
}

// Si no hay checkout = fuera
if (!$checkout) {
    header("Location: productos.php");
    exit();
}

/* PASOS */
$paso = isset($_POST['paso']) ? (int)$_POST['paso'] : 1;
if ($paso < 1 || $paso > 4) $paso = 1;

$envio = $_SESSION['checkout_envio'] ?? [
    'nombre'    => '',
    'direccion' => '',
    'ciudad'    => '',
    'cp'        => '',
    'telefono'  => '',
];

$pago = $_SESSION['checkout_pago'] ?? [
    'metodo'        => '',
    'titular'       => '',
    'num_tarjeta'   => '',
    'caducidad'     => '',
    'cvv'           => '',
    'paypal_email'  => '',
    'paypal_pass'   => '',
    'bizum_tel'     => '',
    'bizum_code'    => '',
];

/* PASO 2 → GUARDAR DIRECCIÓN */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_envio'])) {

    $envio['nombre']    = trim($_POST['nombre_envio'] ?? '');
    $envio['direccion'] = trim($_POST['direccion'] ?? '');
    $envio['ciudad']    = trim($_POST['ciudad'] ?? '');
    $envio['cp']        = trim($_POST['cp'] ?? '');
    $envio['telefono']  = trim($_POST['telefono'] ?? '');

    if (
        $envio['nombre'] === '' ||
        $envio['direccion'] === '' ||
        $envio['ciudad'] === '' ||
        $envio['cp'] === '' ||
        $envio['telefono'] === ''
    ) {
        $error_envio = "Rellena todos los campos.";
        $paso = 2;

    } else {

        $_SESSION['checkout_envio'] = $envio;

        // Guardar dirección en BD
        $nombre_sql = $conn->real_escape_string($envio['nombre']);
        $dir_sql    = $conn->real_escape_string($envio['direccion']);
        $ciu_sql    = $conn->real_escape_string($envio['ciudad']);
        $cp_sql     = $conn->real_escape_string($envio['cp']);
        $tel_sql    = $conn->real_escape_string($envio['telefono']);

        $conn->query("
            INSERT INTO direcciones (id_usuario, nombre, direccion, ciudad, cp, telefono)
            VALUES ($id_usuario, '$nombre_sql', '$dir_sql', '$ciu_sql', '$cp_sql', '$tel_sql')
        ");

        $paso = 3;
    }
}

/* 
   PASO 3 y 4 → PROCESAR PAGO + CREAR PEDIDO
 */
$pedido_creado = false;
$num_pedido    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_pago'])) {

    // Volcamos datos recibidos
    $pago['metodo']       = $_POST['metodo_pago'] ?? '';
    $pago['titular']      = trim($_POST['titular'] ?? '');
    $pago['num_tarjeta']  = trim($_POST['num_tarjeta'] ?? '');
    $pago['caducidad']    = trim($_POST['caducidad'] ?? '');
    $pago['cvv']          = trim($_POST['cvv'] ?? '');
    $pago['paypal_email'] = trim($_POST['paypal_email'] ?? '');
    $pago['paypal_pass']  = trim($_POST['paypal_pass'] ?? '');
    $pago['bizum_tel']    = trim($_POST['bizum_tel'] ?? '');
    $pago['bizum_code']   = trim($_POST['bizum_code'] ?? '');

    $_SESSION['checkout_pago'] = $pago;

    // Validaciones por método
    if ($pago['metodo'] === '') {
        $error_pago = "Selecciona un método de pago.";
        $paso = 3;
    } else {

        // TARJETA
        if ($pago['metodo'] === 'tarjeta') {

            if (
                $pago['titular'] === '' ||
                $pago['num_tarjeta'] === '' ||
                $pago['caducidad'] === '' ||
                $pago['cvv'] === ''
            ) {
                $error_pago = "Rellena todos los datos de la tarjeta.";
                $paso = 3;
            }
            elseif (!preg_match('/^[0-9]{16}$/', str_replace(' ', '', $pago['num_tarjeta']))) {
                $error_pago = "Número de tarjeta incorrecto.";
                $paso = 3;
            }
            elseif (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $pago['caducidad'])) {
                $error_pago = "Formato de caducidad incorrecto.";
                $paso = 3;
            }
            else {
                // Validar que la fecha no ha caducado
                list($mes, $anio) = explode('/', $pago['caducidad']);
                $mes = (int)$mes;
                $anio = (int)$anio + 2000;
                
                $hoy = new DateTime();
                $mes_actual = (int)$hoy->format('n');
                $anio_actual = (int)$hoy->format('Y');
                
                // Tarjeta caducada si el año es menor, o si el año es igual pero el mes es menor
                if ($anio < $anio_actual || ($anio == $anio_actual && $mes < $mes_actual)) {
                    $error_pago = "La tarjeta ha caducado.";
                    $paso = 3;
                }
                elseif (!preg_match('/^[0-9]{3}$/', $pago['cvv'])) {
                    $error_pago = "CVV incorrecto.";
                    $paso = 3;
                } else {
                    $paso = 4;
                }
            }
        }
// PAYPAL
elseif ($pago['metodo'] === 'paypal') {

    $pago['paypal_email'] = trim($pago['paypal_email']);

    if ($pago['paypal_email'] === '' || $pago['paypal_pass'] === '') {
        $error_pago = "Introduce tus credenciales de PayPal.";
        $paso = 3;
    }
    elseif (!filter_var($pago['paypal_email'], FILTER_VALIDATE_EMAIL)) {
        $error_pago = "El correo electrónico no es válido.";
        $paso = 3;
    }
    elseif (strlen($pago['paypal_pass']) < 6) {
        $error_pago = "La contraseña de PayPal debe tener al menos 6 caracteres.";
        $paso = 3;
    }
    else {
        $paso = 4;
    }
}


// BIZUM
elseif ($pago['metodo'] === 'bizum') {

    if ($pago['bizum_code'] === '') {
        $error_pago = "Introduce el código Bizum enviado a tu correo.";
        $paso = 3;
    }
    elseif (!isset($_SESSION['bizum_otp'])) {
        $error_pago = "Debes solicitar un código Bizum antes de continuar.";
        $paso = 3;
    }
    elseif (!isset($_SESSION['bizum_otp_expira']) || time() > $_SESSION['bizum_otp_expira']) {
        $error_pago = "El código Bizum ha caducado (90s). Solicita uno nuevo.";
        unset($_SESSION['bizum_otp'], $_SESSION['bizum_otp_expira']);
        $paso = 3;
    }
    elseif ($pago['bizum_code'] != $_SESSION['bizum_otp']) {
        $error_pago = "El código Bizum es incorrecto.";
        $paso = 3;
    }
    else {
        unset($_SESSION['bizum_otp'], $_SESSION['bizum_otp_expira'], $_SESSION['bizum_otp_tel']);
        $paso = 4;
    }
}

// TRANSFERENCIA BANCARIA
elseif ($pago['metodo'] === 'transferencia') {
    
    $iban_usuario = trim($_POST['transferencia_iban'] ?? '');
    $confirma_transferencia = $_POST['confirma_transferencia'] ?? '';
    
    // Validar que el IBAN no esté vacío
    if (empty($iban_usuario)) {
        $error_pago = "Debes introducir tu número IBAN.";
        $paso = 3;
    }
    // Validar formato básico del IBAN (España: ES + 22 dígitos)
    elseif (!preg_match('/^ES\d{22}$/', str_replace(' ', '', $iban_usuario))) {
        $error_pago = "El formato del IBAN no es válido. Debe ser ES seguido de 22 dígitos.";
        $paso = 3;
    }
    // Validar confirmación
    elseif ($confirma_transferencia !== '1') {
        $error_pago = "Debes confirmar que realizarás la transferencia bancaria.";
        $paso = 3;
    } 
    else {
        // Guardar IBAN del usuario en la sesión de pago
        $_SESSION['pago']['iban_usuario'] = $iban_usuario;
        $paso = 4;
    }
}
}
 // ESTE CIERRE FALTABA

/* PASO 4 = CREAR PEDIDO SI NO HAY ERRORES */
if ($paso === 4) {


        // Calcular total bruto (según modo)
        if ($modo === "single") {
            $totalBruto = $checkout['precio_unit'] * $checkout['cantidad'];
        } else {
            $totalBruto = 0;
            foreach ($checkout as $prod) {
                $totalBruto += $prod['precio_unit'] * $prod['cantidad'];
            }
        }

        $iva   = $totalBruto * 0.21;
        $total = $totalBruto + $iva;

        $direccion_resumen =
            $envio['nombre'] . " | " .
            $envio['direccion'] . " | " .
            $envio['cp'] . " " . $envio['ciudad'];

        $metodo_sql = $conn->real_escape_string($pago['metodo']);
        $dir_sql    = $conn->real_escape_string($direccion_resumen);

        // INSERTAR PEDIDO
        $sqlPedido = "
            INSERT INTO pedidos (id_usuario, fecha, total, estado, metodo_pago, direccion_envio)
            VALUES ($id_usuario, NOW(), $total, 'pendiente', '$metodo_sql', '$dir_sql')
        ";

        if ($conn->query($sqlPedido)) {

            $num_pedido = $conn->insert_id;

            // INSERTAR PRODUCTOS DEL PEDIDO
            if ($modo === "single") {

                $p = $checkout;
                $subtotal_prod = $p['precio_unit'] * $p['cantidad'];

                $conn->query("
                    INSERT INTO pedidos_productos (id_pedido, id_producto, cantidad, subtotal)
                    VALUES ($num_pedido, {$p['id_producto']}, {$p['cantidad']}, $subtotal_prod)
                ");

                // Reducir stock
                $conn->query("
                    UPDATE productos
                    SET stock = GREATEST(stock - {$p['cantidad']}, 0)
                    WHERE id = {$p['id_producto']}
                ");

                // Actualizar ventas
                $conn->query("
                    UPDATE productos
                    SET ventas = ventas + {$p['cantidad']}
                    WHERE id = {$p['id_producto']}
                ");

                // Activar top_ventas si llega a 10
                $conn->query("
                    UPDATE productos
                    SET top_ventas = 1
                    WHERE id = {$p['id_producto']} AND ventas >= 10
                ");

                // Si llega a 0 = vendido
                $conn->query("
                    UPDATE productos
                    SET estado='vendido'
                    WHERE id = {$p['id_producto']} AND stock <= 0
                ");

                // Eliminar reserva si existe
                $conn->query("
                    DELETE FROM reservas
                    WHERE id_producto = {$p['id_producto']} AND id_usuario = $id_usuario
                ");

            } else {
                // MULTI
                foreach ($checkout as $p) {
                    $id_prod  = (int)$p['id_producto'];
                    $cant     = (int)$p['cantidad'];
                    $sub_prod = $p['precio_unit'] * $cant;

                    $conn->query("
                        INSERT INTO pedidos_productos (id_pedido, id_producto, cantidad, subtotal)
                        VALUES ($num_pedido, $id_prod, $cant, $sub_prod)
                    ");

                    $conn->query("
                        UPDATE productos
                        SET stock = GREATEST(stock - $cant, 0)
                        WHERE id = $id_prod
                    ");

                    $conn->query("
                        UPDATE productos
                        SET ventas = ventas + $cant
                        WHERE id = $id_prod
                    ");

                    $conn->query("
                        UPDATE productos
                        SET top_ventas = 1
                        WHERE id = $id_prod AND ventas >= 10
                    ");

                    $conn->query("
                        UPDATE productos
                        SET estado='vendido'
                        WHERE id = $id_prod AND stock <= 0
                    ");

                    // Eliminar reserva si existe
                    $conn->query("
                        DELETE FROM reservas
                        WHERE id_producto = $id_prod AND id_usuario = $id_usuario
                    ");
                }

                // Vaciar carrito del usuario
                $conn->query("DELETE FROM carrito WHERE id_usuario = $id_usuario");
            }

            // Enviar correo
            enviarCorreoPedido(
                $email_usuario,
                $nombre_usuario,
                $num_pedido,
                ($modo === "single" ? $checkout['nombre'] : "Varios productos"),
                number_format($total, 2, ",", "."),
                $direccion_resumen,
                $pago['metodo']
            );

            $_SESSION['mensaje'] = "Pedido realizado con éxito. Nº $num_pedido";
            $_SESSION['tipo_mensaje'] = "ok";

            $pedido_creado = true;

            // Limpieza básica de datos de checkout
            unset(
                $_SESSION['checkout_envio'],
                $_SESSION['checkout_pago']
            );

        } else {
            $error_pago = "Error al registrar el pedido.";
            $paso = 3;
        }
    }
}

/* CÁLCULO DE SUBTOTALES PARA RESUMEN VISUAL (LATERAL) */
if ($modo === "single") {
    $subtotal = $checkout['precio_unit'] * $checkout['cantidad'];
} else {
    $subtotal = 0;
    foreach ($checkout as $p) {
        $subtotal += $p['precio_unit'] * $p['cantidad'];
    }
}
$iva   = $subtotal * 0.21;
$total = $subtotal + $iva;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../img/logo-web.png">

    <title>Checkout - Initial D</title>

    <link rel="stylesheet" href="/initial-d/css/style.css">
    <link rel="stylesheet" href="/initial-d/css/productos.css">
    <link rel="stylesheet" href="/initial-d/css/checkout.css">
</head>
<body>

<?php include("header.php"); ?>

<main class="checkout-page">

    <!-- Overlay PROCESANDO -->
    <div id="overlay-pago" class="overlay-pago" style="display:none;">
        <div class="overlay-box">
            <div class="spinner"></div>
            <h3>Procesando pago...</h3>
            <p>No cierres esta ventana.</p>
        </div>
    </div>

    <div class="checkout-container">

        <!-- PASOS -->
        <div class="checkout-steps">
            <div class="step <?= $paso >= 1 ? 'active':'' ?>"><span>1</span><p>Resumen</p></div>
            <div class="step <?= $paso >= 2 ? 'active':'' ?>"><span>2</span><p>Envío</p></div>
            <div class="step <?= $paso >= 3 ? 'active':'' ?>"><span>3</span><p>Pago</p></div>
            <div class="step <?= $paso >= 4 ? 'active':'' ?>"><span>4</span><p>Confirmación</p></div>
        </div>

        <div class="checkout-grid">

            <!-- COLUMNA IZQUIERDA -->
            <section class="checkout-main">

                <?php if ($paso === 1): ?>
                    <!-- PASO 1: RESUMEN -->
                    <h2>Resumen del pedido</h2>
                    <p class="checkout-info-simulado">
                        Revisa tu pedido antes de continuar.
                    </p>

                    <div class="checkout-card">
                        <?php if ($modo === 'single'): ?>

                            <div class="checkout-product">
                                <img src="../img/<?= htmlspecialchars($checkout['imagen']) ?>" alt="">
                                <div>
                                    <h3><?= htmlspecialchars($checkout['nombre']) ?></h3>
                                    <p>Cantidad: <?= (int)$checkout['cantidad'] ?></p>
                                    <p>Precio unitario: <?= number_format($checkout['precio_unit'],2,",",".") ?> €</p>

                                    <?php if ($checkout['stock_actual'] <= 0): ?>
                                        <p class="stock-warning">⚠ Sin stock. No puedes comprarlo.</p>
                                    <?php else: ?>
                                        <p class="stock-ok">Stock disponible: <?= $checkout['stock_actual'] ?> uds</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php else: ?>

                            <p><strong>Estos son los productos de tu carrito:</strong></p>

                            <?php foreach ($checkout as $p): ?>
                                <div class="checkout-product multi-item">
                                    <img src="../img/<?= htmlspecialchars($p['imagen']) ?>" alt="">
                                    <div>
                                        <h3><?= htmlspecialchars($p['nombre']) ?></h3>
                                        <p>Cantidad: <?= (int)$p['cantidad'] ?></p>
                                        <p>Precio unitario: <?= number_format($p['precio_unit'],2,",",".") ?> €</p>

                                        <?php if ($p['stock'] <= 0): ?>
                                            <p class="stock-warning">⚠ Sin stock.</p>
                                        <?php elseif ($p['stock'] < $p['cantidad']): ?>
                                            <p class="stock-warning">⚠ No hay stock suficiente (<?= $p['stock'] ?> uds).</p>
                                        <?php else: ?>
                                            <p class="stock-ok">Stock disponible: <?= $p['stock'] ?> uds</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php endif; ?>

                        <div class="checkout-actions">
                            <form method="POST">
                                <input type="hidden" name="paso" value="2">
                                <?php if ($modo === 'single'): ?>
                                    <button type="submit" class="btn btn-primario" <?= ($modo==='single' && $checkout['stock_actual'] <= 0) ? 'disabled':'' ?>>
                                        Continuar
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-primario">
                                        Continuar
                                    </button>
                                <?php endif; ?>
                            </form>

                            <?php if ($modo === 'single'): ?>
                                <a href="producto_detalle.php?id=<?= $checkout['id_producto'] ?>" class="checkout-link">
                                    ← Volver al producto
                                </a>
                            <?php else: ?>
                                <a href="usuario/usuario_panel.php?seccion=carrito" class="checkout-link">
                                    ← Volver al carrito
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($paso === 2): ?>
                    <!-- PASO 2: ENVÍO -->
                    <h2>Dirección de envío</h2>

                    <?php if (!empty($error_envio)): ?>
                        <div class="checkout-error"><?= htmlspecialchars($error_envio) ?></div>
                    <?php endif; ?>

                    <form method="POST" class="checkout-form">
                        <input type="hidden" name="paso" value="2">
                        <input type="hidden" name="guardar_envio" value="1">

                        <div class="form-row">
                            <label>Nombre completo</label>
                            <input type="text" name="nombre_envio" value="<?= htmlspecialchars($envio['nombre']) ?>" required>
                        </div>

                        <div class="form-row">
                            <label>Dirección</label>
                            <input type="text" name="direccion" value="<?= htmlspecialchars($envio['direccion']) ?>" required>
                        </div>

                        <div class="form-row form-row-2">
                            <div>
                                <label>Ciudad</label>
                                <input type="text" name="ciudad" value="<?= htmlspecialchars($envio['ciudad']) ?>" required>
                            </div>
                            <div>
                                <label>Código Postal</label>
                                <input type="text" name="cp" value="<?= htmlspecialchars($envio['cp']) ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" value="<?= htmlspecialchars($envio['telefono']) ?>" required>
                        </div>

                        <div class="checkout-actions">
                            <button type="submit" class="btn btn-primario">Guardar y continuar</button>
                        </div>
                    </form>

                    <!-- BOTÓN VOLVER –– FUERA del formulario -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="paso" value="1">
                        <button type="submit" class="checkout-link-btn">← Volver</button>
                    </form>

                <?php elseif ($paso === 3): ?>
                    <!-- PASO 3: PAGO -->
                    <h2>Método de pago</h2>

                    <?php if (!empty($error_pago)): ?>
                        <div class="checkout-error"><?= htmlspecialchars($error_pago) ?></div>
                    <?php endif; ?>

                    <form method="POST" class="checkout-form" id="formPago">
                        <input type="hidden" name="paso" value="3">
                        <input type="hidden" name="guardar_pago" value="1">

                        <!-- Métodos -->
                        <div class="metodos-pago">
                            <label class="metodo-tarjeta">
                                <input type="radio" name="metodo_pago" value="tarjeta" <?= $pago['metodo']==='tarjeta'?'checked':'' ?>>
                                <span>Tarjeta de crédito / débito</span>
                                <small>Pago seguro cifrado.</small>
                            </label>

                            <label class="metodo-paypal">
                                <input type="radio" name="metodo_pago" value="paypal" <?= $pago['metodo']==='paypal'?'checked':'' ?>>
                                <span>PayPal</span>
                                <small>Paga con tu cuenta PayPal.</small>
                            </label>

                            <label class="metodo-bizum">
                                <input type="radio" name="metodo_pago" value="bizum" <?= $pago['metodo']==='bizum'?'checked':'' ?>>
                                <span>Bizum</span>
                                <small>Envío inmediato desde tu móvil.</small>
                            </label>

                            <label class="metodo-transferencia">
                                <input type="radio" name="metodo_pago" value="transferencia" <?= $pago['metodo']==='transferencia'?'checked':'' ?>>
                                <span>Transferencia bancaria</span>
                                <small>Recibirás los datos para transferir.</small>
                            </label>
                        </div>

                        <!-- FORM TARJETA -->
                        <div class="tarjeta-datos <?= $pago['metodo']==='tarjeta'?'visible':'' ?>" id="tarjetaDatos">
                            <div class="tarjeta-preview">
                                <div class="tarjeta-num">
                                    <?php
                                    $num = preg_replace('/\D/', '', $pago['num_tarjeta']);
                                    echo strlen($num)>=4 ? "**** **** **** ".substr($num,-4) : "**** **** **** 0000";
                                    ?>
                                </div>
                                <div class="tarjeta-name">
                                    <?= $pago['titular']!==''?htmlspecialchars($pago['titular']):'NOMBRE TITULAR' ?>
                                </div>
                                <div class="tarjeta-date">
                                    <?= $pago['caducidad']!==''?htmlspecialchars($pago['caducidad']):'MM/AA' ?>
                                </div>
                            </div>

                            <div class="form-row">
                                <label>Nombre del titular</label>
                                <input type="text" name="titular" value="<?= htmlspecialchars($pago['titular']) ?>">
                            </div>

                            <div class="form-row">
                                <label>Número de tarjeta</label>
                                <input type="text" name="num_tarjeta" value="<?= htmlspecialchars($pago['num_tarjeta']) ?>" placeholder="1111222233334444">
                            </div>

                            <div class="form-row form-row-2">
                                <div>
                                    <label>Caducidad (MM/AA)</label>
                                    <input type="text" name="caducidad" value="<?= htmlspecialchars($pago['caducidad']) ?>" placeholder="08/27">
                                </div>
                                <div>
                                    <label>CVV</label>
                                    <input type="password" name="cvv" value="<?= htmlspecialchars($pago['cvv']) ?>" placeholder="123">
                                </div>
                            </div>
                        </div>

                        <!-- PAYPAL -->
                        <div class="metodo-extra <?= $pago['metodo']==='paypal'?'visible':'' ?>" id="paypalDatos">
                            <h3>Conectar con PayPal</h3>
                            <div class="form-row">
                                <label>Email de PayPal</label>
                                <input type="email" name="paypal_email" value="<?= htmlspecialchars($pago['paypal_email']) ?>">
                            </div>
                            <div class="form-row">
                                <label>Contraseña</label>
                                <input type="password" name="paypal_pass" value="<?= htmlspecialchars($pago['paypal_pass']) ?>">
                            </div>
                        </div>

                        <!-- BIZUM -->
                        <div class="metodo-extra <?= $pago['metodo']==='bizum'?'visible':'' ?>" id="bizumDatos">
                            <h3>Pago por Bizum</h3>

                            <div class="form-row">
                                <label>Teléfono</label>
                                <input type="text" id="bizum_tel" name="bizum_tel" value="<?= htmlspecialchars($pago['bizum_tel']) ?>" placeholder="600000000">
                            </div>

                            <button type="button" class="btn btn-primario" id="btnEnviarCodigoBizum" style="margin-top:10px;">
                                Enviar código al correo
                            </button>

                            <button type="button" class="btn btn-secundario" id="btnReenviarBizum" style="margin-top:10px; display:none;">
                                Reenviar código
                            </button>

                            <p id="bizumAviso" style="color:#ffcf00; display:none; margin-top:10px;"></p>

                            <div class="form-row" id="bizumCodigoBox" style="margin-top:10px; display:none;">
                                <label>Código recibido</label>
                                <input type="text" name="bizum_code" placeholder="Introduce el código">
                            </div>
                        </div>


                        <!-- TRANSFERENCIA -->
                        <div class="metodo-extra metodo-transferencia-info <?= $pago['metodo']==='transferencia'?'visible':'' ?>" id="transferenciaInfo">
                            <h3>Transferencia bancaria</h3>
                            <p style="margin-bottom: 15px; color: #555;">
                                Introduce tu <strong>IBAN</strong> para realizar la transferencia y verificaremos el pago.
                            </p>

                            <div class="form-row">
                                <label>Tu IBAN *</label>
                                <input type="text" 
                                       name="transferencia_iban" 
                                       id="ibanInput"
                                       placeholder="ES00 0000 0000 0000 0000 0000"
                                       maxlength="29"
                                       style="text-transform: uppercase; font-family: monospace;">
                            </div>

                            <div id="bancoDetectado" style="display: none; background: #e8f5e9; padding: 12px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #4caf50; color: #222;">
                                <p style="margin: 5px 0; color: #222;"><strong>Banco detectado:</strong> <span id="nombreBanco"></span></p>
                                <p style="margin: 5px 0; color: #222;"><strong>BIC/SWIFT:</strong> <span id="bicSwift" style="font-family: monospace;"></span></p>
                            </div>
                            
                            <div style="background: #fff9e6; padding: 15px; border-radius: 10px; border-left: 4px solid #ffcf00; margin: 15px 0; color: #222;">
                                <p style="margin: 5px 0; color: #222;"><strong style="color: #222;">Datos de destino para la transferencia:</strong></p>
                                <ul style="list-style: none; padding: 0; margin: 10px 0 0 0; color: #222;">
                                    <li style="margin: 8px 0; color: #222;"><strong style="color: #222;">Beneficiario:</strong> <span style="color: #222;">Initial D Motors S.L.</span></li>
                                    <li style="margin: 8px 0; color: #222;"><strong style="color: #222;">Concepto:</strong> <span style="color: #d90000;">PEDIDO-<?= date('Ymd') ?>-<?= $id_usuario ?></span></li>
                                    <li style="margin: 8px 0; color: #222;"><strong style="color: #222;">Importe:</strong> <span style="color: #222;"><?= number_format($total ?? 0, 2, ',', '.') ?> €</span></li>
                                </ul>
                            </div>
                            
                            <div style="background: #e3f2fd; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9em; color: #222;">
                                <strong style="color: #000;">⏱️ Importante:</strong> El pedido se procesará en un plazo máximo de 24-48 horas tras recibir la transferencia.
                                Recibirás un email de confirmación cuando verifiquemos el pago.
                            </div>

                            <div class="form-row" style="margin-top: 15px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="confirma_transferencia" value="1" style="width: auto;">
                                    <span>Confirmo que realizaré la transferencia bancaria desde mi cuenta</span>
                                </label>
                            </div>
                        </div>

                        <div class="checkout-actions">
                            <button type="button" class="btn btn-primario" id="btnConfirmarPago">
                                Confirmar pago
                            </button>
                        </div>
                    </form>

                    <!-- BOTÓN VOLVER –– FUERA DEL FORMULARIO -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="paso" value="2">
                        <button type="submit" class="checkout-link-btn">← Volver</button>
                    </form>

                <?php elseif ($paso === 4): ?>
                    <!-- PASO 4: CONFIRMACIÓN -->
                    <h2>¡Gracias por tu compra!</h2>

                    <div class="checkout-card confirm-card">
                        <p>Tu pedido ha sido registrado correctamente.</p>

                        <?php if ($pedido_creado): ?>
                            <p><strong>Número de pedido:</strong> #<?= $num_pedido ?></p>
                        <?php endif; ?>

                        <p>Hemos enviado un email con los detalles de tu compra.</p>

                        <div class="checkout-actions">
                            <a href="usuario/usuario_panel.php?seccion=pedidos" class="btn btn-primario">
                                Ver mis pedidos
                            </a>
                            <a href="productos.php" class="checkout-link">Seguir comprando</a>
                        </div>
                    </div>

                <?php endif; ?>
            </section>

            <!-- COLUMNA DERECHA -->
            <aside class="checkout-summary">
                <h3>Resumen del pago</h3>

                <?php if ($modo === 'single'): ?>
                    <div class="summary-line">
                        <span>Producto</span><span><?= htmlspecialchars($checkout['nombre']) ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Precio unitario</span><span><?= number_format($checkout['precio_unit'],2,",",".") ?> €</span>
                    </div>
                    <div class="summary-line">
                        <span>Cantidad</span><span><?= (int)$checkout['cantidad'] ?></span>
                    </div>
                <?php else: ?>
                    <div class="summary-line">
                        <span>Productos</span><span><?= count($checkout) ?> artículo(s)</span>
                    </div>
                <?php endif; ?>

                <div class="summary-line">
                    <span>Subtotal</span><span><?= number_format($subtotal,2,",",".") ?> €</span>
                </div>
                <div class="summary-line">
                    <span>IVA (21%)</span><span><?= number_format($iva,2,",",".") ?> €</span>
                </div>
                <div class="summary-line summary-total">
                    <span>Total</span><span><?= number_format($total,2,",",".") ?> €</span>
                </div>

                <?php if ($paso>=2): ?>
                    <div class="summary-envio">
                        <h4>Envío a:</h4>
                        <p><?= htmlspecialchars($envio['nombre']) ?></p>
                        <p><?= htmlspecialchars($envio['direccion']) ?></p>
                        <p><?= htmlspecialchars($envio['cp']) ?> <?= htmlspecialchars($envio['ciudad']) ?></p>
                        <p>Tel: <?= htmlspecialchars($envio['telefono']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($paso>=3): ?>
                    <div class="summary-envio">
                        <h4>Método de pago:</h4>
                        <p>
                            <?php
                            switch ($pago['metodo']) {
                                case 'tarjeta':       echo "Tarjeta de crédito/débito"; break;
                                case 'paypal':        echo "PayPal"; break;
                                case 'bizum':         echo "Bizum"; break;
                                case 'transferencia': echo "Transferencia bancaria"; break;
                            }
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

            </aside>

        </div>
    </div>
</main>

<?php include("footer.php"); ?>

<script src="/initial-d/js/script.js"></script>

</body>
</html>