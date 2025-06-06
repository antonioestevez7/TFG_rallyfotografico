# TFG - Rally Fotográfico

Proyecto de Fin de Grado  
Autor: Juan Antonio Estévez Sánchez  
Centro: IES Velázquez

## Descripción

Aplicación web desarrollada como Trabajo de Fin de Grado del ciclo de Desarrollo de Aplicaciones Web. El sistema permite la gestión integral de concursos fotográficos (rallies), en los que los usuarios pueden registrarse, inscribirse en eventos activos, subir fotografías, votar imágenes de otros participantes y consultar rankings.

El panel de administración permite crear y editar eventos, validar fotografías subidas por los usuarios y gestionar cuentas.

## Funcionalidades principales

- Registro e inicio de sesión de usuarios
- Inscripción a eventos activos
- Subida y gestión de fotos
- Galería pública con sistema de votación
- Visualización de rankings
- Panel de administración con control de eventos, validación de imágenes y usuarios

## Máquina virtual del proyecto

La máquina virtual utilizada para ejecutar el proyecto puede descargarse desde el siguiente enlace:

https://drive.google.com/file/d/1U2fawglX8I_s2Y12UFKlNqqbaBxvhQAJ/view?usp=drive_link

Contiene Debian 12 con Apache, PHP y MySQL preconfigurados, además del proyecto ya desplegado.

## Requisitos para instalación manual

- Servidor web con Apache
- PHP 8.x
- MySQL o MariaDB
- Navegador web moderno

## Estructura del proyecto

- `index.php`: Página principal
- `usuario/`: Funciones del usuario
- `admin/`: Panel de administración
- `concurso/`: Módulo de participación y galería
- `imagenes/`: Imágenes del sistema y subidas
- `utiles/`: Funciones auxiliares y configuración
- `acceso/`: Registro, login y logout

## Instrucciones de uso local

1. Clonar o copiar el proyecto al directorio del servidor web.
2. Importar la base de datos incluida (si aplica).
3. Configurar el archivo `utiles/variables.php` con los datos de conexión locales.
4. Acceder mediante el navegador a `http://localhost/rally_fotografico`.

## Contacto

admin_rally@antonioestevez.es
