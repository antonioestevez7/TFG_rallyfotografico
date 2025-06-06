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

$conn = conectarPDO($host, $user, $password, $bbdd);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_evento'])) {
    $id = $_POST['id_evento'];
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $color_primario = $_POST['color_primario'] ?? '';

    try {
        $update = $conn->prepare("UPDATE eventos SET nombre = :nombre, descripcion = :descripcion, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, color_primario = :color_primario WHERE id_evento = :id");
        $update->execute([
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'color_primario' => $color_primario,
            'id' => $id
        ]);
        $mensajeExito = "Evento actualizado correctamente.";
    } catch (PDOException $e) {
        $errores[] = "Error al actualizar el evento: " . $e->getMessage();
    }
}

$eventos = [];
try {
    $stmt = $conn->query("SELECT * FROM eventos");
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "No se pudieron cargar los eventos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Eventos - Admin</title>
    <link rel="stylesheet" href="../css/style_eventos.css">
</head>
<body>
    <header>
        <h1>Editar Eventos</h1>
        <nav>
            <ul>
                <li><a href="../index.php">Inicio</a></li>
                <li><a href="panel.php">Panel Admin</a></li>
                <li><a href="../acceso/logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <?php if (!empty($mensajeExito)): ?>
            <div class="mensaje-exito"> <?= htmlspecialchars($mensajeExito) ?> </div>
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

       

        <?php foreach ($eventos as $evento): ?>
        <?php
            $hoy = date('Y-m-d');
            if ($evento['fecha_inicio'] > $hoy) {
                $estado = '<strong>Estado:</strong> <span class="estado proximo">🕓 Próximamente</span>';
            } elseif ($evento['fecha_fin'] < $hoy) {
                $estado = '<strong>Estado:</strong> <span class="estado finalizado">🔴 Finalizado</span>';
            } else {
                $estado = '<strong>Estado:</strong> <span class="estado activo">🟢 En activo</span>';
            }
        ?>
        <div class="acordeon-titulo" style="cursor:pointer; background:#eee; padding:10px; border-radius:6px; margin-top:20px;">
            <strong><?= htmlspecialchars($evento['nombre']) ?></strong> — <?= strip_tags($estado) ?>
        </div>
        <div class="acordeon-contenido" style="display:none;">
            <form method="post" class="foto-card">
                <input type="hidden" name="id_evento" value="<?= $evento['id_evento'] ?>">

                <p><?= $estado ?></p>

                <label>Nombre:
                    <input type="text" name="nombre" value="<?= htmlspecialchars($evento['nombre']) ?>" required>
                </label><br>
                <label>Descripción:
                    <textarea name="descripcion" required><?= htmlspecialchars($evento['descripcion']) ?></textarea>
                </label><br>
                <label>Fecha Inicio:
                    <input type="date" name="fecha_inicio" value="<?= $evento['fecha_inicio'] ?>" required>
                </label><br>
                <label>Fecha Fin:
                    <input type="date" name="fecha_fin" value="<?= $evento['fecha_fin'] ?>" required>
                </label><br>
                <label>Color Primario:
                    <input type="color" name="color_primario" value="<?= $evento['color_primario'] ?>">
                </label><br>
                <button type="submit">Guardar Cambios</button>
            </form>
        </div>
        <?php endforeach; ?>
    </main>

   <footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

    <script>
    document.querySelectorAll('.acordeon-titulo').forEach(titulo => {
        titulo.addEventListener('click', () => {
            const contenido = titulo.nextElementSibling;
            contenido.style.display = contenido.style.display === 'block' ? 'none' : 'block';
        });
    });

    
   
    </script>
</body>
</html>
