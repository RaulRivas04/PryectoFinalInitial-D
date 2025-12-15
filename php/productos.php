<?php
session_start();
include("conexion.php");

// Id usuario
$id_usuario = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

// Filtros
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$orden    = isset($_GET['orden']) ? $_GET['orden'] : 'recientes';
$tipo     = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';

// Consulta base
$sql    = "SELECT * FROM productos WHERE 1";
$params = [];
$types  = '';

if ($busqueda !== '') {
    $sql .= " AND (nombre LIKE ? OR marca LIKE ? OR modelo LIKE ?)";
    $like   = "%$busqueda%";
    $params = [$like, $like, $like];
    $types  = "sss";
}

if ($tipo !== '') {
    $sql    .= " AND tipo = ?";
    $params[] = $tipo;
    $types   .= "s";
}

switch ($orden) {
    case 'precio_asc':
        $sql .= " ORDER BY precio ASC";
        break;
    case 'precio_desc':
        $sql .= " ORDER BY precio DESC";
        break;
    case 'nombre_asc':
        $sql .= " ORDER BY nombre ASC";
        break;
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado        = $stmt->get_result();
$total_productos  = $resultado->num_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="../img/logo-web.png">

<title>Catálogo - Initial D</title>

<link rel="stylesheet" href="/initial-d/css/style.css">
<link rel="stylesheet" href="/initial-d/css/productos.css">

</head>
<body>

<?php include("header.php"); ?>

<main class="catalogo">

  <header class="catalogo-header">
    <div>
      <h2>Catálogo de productos</h2>

      <p class="catalogo-resumen">
        <?php if ($busqueda !== ''): ?>
          Mostrando <strong><?= $total_productos ?></strong> resultado(s) para 
          "<strong><?= htmlspecialchars($busqueda) ?></strong>".
        <?php else: ?>
          Hay <strong><?= $total_productos ?></strong> producto(s) disponibles.
        <?php endif; ?>
      </p>
    </div>

    <form class="catalogo-filtros" method="GET">

      <div class="campo-filtro">
        <label for="q">Buscar</label>
        <input type="text" id="q" name="q"
               placeholder="Buscar por nombre, marca o modelo..."
               value="<?= htmlspecialchars($busqueda) ?>"
               onkeyup="autoBuscar()">
      </div>

      <div class="campo-filtro">
        <label for="orden">Ordenar por</label>
        <select name="orden" id="orden" onchange="autoOrden()">
          <option value="recientes"   <?= $orden=='recientes' ? 'selected' : '' ?>>Más recientes</option>
          <option value="precio_asc"  <?= $orden=='precio_asc' ? 'selected' : '' ?>>Precio: de menor a mayor</option>
          <option value="precio_desc" <?= $orden=='precio_desc' ? 'selected' : '' ?>>Precio: de mayor a menor</option>
          <option value="nombre_asc"  <?= $orden=='nombre_asc' ? 'selected' : '' ?>>Nombre A-Z</option>
        </select>
      </div>
      
      <div class="campo-filtro">
          <label for="tipo">Categoría</label>
          <select name="tipo" id="tipo" onchange="autoOrden()">
              <option value="">Todos</option>
              <option value="Coche"      <?= ($tipo=='Coche' ? 'selected' : '') ?>>Coches</option>
              <option value="Moto"       <?= ($tipo=='Moto' ? 'selected' : '') ?>>Motos</option>
              <option value="Accesorio"  <?= ($tipo=='Accesorio' ? 'selected' : '') ?>>Accesorios</option>
          </select>
      </div>

      <button type="button" class="btn-filtrar" onclick="cancelarFiltros()">Cancelar</button>

    </form>

  </header>

  <!-- TOAST -->
  <?php if (isset($_SESSION['mensaje'])): ?>
  <div id="toast" class="toast <?= $_SESSION['tipo_mensaje'] === 'error' ? 'toast-error' : 'toast-ok' ?>">
      <?= htmlspecialchars($_SESSION['mensaje']) ?>
  </div>
  <?php 
  unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
  endif; 
  ?>


  <div class="productos">

    <?php if ($resultado->num_rows > 0): ?>
    <?php 
    // Calcular "nuevo" por fecha:
    $hoy = new DateTime();
    while ($producto = $resultado->fetch_assoc()):
      $id_producto = (int)$producto['id'];

      // RESERVAS
      $res = $conn->query("SELECT id_usuario FROM reservas WHERE id_producto=$id_producto LIMIT 1");
      $reservado_por_otro    = false;
      $reservado_por_usuario = false;

      if ($res && $res->num_rows > 0) {
          $r = $res->fetch_assoc();
          if ((int)$r['id_usuario'] === $id_usuario) $reservado_por_usuario = true;
          else $reservado_por_otro = true;
      }

      // FAVORITOS
      $es_favorito = false;
      if ($id_usuario > 0) {
        $fav = $conn->query("SELECT id FROM favoritos 
                             WHERE id_usuario=$id_usuario 
                             AND id_producto=$id_producto 
                             LIMIT 1");
        $es_favorito = ($fav && $fav->num_rows > 0);
      }

      // VALORACIONES
      $valoracion_media = 0;
      $total_resenas    = 0;

      $val = $conn->query("
        SELECT AVG(puntuacion) AS media, COUNT(*) AS total
        FROM valoraciones
        WHERE id_producto = $id_producto
      ");

      if ($val && $val->num_rows > 0) {
          $d = $val->fetch_assoc();
          if ($d['media'] !== null) $valoracion_media = round($d['media'], 1);
          $total_resenas = (int)$d['total'];
      }

      // BADGES AUTOMÁTICOS
      $badges = [];

      // NUEVO = fecha_creacion <= 10 días
      if (!empty($producto['fecha_creacion'])) {
          try {
              $fechaProd = new DateTime($producto['fecha_creacion']);
              $diff = $hoy->diff($fechaProd);
              // Solo si el producto es RECIENTE (fecha_creacion < hoy) y dentro de 10 días
              if ($diff !== false && $diff->days <= 10 && $fechaProd <= $hoy) {
                  $badges[] = ['label' => 'Nuevo', 'class' => 'badge-new'];
              }
          } catch (Exception $e) {}
      }

      // OFERTA = precio_oferta < precio
      $precio       = (float)$producto['precio'];
      $precioOferta = isset($producto['precio_oferta']) ? (float)$producto['precio_oferta'] : 0;
      $tieneOferta  = ($precioOferta > 0 && $precioOferta < $precio);
      if ($tieneOferta) {
          $badges[] = ['label' => 'Oferta', 'class' => 'badge-oferta'];
      }

      // DESTACADO
      if (!empty($producto['destacado']) && (int)$producto['destacado'] === 1) {
          $badges[] = ['label' => 'Destacado', 'class' => 'badge-destacado'];
      }

      // TOP VENTAS = ventas >= 5
      if (!empty($producto['ventas']) && (int)$producto['ventas'] >= 5) {
          $badges[] = ['label' => 'Top ventas', 'class' => 'badge-top'];
      }

        // BADGE ESTADO (disponible / reservado / vendido)
        // Prioridad: Vendido > Reservado (por ti) > Reservado (otro) > estado en productos
        $claseEstado = '';
        $textoEstado = '';

          if (!empty($producto['estado']) && $producto['estado'] === 'vendido') {
              $claseEstado = 'badge-estado-vendido';
              $textoEstado = 'Sin stock';


        } elseif ($reservado_por_otro) {
          $claseEstado = 'badge-estado-reservado';
          $textoEstado = 'Reservado';

        } elseif (!empty($producto['estado']) && $producto['estado'] === 'reservado') {
         
          $claseEstado = 'badge-estado-reservado';
          $textoEstado = 'Reservado';

        } elseif (!empty($producto['estado']) && $producto['estado'] === 'disponible') {
          $claseEstado = 'badge-estado-disponible';
          $textoEstado = 'Disponible';
        }
    ?>

    <article class="producto">

      <!-- BADGES -->
      <div class="producto-badges">
        <?php if ($textoEstado): ?>
          <span class="badge-estado <?= $claseEstado; ?>">
            <?= htmlspecialchars($textoEstado); ?>
          </span>
        <?php endif; ?>

        <?php foreach ($badges as $b): ?>
          <span class="badge <?= $b['class']; ?>">
            <?= htmlspecialchars($b['label']); ?>
          </span>
        <?php endforeach; ?>
      </div>

      <div class="imagen-producto">
        <img src="../img/<?= htmlspecialchars($producto['imagen']) ?>" 
             alt="<?= htmlspecialchars($producto['nombre']) ?>">
      </div>

      <h3 class="prod-nombre"><?= htmlspecialchars($producto['nombre']); ?></h3>

      <!-- Estrellas dinámicas -->
      <div class="prod-valoracion">
        <?php if ($total_resenas > 0): ?>
            <?php
            $llenas    = floor($valoracion_media);
            $mediaStar = ($valoracion_media - $llenas >= 0.5);
            $vacias    = 5 - $llenas - ($mediaStar ? 1 : 0);
            ?>
            <div class="stars">
                <?php for ($i=0;$i<$llenas;$i++): ?><span class="star llena">★</span><?php endfor; ?>
                <?php if ($mediaStar): ?><span class="star media">★</span><?php endif; ?>
                <?php for ($i=0;$i<$vacias;$i++): ?><span class="star vacia">★</span><?php endfor; ?>
            </div>
            <span class="num-valoraciones">(<?= $total_resenas ?>)</span>
        <?php else: ?>
            <span class="num-valoraciones">Sin valoraciones</span>
        <?php endif; ?>
      </div>

      <?php
      // Precio + posible oferta
      ?>
      <p class="prod-precio">
        <?php if ($tieneOferta): ?>
            <?= number_format($precioOferta, 2, ",", "."); ?> €
            <span class="prod-precio-original">
                <?= number_format($precio, 2, ",", "."); ?> €
            </span>
        <?php else: ?>
            <?= number_format($precio, 2, ",", "."); ?> €
        <?php endif; ?>
      </p>

      <p class="prod-envio">
        Entrega GRATIS el <strong><?= date("j M", strtotime("+3 days")) ?></strong><br>
        Entrega más rápida: <strong><?= date("j M", strtotime("+1 day")) ?></strong>
      </p>

      <!-- BOTONES: Ver + Favorito -->
      <div class="botones-card">

          <a href="producto_detalle.php?id=<?= $id_producto ?>" class="btn">Ver producto</a>

          <?php if ($id_usuario > 0): ?>
              <form action="usuario/acciones_producto.php" method="POST" onsubmit="guardarPosicionScroll()">
                  <input type="hidden" name="id_producto" value="<?= $id_producto ?>">
                  <button name="accion" value="favorito" 
                          class="btn-fav <?= $es_favorito ? 'active' : '' ?>">
                      <span class="corazon">♥</span>
                  </button>
              </form>
          <?php endif; ?>

      </div>

    </article>

    <?php endwhile; ?>
    <?php else: ?>
      <p>No hay productos disponibles.</p>
    <?php endif; ?>

  </div>
</main>

<?php include("footer.php"); ?>

<script src="/initial-d/js/script.js"></script>

</body>
</html>