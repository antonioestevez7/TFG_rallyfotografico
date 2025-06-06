<?php
session_start();
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';

$errores = [];
$mensajeExito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Por favor, ingresa un correo válido.";
    } else {
        try {
            $conexion = conectarPDO($host, $user, $password, $bbdd);

            // busca si el usuario existe.
            $stmt = $conexion->prepare("SELECT id_usuario, nombre FROM usuarios WHERE email = :email");
            $stmt->execute(["email" => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                // crea el token
                $token = bin2hex(random_bytes(16));

                // guarda token temporalmnte
                $stmtToken = $conexion->prepare("UPDATE usuarios SET token = :token WHERE id_usuario = :id_usuario");
                $stmtToken->execute([
                    "token" => $token,
                    "id_usuario" => $usuario['id_usuario']
                ]);

                // envia el email
                $linkRecuperar = "http://localhost:3000/rally_fotografico/acceso/restablecer_password.php?token=" . urlencode($token) . "&email=" . urlencode($email);

                $asunto = "Recupera tu contraseña - Rally Fotográfico";
                $mensaje = "Hola {$usuario['nombre']},\n\n";
                $mensaje .= "Has solicitado recuperar tu contraseña.\n";
                $mensaje .= "Haz clic en este enlace para establecer una nueva contraseña:\n\n";
                $mensaje .= "$linkRecuperar\n\n";
                $mensaje .= "Si no lo solicitaste, ignora este mensaje.";

                $headers = "From: admin@rally.com\r\n";
                $headers .= "Content-type: text/plain; charset=utf-8\r\n";

                if (mail($email, $asunto, $mensaje, $headers)) {
                    $mensajeExito = "✅ Te hemos enviado un correo para restablecer tu contraseña.";
                } else {
                    $errores[] = "No se pudo enviar el correo. Intenta más tarde.";
                }
            } else {
                $errores[] = "No se encontró una cuenta con ese correo.";
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
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header>
    <h1>Recuperar Contraseña</h1>
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

    <form action="" method="post">
        <p>
            <label for="email">Correo electrónico:</label>
            <input type="email" name="email" id="email" required>
        </p>
        <p>
            <button type="submit">Enviar enlace de recuperación</button>
        </p>
    </form>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
