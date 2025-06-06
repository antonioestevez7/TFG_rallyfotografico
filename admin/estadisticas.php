<?php

session_start();
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../acceso/login.php");
    exit();
}

$errores = [];
$estadisticas = [];

try {
    $conexion = conectarPDO($host, $user, $password, $bbdd);

    //numero de usuarios totales
    $stmt = $conexion->query("SELECT COUNT(*) AS total_usuarios FROM usuarios");
    $estadisticas['usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_usuarios'];

    //numero total de eventgs
    $stmt = $conexion->query("SELECT COUNT(*) AS total_eventos FROM eventos");
    $estadisticas['eventos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_eventos'];

    // numero total de fotos subidas
    $stmt = $conexion->query("SELECT COUNT(*) AS total_fotos FROM fotos");
    $estadisticas['fotos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_fotos'];

    // numero de votos 
    $stmt = $conexion->query("SELECT COUNT(*) AS total_votos FROM votos");
    $estadisticas['votos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_votos'];

    // numero de usuarios con mas fotos subidas
    $stmt = $conexion->query(
        "SELECT u.nombre, COUNT(f.id_foto) AS cantidad 
         FROM usuarios u
         JOIN fotos f ON u.id_usuario = f.id_usuario
         GROUP BY u.id_usuario
         ORDER BY cantidad DESC LIMIT 1"
    );
    $estadisticas['top_usuario'] = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errores[] = "Error al recuperar las estadísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas - Admin</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header>
    <h1>Estadísticas Generales</h1>
    <nav>
        <ul>
            <li><a href="panel.php">Usuarios</a></li>
            <li><a href="crear_evento.php">Crear Rally</a></li>
            <li><a href="validar_fotos.php">Validar Fotografías</a></li>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="../acceso/logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>
</header>

<main>
    <?php if (!empty($errores)): ?>
        <div class="mensaje-error">
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <ul>
            <li><strong>Total de Usuarios:</strong> <?= $estadisticas['usuarios'] ?></li>
            <li><strong>Total de Eventos:</strong> <?= $estadisticas['eventos'] ?></li>
            <li><strong>Total de Fotos Subidas:</strong> <?= $estadisticas['fotos'] ?></li>
            <li><strong>Total de Votos Emitidos:</strong> <?= $estadisticas['votos'] ?></li>
            <?php if (!empty($estadisticas['top_usuario'])): ?>
                <li><strong>Usuario con más fotos:</strong> <?= htmlspecialchars($estadisticas['top_usuario']['nombre']) ?> (<?= $estadisticas['top_usuario']['cantidad'] ?> fotos)</li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
