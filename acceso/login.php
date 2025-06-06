<?php
session_start();
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Variables
$errores = [];
$email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
$campo_password = isset($_POST["password"]) ? $_POST["password"] : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    if (empty($email) || empty($campo_password)) {
        $errores[] = "Por favor, completa todos los campos.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no tiene un formato válido.";
    }

    if (empty($errores)) {
        try {
            // nos conectamos a la bbdd
            $conexion = conectarPDO($host, $user, $password, $bbdd);

            // 
            $query = "SELECT id_usuario, nombre, password, email, rol, activo FROM usuarios WHERE email = :email LIMIT 1";
            $stmt = $conexion->prepare($query);
            $stmt->execute(["email" => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // verifica si encuentra al usuario y la pass es correcta
            if ($usuario && password_verify($campo_password, $usuario["password"])) {

                // verifica si la cuenta este activa
                if ($usuario['activo'] == 1) {
                    session_regenerate_id(true);
                    $_SESSION["usuario_id"] = $usuario["id_usuario"];
                    $_SESSION["email"] = $usuario["email"];
                    $_SESSION["rol"] = $usuario["rol"];
                    $_SESSION["nombre"] = $usuario["nombre"];

                    header("Location: ../index.php");
                    exit();
                } else {
                    $errores[] = "Tu cuenta no está activada. Revisa tu correo para activarla.";
                }

            } else {
                $errores[] = "Correo o contraseña incorrectos.";
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
    <title>Iniciar Sesión - Rally Fotográfico</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header>
    <h1>Iniciar Sesión</h1>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="registro.php">Registrarse</a></li>
        </ul>
    </nav>
</header>

<main>

    <?php if (!empty($errores)): ?>
        <div class="error">
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
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required>
        </p>
        <p>
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>
        </p>
        <p>
            <button type="submit">Iniciar Sesión</button>
        </p>
    </form>

    <div class="enlaces-login">
        <p>¿No tienes cuenta? <a href="registro.php">Registrarse</a></p>
        <p>¿Olvidaste tu contraseña? <a href="recuperar_password.php">Recuperar contraseña</a></p>
    </div>

</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
