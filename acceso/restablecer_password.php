<?php
session_start();
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';

$errores = [];
$mensajeExito = "";

// Recoger email y token de la URL
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    if (empty($password) || empty($confirmar_password)) {
        $errores[] = "Debes completar ambos campos de contraseña.";
    } elseif ($password !== $confirmar_password) {
        $errores[] = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        try {
            $conexion = conectarPDO($host, $user, $password, $bbdd);

            // Validar email y token
            $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE email = :email AND token = :token");
            $stmt->execute([
                "email" => $email,
                "token" => $token
            ]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                // Actualizar contraseña y eliminar token
                $nuevaPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmtUpdate = $conexion->prepare("UPDATE usuarios SET password = :password, token = NULL WHERE id_usuario = :id_usuario");
                $stmtUpdate->execute([
                    "password" => $nuevaPassword,
                    "id_usuario" => $usuario['id_usuario']
                ]);

                $mensajeExito = "✅ Tu contraseña ha sido actualizada correctamente. Ahora puedes iniciar sesión.";
            } else {
                $errores[] = "El enlace de recuperación no es válido o ha expirado.";
            }

        } catch (PDOException $e) {
            $errores[] = "Error de conexión: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header>
    <h1>Restablecer Contraseña</h1>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="login.php">Iniciar Sesión</a></li>
        </ul>
    </nav>
</header>

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

    <?php if (empty($mensajeExito)): ?>
        <form action="" method="post">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <p>
                <label for="password">Nueva contraseña:</label>
                <input type="password" name="password" id="password" required>
            </p>

            <p>
                <label for="confirmar_password">Confirmar nueva contraseña:</label>
                <input type="password" name="confirmar_password" id="confirmar_password" required>
            </p>

            <p>
                <button type="submit">Guardar nueva contraseña</button>
            </p>
        </form>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
