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

    $stmt = $conexion->query("SELECT id_usuario, nombre, email, rol, activo, fecha_registro FROM usuarios ORDER BY fecha_registro DESC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errores[] = "Error al conectar a la base de datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración - Usuarios</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header>
    <h1>Panel de Administración</h1>
</header>

<nav>
    <ul>
        <li><a href="crear_evento.php">Crear Rally</a></li>
        <li><a href="validar_fotos.php">Validar Fotografías</a></li>
        <li><a href="estadisticas.php">Ver Estadísticas</a></li>
        <li><a href="../index.php">Volver al Inicio</a></li>
        <li><a href="../acceso/logout.php">Cerrar Sesión</a></li>
    </ul>
</nav>

<main>
    <h2>Usuarios Registrados</h2>

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

    <?php if ($usuarios): ?>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Registrado el</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['rol']) ?></td>
                        <td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
                        <td><?= $u['fecha_registro'] ?></td>
                        <td class="admin-actions">
                            <?php if ($u['rol'] !== 'admin'): ?>
                               <!-- Editar -->
                                <form method="get" action="editar_usuario.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $u['id_usuario'] ?>">
                                    <button type="submit">✏️ Editar</button>
                                </form>

                                <!-- Eliminar -->
                                <form method="post" action="eliminar_usuario.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                    <input type="hidden" name="usuario_id" value="<?= $u['id_usuario'] ?>">
                                    <button type="submit">🗑️ Eliminar</button>
                                </form>

                            <?php else: ?>
                                <em>Admin</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay usuarios registrados.</p>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>
</body>
</html>
