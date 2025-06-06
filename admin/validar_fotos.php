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

try {
    $conexion = conectarPDO($host, $user, $password, $bbdd);

    // validar o eliminar foto
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['id_foto'])) {
        $id_foto = $_POST['id_foto'];

        if ($_POST['accion'] === 'validar') {
    $stmt = $conexion->prepare("UPDATE fotos SET validada = 1 WHERE id_foto = :id_foto");
    $stmt->execute(["id_foto" => $id_foto]);
    $mensajeExito = "📸 Foto validada correctamente.";
}

if ($_POST['accion'] === 'invalidar') {
    $motivo = $_POST['motivo'] ?? '';
    if ($motivo === 'Otros') {
        $motivo = $_POST['motivo_otro'] ?? '';
    }
    $motivo = trim($motivo);

    $stmt = $conexion->prepare("UPDATE fotos SET validada = -1, motivo_invalidacion = :motivo WHERE id_foto = :id_foto");
    $stmt->execute([
        "id_foto" => $id_foto,
        "motivo" => $motivo
    ]);
    $mensajeExito = "❌ Foto invalidada con motivo: " . htmlspecialchars($motivo);
}


if ($_POST['accion'] === 'eliminar') {
    // borrar votos
    $conexion->prepare("DELETE FROM votos WHERE id_foto = :id_foto")->execute(["id_foto" => $id_foto]);

    // obtener archivo para borrado físico
    $stmtFoto = $conexion->prepare("SELECT nombre_archivo FROM fotos WHERE id_foto = :id_foto");
    $stmtFoto->execute(["id_foto" => $id_foto]);
    $foto = $stmtFoto->fetch();

    if ($foto) {
        $ruta = "../imagenes/" . $foto['nombre_archivo'];
        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    // eliminar de la base de datos
    $conexion->prepare("DELETE FROM fotos WHERE id_foto = :id_foto")->execute(["id_foto" => $id_foto]);

    $mensajeExito = "Foto eliminada correctamente.";
}

    }

    // cargar fotos pendientes
    $stmt = $conexion->query("
        SELECT f.id_foto, f.nombre_archivo, f.fecha_subida, u.nombre AS usuario, e.nombre AS evento
        FROM fotos f
        INNER JOIN usuarios u ON f.id_usuario = u.id_usuario
        INNER JOIN eventos e ON f.id_evento = e.id_evento
        WHERE f.validada = 0
        ORDER BY f.fecha_subida ASC
    ");
    $fotosPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errores[] = "Error en la base de datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Validar Fotos - Admin</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header>
    <h1>Panel de Validación de Fotografías</h1>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="panel.php">Administrar Usuarios</a></li>
            <li><a href="validar_fotos.php">Validar Fotos</a></li>
            <li><a href="../acceso/logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>
</header>

<main>

    <?php if ($mensajeExito): ?>
        <div class="mensaje-exito"><?= htmlspecialchars($mensajeExito) ?></div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="mensaje-error">
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <h2>Fotografías Pendientes de Evaluación</h2>

    <?php if (!empty($fotosPendientes)): ?>
        <ul>
            <?php foreach ($fotosPendientes as $foto): ?>
                <li>
                    <strong><?= htmlspecialchars($foto['usuario']) ?></strong> - Evento: <?= htmlspecialchars($foto['evento']) ?><br>
                    <img src="../imagenes/<?= htmlspecialchars($foto['nombre_archivo']) ?>" alt="Foto" width="300"><br>
                    <small>Subida el: <?= $foto['fecha_subida'] ?></small><br>

                    <form action="" method="post" style="display:inline;">
    <input type="hidden" name="id_foto" value="<?= $foto['id_foto'] ?>">
    <input type="hidden" name="accion" value="validar">
    <button type="submit">✅ Validar</button>
</form>

<form action="" method="post" style="display:inline;" onsubmit="return validarMotivo(this);">
    <input type="hidden" name="id_foto" value="<?= $foto['id_foto'] ?>">
    <input type="hidden" name="accion" value="invalidar">
    
    <select name="motivo" onchange="mostrarOtroMotivo(this)">
        <option value="">-- Selecciona un motivo para invalidar--</option>
        <option value="Contenido explícito">Contenido explícito</option>
        <option value="Contenido explícito">Fuera de contexto</option>
        <option value="No cumple requisitos de dimensión">No cumple requisitos de dimensión</option>
        <option value="Otros">Otros</option>
    </select>

    <input type="text" name="motivo_otro" placeholder="Especifica motivo..." style="display:none; margin-left: 5px;">

    <button type="submit">❌ Invalidar</button>
</form>

<form action="" method="post" style="display:inline;">
    <input type="hidden" name="id_foto" value="<?= $foto['id_foto'] ?>">
    <input type="hidden" name="accion" value="eliminar">
    <button type="submit" onclick="return confirm('¿Estás seguro de eliminar esta foto definitivamente?')">🗑️ Eliminar</button>
</form>
                </li>
                <hr>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No hay fotos pendientes de validar.</p>
    <?php endif; ?>

</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>
<script>
    //script para mostrar el campo de Otro para invalidar una foto
function mostrarOtroMotivo(select) {
    const input = select.parentElement.querySelector('input[name="motivo_otro"]');
    input.style.display = (select.value === 'Otros') ? 'inline-block' : 'none';
}
//funcion para validar con motivoi
function validarMotivo(form) {
    const select = form.querySelector('select[name="motivo"]');
    const otro = form.querySelector('input[name="motivo_otro"]');

    if (!select.value) {
        alert('Selecciona un motivo de invalidación.');
        return false;
    }

    if (select.value === 'Otros' && otro.value.trim() === '') {
        alert('Debes especificar el motivo.');
        return false;
    }

    return true;
}
</script>

</body>
</html>
