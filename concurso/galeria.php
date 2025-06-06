<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../acceso/login.php");
    exit();
}
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';

$errores = [];
$mensajeExito = "";

try {
    $conexion = conectarPDO($host, $user, $password, $bbdd);

    $rallyId = $_GET['rally_id'] ?? null;
    $usuarioId = $_SESSION['usuario_id'] ?? 0;

    $colorPrimario = '#007BFF';

    if ($rallyId) {
        $stmtColores = $conexion->prepare("SELECT color_primario FROM eventos WHERE id_evento = :id");
        $stmtColores->execute(["id" => $rallyId]);
        $colores = $stmtColores->fetch(PDO::FETCH_ASSOC);
        if ($colores) {
            $colorPrimario = $colores['color_primario'] ?? $colorPrimario;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idFoto = $_POST['id_foto'] ?? null;
        $accion = $_POST['accion'] ?? '';

        if ($usuarioId && $idFoto && in_array($accion, ['votar', 'quitar', 'eliminar_foto'])) {
            if ($accion === 'votar') {
                $stmtInsert = $conexion->prepare("INSERT INTO votos (id_usuario, id_foto) VALUES (:usuario, :foto)");
                $stmtInsert->execute(['usuario' => $usuarioId, 'foto' => $idFoto]);
            } elseif ($accion === 'quitar') {
                $stmtDelete = $conexion->prepare("DELETE FROM votos WHERE id_usuario = :usuario AND id_foto = :foto");
                $stmtDelete->execute(['usuario' => $usuarioId, 'foto' => $idFoto]);
            } elseif ($accion === 'eliminar_foto' && $_SESSION['rol'] === 'admin') {
                $stmtDelete = $conexion->prepare("DELETE FROM fotos WHERE id_foto = :foto");
                $stmtDelete->execute(['foto' => $idFoto]);
            }

            header("Location: galeria.php?rally_id=" . urlencode($rallyId));
            exit();
        }
    }

    // PAGINACION
    $paginaActual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $fotosPorPagina = 5;
    $offset = ($paginaActual - 1) * $fotosPorPagina;

    // TOTAL FOTOS
    $sqlTotal = "SELECT COUNT(*) FROM fotos f JOIN eventos e ON f.id_evento = e.id_evento WHERE f.validada = 1 AND e.fecha_fin >= CURDATE()";
    if ($rallyId !== null) {
        $sqlTotal .= " AND f.id_evento = :rally_id_total";
        $stmtTotal = $conexion->prepare($sqlTotal);
        $stmtTotal->execute(["rally_id_total" => $rallyId]);
    } else {
        $stmtTotal = $conexion->query($sqlTotal);
    }
    $totalFotos = $stmtTotal->fetchColumn();
    $totalPaginas = ceil($totalFotos / $fotosPorPagina);

    // Consulta de fotos
    $sql = "
        SELECT f.id_foto, f.nombre_archivo, u.nombre AS usuario, u.id_usuario AS id_autor,
               e.nombre AS evento, f.fecha_subida,
               (SELECT COUNT(*) FROM votos WHERE id_foto = f.id_foto) AS total_votos,
               (SELECT COUNT(*) FROM votos WHERE id_foto = f.id_foto AND id_usuario = :usuario_id) AS ya_votado
        FROM fotos f
        JOIN usuarios u ON f.id_usuario = u.id_usuario
        JOIN eventos e ON f.id_evento = e.id_evento
        WHERE f.validada = 1 AND e.fecha_fin >= CURDATE()
    ";

    if ($rallyId !== null) {
        $sql .= " AND f.id_evento = :rally_id";
    }

    $sql .= " ORDER BY f.fecha_subida DESC LIMIT :limit OFFSET :offset";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $fotosPorPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    if ($rallyId !== null) {
        $stmt->bindValue(':rally_id', $rallyId, PDO::PARAM_INT);
    }
    $stmt->execute();
    $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // RECALCULAR votos usados y restantes tras procesar el voto
    $votosUsados = 0;
    $votosRestantes = 2;
    if ($usuarioId && $rallyId) {
        $stmtVotos = $conexion->prepare("SELECT COUNT(*) FROM votos v JOIN fotos f ON v.id_foto = f.id_foto WHERE v.id_usuario = :usuario AND f.id_evento = :evento");
        $stmtVotos->execute(["usuario" => $usuarioId, "evento" => $rallyId]);
        $votosUsados = $stmtVotos->fetchColumn();
        $votosRestantes = max(0, 2 - $votosUsados);
    }

} catch (PDOException $e) {
    $errores[] = "Error al cargar la galería: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Galería - Rally Fotográfico</title>
    <link rel="stylesheet" href="../css/style_galeria.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
    <style>
        :root {
            --color-primario: <?= htmlspecialchars($colorPrimario) ?>;
        }
    </style>
</head>
<body>

<header>
    <h1>Galería de Fotografías</h1>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="ranking.php?rally_id=<?= urlencode($rallyId) ?>" class="btn-ver-fotos">Ranking</a></li>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <li><a href="../usuario/inscripcion.php">Inscribirse</a></li>
                <li><a href="../acceso/logout.php">Cerrar Sesión</a></li>
            <?php else: ?>
                <li><a href="../acceso/login.php">Iniciar Sesión</a></li>
                <li><a href="../acceso/registro.php">Registrarse</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main>
<?php if ($rallyId): ?>
    <div class="galeria-header">
        <h3><?= !empty($fotos) ? htmlspecialchars("Fotos que participan en el rally " . $fotos[0]['evento']) : "Este rally aún no tiene fotos subidas." ?></h3>
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <?php
                $stmtInscrito = $conexion->prepare("SELECT COUNT(*) FROM inscripciones WHERE id_usuario = :id_usuario AND id_evento = :id_evento");
                $stmtInscrito->execute([
                    "id_usuario" => $_SESSION['usuario_id'],
                    "id_evento" => $rallyId
                ]);
                $estaInscrito = $stmtInscrito->fetchColumn() > 0;
            ?>
            <div style="text-align:center; margin-top: 15px;">
                <?php if ($estaInscrito): ?>
                    <a href="../usuario/fotos.php?rally_id=<?= urlencode($rallyId) ?>" class="btn-subir-fotos">📤 Subir Foto a este Rally</a>
                <?php else: ?>
                    <a href="../usuario/inscripcion.php" class="btn-subir-fotos">📝 Inscribirse en este Rally</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($usuarioId): ?>
    <p><strong><?= htmlspecialchars($_SESSION['nombre']) ?>, te quedan <?= $votosRestantes ?> votos.</strong></p>
<?php endif; ?>

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

<?php if ($fotos): ?>
    <ul style="list-style: none; padding: 0;">
        <?php foreach ($fotos as $foto): ?>
            <li class="foto-card">
                <p><strong>Foto subida por <?= htmlspecialchars($foto['usuario']) ?></strong></p>
                <img src="../imagenes/<?= htmlspecialchars($foto['nombre_archivo']) ?>" alt="Foto" width="300"><br>
                <p><strong>Votos: <?= $foto['total_votos'] ?></strong></p>

                <?php if ($usuarioId): ?>
                    <?php if ($foto['id_autor'] != $usuarioId): ?>
                        <?php if ($foto['ya_votado'] == 0 && $votosUsados < 2): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_foto" value="<?= $foto['id_foto'] ?>">
                                <input type="hidden" name="accion" value="votar">
                                <button type="submit">🗳️ Votar</button>
                            </form>
                        <?php elseif ($foto['ya_votado'] == 1): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_foto" value="<?= $foto['id_foto'] ?>">
                                <input type="hidden" name="accion" value="quitar">
                                <button type="submit">❌ Quitar Voto</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><em>No puedes votar tu propia foto.</em></p>
                    <?php endif; ?>

                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id_foto" value="<?= $foto['id_foto'] ?>">
                            <input type="hidden" name="accion" value="eliminar_foto">
                            <button type="submit" onclick="return confirm('¿Eliminar esta foto?')">🗑️ Eliminar</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p><em>Inicia sesión para votar.</em></p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <div style="text-align:center; margin-top: 20px;">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <?php if ($i == $paginaActual): ?>
                <strong>[<?= $i ?>]</strong>
            <?php else: ?>
                <a href="?rally_id=<?= urlencode($rallyId) ?>&pagina=<?= $i ?>">[<?= $i ?>]</a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>

<?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
