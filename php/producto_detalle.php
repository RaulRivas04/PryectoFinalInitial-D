<?php
session_start();
include("conexion.php");

// Id usuario logueado
$id_usuario = $_SESSION['id'] ?? 0;

// Inicializar mensaje de error
$errorMsg = null;
$mensaje_valoracion_ok = $_SESSION['mensaje_valoracion_ok'] ?? null;
$mensaje_valoracion_error = $_SESSION['mensaje_valoracion_error'] ?? null;
unset($_SESSION['mensaje_valoracion_ok'], $_SESSION['mensaje_valoracion_error']);

// VALIDAR ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $errorMsg = "ID de producto no válido.";
} else {
    $id = intval($_GET['id']);

// ACTUALIZAR VISITAS
$conn->query("UPDATE productos SET visitas = visitas + 1 WHERE id = $id");

// Registrar visita por usuario (si está logueado)
$id_user_visita = isset($_SESSION['id']) ? intval($_SESSION['id']) : "NULL";
$conn->query("
    INSERT INTO producto_visitas (id_producto, id_usuario)
    VALUES ($id, $id_user_visita)
");

    // GESTIONAR ENVÍO DE VALORACIÓN (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_valoracion']) && $id_usuario > 0) {
        $accion_val = $_POST['accion_valoracion'];

        if ($accion_val === 'guardar') {
            $puntuacion = isset($_POST['puntuacion']) ? (int)$_POST['puntuacion'] : 0;
            $comentario = trim($_POST['comentario'] ?? '');

            if ($puntuacion < 1 || $puntuacion > 5) {
                $_SESSION['mensaje_valoracion_error'] = "La puntuación debe estar entre 1 y 5 estrellas.";
            } else {
                // Comprobar si ya existe una valoración de este usuario para este producto
                $stmtCheck = $conn->prepare("SELECT id FROM valoraciones WHERE id_producto = ? AND id_usuario = ? LIMIT 1");
                $stmtCheck->bind_param("ii", $id, $id_usuario);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();

                if ($resCheck && $resCheck->num_rows > 0) {
                    // UPDATE
                    $rowVal = $resCheck->fetch_assoc();
                    $id_val = (int)$rowVal['id'];

                    $stmtUpdate = $conn->prepare("UPDATE valoraciones SET puntuacion = ?, comentario = ?, fecha = NOW() WHERE id = ?");
                    $stmtUpdate->bind_param("isi", $puntuacion, $comentario, $id_val);
                    if ($stmtUpdate->execute()) {
                        $_SESSION['mensaje_valoracion_ok'] = "Tu opinión se ha actualizado correctamente.";
                    } else {
                        $_SESSION['mensaje_valoracion_error'] = "No se pudo actualizar tu opinión. Inténtalo de nuevo.";
                    }
                } else {
                    // INSERT
                    $stmtIns = $conn->prepare("INSERT INTO valoraciones (id_usuario, id_producto, puntuacion, comentario) VALUES (?, ?, ?, ?)");
                    $stmtIns->bind_param("iiis", $id_usuario, $id, $puntuacion, $comentario);
                    if ($stmtIns->execute()) {
                        $_SESSION['mensaje_valoracion_ok'] = "¡Gracias por enviar tu opinión!";
                    } else {
                        $_SESSION['mensaje_valoracion_error'] = "No se pudo guardar tu opinión. Inténtalo de nuevo.";
                    }
                }
            }
        } elseif ($accion_val === 'eliminar') {
            // Eliminar la valoración del usuario para este producto
            $stmtDel = $conn->prepare("DELETE FROM valoraciones WHERE id_producto = ? AND id_usuario = ?");
            $stmtDel->bind_param("ii", $id, $id_usuario);
            if ($stmtDel->execute()) {
                $_SESSION['mensaje_valoracion_ok'] = "Tu opinión se ha eliminado.";
            } else {
                $_SESSION['mensaje_valoracion_error'] = "No se pudo eliminar tu opinión. Inténtalo de nuevo.";
            }
        }

        // Redirección para evitar reenvío de formulario
        header("Location: producto_detalle.php?id=" . $id);
        exit();
    }

    // CARGAR PRODUCTO
    $sql = "SELECT * FROM productos WHERE id = $id";
    $resultado = $conn->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        $producto = $resultado->fetch_assoc();
    } else {
        $errorMsg = "Producto no encontrado.";
    }
}

// Inicializar flags
$es_favorito = false;
$en_carrito = false;
$reservado_por_usuario = false;
$reservado_por_otro = false;
$es_vendido = false;
$estado_visual = '';

$valoracion_media = 0;
$total_opiniones = 0;
$valoraciones_lista = [];
$valoracion_usuario = null;

if (empty($errorMsg)) {
    // FAVORITOS
    if ($id_usuario > 0) {
        $fav_check = $conn->query(
            "SELECT id FROM favoritos WHERE id_usuario=$id_usuario AND id_producto=$id"
        );
        $es_favorito = $fav_check && $fav_check->num_rows > 0;
    }

    // CARRITO
    if ($id_usuario > 0) {
        $carrito_check = $conn->query(
            "SELECT id FROM carrito WHERE id_usuario=$id_usuario AND id_producto=$id"
        );
        $en_carrito = $carrito_check && $carrito_check->num_rows > 0;
    }

// RESERVAS
$reserva_check = $conn->query(
    "SELECT id_usuario FROM reservas WHERE id_producto = $id LIMIT 1"
);

if ($reserva_check && $reserva_check->num_rows > 0) {
    $fila_res = $reserva_check->fetch_assoc();

    if ((int)$fila_res['id_usuario'] === (int)$id_usuario) {
        $reservado_por_usuario = true;
    } else {
        $reservado_por_otro = true;
    }
}

// ESTADO ORIGINAL (BD)
$estado_original = $producto['estado'] ?? 'disponible'; // disponible | reservado | vendido

// FLAGS DE ESTADO
$es_vendido = ($estado_original === 'vendido');

// ESTADO VISUAL QUE VERÁ EL USUARIO
if ($es_vendido) {
    $estado_visual = "Vendido";
} elseif ($reservado_por_usuario) {
    $estado_visual = "Reservado por ti";
} elseif ($reservado_por_otro || $estado_original === 'reservado') {
    $estado_visual = "Reservado";
} else {
    $estado_visual = "Disponible";
}

// LÓGICA DE STOCK (CONSISTENTE)
$stock = max(0, (int)($producto['stock'] ?? 0));
$stock_estado = 'stock-en-stock';
$stock_texto  = 'En stock';

// Producto vendido = el stock SIEMPRE debe mostrar "Sin stock"
if ($es_vendido) {
    $stock_estado = 'stock-agotado';
    $stock_texto  = 'Sin stock';
}
// Sin stock pero NO vendido
elseif ($stock == 0) {
    $stock_estado = 'stock-agotado';
    $stock_texto  = 'Sin stock';
}
// Últimas unidades reales
elseif ($stock <= 3) {
    $stock_estado = 'stock-ultimas';
    $stock_texto  = '¡Últimas unidades!';
}

    // PRECIO FORMATEADO
    $precio_formateado = number_format((float)$producto['precio'], 2, ',', '.');

    // URL DEL PRODUCTO (para compartir, microdatos, etc.)
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $url_actual = $protocolo . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // VALORACIONES: MEDIA Y TOTAL
    $stmtResumen = $conn->prepare("
        SELECT 
            AVG(puntuacion) AS media,
            COUNT(*) AS total
        FROM valoraciones
        WHERE id_producto = ?
    ");
    $stmtResumen->bind_param("i", $id);
    $stmtResumen->execute();
    $resResumen = $stmtResumen->get_result();
    if ($resResumen && $rowRes = $resResumen->fetch_assoc()) {
        if ($rowRes['media'] !== null) {
            $valoracion_media = round((float)$rowRes['media'], 1);
        }
        $total_opiniones = (int)$rowRes['total'];
    }

    // VALORACIÓN DEL USUARIO ACTUAL (si está logueado)
    if ($id_usuario > 0) {
        $stmtUserVal = $conn->prepare("
            SELECT id, puntuacion, comentario, fecha 
            FROM valoraciones 
            WHERE id_producto = ? AND id_usuario = ?
            LIMIT 1
        ");
        $stmtUserVal->bind_param("ii", $id, $id_usuario);
        $stmtUserVal->execute();
        $resUserVal = $stmtUserVal->get_result();
        if ($resUserVal && $resUserVal->num_rows > 0) {
            $valoracion_usuario = $resUserVal->fetch_assoc();
        }
    }

    // LISTADO DE VALORACIONES (todas)
    $stmtLista = $conn->prepare("
        SELECT v.id, v.puntuacion, v.comentario, v.fecha, u.nombre 
        FROM valoraciones v
        JOIN usuarios u ON u.id = v.id_usuario
        WHERE v.id_producto = ?
        ORDER BY v.fecha DESC
    ");
    $stmtLista->bind_param("i", $id);
    $stmtLista->execute();
    $resLista = $stmtLista->get_result();
    if ($resLista && $resLista->num_rows > 0) {
        while ($row = $resLista->fetch_assoc()) {
            $valoraciones_lista[] = $row;
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

    <title>
        <?php
        if (!empty($errorMsg)) {
            echo "Producto - Initial D";
        } else {
            echo htmlspecialchars($producto['nombre']) . " | Detalle de producto - Initial D";
        }
        ?>
    </title>

    <!-- Meta básica SEO -->
    <meta name="description" content="<?php
        if (!empty($errorMsg)) {
            echo "Detalle de producto de Initial D.";
        } else {
            $desc = strip_tags($producto['descripcion'] ?? '');
            $desc = mb_substr($desc, 0, 155);
            echo htmlspecialchars($desc);
        }
    ?>">

    <!-- Open Graph para compartir en redes -->
    <?php if (empty($errorMsg)): ?>
        <meta property="og:title" content="<?php echo htmlspecialchars($producto['nombre']); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars($desc); ?>">
        <meta property="og:type" content="product">
        <meta property="og:url" content="<?php echo htmlspecialchars($url_actual); ?>">
        <meta property="og:image" content="<?php echo htmlspecialchars('/img/' . $producto['imagen']); ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="/initial-d/css/style.css">
    <link rel="stylesheet" href="/initial-d/css/productos.css">
        <link rel="stylesheet" href="/initial-d/css/checkout.css">

</head>
<body>

<?php include("header.php"); ?>

<main class="detalle-producto-page">
    <?php if (!empty($errorMsg)): ?>
        <section class="detalle-producto error-producto">
            <div class="contenedor-detalle">
                <p class="mensaje-error-detalle"><?php echo htmlspecialchars($errorMsg); ?></p>
                <a href="productos.php" class="btn volver">Volver al catálogo</a>
            </div>
        </section>
    <?php else: ?>

        <!-- BREADCRUMBS -->
        <nav class="breadcrumbs" aria-label="Ruta de navegación">
            <a href="../index.php">Inicio</a>
            <span>/</span>
            <a href="productos.php">Catálogo</a>
            <span>/</span>
            <span class="breadcrumb-actual"><?php echo htmlspecialchars($producto['nombre']); ?></span>
        </nav>

        <section class="detalle-producto">
            <div 
                class="contenedor-detalle" 
                data-producto-id="<?php echo (int)$producto['id']; ?>"
                data-stock="<?php echo (int)$producto['stock']; ?>"
                data-precio="<?php echo htmlspecialchars($producto['precio']); ?>"
            >
                <!-- COLUMNA IZQUIERDA: GALERÍA + RESUMEN -->
                <div class="columna-izquierda">
                    <div class="imagen-detalle-wrapper">

                <?php
                    // Construir array de imágenes disponibles (solo si existen)
                    $imagenes = [];

                    if (!empty($producto['imagen']))  $imagenes[] = $producto['imagen'];
                    if (!empty($producto['imagen2'])) $imagenes[] = $producto['imagen2'];
                    if (!empty($producto['imagen3'])) $imagenes[] = $producto['imagen3'];

                    // Imagen principal: la primera disponible o una imagen por defecto
                    $imgPrincipal = $imagenes[0] ?? 'no-image.jpg';
                ?>


                 <div class="galeria-producto">

                    <!-- MINIATURAS VERTICALES -->
                    <div class="galeria-miniaturas">

                        <?php foreach ($imagenes as $index => $img): ?>
                            <img 
                                class="miniatura <?php echo $index === 0 ? 'active' : ''; ?>" 
                                src="../img/<?php echo htmlspecialchars($img); ?>" 
                                data-full="../img/<?php echo htmlspecialchars($img); ?>" 
                                alt="Miniatura <?php echo $index + 1; ?>">
                        <?php endforeach; ?>
                    </div>

                            <!-- IMAGEN PRINCIPAL -->
                            <div class="galeria-principal">
                            <div class="galeria-badges">
                                <?php if ($reservado_por_usuario): ?>
                                    <span class="badge-reservado reservado-propio">Reservado por ti</span>
                                <?php elseif ($reservado_por_otro || $estado_original === 'reservado'): ?>
                                    <span class="badge-reservado reservado-ajeno">Reservado</span>
                                <?php endif; ?>
                            </div>


                                <img id="imagenPrincipal"
                                     src="../img/<?php echo htmlspecialchars($imgPrincipal); ?>" 
                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>">

                                <span class="badge-stock <?php echo $stock_estado; ?>">
                                    <?php echo htmlspecialchars($stock_texto); ?>
                                </span>
                            </div>
                        </div>

<!-- RESUMEN RÁPIDO -->
<div class="resumen-rapido">
    <ul>
        <li><span>Tipo:</span> <?php echo htmlspecialchars($producto['tipo']); ?></li>
        <li><span>Marca:</span> <?php echo htmlspecialchars($producto['marca']); ?></li>
        <li><span>Modelo:</span> <?php echo htmlspecialchars($producto['modelo']); ?></li>
        <li><span>Estado:</span> <?php echo htmlspecialchars($estado_visual); ?></li>
    </ul>
</div>

<!-- BLOQUE EXTRA — AUTOMÁTICO SEGÚN EL TIPO -->
<div class="detalle-extra-info">
    <h3>Características destacadas</h3>
    <ul>
        <?php
        $tipo = strtolower($producto['tipo']);

        /* PARA COCHES */
        if (strpos($tipo, 'coche') !== false || $tipo === 'car') {
            echo '
                <li>✔ Kilómetros certificados</li>
                <li>✔ Revisión mecánica completa</li>
                <li>✔ ITV al día</li>
                <li>✔ Garantía incluida</li>
                <li>✔ Entrega inmediata</li>
            ';
        }

        /* PARA MOTOS */
        elseif (strpos($tipo, 'moto') !== false || $tipo === 'motorcycle') {
            echo '
                <li>✔ Bajo consumo y mantenimiento</li>
                <li>✔ Revisión técnica realizada</li>
                <li>✔ Neumáticos en excelente estado</li>
                <li>✔ Garantía disponible</li>
                <li>✔ Lista para circular</li>
            ';
        }

        /* PARA ACCESORIOS U OTROS TIPOS */
        else {
            echo '
                <li>✔ Producto 100% original</li>
                <li>✔ Compatibilidad verificada</li>
                <li>✔ Material premium</li>
                <li>✔ Garantía de fabricante</li>
                <li>✔ Envío rápido disponible</li>
            ';
        }
        ?>
    </ul>
</div>

</div>
<!-- TABLA EXTRA DE INFORMACIÓN -->
<div class="tabla-extra-info">
    <h3>Información adicional</h3>

    <table>
        <tbody>
            <tr>
                <th>Publicación</th>
                <td><?php echo !empty($producto['fecha_creacion']) ? date("d/m/Y", strtotime($producto['fecha_creacion'])) : 'N/D'; ?></td>
            </tr>

            <tr>
                <th>Vendedor</th>
                <td>Tienda oficial Initial D</td>
            </tr>

            <tr>
                <th>Garantía</th>
                <td>Garantía incluida según categoría del producto</td>
            </tr>

            <tr>
                <th>Estado general</th>
                <td>
                    <?php
                    if ($estado_visual === "Vendido") {
                        echo "Producto vendido";
                    } elseif ($estado_visual === "Reservado por ti") {
                        echo "Reservado por el cliente";
                    } elseif ($estado_visual === "Reservado") {
                        echo "Actualmente reservado";
                    } else {
                        echo "Disponible para compra";
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <th>Tipo de producto</th>
                <td><?php echo htmlspecialchars($producto['tipo']); ?></td>
            </tr>
        </tbody>
    </table>
</div>

</div>

                <!-- COLUMNA DERECHA: INFO PRINCIPAL -->
                <div class="columna-derecha">
                    <header class="cabecera-detalle">
                        <h1 class="titulo-producto"><?php echo htmlspecialchars($producto['nombre']); ?></h1>

                        <!-- VALORACIÓN RESUMEN -->
                        <div class="rating-producto">
                            <?php if ($total_opiniones > 0): ?>
                                <div class="estrellas">
                                    <?php
                                    $estrellasLlenas = floor($valoracion_media);
                                    $estrellaMedia   = ($valoracion_media - $estrellasLlenas >= 0.5);
                                    $estrellasVacias = 5 - $estrellasLlenas - ($estrellaMedia ? 1 : 0);

                                    for ($i = 0; $i < $estrellasLlenas; $i++) {
                                        echo '<span class="star llena">★</span>';
                                    }
                                    if ($estrellaMedia) {
                                        echo '<span class="star media">★</span>';
                                    }
                                    for ($i = 0; $i < $estrellasVacias; $i++) {
                                        echo '<span class="star vacia">★</span>';
                                    }
                                    ?>
                                </div>
                                <span class="rating-texto">
                                    <?php echo number_format($valoracion_media, 1, ',', '.'); ?>/5
                                    (<?php echo $total_opiniones; ?> opiniones)
                                </span>
                            <?php else: ?>
                                <span class="rating-texto rating-sin-opiniones">
                                    Sin valoraciones todavía
                                </span>
                            <?php endif; ?>
                        </div>
                    </header>

                    <!-- MENSAJES DE VALORACIÓN -->
                    <?php
                    if ($mensaje_valoracion_ok) {
                        echo "<p class='mensaje-accion ok'>" . htmlspecialchars($mensaje_valoracion_ok) . "</p>";
                    }
                    if ($mensaje_valoracion_error) {
                        echo "<p class='mensaje-accion error'>" . htmlspecialchars($mensaje_valoracion_error) . "</p>";
                    }
                    ?>

                    <!-- PRECIO + STOCK -->
                    <div class="bloque-precio">
                        <div class="precio-principal">
                            <?php
                            $precio = (float)$producto['precio'];
                            $precio_oferta = isset($producto['precio_oferta']) ? (float)$producto['precio_oferta'] : 0;
                            $tiene_oferta = $precio_oferta > 0 && $precio_oferta < $precio;
                            ?>

                            <?php if ($tiene_oferta): ?>
                                <!-- PRECIO CON OFERTA -->
                                <div class="precio-oferta-detalle">
                                    <span class="badge-oferta-activa">🔥 OFERTA</span>
                                    <span class="precio-actual precio-con-descuento"><?php echo number_format($precio_oferta, 2, ',', '.'); ?> €</span>
                                    <span class="precio-antes precio-tachado-detalle"><?php echo number_format($precio, 2, ',', '.'); ?> €</span>
                                    <?php
                                    $descuento = round((($precio - $precio_oferta) / $precio) * 100);
                                    ?>
                                    <span class="descuento-porcentaje">Ahorras <?php echo $descuento; ?>%</span>
                                </div>
                            <?php else: ?>
                                <!-- PRECIO NORMAL -->
                                <span class="precio-actual"><?php echo $precio_formateado; ?> €</span>
                            <?php endif; ?>

                            <span class="precio-iva">Precio IVA incluido</span>
                        </div>

                        <div class="info-stock-detalle">
                            <span class="texto-stock">
                                <?php if ($es_vendido): ?>
                                    Este producto ya está vendido.
                                <?php elseif ($stock <= 0): ?>
                                    Actualmente no hay unidades disponibles.
                                <?php elseif ($stock <= 3): ?>
                                    Quedan solo <?php echo $stock; ?> en stock. ¡No te quedes sin él!
                                <?php else: ?>
                                    Stock disponible: <?php echo $stock; ?> unidad(es).
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <!-- DESCRIPCIÓN -->
                    <section class="descripcion-detalle">
                        <h2>Descripción</h2>
                        <p><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
                    </section>

                    <!-- FICHA TÉCNICA / DETALLES EXTRA -->
                    <?php
                    $camposTecnicos = [
                        'anio'        => 'Año',
                        'kilometros'  => 'Kilómetros',
                        'combustible' => 'Combustible',
                        'transmision' => 'Transmisión',
                        'color'       => 'Color',
                        'potencia'    => 'Potencia',
                        'plazas'      => 'Plazas'
                    ];

                    $tieneFicha = false;
                    foreach ($camposTecnicos as $campo => $label) {
                        if (!empty($producto[$campo] ?? null)) {
                            $tieneFicha = true;
                            break;
                        }
                    }
                    ?>

                    <?php if ($tieneFicha): ?>
                        <section class="ficha-tecnica">
                            <h2>Ficha técnica</h2>
                            <table>
                                <tbody>
                                    <?php foreach ($camposTecnicos as $campo => $label): ?>
                                        <?php if (!empty($producto[$campo] ?? null)): ?>
                                            <tr>
                                                <th><?php echo htmlspecialchars($label); ?></th>
                                                <td><?php echo htmlspecialchars($producto[$campo]); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </section>
                    <?php endif; ?>

<!-- ACCIONES PRINCIPALES -->
<section class="acciones-detalle">
<?php if(isset($_SESSION['id'])): ?>

    <?php if ($es_vendido): ?>
        <p class="aviso-vendido">Este producto está vendido.</p>

    <?php elseif ($reservado_por_otro): ?>
        <p class="aviso-reservado">Este producto ya está reservado por otro usuario.</p>

    <?php else: ?>
        <!-- Comprar -->
        <form action="checkout.php" method="POST" style="display:inline;">
            <input type="hidden" name="id_producto" value="<?php echo (int)$producto['id']; ?>">
            <button type="submit" class="btn btn-primario">
                Comprar ahora
            </button>
        </form>
    <?php endif; ?>

    <!-- FORMULARIO PRINCIPAL DE ACCIONES (CARRITO / RESERVA / FAVORITO) -->
    <form method="POST" action="usuario/acciones_producto.php" class="acciones-producto" onsubmit="guardarPosicionScroll()">
        <input type="hidden" name="id_producto" value="<?php echo (int)$producto['id']; ?>">

        <?php if (!$es_vendido && !$reservado_por_otro): ?>

            <!-- Carrito -->
            <button type="submit" name="accion" value="carrito" 
                class="btn btn-secundario btn-icon carrito-icon <?php echo $en_carrito ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2z"/>
                    <path d="M7 4h14v2H7V4zm0 4h14v2H7V8zm0 4h14v2H7v-2z"/>
                </svg>
                <?php echo $en_carrito ? 'En tu carrito' : 'Añadir al carrito'; ?>
            </button>

            <!-- Reservar -->
            <button type="submit" name="accion" value="reservar" 
                class="btn btn-outline btn-icon reserva-icon <?php echo $reservado_por_usuario ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
                <?php echo $reservado_por_usuario ? 'Cancelar reserva' : 'Reservar'; ?>
            </button>

            <?php endif; ?>

            <!-- Favorito -->
            <button type="submit" name="accion" value="favorito" 
                class="btn btn-icon btn-favorito <?php echo $es_favorito ? 'active' : ''; ?>" 
                aria-label="Añadir a favoritos">
                <svg class="heart-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 
                            2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 
                            4.5 2.09C13.09 3.81 14.76 3 
                            16.5 3 19.58 3 22 5.42 22 8.5c0 
                            3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
                <?php echo $es_favorito ? 'En favoritos' : 'Añadir a favoritos'; ?>
            </button>

    </form>

<?php else: ?>
    <p class="mensaje-login-requerido">
        <a href="login.php">Inicia sesión</a> para comprar, reservar, añadir a favoritos y dejar tu opinión.
    </p>
<?php endif; ?>
</section>


                    <!-- BLOQUE DE VALORACIONES DE CLIENTES -->
                    <section class="bloque-valoraciones">
                        <h2>Opiniones de clientes</h2>

                        <?php if ($id_usuario > 0): ?>
                            <div class="tu-valoracion">
                                <h3><?php echo $valoracion_usuario ? 'Tu opinión' : 'Escribe tu opinión'; ?></h3>

                                <form method="POST" action="producto_detalle.php?id=<?php echo (int)$producto['id']; ?>" class="form-valoracion">
                                    <input type="hidden" name="accion_valoracion" value="guardar">

                                    <!-- SISTEMA DE ESTRELLAS INTERACTIVAS -->
                                    <label>Puntuación</label>

                                    <div class="rating-input" data-valor="<?php echo $valoracion_usuario['puntuacion'] ?? 0; ?>">
                                        <input type="hidden" name="puntuacion" id="puntuacion"
                                               value="<?php echo $valoracion_usuario['puntuacion'] ?? 0; ?>">

                                        <span class="star-input" data-value="1">★</span>
                                        <span class="star-input" data-value="2">★</span>
                                        <span class="star-input" data-value="3">★</span>
                                        <span class="star-input" data-value="4">★</span>
                                        <span class="star-input" data-value="5">★</span>
                                    </div>

                                    <label for="comentario">Comentario (opcional)</label>
                                    <textarea name="comentario" id="comentario" rows="4" placeholder="Cuenta tu experiencia..."><?php
                                        echo htmlspecialchars($valoracion_usuario['comentario'] ?? '');
                                    ?></textarea>

                                    <div class="acciones-valoracion">
                                        <button type="submit" class="btn btn-primario">
                                            <?php echo $valoracion_usuario ? 'Actualizar opinión' : 'Enviar opinión'; ?>
                                        </button>

                                        <?php if ($valoracion_usuario): ?>
                                            <button type="button" 
                                                    class="btn btn-outline btn-eliminar-valoracion"
                                                    onclick="mostrarModalEliminar()">
                                                Eliminar mi opinión
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <p class="mensaje-login-requerido">
                                <a href="login.php">Inicia sesión</a> para dejar tu opinión sobre este producto.
                            </p>
                        <?php endif; ?>

                        <div class="lista-valoraciones">
                            <?php if ($total_opiniones > 0): ?>
                                <?php foreach ($valoraciones_lista as $val): ?>
                                    <article class="item-valoracion">
                                        <header>
                                            <strong class="nombre-usuario">
                                                <?php echo htmlspecialchars($val['nombre']); ?>
                                            </strong>
                                            <span class="fecha-valoracion">
                                                <?php echo date("d/m/Y", strtotime($val['fecha'])); ?>
                                            </span>
                                        </header>
                                        <div class="estrellas">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo '<span class="star '.($i <= (int)$val['puntuacion'] ? 'llena' : 'vacia').'">★</span>';
                                            }
                                            ?>
                                        </div>
                                        <?php if (!empty($val['comentario'])): ?>
                                            <p class="comentario-valoracion">
                                                <?php echo nl2br(htmlspecialchars($val['comentario'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="sin-valoraciones">Todavía no hay opiniones. Sé el primero en opinar.</p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- BLOQUE CONTACTO / INFO EXTRA -->
                    <section class="bloque-contacto-info">
                        <div class="column">
                            <form method="POST" action="contacto.php">
                                <input type="hidden" name="producto_interes" value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                <button type="submit" class="btn btn-ghost" style="width: 100%; text-align: center;">
                                    Solicitar información / reserva
                                </button>
                            </form>
                        </div>
                        <div class="column info-extra">
                            <ul>
                                <li>✔ Atención personalizada</li>
                                <li>✔ Posibilidad de reservar el vehículo</li>
                                <li>✔ Opción de financiación (consúltanos)</li>
                            </ul>
                        </div>
                    </section>

                        <!-- COMPARTIR -->
                <section class="compartir-producto">
                    <span class="compartir-titulo">Compartir:</span>

                    <div class="compartir-botones">

                        <!-- FACEBOOK -->
                        <a class="btn-share btn-facebook"
                        href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($url_actual); ?>"
                        target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24">
                                <path d="M22 12a10 10 0 1 0-11.5 9.9v-7h-2v-3h2v-2.3c0-2 1.2-3.1 3-3.1.9 0 1.8.1 1.8.1v2h-1c-1 0-1.3.6-1.3 1.2V12h2.3l-.4 3h-1.9v7A10 10 0 0 0 22 12"/>
                            </svg>
                            Facebook
                        </a>

                        <!-- X / TWITTER -->
                        <a class="btn-share btn-x"
                        href="https://twitter.com/intent/tweet?url=<?php echo urlencode($url_actual); ?>&text=<?php echo urlencode($producto['nombre']); ?>"
                        target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24">
                                <path d="M18.6 2H22l-7.6 8.3L23 22h-6.6l-5.2-6.8L5.4 22H2l8.1-8.9L1 2h6.7l4.7 6.2L18.6 2z"/>
                            </svg>
                            X
                        </a>

                        <!-- WHATSAPP -->
                        <a class="btn-share btn-whatsapp"
                        href="https://wa.me/?text=<?php echo urlencode($producto['nombre'] . ' - ' . $url_actual); ?>"
                        target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 2A10 10 0 0 0 3 17l-1 4 4-1a10 10 0 1 0 6-18zm5 13.5c-.2.6-1 1-1.6 1.1-.4 0-.8 0-1.2-.1-.6-.1-1.3-.4-2.2-.9a12.6 12.6 0 0 1-2.2-1.7c-.6-.6-1.2-1.4-1.6-2.2-.2-.4-.3-.9-.4-1.3 0-.5.2-1 .5-1.3l.7-.7c.2-.2.4-.3.7-.3h.5l.6 1.2c.2.4.2.6 0 .9l-.3.5c-.1.2-.2.3-.1.5.2.5.7 1.1 1.2 1.6.5.5 1.1 1 1.7 1.2.2.1.4 0 .6-.1l.6-.3c.3-.2.5-.1.8 0l1.2.6c.3.2.5.3.6.5.2.3.2.7 0 1z"/>
                            </svg>
                            WhatsApp
                        </a>

                    </div>
                </section>


                    <!-- VOLVER -->
                    <div class="volver-catalogo">
                        <a href="productos.php" class="btn volver">Volver al catálogo</a>
                    </div>
                </div>
            </div>
        </section>

        <?php
        // PRODUCTOS RELACIONADOS (mismo tipo, excluyendo el actual)
        $relacionados = [];
        if (!empty($producto['tipo'])) {
            $tipo = $conn->real_escape_string($producto['tipo']);
            $sql_rel = "SELECT id, nombre, precio, imagen 
                        FROM productos 
                        WHERE tipo = '$tipo' AND id != $id 
                        ORDER BY RAND() 
                        LIMIT 4";
            $res_rel = $conn->query($sql_rel);
            if ($res_rel && $res_rel->num_rows > 0) {
                while ($row = $res_rel->fetch_assoc()) {
                    $relacionados[] = $row;
                }
            }
        }
        ?>

        <?php if (!empty($relacionados)): ?>
            <section class="productos-relacionados">
                <div class="contenedor-relacionados">
                    <h2>También te puede interesar</h2>
                    <div class="grid-relacionados">
                        <?php foreach ($relacionados as $rel): ?>
                            <article class="card-relacionado">
                                <a href="producto_detalle.php?id=<?php echo (int)$rel['id']; ?>">
                                    <div class="card-relacionado-img">
                                        <img src="../img/<?php echo htmlspecialchars($rel['imagen']); ?>" 
                                            alt="<?php echo htmlspecialchars($rel['nombre']); ?>">
                                    </div>
                                    <div class="card-relacionado-body">
                                        <h3><?php echo htmlspecialchars($rel['nombre']); ?></h3>
                                        <span class="precio-relacionado">
                                            <?php echo number_format((float)$rel['precio'], 2, ',', '.'); ?> €
                                        </span>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    <?php endif; ?>

    <!-- TOAST para mensajes de acciones (favoritos, carrito, reservas) -->
    <?php if (isset($_SESSION['mensaje'])): ?>
    <div id="toast" class="toast <?= $_SESSION['tipo_mensaje'] === 'error' ? 'toast-error' : 'toast-ok' ?>">
        <?= htmlspecialchars($_SESSION['mensaje']) ?>
    </div>
    <?php 
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
    endif; 
    ?>

    <!-- Modal de confirmación para eliminar opinión -->
    <div id="modalEliminarOpinion" class="modal-eliminar-opinion" style="display: none;">
        <div class="modal-backdrop" onclick="cerrarModalEliminar()"></div>
        <div class="modal-contenido">
            <div class="modal-icono">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
            </div>
            <h3>¿Eliminar tu opinión?</h3>
            <p>Esta acción no se puede deshacer. Tu valoración y comentario serán eliminados permanentemente.</p>
            <div class="modal-acciones">
                <button type="button" class="btn-modal-cancelar" onclick="cerrarModalEliminar()">Cancelar</button>
                <form method="POST" action="producto_detalle.php?id=<?php echo (int)$producto['id']; ?>" style="display: inline;">
                    <input type="hidden" name="accion_valoracion" value="eliminar">
                    <button type="submit" class="btn-modal-eliminar">Eliminar opinión</button>
                </form>


            </div>
        </div>
    </div>
</main>

<?php include("footer.php"); ?>

<script src="/initial-d/js/script.js"></script>

</body>
</html>