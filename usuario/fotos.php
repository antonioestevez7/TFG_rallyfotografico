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
$directorioFotos = "../imagenes/";
$rallySeleccionado = $_GET['rally_id'] ?? null;


$paginaActual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$fotosPorPagina = 5;
$offset = ($paginaActual - 1) * $fotosPorPagina;

try {
    $conexion = conectarPDO($host, $user, $password, $bbdd);


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar_foto'])) {
        $nombreArchivo = $_POST['borrar_foto'];

        $stmtGetId = $conexion->prepare("SELECT id_foto FROM fotos WHERE nombre_archivo = :nombre AND id_usuario = :id_usuario");
        $stmtGetId->execute([
            "nombre" => $nombreArchivo,
            "id_usuario" => $_SESSION['usuario_id']
        ]);
        $foto = $stmtGetId->fetch();

        if ($foto) {
            $conexion->prepare("DELETE FROM votos WHERE id_foto = :id_foto")->execute(["id_foto" => $foto['id_foto']]);
            $conexion->prepare("DELETE FROM fotos WHERE id_foto = :id_foto")->execute(["id_foto" => $foto['id_foto']]);

            $ruta = $directorioFotos . $nombreArchivo;
            if (file_exists($ruta)) {
                unlink($ruta);
            }

            $mensajeExito = "Foto eliminada correctamente.";
        } else {
            $errores[] = "No se encontró la foto.";
        }
    }

    // obbtiene los eventos que estan activos y esta inscrito
    $stmt = $conexion->prepare("
        SELECT e.id_evento, e.nombre 
        FROM inscripciones i
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        WHERE i.id_usuario = :id_usuario AND CURDATE() <= e.fecha_fin
    ");
    $stmt->execute(["id_usuario" => $_SESSION['usuario_id']]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);


    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_evento']) && isset($_FILES['foto'])) {
        $id_evento = $_POST['id_evento'];

       
        $stmtEvento = $conexion->prepare("SELECT fecha_fin FROM eventos WHERE id_evento = :id_evento");
        $stmtEvento->execute(["id_evento" => $id_evento]);
        $eventoDatos = $stmtEvento->fetch();

        if (!$eventoDatos || $eventoDatos['fecha_fin'] < date('Y-m-d')) {
            $errores[] = "Este evento ya ha finalizado. No puedes subir fotos.";
        } else {
           
            $stmtCount = $conexion->prepare("SELECT COUNT(*) as total FROM fotos WHERE id_usuario = :id_usuario AND id_evento = :id_evento");
            $stmtCount->execute([
                "id_usuario" => $_SESSION['usuario_id'],
                "id_evento" => $id_evento
            ]);
            $resultado = $stmtCount->fetch();

            if ($resultado['total'] >= 2) {
                $errores[] = "¡Ya has subido 2 fotos para este evento!";
            } else {
                $archivo = $_FILES['foto'];

                if ($archivo['error'] === UPLOAD_ERR_OK) {
                    $nombreTmp = $archivo['tmp_name'];
                    $nombreArchivo = uniqid() . "_" . basename($archivo['name']);
                    $rutaDestino = $directorioFotos . $nombreArchivo;

                    $tipoPermitido = ['image/jpeg', 'image/png', 'image/jpg'];
                    if (in_array($archivo['type'], $tipoPermitido)) {
                        if (move_uploaded_file($nombreTmp, $rutaDestino)) {
                            $insert = "INSERT INTO fotos (id_usuario, id_evento, nombre_archivo, validada) 
                                       VALUES (:id_usuario, :id_evento, :nombre_archivo, 0)";
                            $stmtInsert = $conexion->prepare($insert);
                            $stmtInsert->execute([
                                "id_usuario" => $_SESSION['usuario_id'],
                                "id_evento" => $id_evento,
                                "nombre_archivo" => $nombreArchivo
                            ]);
                            $mensajeExito = "¡Fotografía subida correctamente y pendiente de evaluación!";
                        } else {
                            $errores[] = "Error al mover la foto al servidor.";
                        }
                    } else {
                        $errores[] = "Tipo de archivo no permitido. Solo JPG o PNG.";
                    }
                } else {
                    $errores[] = "Error al subir la foto.";
                }
            }
        }
    }

    
    $stmtTotal = $conexion->prepare("SELECT COUNT(*) FROM fotos WHERE id_usuario = :id_usuario");
    $stmtTotal->execute(["id_usuario" => $_SESSION['usuario_id']]);
    $totalFotos = $stmtTotal->fetchColumn();
    $totalPaginas = ceil($totalFotos / $fotosPorPagina);

   
    $stmtFotos = $conexion->prepare("
        SELECT f.id_evento, f.nombre_archivo, f.validada, f.motivo_invalidacion, e.nombre AS evento
        FROM fotos f
        INNER JOIN eventos e ON f.id_evento = e.id_evento
        WHERE f.id_usuario = :id_usuario
        ORDER BY f.fecha_subida DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmtFotos->bindValue(':id_usuario', $_SESSION['usuario_id'], PDO::PARAM_INT);
    $stmtFotos->bindValue(':limit', $fotosPorPagina, PDO::PARAM_INT);
    $stmtFotos->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtFotos->execute();
    $fotos = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errores[] = "Error en la base de datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Fotografías - Rally Fotográfico</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header>
    <h1>Mis Fotografías</h1>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="inscripcion.php">Inscribirse en Evento</a></li>
            <li><a href="mis-inscripciones.php">Mis Inscripciones</a></li>
            <li><a href="fotos.php">Subir Fotografías</a></li>
            <li><a href="../acceso/logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>
</header>

<main>

    <h2>Subir Nueva Fotografía</h2>

    <?php if (!empty($mensajeExito)): ?>
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

    <?php if (!empty($eventos)): ?>
        <form action="" method="post" enctype="multipart/form-data">
            <p>
                <label for="id_evento">Selecciona un evento:</label>
                <select name="id_evento" id="id_evento" required>
                    <?php foreach ($eventos as $evento): ?>
                        <option value="<?= $evento['id_evento'] ?>" <?= ($evento['id_evento'] == $rallySeleccionado ? 'selected' : '') ?>>
                            <?= htmlspecialchars($evento['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="foto">Selecciona tu fotografía:</label>
                <input type="file" name="foto" id="foto" accept="image/png, image/jpeg" required>
            </p>
            <div id="preview-container" style="text-align:center; margin-top:10px;">
                <img id="preview" src="#" alt="Previsualización" style="display:none; max-width:700px; border:1px solid #ccc; border-radius:8px;">
            </div>
            <button type="submit">Subir Fotografía</button>
        </form>
    <?php else: ?>
        <p>No estás inscrito en ningún evento activo o ya han expirado todos.</p>
    <?php endif; ?>

    <h2>Mis Fotos Subidas</h2>

    <?php if (!empty($fotos)): ?>
        <ul>
            <?php foreach ($fotos as $foto): ?>
                <li>
                    <strong><?= htmlspecialchars($foto['evento']) ?></strong><br>
                    <img src="../imagenes/<?= htmlspecialchars($foto['nombre_archivo']) ?>" alt="Fotografía" width="200"><br>
                    <?php
                        switch ($foto['validada']) {
                            case 1:
                                echo '<p style="color: green;">✅ Foto validada</p>';
                                break;
                            case 0:
                                echo '<p style="color: orange;">⏳ Pendiente de evaluación</p>';
                                break;
                            case -1:
                                echo '<p style="color: red;">❌ Foto invalidada</p>';
                                if (!empty($foto['motivo_invalidacion'])) {
                                    echo '<p><strong>Motivo:</strong> ' . htmlspecialchars($foto['motivo_invalidacion']) . '</p>';
                                }
                                break;
                        }
                    ?>
                    <form action="" method="post" style="display:inline;">
                        <input type="hidden" name="borrar_foto" value="<?= htmlspecialchars($foto['nombre_archivo']) ?>">
                        <button type="submit" onclick="return confirm('¿Seguro que deseas eliminar esta foto?')">Eliminar Foto</button>
                    </form>
                </li>
                <hr>
            <?php endforeach; ?>
        </ul>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
            <div style="text-align:center; margin-top:20px;">
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <?php if ($i == $paginaActual): ?>
                        <strong>[<?= $i ?>]</strong>
                    <?php else: ?>
                        <a href="?pagina=<?= $i ?><?= $rallySeleccionado ? '&rally_id=' . urlencode($rallySeleccionado) : '' ?>">[<?= $i ?>]</a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <p>Aún no has subido ninguna fotografía.</p>
    <?php endif; ?>

</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

<script>
document.getElementById('foto').addEventListener('change', function (e) {
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
