<?php
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

// Activar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mensaje = "";

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = urldecode($_GET['email']);
    $token = $_GET['token'];

    try {
        $conexion = conectarPDO($host, $user, $password, $bbdd);

        // Buscar usuario con ese email y token
        $query = "SELECT id_usuario FROM usuarios WHERE email = :email AND token = :token AND activo = 0 LIMIT 1";
        $stmt = $conexion->prepare($query);
        $stmt->execute([
            "email" => $email,
            "token" => $token
        ]);

        if ($stmt->rowCount() == 1) {
            // Usuario encontrado, actualizar activo y eliminar token
            $update = "UPDATE usuarios SET activo = 1, token = NULL WHERE email = :email";
            $stmtUpdate = $conexion->prepare($update);
            $stmtUpdate->execute(["email" => $email]);

            $mensaje = "¡Tu cuenta ha sido activada con éxito! Ahora puedes iniciar sesión.";
        } else {
            $mensaje = "El enlace no es válido o tu cuenta ya está activada.";
        }

    } catch (PDOException $e) {
        $mensaje = "Error en la activación: " . $e->getMessage();
    }

} else {
    $mensaje = "Parámetros de activación inválidos.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Activación de Cuenta - Rally Fotográfico</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header>
    <h1>Activación de Cuenta</h1>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="login.php">Iniciar Sesión</a></li>
        </ul>
    </nav>
</header>

<main>
    <p><?= htmlspecialchars($mensaje) ?></p>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
