<?php
session_start();
require_once 'utiles/variables.php';
require_once 'utiles/funciones.php';

// Conexión a la base de datos
try {
    $conexion = conectarPDO($host, $user, $password, $bbdd);
    $consulta = "SELECT * FROM eventos WHERE CURDATE() BETWEEN fecha_inicio AND fecha_fin";
    $resultadoEventos = resultadoConsulta($conexion, $consulta);
} catch (PDOException $error) {
    $mensajeError = "Error al conectar con la base de datos: " . $error->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Participa en el Rally Fotográfico. Inscríbete, sube tus mejores fotos, vota y consulta el ranking.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rally Fotográfico</title>
    <link rel="stylesheet" href="css/style_index.css">
    <link rel="icon" href="imagenes/icono-camara.jpg" type="image/jpg">
</head>
<body>

<header class="hero">
    <div class="hero-overlay">
        <h1>Rally Fotográfico</h1>
        <p>Captura el momento perfecto y compártelo con el mundo</p>
    </div>
</header>

<nav class="sticky-nav">
<div class="menu-toggle" id="menu-toggle">&#9776;</div>

<ul class="nav-links" id="nav-links">
<?php if (isset($_SESSION['usuario_id'])): ?>
            <li><a href="usuario/fotos.php">Mis fotos</a></li>
            <!--<li><a href="usuario/inscripcion.php">Inscribirse</a></li>-->
            <li><a href="usuario/mis-inscripciones.php">Mis Inscripciones</a></li>

            

        <!--<li><a href="concurso/galeria.php">Galería</a></li>-->
        <!--<li><a href="concurso/ranking.php">Ranking</a></li>-->
        <!--<li><a href="concurso/votacion.php">Votar Fotos</a></li>-->
        
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <li><a href="admin/panel.php">Administrar Usuarios</a></li>
                <li><a href="admin/validar_fotos.php">Validar Fotos</a></li>
                <li><a href="admin/crear_evento.php">Crear Rally</a></li>
                <li><a href="admin/editar_evento.php">Editar Rally</a></li>
            <?php endif; ?>
            <li><a href="usuario/modificar.php">Modifica tus datos</a></li>
            <li><a href="acceso/logout.php">Cerrar Sesión</a></li>
        <?php else: ?>
            <li><a href="acceso/login.php">Iniciar Sesión</a></li>
            <li><a href="acceso/registro.php">Registrarse</a></li>
            
        <?php endif; ?>

        
        
    </ul>
</nav>


<?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['nombre'])): ?>
    <div class="bienvenida-usuario">
        <p>👋 ¡Hola, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>! Bienvenido de nuevo al Rally Fotográfico.</p>
    </div>
<?php endif; ?>


<main>
<section class="eventos">
    <h2>Galeria por evento activo</h2>

    <?php if (isset($mensajeError)): ?>
        <p class="mensaje-error"><?= $mensajeError ?></p>
    <?php elseif ($resultadoEventos && $resultadoEventos->rowCount() > 0): ?>
        <div class="carousel-container-global">
            <!-- Flecha Izquierda -->
            <button class="arrow outside left" onclick="scrollCarrusel(-1)">&#10094;</button>

            <!-- Carrusel envuelto -->
            <div class="carousel-wrapper">
                <div class="carousel" id="carruselEventos">
                  <?php foreach ($resultadoEventos as $evento): ?>
    <div class="card-evento">
        <div class="img-fondo" style="background-image: url('imagenes/eventos/<?= htmlspecialchars($evento['imagen'] ?? 'evento-default.jpg') ?>');"></div>
        <div class="contenido">
            <h3><?= htmlspecialchars($evento['nombre']) ?></h3>
            <p><?= htmlspecialchars($evento['descripcion']) ?></p>
            <span class="fecha"><?= $evento['fecha_inicio'] ?> al <?= $evento['fecha_fin'] ?></span>

            <!-- Botón Ver Fotos -->
            <div class="boton-ver-fotos">
                <a href="concurso/galeria.php?rally_id=<?= $evento['id_evento'] ?>" class="btn-ver-fotos">Ver galeria</a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
                    
                </div>
                
            </div>

            <!-- Flecha Derecha -->
            <button class="arrow outside right" onclick="scrollCarrusel(1)">&#10095;</button>
        </div>
    <?php else: ?>
        <p>No hay eventos activos por ahora.</p>
    <?php endif; ?>
</section>




    <!-- Sección para todos los usuarios -->
    <section class="inscribete">
        <h2>¿Listo para participar?</h2>
        <p>Inscríbete en un evento y comparte tus mejores fotos con la comunidad.</p>
        <a href="<?= isset($_SESSION['usuario_id']) ? 'usuario/inscripcion.php' : 'acceso/login.php' ?>" class="btn-inscribirse">Inscribirse ahora</a>
    </section>
</main>

<footer>
    <p>&copy; 2025 Rally Fotográfico</p>
    <p>Contactanos en admin_rally@antonioestevez.es</p>
</footer>
<script>
function scrollCarrusel(direccion) {
    const carrusel = document.getElementById("carruselEventos");
    const tarjeta = carrusel.querySelector(".card-evento");
    const ancho = tarjeta.offsetWidth + 20;
    carrusel.scrollBy({
        left: direccion * ancho,
        behavior: "smooth"
    });
}

//mmenu
const toggle = document.getElementById('menu-toggle');
  const navLinks = document.getElementById('nav-links');

  toggle.addEventListener('click', () => {
    navLinks.classList.toggle('show');
  });
</script>



</body>
</html>
