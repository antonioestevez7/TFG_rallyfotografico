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

    // desinscribirse del evento
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_evento'])) {
        $id_usuario = $_SESSION['usuario_id'];
        $id_evento = $_POST['id_evento'];

        // obtiene id y nombres de fotos que serán borradas
        $stmtFotos = $conexion->prepare("SELECT id_foto, nombre_archivo FROM fotos WHERE id_usuario = :id_usuario AND id_evento = :id_evento");
        $stmtFotos->execute([
            'id_usuario' => $id_usuario,
            'id_evento' => $id_evento
        ]);
        $fotos = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);

        // borra votos
        foreach ($fotos as $foto) {
            $conexion->prepare("DELETE FROM votos WHERE id_foto = :id_foto")
                     ->execute(['id_foto' => $foto['id_foto']]);
        }

        // borra fotos del servidor
        foreach ($fotos as $foto) {
            $ruta = "../imagenes/" . $foto['nombre_archivo'];
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }

        // borra de base de datos
        $conexion->prepare("DELETE FROM fotos WHERE id_usuario = :id_usuario AND id_evento = :id_evento")
                 ->execute(['id_usuario' => $id_usuario, 'id_evento' => $id_evento]);

        $conexion->prepare("DELETE FROM inscripciones WHERE id_usuario = :id_usuario AND id_evento = :id_evento")
                 ->execute(['id_usuario' => $id_usuario, 'id_evento' => $id_evento]);

        $mensajeExito = "✅ Te has desinscrito correctamente. Se eliminaron tus fotos y votos relacionados.";
    }

    // muestra eventos inscritos que no han finalizado
    $stmt = $conexion->prepare("
        SELECT e.id_evento, e.nombre, e.fecha_inicio, e.fecha_fin, i.fecha_inscripcion
        FROM inscripciones i
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        WHERE i.id_usuario = :id_usuario
        AND CURDATE() <= e.fecha_fin
        ORDER BY i.fecha_inscripcion DESC
    ");
    $stmt->execute(['id_usuario' => $_SESSION['usuario_id']]);
    $inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errores[] = "Error al procesar la solicitud: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Inscripciones - Rally Fotográfico</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header class="encabezado-login">
    <h1>📋 Tus Inscripciones</h1>
</header>

<nav>
    <ul>
        <li><a href="../index.php">Inicio</a></li>
        <li><a href="inscripcion.php">Inscribirse en Evento</a></li>
        <li><a href="fotos.php">Subir Fotografías</a></li>
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
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <h2>Eventos en los que estás inscrito</h2>

    <?php if (count($inscripciones) > 0): ?>
        <ul>
            <?php foreach ($inscripciones as $inscripcion): ?>
                <li>
                    <strong><?= htmlspecialchars($inscripcion['nombre']) ?></strong><br>
                    Fecha: <?= $inscripcion['fecha_inicio'] ?> - <?= $inscripcion['fecha_fin'] ?><br>
                    Inscripción: <?= $inscripcion['fecha_inscripcion'] ?><br>
                    <div class="botones-accion">
    <a href="fotos.php?rally_id=<?= urlencode($inscripcion['id_evento']) ?>" class="btn-accion">📷 Subir Foto</a>

    <form method="post" onsubmit="return confirm('¿Seguro que deseas cancelar tu inscripción? Se eliminarán también tus fotos y votos de este evento.');" style="display:inline;">
        <input type="hidden" name="id_evento" value="<?= $inscripcion['id_evento'] ?>">
        <button type="submit" class="btn-accion">❌ Cancelar Inscripción</button>
    </form>
</div>

                <hr>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No estás inscrito en ningún evento actualmente.</p>
    <?php endif; ?>

</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
