CREATE TABLE `Tpersona` (
  `id_persona` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `primerNombre` varchar(100) NOT NULL,
  `segundoNombre` varchar(100),
  `primerApellido` varchar(100) NOT NULL,
  `segundoApellido` varchar(100),
  `ci` int UNIQUE NOT NULL,
  `celular` int,
  `direccion` varchar(255),
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Tchofer` (
  `id_chofer` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_persona` int NOT NULL,
  `fechaIngreso` date,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Tpropietario` (
  `id_propietario` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_persona` int NOT NULL,
  `fechaRegistro` date,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Tusuario` (
  `id_usuario` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_persona` int NOT NULL,
  `username` varchar(100),
  `password` varchar(100),
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Trol` (
  `id_rol` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100),
  `descripcion` text,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `TuserRol` (
  `id_userRol` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_rol` int NOT NULL,
  `id_usuario` int NOT NULL,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `TcambioRol` (
  `id_cambioRol` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_persona` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date,
  `Descripcion` text,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Tgrupo` (
  `id_grupo` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Tauto` (
  `id_auto` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_propietario` int NOT NULL,
  `placa` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `gestion` int NOT NULL,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `TchoferAuto` (
  `id_choferAuto` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_auto` int NOT NULL,
  `id_chofer` int NOT NULL,
  `id_grupo` int NOT NULL,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Tlugar` (
  `id_lugar` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Tasistencia` (
  `id_asistencia` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_chofer` int NOT NULL,
  `id_lugar` int NOT NULL,
  `id_inspector` int NOT NULL,
  `fecha_hora` datetime,
  `asistencia` bit NOT NULL,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `TtipoObligacion` (
  `id_tipoObligacion` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100),
  `descripcion` text,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Tobligaciones` (
  `id_obligacion` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_persona` int NOT NULL,
  `id_tipoObligacion` int NOT NULL,
  `monto` int NOT NULL,
  `fecha_creacion` date,
  `fecha_fin` date,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Tmulta` (
  `id_multa` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_chofer` int NOT NULL,
  `id_inspector` int NOT NULL,
  `monto` int NOT NULL,
  `motivo` text,
  `sancion` text,
  `fecha_infraccion` date,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `Tpago` (
  `id_pago` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_persona` int NOT NULL,
  `fecha_pago` date NOT NULL,
  `motivo` text,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `TpagoObligaciones` (
  `id_pagoObligaciones` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_pago` int NOT NULL,
  `id_obligaciones` int NOT NULL,
  `monto_abonado` int NOT NULL,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

CREATE TABLE `TpagoMultas` (
  `id_pagoMulta` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_pago` int NOT NULL,
  `id_multa` int NOT NULL,
  `monto_abonado` int NOT NULL,
  `estado` bit,
  `usuarioA` varchar(100),
  `fechaA` date
);

ALTER TABLE `Tchofer` ADD FOREIGN KEY (`id_persona`) REFERENCES `Tpersona` (`id_persona`);

ALTER TABLE `Tpropietario` ADD FOREIGN KEY (`id_persona`) REFERENCES `Tpersona` (`id_persona`);

ALTER TABLE `Tusuario` ADD FOREIGN KEY (`id_persona`) REFERENCES `Tpersona` (`id_persona`);

ALTER TABLE `TcambioRol` ADD FOREIGN KEY (`id_persona`) REFERENCES `Tpersona` (`id_persona`);

ALTER TABLE `TuserRol` ADD FOREIGN KEY (`id_rol`) REFERENCES `Trol` (`id_rol`);

ALTER TABLE `TuserRol` ADD FOREIGN KEY (`id_usuario`) REFERENCES `Tusuario` (`id_usuario`);

ALTER TABLE `Tauto` ADD FOREIGN KEY (`id_propietario`) REFERENCES `Tpropietario` (`id_propietario`);

ALTER TABLE `TchoferAuto` ADD FOREIGN KEY (`id_auto`) REFERENCES `Tauto` (`id_auto`);

ALTER TABLE `TchoferAuto` ADD FOREIGN KEY (`id_chofer`) REFERENCES `Tchofer` (`id_chofer`);

ALTER TABLE `TchoferAuto` ADD FOREIGN KEY (`id_grupo`) REFERENCES `Tgrupo` (`id_grupo`);

ALTER TABLE `Tasistencia` ADD FOREIGN KEY (`id_lugar`) REFERENCES `Tlugar` (`id_lugar`);

ALTER TABLE `Tasistencia` ADD FOREIGN KEY (`id_chofer`) REFERENCES `Tchofer` (`id_chofer`);

ALTER TABLE `Tasistencia` ADD FOREIGN KEY (`id_inspector`) REFERENCES `Tchofer` (`id_chofer`);

ALTER TABLE `Tobligaciones` ADD FOREIGN KEY (`id_persona`) REFERENCES `Tpersona` (`id_persona`);

ALTER TABLE `Tobligaciones` ADD FOREIGN KEY (`id_tipoObligacion`) REFERENCES `TtipoObligacion` (`id_tipoObligacion`);

ALTER TABLE `Tmulta` ADD FOREIGN KEY (`id_chofer`) REFERENCES `Tchofer` (`id_chofer`);

ALTER TABLE `Tmulta` ADD FOREIGN KEY (`id_inspector`) REFERENCES `Tpersona` (`id_persona`);

ALTER TABLE `Tpago` ADD FOREIGN KEY (`id_persona`) REFERENCES `Tpersona` (`id_persona`);

ALTER TABLE `TpagoObligaciones` ADD FOREIGN KEY (`id_pago`) REFERENCES `Tpago` (`id_pago`);

ALTER TABLE `TpagoObligaciones` ADD FOREIGN KEY (`id_obligaciones`) REFERENCES `Tobligaciones` (`id_obligacion`);

ALTER TABLE `TpagoMultas` ADD FOREIGN KEY (`id_pago`) REFERENCES `Tpago` (`id_pago`);

ALTER TABLE `TpagoMultas` ADD FOREIGN KEY (`id_multa`) REFERENCES `Tmulta` (`id_multa`);
