<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../acceso/login.php");
    exit();
}
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';



$fallos = [];
$mensaje = "";
$conn = conectarPDO($host, $user, $password, $bbdd);


if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['id_evento'])) {
    $usuario = $_SESSION['usuario_id'];
    $evento = $_POST['id_evento'];

    try {
        $check = $conn->prepare("SELECT COUNT(*) as total FROM inscripciones WHERE id_usuario = :u AND id_evento = :e");
        $check->execute(['u' => $usuario, 'e' => $evento]);
        $yaInscrito = $check->fetchColumn();

        if ($yaInscrito) {
            $fallos[] = "Ya estás apuntado a ese evento.";
        } else {
            $insertar = $conn->prepare("INSERT INTO inscripciones (id_usuario, id_evento) VALUES (:u, :e)");
            $insertar->execute(['u' => $usuario, 'e' => $evento]);
            $mensaje = "✅ Te has inscrito correctamente.";
        }
    } catch (PDOException $err) {
        $fallos[] = "Error en la inscripción: " . $err->getMessage();
    }
}

try {
    $usuario = $_SESSION['usuario_id'];
    $stmtEventos = $conn->prepare("
        SELECT id_evento, nombre, descripcion, fecha_inicio, fecha_fin, imagen 
        FROM eventos 
        WHERE CURDATE() BETWEEN fecha_inicio AND fecha_fin
        AND id_evento NOT IN (
            SELECT id_evento FROM inscripciones WHERE id_usuario = :usuario
        )
    ");
    $stmtEventos->execute(['usuario' => $usuario]);
    $listaEventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $err) {
    $fallos[] = "No se pudieron cargar los eventos: " . $err->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inscribirse en un Rally</title>
    <link rel="stylesheet" href="../css/style_inscripcion.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header>
    <h1>Inscribirse en un Rally</h1>
    <nav>
        <ul>
            <li><a href="../index.php">🏠 Inicio</a></li>
            <li><a href="mis-inscripciones.php">📋 Mis eventos</a></li>
            <li><a href="../acceso/logout.php">Salir</a></li>
        </ul>
    </nav>
</header>

<main>
    <?php
    if (!empty($fallos)) {
        echo "<div class='mensaje-error'><ul>";
        foreach ($fallos as $f) {
            echo "<li>" . htmlspecialchars($f) . "</li>";
        }
        echo "</ul></div>";
    }

    if ($mensaje) {
        echo "<p class='mensaje-exito'>" . htmlspecialchars($mensaje) . "</p>";
    }
    ?>

    <h2>Rallys disponibles</h2>

    <?php if (!empty($listaEventos)): ?>
        <div class="eventos-grid">
            <?php foreach ($listaEventos as $ev): ?>
                <div class="tarjeta-evento">
                    <div class="img-evento" style="background-image: url('../imagenes/eventos/<?= htmlspecialchars($ev['imagen'] ?? 'evento-default.jpg') ?>');"></div>
                    <div class="contenido-evento">
                        <h3><?= htmlspecialchars($ev['nombre']) ?></h3>
                        <p><?= htmlspecialchars("Descripción: ".$ev['descripcion']) ?></p>
                        <p class="fechas"><?= "Fecha Inicio: ".$ev['fecha_inicio'] ?> <br><br>
                        <?= "Fecha de fin: ".$ev['fecha_fin'] ?></p>
                        <form method="post">
                            <input type="hidden" name="id_evento" value="<?= $ev['id_evento'] ?>">
                            <button type="submit">Inscribirme</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No hay eventos disponibles por ahora.</p>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
