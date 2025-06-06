<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../acceso/login.php");
    exit();
}
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';

$errores = [];
$fotos = [];
$rallyId = $_GET['rally_id'] ?? null;

try {
    $conexion = conectarPDO($host, $user, $password, $bbdd);

    $consultaRanking = "
        SELECT 
            f.nombre_archivo, 
            e.nombre AS evento, 
            u.nombre AS usuario,
            (SELECT COUNT(*) FROM votos v WHERE v.id_foto = f.id_foto) AS total_votos
        FROM fotos f
        INNER JOIN eventos e ON f.id_evento = e.id_evento
        INNER JOIN usuarios u ON f.id_usuario = u.id_usuario
        WHERE e.fecha_fin >= CURDATE()
    ";

    $params = [];

    if ($rallyId) {
        $consultaRanking .= " AND f.id_evento = :rally_id";
        $params['rally_id'] = $rallyId;
    }

    $consultaRanking .= " ORDER BY total_votos DESC, f.fecha_subida ASC LIMIT 5";

    $stmt = $conexion->prepare($consultaRanking);
    $stmt->execute($params);
    $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $error) {
    $errores[] = "No se pudo obtener el ranking. " . $error->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ranking - Rally Fotográfico</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header>
    <h1>📷 Ranking General de Fotografías</h1>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <li><a href="../usuario/inscripcion.php">Inscribirme</a></li>
                <li><a href="../usuario/fotos.php">Mis Fotos</a></li>
                <li><a href="../acceso/logout.php">Salir</a></li>
            <?php else: ?>
                <li><a href="../acceso/login.php">Log in</a></li>
                <li><a href="../acceso/registro.php">Registro</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main>
    <h2>🏆 Las Fotos con Más Votos<?= $rallyId && $fotos ? " del Evento: " . htmlspecialchars($fotos[0]['evento']) : "" ?></h2>
                
    <?php if (!empty($errores)): ?>
        <div class="mensaje-error">
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($fotos): ?>
        <ul style="list-style: none; padding-left: 0;">
            <?php foreach ($fotos as $pos => $foto): ?>
                <li style="margin-bottom: 2em;">
                    <h3>#<?= $pos + 1 ?> - <?= htmlspecialchars($foto['usuario']) ?></h3>
                   
                    <img src="../imagenes/<?= htmlspecialchars($foto['nombre_archivo']) ?>" alt="Imagen subida" width="300"><br>
                    <p><strong>Total de votos:</strong> <?= $foto['total_votos'] ?></p>
                </li>
                <hr>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No hay fotografías con votos todavía o los eventos ya finalizaron.</p>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
