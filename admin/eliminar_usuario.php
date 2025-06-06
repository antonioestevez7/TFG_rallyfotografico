<?php
// eliminar_usuario.php
session_start();
require_once '../utiles/variables.php';
require_once '../utiles/funciones.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../acceso/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id'])) {
    $id_usuario = (int)$_POST['usuario_id'];

    try {
        $conexion = conectarPDO($host, $user, $password, $bbdd);

        // No permitir borrar admins
        $stmt = $conexion->prepare("SELECT rol FROM usuarios WHERE id_usuario = :id");
        $stmt->execute(["id" => $id_usuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && $usuario['rol'] !== 'admin') {
            // Eliminar usuario
            $stmtDelete = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
            $stmtDelete->execute(["id" => $id_usuario]);
        }

    } catch (PDOException $e) {
        // Redireccionar con error
        header("Location: panel.php?error=1");
        exit();
    }
}

header("Location: panel.php");
exit();
