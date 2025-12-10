-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 10-12-2025 a las 10:23:36
-- Versión del servidor: 10.11.13-MariaDB-0ubuntu0.24.04.1
-- Versión de PHP: 8.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `aither`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id`, `usuario_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distancia_diaria`
--

CREATE TABLE `distancia_diaria` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `distancia_total` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_incidencia`
--

CREATE TABLE `estado_incidencia` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estado_incidencia`
--

INSERT INTO `estado_incidencia` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Abierta', 'Incidencia recién creada'),
(2, 'Asignada', 'Asignada a un técnico'),
(3, 'En curso', 'Actualmente en resolución'),
(4, 'Cerrada', 'Incidencia resuelta y finalizada'),
(5, 'Cancelada', 'Incidencia anulada o inválida');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos_incidencia`
--

CREATE TABLE `fotos_incidencia` (
  `id` int(11) NOT NULL,
  `incidencia_id` int(11) NOT NULL,
  `foto` mediumblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `fotos_incidencia`
--

INSERT INTO `fotos_incidencia` (`id`, `incidencia_id`, `foto`) VALUES
(1, 1, );

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos_perfil_usuario`
--

CREATE TABLE `fotos_perfil_usuario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `foto` mediumblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `fotos_perfil_usuario`
--

INSERT INTO `fotos_perfil_usuario` (`id`, `usuario_id`, `foto`) VALUES
(1, 22, );

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias`
--

CREATE TABLE `incidencias` (
  `id` int(11) NOT NULL,
  `id_tecnico` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_sensor` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_finalizacion` timestamp NULL DEFAULT NULL,
  `estado_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `incidencias`
--

INSERT INTO `incidencias` (`id`, `id_tecnico`, `id_user`, `id_sensor`, `titulo`, `descripcion`, `fecha_creacion`, `fecha_finalizacion`, `estado_id`) VALUES
(1, NULL, NULL, NULL, 'prueba', 'adfsgfrtfnhygjmh,mdfsa', '2025-11-20 09:03:37', NULL, 1),
(2, NULL, 1, NULL, 'Sensor sin conexión', 'El sensor MAC-001 no transmite datos desde ayer por la tarde.', '2025-11-20 13:30:00', NULL, 2),
(3, NULL, 2, NULL, 'Lectura errática de humedad', 'Los valores de humedad saltan de 30 % a 90 % sin motivo aparente.', '2025-11-19 08:15:00', NULL, 2),
(4, NULL, 3, NULL, 'Batería baja en nodo exterior', 'El nodo ubicado en jardín muestra 5 % de batería.', '2025-11-18 15:45:00', NULL, 3),
(5, NULL, 2, NULL, 'Ruido en sensor de CO₂', 'Gráfica con picos anómalos cada 5 minutos.', '2025-11-17 10:00:00', NULL, 4),
(6, NULL, 3, NULL, 'Caja abierta', 'La tapa del sensor está rota y expone la placa a la lluvia.', '2025-11-16 07:20:00', '2025-12-09 09:14:26', 1),
(7, NULL, 1, NULL, 'Pérdida de GPS', 'La localización aparece como 0,0.', '2025-11-15 12:10:00', NULL, 2),
(8, NULL, 3, NULL, 'Temperatura congelada', 'Hace 24 h que el valor no cambia: siempre 25,3 °C.', '2025-11-14 06:55:00', NULL, 3),
(9, NULL, 2, NULL, 'Firmware desactualizado', 'Versión 1.0.3 reporta error de checksum al arrancar.', '2025-11-13 11:30:00', NULL, 5),
(10, NULL, 2, NULL, 'LED de estado apagado', 'El led verde no parpadea, aunque los datos llegan correctamente.', '2025-11-12 17:40:00', NULL, 4),
(11, NULL, 1, NULL, 'Antena doblada', 'El dispositivo recibió un golpe y la antena está deformada.', '2025-11-11 09:05:00', NULL, 1);

--
-- Disparadores `incidencias`
--
DELIMITER $$
CREATE TRIGGER `trg_actualizar_fecha_cierre` BEFORE UPDATE ON `incidencias` FOR EACH ROW BEGIN
  DECLARE idCerrada INT;

  -- Buscar el ID del estado 'Cerrada'
  SELECT id INTO idCerrada 
  FROM estado_incidencia 
  WHERE nombre = 'Cerrada' 
  LIMIT 1;

  -- Si cambia a estado Cerrada, registrar la fecha de finalización
  IF NEW.estado_id = idCerrada AND OLD.estado_id <> idCerrada THEN
    SET NEW.fecha_finalizacion = NOW();
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicion`
--

CREATE TABLE `medicion` (
  `id` int(11) NOT NULL,
  `tipo_medicion_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `localizacion` varchar(255) DEFAULT NULL,
  `sensor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sensor`
--

CREATE TABLE `sensor` (
  `id` int(11) NOT NULL,
  `mac` varchar(50) NOT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `problema` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tecnicos`
--

CREATE TABLE `tecnicos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tecnicos`
--

INSERT INTO `tecnicos` (`id`, `usuario_id`) VALUES
(1, 2),
(2, 21);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_medicion`
--

CREATE TABLE `tipo_medicion` (
  `id` int(11) NOT NULL,
  `medida` varchar(100) NOT NULL,
  `unidad` varchar(50) NOT NULL,
  `txt` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(150) DEFAULT NULL,
  `gmail` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 0,
  `token` text DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `nombre`, `apellidos`, `gmail`, `password`, `activo`, `token`, `token_expira`) VALUES
(1, 'Manuel', 'Pérez Garcia', 'mpergar9@upv.edu', 'pablothegoat', 1, NULL, NULL),
(2, 'Greycy', 'Burgos Salazar', 'grey@gmail.com', 'asdfghjkl', 1, NULL, NULL),
(3, 'Pablo', 'BoxMark', 'palomaperu@gmail.com', 'qwertyuiop', 1, NULL, NULL),
(21, 'marco', 'polo', 'manupergar02@gmail.com', '$2y$10$gRZuSHKEavwIoXDGkw5bL.o.XuIlvrEGPHUf7l8rVUl8T4EprZwnW', 1, 'Loc@1234', NULL),
(22, 'ferran', 'sansaloni', 'ferransansaloni@gmail.com', '$2y$10$l6xi6g8l..smYRz.p2Od5.qn/t771VcSb1Fy50WFVXIAqSI0Wwr2.', 1, NULL, NULL),
(23, 'ferran', 'sansaloni', 'ferransansaloni2@gmail.com', '$2y$10$h03pvNYEaZcg4v3foMLT7ujGBfaXA8iaQ4IR6eaTSnLxmokJGUDWC', 1, NULL, NULL),
(24, 'Manuel', 'Perez', 'manuper777@gmail.com', '$2y$10$BcHO4nLi2qJWvJ0zAMaIfe1EMutymjN/gmj0MtXQ5Yb/2NmYQkuyq', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_sensor`
--

CREATE TABLE `usuario_sensor` (
  `id_relacion` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `sensor_id` int(11) NOT NULL,
  `actual` tinyint(1) DEFAULT 1,
  `inicio_relacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fin_relacion` timestamp NULL DEFAULT NULL,
  `comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `distancia_diaria`
--
ALTER TABLE `distancia_diaria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_fecha_unica` (`usuario_id`,`fecha`);

--
-- Indices de la tabla `estado_incidencia`
--
ALTER TABLE `estado_incidencia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `fotos_incidencia`
--
ALTER TABLE `fotos_incidencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incidencia_id` (`incidencia_id`);

--
-- Indices de la tabla `fotos_perfil_usuario`
--
ALTER TABLE `fotos_perfil_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_incidencias_usuarios` (`id_tecnico`,`id_user`),
  ADD KEY `fk_incidencias_user` (`id_user`),
  ADD KEY `fk_incidencias_estado` (`estado_id`),
  ADD KEY `idx_incidencias_fecha` (`fecha_creacion`),
  ADD KEY `fk_incidencia_sensor` (`id_sensor`);

--
-- Indices de la tabla `medicion`
--
ALTER TABLE `medicion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_medicion_id` (`tipo_medicion_id`),
  ADD KEY `idx_medicion_sensor_hora` (`sensor_id`,`hora`);

--
-- Indices de la tabla `sensor`
--
ALTER TABLE `sensor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mac` (`mac`);

--
-- Indices de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `tipo_medicion`
--
ALTER TABLE `tipo_medicion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gmail` (`gmail`),
  ADD KEY `idx_usuario_gmail` (`gmail`);

--
-- Indices de la tabla `usuario_sensor`
--
ALTER TABLE `usuario_sensor`
  ADD PRIMARY KEY (`id_relacion`),
  ADD KEY `sensor_id` (`sensor_id`),
  ADD KEY `idx_usuario_sensor_actual` (`usuario_id`,`actual`),
  ADD KEY `idx_usuario_sensor_usuario` (`usuario_id`),
  ADD KEY `idx_usuario_sensor_sensor` (`sensor_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `distancia_diaria`
--
ALTER TABLE `distancia_diaria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estado_incidencia`
--
ALTER TABLE `estado_incidencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `fotos_incidencia`
--
ALTER TABLE `fotos_incidencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `fotos_perfil_usuario`
--
ALTER TABLE `fotos_perfil_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `medicion`
--
ALTER TABLE `medicion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sensor`
--
ALTER TABLE `sensor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `tipo_medicion`
--
ALTER TABLE `tipo_medicion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `usuario_sensor`
--
ALTER TABLE `usuario_sensor`
  MODIFY `id_relacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD CONSTRAINT `fk_admin_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `distancia_diaria`
--
ALTER TABLE `distancia_diaria`
  ADD CONSTRAINT `distancia_diaria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `fotos_incidencia`
--
ALTER TABLE `fotos_incidencia`
  ADD CONSTRAINT `fotos_incidencia_ibfk_1` FOREIGN KEY (`incidencia_id`) REFERENCES `incidencias` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `fotos_perfil_usuario`
--
ALTER TABLE `fotos_perfil_usuario`
  ADD CONSTRAINT `fotos_perfil_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD CONSTRAINT `fk_incidencia_sensor` FOREIGN KEY (`id_sensor`) REFERENCES `sensor` (`id`),
  ADD CONSTRAINT `fk_incidencias_estado` FOREIGN KEY (`estado_id`) REFERENCES `estado_incidencia` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_incidencias_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `usuario` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_incidencias_user` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `medicion`
--
ALTER TABLE `medicion`
  ADD CONSTRAINT `medicion_ibfk_1` FOREIGN KEY (`tipo_medicion_id`) REFERENCES `tipo_medicion` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `medicion_ibfk_2` FOREIGN KEY (`sensor_id`) REFERENCES `sensor` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  ADD CONSTRAINT `fk_tecnico_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario_sensor`
--
ALTER TABLE `usuario_sensor`
  ADD CONSTRAINT `usuario_sensor_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `usuario_sensor_ibfk_2` FOREIGN KEY (`sensor_id`) REFERENCES `sensor` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
