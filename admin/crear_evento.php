<?php
session_start();
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../acceso/login.php");
    exit();
}

$errores = [];
$mensajeExito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tema = trim($_POST['tema'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $imagenNombre = null;
    $color_primario = $_POST['color_primario'] ?? '#007BFF';

    if (!validar_requerido($nombre)) $errores[] = "El nombre del evento es obligatorio.";
    if (!validar_requerido($descripcion)) $errores[] = "La descripción es obligatoria.";
    if (!validar_requerido($tema)) $errores[] = "El tema es obligatorio.";
    if (empty($fecha_inicio) || empty($fecha_fin)) $errores[] = "Debes indicar ambas fechas.";
    if ($fecha_inicio > $fecha_fin) $errores[] = "La fecha de inicio no puede ser mayor que la de fin.";

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $permitidos = ['image/jpeg', 'image/png'];
        if (in_array($_FILES['imagen']['type'], $permitidos)) {
            $tmp = $_FILES['imagen']['tmp_name'];
            $imagenNombre = uniqid() . "_" . basename($_FILES['imagen']['name']);
            $rutaDestino = "../imagenes/eventos/" . $imagenNombre;
            if (!move_uploaded_file($tmp, $rutaDestino)) {
                $errores[] = "No se pudo guardar la imagen.";
            }
        } else {
            $errores[] = "Formato de imagen no permitido. Usa JPG o PNG.";
        }
    }

    if (empty($errores)) {
        try {
            $conexion = conectarPDO($host, $user, $password, $bbdd);
            $stmt = $conexion->prepare("
                INSERT INTO eventos (nombre, descripcion, tema, fecha_inicio, fecha_fin, imagen, color_primario) 
                VALUES (:nombre, :descripcion, :tema, :fecha_inicio, :fecha_fin, :imagen, :color_primario)
            ");
            $stmt->execute([
                "nombre" => $nombre,
                "descripcion" => $descripcion,
                "tema" => $tema,
                "fecha_inicio" => $fecha_inicio,
                "fecha_fin" => $fecha_fin,
                "imagen" => $imagenNombre,
                "color_primario" => $color_primario
            ]);
            $mensajeExito = "✅ Rally creado correctamente.";
        } catch (PDOException $e) {
            $errores[] = "Error al insertar el evento: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Rally - Admin</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header class="encabezado-login">
    <h1>📅 Crear Nuevo Rally Fotográfico</h1>
</header>

<nav>
    <ul>
        <li><a href="../index.php">Inicio</a></li>
        <li><a href="panel.php">Administrar Usuarios</a></li>
        <li><a href="validar_fotos.php">Validar Fotos</a></li>
        <li><a href="crear_evento.php">Crear Rally</a></li>
        <li><a href="../acceso/logout.php">Cerrar Sesión</a></li>
    </ul>
</nav>

<main>
    <?php if (!empty($mensajeExito)): ?>
        <div class="mensaje-exito"><?= htmlspecialchars($mensajeExito) ?></div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="mensaje-error">
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <p>
            <label for="nombre">Nombre del evento:</label>
            <input type="text" name="nombre" id="nombre" required>
        </p>
        <p>
            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" id="descripcion" rows="4" required></textarea>
        </p>
        <p>
            <label for="tema">Tema:</label>
            <input type="text" name="tema" id="tema" required>
        </p>
        <p>
            <label for="fecha_inicio">Fecha de inicio:</label>
            <input type="date" name="fecha_inicio" id="fecha_inicio" required>
        </p>
        <p>
            <label for="fecha_fin">Fecha de fin:</label>
            <input type="date" name="fecha_fin" id="fecha_fin" required>
        </p>
        <p>
            <label for="imagen">Imagen representativa:</label>
            <input type="file" name="imagen" id="imagen" accept="image/png, image/jpeg" required>
        </p>
        <div id="preview-container" style="text-align:center; margin-top:10px;">
            <img id="preview" src="#" alt="Previsualización" style="display:none; max-width:700px; max-height:400px; border:1px solid #ccc; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
        </div>
        <p>
            <label for="color_primario">Color representativo:</label>
            <input type="color" name="color_primario" id="color_primario" value="#007BFF">
        </p>
        <p>
            <button type="submit">Crear Evento</button>
        </p>
    </form>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

<script>
document.getElementById('imagen').addEventListener('change', function (e) {
    const file = e.target.files[0];
    const preview = document.getElementById('preview');

    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function (event) {
            preview.src = event.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }
});
</script>

</body>
</html>
