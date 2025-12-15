<?php
session_start();
include("../conexion.php");

// Comprobar si es admin
if (!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

/* AGREGAR PRODUCTO */
if (isset($_POST['accion']) && $_POST['accion'] === "agregar") {

    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $estado = $_POST['estado'];
    $stock = $_POST['stock'];

    $destacado   = isset($_POST['destacado']) ? 1 : 0;
    $top_ventas  = isset($_POST['top_ventas']) ? 1 : 0;
    $precio_oferta = (isset($_POST['oferta']) && !empty($_POST['precio_oferta'])) ? $_POST['precio_oferta'] : null;

    // SUBIR IMAGEN PRINCIPAL
    $imagen = $_FILES['imagen']['name'];
    move_uploaded_file($_FILES['imagen']['tmp_name'], "../../img/" . $imagen);

    // IMÁGENES SECUNDARIAS
    $imagen2 = "";
    $imagen3 = "";

    if (!empty($_FILES['imagen2']['name'])) {
        $imagen2 = $_FILES['imagen2']['name'];
        move_uploaded_file($_FILES['imagen2']['tmp_name'], "../../img/" . $imagen2);
    }

    if (!empty($_FILES['imagen3']['name'])) {
        $imagen3 = $_FILES['imagen3']['name'];
        move_uploaded_file($_FILES['imagen3']['tmp_name'], "../../img/" . $imagen3);
    }

    $conn->query("
        INSERT INTO productos
        (nombre, tipo, marca, modelo, descripcion, precio, precio_oferta, imagen,
         imagen2, imagen3, estado, stock,
         destacado, top_ventas, ventas, visitas)
        VALUES (
            '$nombre','$tipo','$marca','$modelo','$descripcion','$precio'," .
            ($precio_oferta ? "'$precio_oferta'" : "NULL") . ",
            '$imagen', '$imagen2', '$imagen3',
            '$estado','$stock',
            $destacado, $top_ventas,
            0, 0
        )
    ");
}

/* ELIMINAR PRODUCTO */
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    
    // Eliminar las visitas asociadas
    $conn->query("DELETE FROM producto_visitas WHERE id_producto=$id");
    
    // Eliminar el producto
    $conn->query("DELETE FROM productos WHERE id=$id");
    
    header("Location: productos_admin.php");
    exit();
}

/* EDITAR PRODUCTO - CARGAR DATOS */
$editando = false;
$producto_edit = null;

if (isset($_GET['editar'])) {
    $editando = true;
    $id_edit = intval($_GET['editar']);
    $producto_edit = $conn->query("SELECT * FROM productos WHERE id=$id_edit")->fetch_assoc();
}

/* GUARDAR EDICIÓN */
if (isset($_POST['accion']) && $_POST['accion'] === "editar_guardar") {

    $id = intval($_POST['id']);

    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $estado = $_POST['estado'];
    $stock = $_POST['stock'];

    $destacado   = isset($_POST['destacado']) ? 1 : 0;
    $top_ventas  = isset($_POST['top_ventas']) ? 1 : 0;
    $precio_oferta = (isset($_POST['oferta']) && !empty($_POST['precio_oferta'])) ? $_POST['precio_oferta'] : null;

    // ACTUALIZAR IMÁGENES
    $img_update = "";

    // principal
    if (!empty($_FILES['imagen']['name'])) {
        $imagen = $_FILES['imagen']['name'];
        move_uploaded_file($_FILES['imagen']['tmp_name'], "../../img/" . $imagen);
        $img_update .= ", imagen='$imagen'";
    }

    // imagen2
    if (!empty($_FILES['imagen2']['name'])) {
        $file2 = $_FILES['imagen2']['name'];
        move_uploaded_file($_FILES['imagen2']['tmp_name'], "../../img/" . $file2);
        $img_update .= ", imagen2='$file2'";
    }

    // imagen3
    if (!empty($_FILES['imagen3']['name'])) {
        $file3 = $_FILES['imagen3']['name'];
        move_uploaded_file($_FILES['imagen3']['tmp_name'], "../../img/" . $file3);
        $img_update .= ", imagen3='$file3'";
    }

    $conn->query("
        UPDATE productos SET
            nombre='$nombre',
            tipo='$tipo',
            marca='$marca',
            modelo='$modelo',
            descripcion='$descripcion',
            precio='$precio',
            estado='$estado',
            stock='$stock',
            destacado=$destacado,
            top_ventas=$top_ventas,
            precio_oferta=" . ($precio_oferta ? "'$precio_oferta'" : "NULL") . 
            $img_update . "
        WHERE id=$id
    ");

    header("Location: productos_admin.php");
    exit();
}

/* LISTADO FINAL */
$resultado = $conn->query("SELECT * FROM productos ORDER BY id DESC");

include("../header.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="../../img/logo-web.png">

<title>Gestión de Productos - Admin</title>

<link rel="stylesheet" href="../../css/style.css">
<link rel="stylesheet" href="../../css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>

<main class="admin-productos">

<h2>Gestión de productos</h2>

<h3><?= $editando ? "Editar producto #".$producto_edit['id'] : "Agregar nuevo producto" ?></h3>

<form method="POST" enctype="multipart/form-data" class="form-admin">

    <?php if ($editando): ?>
        <input type="hidden" name="accion" value="editar_guardar">
        <input type="hidden" name="id" value="<?= $producto_edit['id'] ?>">
    <?php else: ?>
        <input type="hidden" name="accion" value="agregar">
    <?php endif; ?>

    <input type="text" name="nombre" placeholder="Nombre" required 
           value="<?= $editando ? $producto_edit['nombre'] : "" ?>">

    <select name="tipo" required>
        <?php foreach(["Coche","Moto","Accesorio"] as $t): ?>
            <option value="<?= $t ?>" <?= ($editando && $producto_edit['tipo']==$t)?"selected":"" ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>

    <input type="text" name="marca" placeholder="Marca" value="<?= $editando ? $producto_edit['marca'] : "" ?>">
    <input type="text" name="modelo" placeholder="Modelo" value="<?= $editando ? $producto_edit['modelo'] : "" ?>">

    <textarea name="descripcion" placeholder="Descripción"><?= $editando ? $producto_edit['descripcion'] : "" ?></textarea>

    <input type="number" step="0.01" name="precio" placeholder="Precio" value="<?= $editando ? $producto_edit['precio'] : "" ?>">

    <!-- ESTADO -->
    <select name="estado">
        <option value="disponible" <?= ($editando && $producto_edit['estado']=="disponible")?'selected':'' ?>>Disponible</option>
        <option value="reservado" <?= ($editando && $producto_edit['estado']=="reservado")?'selected':'' ?>>Reservado</option>
        <option value="vendido" <?= ($editando && $producto_edit['estado']=="vendido")?'selected':'' ?>>Vendido</option>
    </select>

    <!-- STOCK -->
    <input type="number" name="stock" placeholder="Stock" min="0"
           value="<?= $editando ? $producto_edit['stock'] : "1" ?>">

    <!-- BADGES -->
    <label><input type="checkbox" name="destacado" <?= ($editando && $producto_edit['destacado'])?"checked":"" ?>> ⭐ Destacado</label>
    <label><input type="checkbox" name="top_ventas" <?= ($editando && $producto_edit['top_ventas'])?"checked":"" ?>> 🔥 Top ventas</label>
    <label><input type="checkbox" name="oferta" <?= ($editando && $producto_edit['precio_oferta'])?"checked":"" ?> onclick="toggleOferta()"> 💰 Oferta</label>

    <input type="number" step="0.01" name="precio_oferta" id="precio_oferta"
           placeholder="Precio de oferta" 
           style="display: <?= ($editando && $producto_edit['precio_oferta'])?'block':'none' ?>;"
           value="<?= $editando ? $producto_edit['precio_oferta'] : "" ?>">

    <!-- IMÁGENES -->
    <label>Imagen principal</label>
    <input type="file" name="imagen">

    <label>Imagen 2</label>
    <input type="file" name="imagen2">

    <label>Imagen 3</label>
    <input type="file" name="imagen3">

    <button type="submit"><?= $editando ? "Guardar cambios" : "Agregar producto" ?></button>

</form>

<!-- TABLA COMPLETA -->
<div class="table-wrapper">
<table class="admin-table">
<thead>
<tr>
    <th>ID</th>
    <th>Imagen</th>
    <th>Nombre</th>
    <th>Tipo</th>
    <th>Marca</th>
    <th>Modelo</th>
    <th>Estado</th>
    <th>Stock</th>
    <th>Precio</th>
    <th>Badges</th>
    <th>Ventas</th>
    <th>Visitas</th>
    <th>Fecha</th>
    <th>Acciones</th>
</tr>
</thead>

<tbody>
<?php while ($row = $resultado->fetch_assoc()): ?>
<tr>

    <td><?= $row['id'] ?></td>

    <td><img src="../../img/<?= $row['imagen'] ?>" class="admin-img"></td>

    <td><?= $row['nombre'] ?></td>

    <td><span class="badge badge-tipo"><?= $row['tipo'] ?></span></td>

    <td><?= $row['marca'] ?: '-' ?></td>

    <td><?= $row['modelo'] ?: '-' ?></td>

    <td><?= $row['estado'] ?></td>

    <td><?= $row['stock'] ?></td>

    <td>
        <?php if ($row['precio_oferta']): ?>
            <span class="precio-tachado"><?= $row['precio'] ?> €</span>
            <span class="precio-oferta"><?= $row['precio_oferta'] ?> €</span>
        <?php else: ?>
            <?= $row['precio'] ?> €
        <?php endif; ?>
    </td>

    <td>
        <?php if ($row['destacado']): ?>
            <span class="badge badge-destacado"><i class="fas fa-star"></i> Destacado</span>
        <?php endif; ?>

        <?php if ($row['top_ventas']): ?>
            <span class="badge badge-topventas"><i class="fas fa-fire"></i> Top ventas</span>
        <?php endif; ?>

        <?php if ($row['precio_oferta']): ?>
            <span class="badge badge-oferta"><i class="fas fa-tags"></i> Oferta</span>
        <?php endif; ?>
    </td>

    <td><?= $row['ventas'] ?></td>

    <td><?= $row['visitas'] ?></td>

    <td><?= $row['fecha_creacion'] ?></td>

    <td class="acciones">
        <a class="btn-action edit" href="productos_admin.php?editar=<?= $row['id'] ?>">
            <i class="fas fa-edit"></i>
        </a>

        <a class="btn-action delete" href="#" onclick="confirmarEliminar(<?= $row['id'] ?>); return false;">
            <i class="fas fa-trash-alt"></i>
        </a>
    </td>

</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<a href="dashboard.php" class="btn">Volver al Panel</a>

</main>

<?php include("../footer.php"); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="/initial-d/js/script.js"></script>
</body>
</html>
