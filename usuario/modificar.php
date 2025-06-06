<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../acceso/login.php");
    exit();
}
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';



$errores = [];
$exito = "";

try {
    $conexion = conectarPDO($host, $user, $password, $bbdd);
    $id = $_SESSION['usuario_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmar = $_POST['confirmar_password'];

        if (!validar_requerido($nombre)) $errores[] = "El nombre es obligatorio.";
        if (!validarEmail($email)) $errores[] = "Correo electrónico inválido.";

        // valida la pass si se quisiera cambiar
        if (!empty($password)) {
            if ($password !== $confirmar) {
                $errores[] = "Las contraseñas no coinciden.";
            } elseif (strlen($password) < 6) {
                $errores[] = "La contraseña debe tener al menos 6 caracteres.";
            }
        }

        if (empty($errores)) {
            if (!empty($password)) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $update = "UPDATE usuarios SET nombre = :nombre, email = :email, password = :password WHERE id_usuario = :id";
                $params = ["nombre" => $nombre, "email" => $email, "password" => $passwordHash, "id" => $id];
            } else {
                $update = "UPDATE usuarios SET nombre = :nombre, email = :email WHERE id_usuario = :id";
                $params = ["nombre" => $nombre, "email" => $email, "id" => $id];
            }

            $stmt = $conexion->prepare($update);
            $stmt->execute($params);
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;
            $exito = "✅ Perfil actualizado correctamente.";
        }
    }

    $stmt = $conexion->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = :id");
    $stmt->execute(["id" => $id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "Error de base de datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="../css/style_fotos.css">
    <link rel="icon" href="../imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header class="encabezado-login">
    <h1>✏️ Editar Perfil</h1>
</header>

<nav>
    <ul>
        <li><a href="../index.php">Inicio</a></li>
        <li><a href="fotos.php">Mis Fotos</a></li>
        <li><a href="mis-inscripciones.php">Mis Inscripciones</a></li>
        <li><a href="../acceso/logout.php">Cerrar Sesión</a></li>
    </ul>
</nav>

<main>
    <?php if (!empty($exito)): ?>
        <div class="mensaje-exito"><?= $exito ?></div>
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

    <form method="post">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required><br><br>

        

        <label for="password">Nueva contraseña (opcional):</label>
        <input type="password" name="password" id="password" placeholder="Déjalo en blanco para no cambiarla"><br><br>

        <label for="confirmar_password">Confirmar nueva contraseña:</label>
        <input type="password" name="confirmar_password" id="confirmar_password" placeholder="Repite la nueva contraseña"><br><br>

        <button type="submit">Guardar Cambios</button>
    </form>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>

</body>
</html>
