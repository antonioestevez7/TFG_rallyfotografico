<?php
// editar_usuario.php
session_start();
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../acceso/login.php");
    exit();
}

$errores = [];
$mensajeExito = "";

// Obtener usuario a editar
if (!isset($_GET['id'])) {
    header("Location: panel.php");
    exit();
}

$id = (int)$_GET['id'];

try {
    $conexion = conectarPDO($host, $user, $password, $bbdd);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $rol = $_POST['rol'];

        if (!validar_requerido($nombre)) {
            $errores[] = "El nombre es obligatorio.";
        }
        if (!validarEmail($email)) {
            $errores[] = "El email no es válido.";
        }

        if (empty($errores)) {
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, email = :email, rol = :rol WHERE id_usuario = :id");
            $stmt->execute([
                "nombre" => $nombre,
                "email" => $email,
                "rol" => $rol,
                "id" => $id
            ]);

            $mensajeExito = "Usuario actualizado correctamente.";
        }
    }

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id_usuario = :id");
    $stmt->execute(["id" => $id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header("Location: panel.php");
        exit();
    }

} catch (PDOException $e) {
    $errores[] = "Error al conectar con la base de datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
</head>
<body>
<header class="encabezado-login">
    <h1>Editar Usuario</h1>
</header>

<main>
    <?php if ($mensajeExito): ?>
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

    <form action="" method="post">
        <p>
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
        </p>
        <p>
            <label for="email">Correo Electrónico:</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
        </p>
        <p>
            <label for="rol">Rol:</label>
            <select name="rol" id="rol">
                <option value="participante" <?= $usuario['rol'] === 'participante' ? 'selected' : '' ?>>Participante</option>
                <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </p>
        <button type="submit">Guardar Cambios</button>
    </form>

    <p><a href="panel.php">&larr; Volver a la lista de usuarios</a></p>
</main>
</body>
</html>
