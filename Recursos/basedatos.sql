-- Adminer 4.8.1 MySQL 10.11.6-MariaDB-0+deb12u1 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `eventos`;
CREATE TABLE `eventos` (
  `id_evento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tema` varchar(100) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `max_fotos_usuario` int(11) DEFAULT 2,
  `color_primario` varchar(7) DEFAULT '#007BFF',
  PRIMARY KEY (`id_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `eventos` (`id_evento`, `nombre`, `descripcion`, `tema`, `fecha_inicio`, `fecha_fin`, `imagen`, `max_fotos_usuario`, `color_primario`) VALUES
(1,	'Rally Primavera12',	'Fotografías relacionadas con la primavera',	'Primavera',	'2025-03-01',	'2025-05-14',	NULL,	2,	'#007bff'),
(2,	'Rally Urbano',	'Captura la esencia de la ciudad',	'Ciudad',	'2025-04-01',	'2025-04-30',	NULL,	2,	'#007BFF'),
(21,	'Rally de coches de lujo',	'Rally basado en fotos de coches de muy alta gama.',	'Coches',	'2025-06-04',	'2025-08-07',	'6841896499c6e_rolls-royce-ghost-frente-imponente.jpg',	2,	'#3c4044'),
(22,	'Rally Cristiano Ronaldo',	'Rally basado en el mejor jugador de la historia del futbol',	'Cristiano Ronaldo',	'2025-06-02',	'2025-07-10',	'68418a8a280d2_cr71.jpg',	2,	'#c24242');

DROP TABLE IF EXISTS `fotos`;
CREATE TABLE `fotos` (
  `id_foto` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp(),
  `validada` tinyint(1) DEFAULT 0,
  `motivo_invalidacion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_foto`),
  KEY `id_usuario` (`id_usuario`),
  KEY `fotos_ibfk_2` (`id_evento`),
  CONSTRAINT `fotos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fotos_ibfk_2` FOREIGN KEY (`id_evento`) REFERENCES `eventos` (`id_evento`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `fotos` (`id_foto`, `id_usuario`, `id_evento`, `nombre_archivo`, `fecha_subida`, `validada`, `motivo_invalidacion`) VALUES
(39,	1,	1,	'6841846e3003f_pulpo.png',	'2025-06-05 13:50:08',	1,	NULL),
(40,	1,	21,	'684189d4d4bfa_1.jpg',	'2025-06-05 14:13:10',	1,	NULL),
(41,	1,	21,	'684189d89d7d0_2.jpg',	'2025-06-05 14:13:14',	1,	NULL),
(42,	1,	21,	'684189dba7ff4_3.jpg',	'2025-06-05 14:13:17',	1,	NULL),
(43,	1,	21,	'684189e26bd9f_6.jpg',	'2025-06-05 14:13:24',	1,	NULL),
(44,	1,	21,	'684189e84eccd_8.jpg',	'2025-06-05 14:13:30',	1,	NULL),
(45,	1,	21,	'684189efd0dc7_9.jpg',	'2025-06-05 14:13:37',	1,	NULL),
(46,	1,	21,	'684189f535829_11.jpg',	'2025-06-05 14:13:43',	1,	NULL),
(47,	1,	21,	'684189fa145aa_12.jpg',	'2025-06-05 14:13:48',	1,	NULL),
(48,	1,	21,	'684189fdc0237_14.jpg',	'2025-06-05 14:13:51',	1,	NULL),
(49,	1,	22,	'68418ac3614cc_c1.jpg',	'2025-06-05 14:17:09',	1,	NULL),
(50,	1,	22,	'68418ac6dab32_c2.jpg',	'2025-06-05 14:17:12',	1,	NULL),
(51,	1,	22,	'68418ac9bc7a5_c3.jpg',	'2025-06-05 14:17:15',	1,	NULL),
(52,	1,	22,	'68418accbec32_c4.jpg',	'2025-06-05 14:17:18',	1,	NULL),
(53,	1,	22,	'68418ad07bb18_c5.jpg',	'2025-06-05 14:17:22',	1,	NULL),
(54,	1,	22,	'68418ad5002f5_c7.jpeg',	'2025-06-05 14:17:27',	1,	NULL);

DROP TABLE IF EXISTS `inscripciones`;
CREATE TABLE `inscripciones` (
  `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `fecha_inscripcion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_inscripcion`),
  UNIQUE KEY `id_usuario` (`id_usuario`,`id_evento`),
  KEY `id_evento` (`id_evento`),
  CONSTRAINT `fk_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`id_evento`) REFERENCES `eventos` (`id_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inscripciones` (`id_inscripcion`, `id_usuario`, `id_evento`, `fecha_inscripcion`) VALUES
(23,	1,	1,	'2025-06-05 13:49:58'),
(24,	1,	21,	'2025-06-05 14:11:45'),
(25,	1,	22,	'2025-06-05 14:16:18');

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','participante') DEFAULT 'participante',
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 0,
  `token` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `email`, `password`, `rol`, `fecha_registro`, `activo`, `token`) VALUES
(1,	'Administrador',	'admin@rally.es',	'$2y$10$.Aiv5VdzMpN3I7x7RxCQT.1YPyD25wslh//IeolO43ShXJ8jME9ru',	'admin',	'2025-03-24 09:45:01',	1,	NULL),
(2,	'Ana',	'ana@email.com',	'$2y$10$DHr.1sIGBM6BgNZpC0MidOP0LhxfhX32W1FTJKxcabYimGMkvalu2',	'participante',	'2025-03-24 09:45:01',	1,	'f556676af4341e5701cd12072ef9b39c');

DROP TABLE IF EXISTS `votos`;
CREATE TABLE `votos` (
  `id_voto` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `id_foto` int(11) NOT NULL,
  `fecha_voto` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_voto`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_foto` (`id_foto`),
  CONSTRAINT `votos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `votos_ibfk_2` FOREIGN KEY (`id_foto`) REFERENCES `fotos` (`id_foto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `votos` (`id_voto`, `id_usuario`, `id_foto`, `fecha_voto`) VALUES
(45,	2,	54,	'2025-06-05 12:58:33'),
(46,	2,	53,	'2025-06-05 12:58:34');

-- 2025-06-05 14:24:31