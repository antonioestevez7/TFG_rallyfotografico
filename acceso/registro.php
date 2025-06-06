<?php
//Phpmailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

//requeriodo para phpmailer
require_once '../phpmailer/PHPMailer.php';
require_once '../phpmailer/SMTP.php';
require_once '../phpmailer/Exception.php';
// conexion a la base de datos
$conexion = conectarPDO($host, $user, $password, $bbdd);

// Variables
$errores = [];
$nombre = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
$email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
$password = isset($_POST["password"]) ? $_POST["password"] : "";
$password_confirm = isset($_POST["password_confirm"]) ? $_POST["password_confirm"] : "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // validaciones
    if (!validar_requerido($nombre)) {
        $errores[] = "El campo Nombre es obligatorio.";
    }
    if (!validar_requerido($email)) {
        $errores[] = "El campo Email es obligatorio.";
    }
    if (!validarEmail($email)) {
        $errores[] = "El Email no tiene un formato válido.";
    }
    if (!validar_requerido($password)) {
        $errores[] = "El campo Contraseña es obligatorio.";
    }
    if ($password !== $password_confirm) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    // comprueba si el email este ya registrado
    $select = "SELECT COUNT(*) as cuenta FROM usuarios WHERE email = :email";
    $consulta = $conexion->prepare($select);
    $consulta->execute(["email" => $email]);
    $resultado = $consulta->fetch();

    if ($resultado["cuenta"] > 0) {
        $errores[] = "El correo electrónico ya está registrado.";
    }

    // si no hay errores inserta el usuario 
    if (count($errores) === 0) {
        try {
            // Genera el token
            $token = bin2hex(random_bytes(16));

            $insert = "INSERT INTO usuarios (nombre, email, password, rol, activo, token, fecha_registro) 
                       VALUES (:nombre, :email, :password, :rol, :activo, :token, NOW())";
            $consulta = $conexion->prepare($insert);
            $consulta->execute([
                "nombre" => $nombre,
                "email" => $email,
                "password" => password_hash($password, PASSWORD_BCRYPT),
                "rol" => 'participante',
                "activo" => 0, // Cuenta desactivada
                "token" => $token
            ]);

            // email por phpmailer (IONOS)
            $emailEncoded = urlencode($email);
            $tokenEncoded = urlencode($token);

            
            $esLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
            $baseUrl = $esLocal 
            //si es por local
                ? 'http://localhost:3000/' 
                //si es desde IONOS
                : 'https://antonioestevez.es/rally';

            $linkActivacion = "$baseUrl/acceso/verificar-cuenta.php?email=$emailEncoded&token=$tokenEncoded";

            $mensaje = "Hola $nombre,\n\n";
            $mensaje .= "Gracias por registrarte en el Rally Fotografico.\n";
            $mensaje .= "Para activar tu cuenta, haz clic en el siguiente enlace:\n\n";
            $mensaje .= "$linkActivacion\n\n";
            $mensaje .= "Nos vemos pronto!";

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.ionos.es';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'admin_rally@antonioestevez.es'; 
                $mail->Password   = 'tfgjuanantonio';               
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('admin_rally@antonioestevez.es', 'Rally Fotografico');
                $mail->addAddress($email, $nombre);

                $mail->Subject = 'Activa tu cuenta - Rally Fotografico';
                $mail->Body    = $mensaje;

                $mail->send();
                echo "<script>
                    alert('Te hemos enviado un correo para activar tu cuenta.');
                    window.location.href = 'login.php';
                </script>";
            } catch (Exception $e) {
                $errores[] = "No se pudo enviar el correo. Error: " . $mail->ErrorInfo;
            }

        } catch (PDOException $e) {
            $errores[] = "Error al registrar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Rally Fotográfico</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="/favicon.ico?v=10" type="image/x-icon">
</head>
<body>

<header>
    <h1>Registro de Participantes</h1>
    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="login.php">Iniciar Sesión</a></li>
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
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
        </p>
        <p>
            <label for="email">Correo electrónico:</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required>
        </p>
        <p>
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>
        </p>
        <p>
            <label for="password_confirm">Confirmar Contraseña:</label>
            <input type="password" name="password_confirm" id="password_confirm" required>
        </p>
        <p>
            <button type="submit">Registrarse</button>
        </p>
    </form>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
