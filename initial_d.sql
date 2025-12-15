-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-12-2025 a las 01:03:41
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `initial_d`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

CREATE TABLE `direcciones` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ciudad` varchar(120) DEFAULT NULL,
  `cp` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_contacto`
--

CREATE TABLE `mensajes_contacto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `producto` varchar(100) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) DEFAULT 0.00,
  `estado` enum('pendiente','confirmado','enviado','completado') DEFAULT 'pendiente',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `direccion_envio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_productos`
--

CREATE TABLE `pedidos_productos` (
  `id` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT 1,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('Coche','Moto','Accesorio') NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `precio_oferta` decimal(10,2) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `estado` enum('disponible','reservado','vendido') DEFAULT 'disponible',
  `stock` int(11) DEFAULT 1,
  `imagen2` varchar(255) DEFAULT NULL,
  `imagen3` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `ventas` int(11) NOT NULL DEFAULT 0,
  `visitas` int(11) NOT NULL DEFAULT 0,
  `top_ventas` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `tipo`, `marca`, `modelo`, `descripcion`, `precio`, `precio_oferta`, `imagen`, `estado`, `stock`, `imagen2`, `imagen3`, `fecha_creacion`, `destacado`, `ventas`, `visitas`, `top_ventas`) VALUES
(1, 'Toyota AE86 Trueno', 'Coche', 'Toyota', 'AE86', 'Coche clásico de Initial D, ligero y ágil, ideal para montaña.', 15000.00, NULL, 'ae86.jpg', 'disponible', 3, 'ae86(2).jpg', 'ae86(3).jpg', '2025-12-04 14:09:37', 1, 0, 101, 0),
(2, 'Mazda RX-7 FD', 'Coche', 'Mazda', 'RX-7', 'Deportivo japonés con motor rotativo y tracción trasera.', 22000.00, NULL, 'rx7.jpg', 'disponible', 2, 'rx7(2).jpg', 'rx7(3).jpg', '2025-12-04 14:09:37', 0, 0, 22, 0),
(3, 'Nissan Skyline GT-R R34', 'Coche', 'Nissan', 'GT-R R34', 'Icónico deportivo con motor RB26DETT y tracción total.', 45000.00, NULL, 'r34.jpg', 'disponible', 1, 'r34(2).jpg', 'r34(3).jpg', '2025-12-04 14:09:37', 1, 0, 29, 0),
(4, 'Subaru Impreza WRX STI', 'Coche', 'Subaru', 'WRX STI', 'Deportivo 4x4 con gran rendimiento en curvas y rally.', 28000.00, NULL, 'impreza.jpg', 'disponible', 3, 'impreza(2).jpg', 'impreza(3).jpg', '2025-12-04 14:09:37', 0, 0, 9, 0),
(5, 'Mitsubishi Lancer Evo VI', 'Coche', 'Mitsubishi', 'Evo VI', 'Modelo legendario con tracción integral y alto rendimiento.', 30000.00, NULL, 'evo6.jpg', 'disponible', 2, 'evo6(2).jpg', 'evo6(3).jpg', '2025-12-04 14:09:37', 0, 0, 5, 0),
(6, 'Yamaha R1', 'Moto', 'Yamaha', 'R1', 'Moto deportiva de 1000cc, ligera y potente.', 12000.00, 10000.00, 'r1.jpg', 'disponible', 3, 'r1(2).jpg', 'r1(3).jpg', '2025-12-04 14:09:37', 1, 0, 4, 0),
(7, 'Kawasaki Ninja ZX-10R', 'Moto', 'Kawasaki', 'ZX-10R', 'Superbike de alto rendimiento y diseño aerodinámico.', 13500.00, NULL, 'zx10r.jpg', 'disponible', 1, 'zx10r(2).jpg', 'zx10r(3).jpg', '2025-12-04 14:09:37', 0, 0, 5, 0),
(8, 'Honda CBR600RR', 'Moto', 'Honda', 'CBR600RR', 'Deportiva equilibrada, ideal para circuito y carretera.', 11000.00, NULL, 'cbr600rr.jpg', 'disponible', 2, 'cbr600rr(2).jpg\r\n', 'cbr600rr(3).jpg\r\n', '2025-12-04 14:09:37', 0, 0, 25, 0),
(9, 'Camiseta Initial D', 'Accesorio', 'Initial D', 'Edición limitada', 'Camiseta oficial con logotipo bordado.', 25.00, NULL, 'camiseta.jpg', 'disponible', 9, 'camiseta(2).jpg\r\n', 'camiseta(3).jpg\r\n', '2025-12-04 14:09:37', 1, 0, 28, 0),
(10, 'Gorra Team Akina', 'Accesorio', 'Initial D', 'Team Akina', 'Gorra negra con logo del equipo de Takumi.', 18.00, NULL, 'gorra.jpg', 'disponible', 10, 'gorra(2).jpg', 'gorra(3).jpg', '2025-12-04 14:09:37', 0, 0, 12, 0),
(11, 'Llavero AE86', 'Accesorio', 'Initial D', 'AE86 Keychain', 'Llavero metálico con diseño del AE86 Trueno.', 8.50, NULL, 'llavero.jpg', 'disponible', 20, 'llavero(2).jpg', 'llavero(3).jpg', '2025-12-04 14:09:37', 0, 0, 3, 0),
(12, 'Poster Initial D', 'Accesorio', 'Initial D', 'Classic Poster', 'Póster decorativo con arte original del anime.', 12.00, NULL, 'poster.jpg', 'disponible', 6, 'poster(2).jpg', '', '2025-12-04 14:09:37', 0, 0, 47, 0),
(22, 'Nissan Silvia S15 Spec-R', 'Coche', 'Nissan', 'Silvia S15', 'Coche icónico de drift, tracción trasera y motor turbo SR20DET.', 32000.00, NULL, 's15.jpg', 'disponible', 2, 's15(2).jpg', 's15(3).jpg', '2025-12-13 16:29:15', 0, 0, 6, 0),
(23, 'Toyota Supra MK4', 'Coche', 'Toyota', 'Supra MK4', 'Legendario deportivo con motor 2JZ-GTE, enorme potencial.', 68000.00, NULL, 'supra.jpg', 'disponible', 1, 'supra(2).jpg', 'supra(3).jpg', '2025-12-13 16:29:15', 0, 0, 1, 0),
(24, 'Mazda RX-8', 'Coche', 'Mazda', 'RX-8', 'Deportivo rotativo equilibrado y ligero.', 16000.00, NULL, 'rx8.jpg', 'disponible', 4, 'rx8(2).jpg', 'rx8(3).jpg', '2025-12-13 16:29:15', 0, 0, 3, 0),
(25, 'Toyota GT86', 'Coche', 'Toyota', 'GT86', 'Coupé moderno de tracción trasera perfecto para drift.', 24000.00, NULL, 'gt86.jpg', 'disponible', 5, 'gt86(2).jpg', 'gt86(3).jpg', '2025-12-13 16:29:15', 0, 0, 2, 0),
(26, 'BMW E36 Drift', 'Coche', 'BMW', 'E36', 'Base muy usada en drift profesional.', 18000.00, NULL, 'e36.jpg', 'disponible', 3, 'e36(2).jpg', 'e36(3).jpg', '2025-12-13 16:29:15', 0, 0, 3, 0),
(27, 'BMW E46 M3', 'Coche', 'BMW', 'E46 M3', 'Deportivo equilibrado con motor atmosférico.', 39000.00, 32000.00, 'e46.jpg', 'disponible', 2, 'e46(2).jpg', 'e46(3).jpg', '2025-12-13 16:29:15', 0, 0, 12, 0),
(28, 'Nissan 350Z', 'Coche', 'Nissan', '350Z', 'Coche popular en drift con motor V6.', 21000.00, NULL, '350z.jpg', 'disponible', 4, '350z(2).jpg', '350z(3).jpg', '2025-12-13 16:29:15', 0, 0, 12, 0),
(29, 'Toyota Chaser JZX100', 'Coche', 'Toyota', 'JZX100', 'Sedán japonés muy usado en drift profesional.', 35000.00, NULL, 'chaser.jpg', 'disponible', 1, 'chaser(2).jpg', 'chaser(3).jpg', '2025-12-13 16:29:15', 0, 0, 7, 0),
(30, 'Yamaha R6', 'Moto', 'Yamaha', 'R6', 'Supersport ligera y muy usada en circuito.', 9500.00, NULL, 'r6.jpg', 'disponible', 6, 'r6(2).jpg', 'r6(3).jpg', '2025-12-13 16:29:15', 0, 0, 3, 0),
(31, 'Honda CBR1000RR', 'Moto', 'Honda', 'CBR1000RR', 'Fireblade de alto rendimiento.', 14500.00, NULL, 'cbr1000rr.jpg', 'disponible', 3, 'cbr1000rr(2).jpg', 'cbr1000rr(3).jpg', '2025-12-13 16:29:15', 0, 0, 1, 0),
(32, 'Suzuki GSX-R750', 'Moto', 'Suzuki', 'GSX-R750', 'Equilibrio perfecto entre potencia y peso.', 10500.00, NULL, 'gsxr750.jpg', 'disponible', 4, 'gsxr750(2).jpg', 'gsxr750(3).jpg', '2025-12-13 16:29:15', 0, 0, 2, 0),
(33, 'KTM RC8', 'Moto', 'KTM', 'RC8', 'Deportiva V-Twin exclusiva.', 12500.00, NULL, 'rc8.jpg', 'disponible', 2, 'rc8(2).jpg', 'rc8(3).jpg', '2025-12-13 16:29:15', 0, 0, 6, 0),
(34, 'Turbo Garrett GT2860', 'Accesorio', 'Garrett', 'GT2860', 'Turbo de alto rendimiento para motores turbo JDM.', 1350.00, 1199.00, 'GT2860.jpg', 'disponible', 10, 'GT2860(2).jpg', 'GT2860(3).jpg', '2025-12-13 16:29:15', 0, 0, 16, 0),
(35, 'Suspensión Coilover BC Racing', 'Accesorio', 'BC Racing', 'BR Series', 'Coilovers regulables para drift y track.', 980.00, NULL, 'coilovers.jpg', 'disponible', 8, 'coilovers(2).jpg', 'coilovers(3).jpg', '2025-12-13 16:29:15', 0, 0, 5, 0),
(36, 'Embrague Stage 3 Exedy', 'Accesorio', 'Exedy', 'Stage 3', 'Embrague reforzado para altas potencias.', 620.00, NULL, 'embrague.jpg', 'disponible', 12, 'embrague(2).jpg', 'embrague(3).jpg', '2025-12-13 16:29:15', 0, 0, 2, 0),
(37, 'Intercooler Front Mount', 'Accesorio', 'HKS', 'FMIC', 'Intercooler frontal para motores turbo.', 480.00, NULL, 'intercooler.jpg', 'disponible', 9, 'intercooler(2).jpg', 'intercooler(3).jpg', '2025-12-13 16:29:15', 0, 0, 1, 0),
(38, 'Frenos Brembo 6 Pistones', 'Accesorio', 'Brembo', 'GT Kit', 'Kit de frenos de alto rendimiento.', 2200.00, 1800.00, 'brembo.jpg', 'disponible', 3, 'brembo(2).jpg', 'brembo(3).jpg', '2025-12-13 16:29:15', 0, 0, 3, 0),
(39, 'Diferencial LSD Cusco', 'Accesorio', 'Cusco', 'LSD RS', 'Diferencial autoblocante para drift.', 1100.00, NULL, 'lsd.jpg', 'disponible', 5, 'lsd(2).jpg', 'lsd(3).jpg', '2025-12-13 16:29:15', 0, 0, 0, 0),
(40, 'Chaqueta Initial D', 'Accesorio', 'Initial D', 'Takumi Edition', 'Chaqueta inspirada en Takumi Fujiwara.', 89.00, NULL, 'chaqueta.jpg', 'disponible', 15, 'chaqueta(2).jpg', 'chaqueta(3).jpg', '2025-12-13 16:29:15', 0, 0, 7, 0),
(41, 'Sudadera AE86', 'Accesorio', 'Initial D', 'AE86 Hoodie', 'Sudadera premium del AE86.', 65.00, NULL, 'sudadera.jpg', 'disponible', 20, 'sudadera(2).jpg', 'sudadera(3).jpg', '2025-12-13 16:29:15', 0, 0, 9, 0),
(42, 'Reloj Initial D', 'Accesorio', 'Initial D', 'Limited Watch', 'Reloj edición limitada.', 120.00, NULL, 'reloj.jpg', 'disponible', 7, 'reloj(2).jpg', 'reloj(3).jpg', '2025-12-13 16:29:15', 0, 0, 2, 0),
(43, 'Figura AE86 Drift', 'Accesorio', 'Initial D', 'AE86 Figure', 'Figura coleccionista del AE86.', 55.00, NULL, 'figura.jpg', 'disponible', 10, 'figura(2).jpg', 'figura(3).jpg', '2025-12-13 16:29:15', 0, 0, 10, 0),
(44, 'Filtro Aire K&N Panel', 'Accesorio', 'K&N', 'High Flow', 'Filtro de alto flujo reutilizable.', 75.00, NULL, 'filtro_kn.jpg', 'disponible', 20, 'filtro_kn(2).jpg', 'filtro_kn(3).jpg', '2025-12-13 16:31:53', 0, 0, 1, 0),
(45, 'Blow Off HKS SSQV', 'Accesorio', 'HKS', 'SSQV', 'Válvula de descarga para motores turbo.', 260.00, NULL, 'blowoff_hks.jpg', 'disponible', 8, 'blowoff_hks(2).jpg', 'blowoff_hks(3).jpg', '2025-12-13 16:31:53', 0, 0, 4, 0),
(46, 'Colector Escape Acero', 'Accesorio', 'Aftermarket', 'Turbo Manifold', 'Colector reforzado para turbo.', 520.00, NULL, 'colector_escape.jpg', 'disponible', 5, 'colector_escape(2).jpg', 'colector_escape(3).jpg', '2025-12-13 16:31:53', 0, 0, 1, 0),
(47, 'Downpipe Performance', 'Accesorio', 'Aftermarket', 'Downpipe', 'Downpipe de alto flujo.', 390.00, 320.00, 'downpipe.jpg', 'disponible', 7, 'downpipe(2).jpg', 'downpipe(3).jpg', '2025-12-13 16:31:53', 0, 0, 1, 0),
(48, 'Radiador Aluminio Mishimoto', 'Accesorio', 'Mishimoto', 'Aluminum Rad', 'Radiador de aluminio de alto rendimiento.', 450.00, NULL, 'radiador.jpg', 'disponible', 6, 'radiador(2).jpg', 'radiador(3).jpg', '2025-12-13 16:31:53', 0, 0, 1, 0),
(49, 'Volante Motor Aligerado', 'Accesorio', 'Exedy', 'Lightweight Flywheel', 'Mejora respuesta del motor.', 380.00, NULL, 'volante_motor.jpg', 'disponible', 6, 'volante_motor(2).jpg', 'volante_motor(3).jpg', '2025-12-13 16:31:53', 0, 0, 2, 0),
(50, 'Palanca Short Shifter', 'Accesorio', 'IRP', 'Short Shifter', 'Recorridos de cambio más cortos.', 290.00, NULL, 'short_shifter.jpg', 'disponible', 9, 'short_shifter(2).jpg', 'short_shifter(3).jpg', '2025-12-13 16:31:53', 0, 0, 4, 0),
(51, 'Cardan Reforzado Drift', 'Accesorio', 'Aftermarket', 'Drift Shaft', 'Eje de transmisión reforzado.', 680.00, NULL, 'cardan.jpg', 'disponible', 3, 'cardan(2).jpg', 'cardan(3).jpg', '2025-12-13 16:31:53', 0, 0, 1, 0),
(52, 'Soportes Motor Poliuretano', 'Accesorio', 'Energy Suspension', 'Engine Mounts', 'Reduce movimiento del motor.', 210.00, NULL, 'soportes_motor.jpg', 'disponible', 10, 'soportes_motor(2).jpg', 'soportes_motor(3).jpg', '2025-12-13 16:31:53', 0, 0, 2, 0),
(53, 'Brazos Dirección Angle Kit', 'Accesorio', 'Wisefab', 'Angle Kit', 'Mayor ángulo de giro para drift.', 850.00, NULL, 'angle_kit.jpg', 'disponible', 2, 'angle_kit(2).jpg', 'angle_kit(3).jpg', '2025-12-13 16:31:53', 0, 0, 9, 0),
(54, 'Barra Estabilizadora Delantera', 'Accesorio', 'Whiteline', 'Front Sway Bar', 'Reduce balanceo en curvas.', 260.00, NULL, 'barra_estab.jpg', 'disponible', 7, 'barra_estab(2).jpg', 'barra_estab(3).jpg', '2025-12-13 16:31:53', 0, 0, 2, 0),
(55, 'Copelas Regulables', 'Accesorio', 'Tein', 'Pillowball', 'Ajuste preciso de caída.', 340.00, NULL, 'copelas.jpg', 'disponible', 5, 'copelas(2).jpg', 'copelas(3).jpg', '2025-12-13 16:31:53', 0, 0, 10, 0),
(56, 'Latiguillos Metálicos', 'Accesorio', 'Goodridge', 'Brake Lines', 'Mejor tacto de frenada.', 120.00, NULL, 'latiguillos.jpg', 'disponible', 15, 'latiguillos(2).jpg', 'latiguillos(3).jpg', '2025-12-13 16:31:53', 0, 0, 3, 0),
(57, 'Pastillas Frenos Track', 'Accesorio', 'EBC', 'Yellowstuff', 'Pastillas deportivas para circuito.', 160.00, 120.00, 'pastillas.jpg', 'disponible', 12, 'pastillas(2).jpg', 'pastillas(3).jpg', '2025-12-13 16:31:53', 0, 0, 9, 0),
(58, 'Discos Frenos Rayados', 'Accesorio', 'Brembo', 'Sport Disc', 'Discos ventilados y rayados.', 420.00, NULL, 'discos.jpg', 'disponible', 6, 'discos(2).jpg', 'discos(3).jpg', '2025-12-13 16:31:53', 0, 0, 12, 0),
(59, 'Centralita Programable', 'Accesorio', 'Haltech', 'Elite 750', 'ECU programable profesional.', 1450.00, NULL, 'haltech.jpg', 'disponible', 2, 'haltech(2).jpg', 'haltech(3).jpg', '2025-12-13 16:31:53', 0, 0, 13, 0),
(60, 'Wideband AFR', 'Accesorio', 'AEM', 'Wideband', 'Control preciso de mezcla.', 140.00, NULL, 'wideband.jpg', 'disponible', 6, 'wideband(2).jpg', 'wideband(3).jpg', '2025-12-13 16:31:53', 0, 0, 1, 0),
(61, 'Reloj Presión Turbo', 'Accesorio', 'Defi', 'Boost Gauge', 'Indicador de presión de turbo.', 120.00, 90.00, 'boost_gauge.jpg', 'disponible', 7, 'boost_gauge(2).jpg', 'boost_gauge(3).jpg', '2025-12-13 16:31:53', 0, 0, 19, 0),
(62, 'Asiento Baquet FIA', 'Accesorio', 'Sparco', 'Sprint', 'Asiento homologado FIA.', 420.00, NULL, 'baquet.jpg', 'disponible', 5, 'baquet(2).jpg', 'baquet(3).jpg', '2025-12-13 16:31:53', 0, 0, 20, 0),
(63, 'Arnés 4 Puntos', 'Accesorio', 'OMP', '4 Point', 'Arnés de seguridad.', 180.00, NULL, 'arnes.jpg', 'disponible', 10, 'arnes(2).jpg', 'arnes(3).jpg', '2025-12-13 16:31:53', 0, 0, 21, 0),
(64, 'Volante Drift', 'Accesorio', 'Nardi', 'Deep Corn', 'Volante cóncavo para drift.', 310.00, NULL, 'volante_drift.jpg', 'disponible', 6, 'volante_drift(2).jpg', 'volante_drift(3).jpg', '2025-12-13 16:31:53', 1, 0, 211, 0),
(65, 'Kawasaki Ninja H2', 'Moto', 'Kawasaki', 'Ninja H2', 'Hiperdeportiva sobrealimentada con compresor, rendimiento extremo y tecnología de competición.', 32000.00, NULL, 'ninja_h2.jpg', 'disponible', 2, 'ninja_h2(2).jpg', 'ninja_h2(3).jpg', '2025-12-13 21:47:37', 0, 0, 73, 0),
(66, 'Volkswagen Golf 8 GTI', 'Coche', 'Volkswagen', 'Golf GTI MK8', 'Compacto deportivo con tracción delantera y comportamiento equilibrado.', 42000.00, NULL, 'golf8_gti.jpg', 'disponible', 4, 'golf8_gti(2).jpg', 'golf8_gti(3).jpg', '2025-12-13 21:47:37', 0, 0, 123, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_visitas`
--

CREATE TABLE `producto_visitas` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `fecha_reserva` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('usuario','admin') DEFAULT 'usuario',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `google_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `fecha_registro`, `google_id`) VALUES
(1, 'Raul Rivas', 'initialdmotors@gmail.com', '436157e6b68c6374f6ef535090c16184f1fc94e379203dcda9c95920cd7976c2', 'admin', '2025-10-26 18:42:52', NULL),
(2, 'Usuario Prueba', 'usuario@correo.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'usuario', '2025-10-26 18:42:52', NULL),
(3, 'Raul', 'raulrivasortega94@gmail.com', '7aad41c5994ad773b5fe7ff78729f171a8aca605218d89efebc41a49a03b7000', 'usuario', '2025-10-26 19:43:46', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoraciones`
--

CREATE TABLE `valoraciones` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `puntuacion` tinyint(4) NOT NULL CHECK (`puntuacion` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `pedidos_productos`
--
ALTER TABLE `pedidos_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `producto_visitas`
--
ALTER TABLE `producto_visitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `valoraciones`
--
ALTER TABLE `valoraciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_producto` (`id_producto`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT de la tabla `pedidos_productos`
--
ALTER TABLE `pedidos_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT de la tabla `producto_visitas`
--
ALTER TABLE `producto_visitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=959;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `valoraciones`
--
ALTER TABLE `valoraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `carrito_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD CONSTRAINT `direcciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos_productos`
--
ALTER TABLE `pedidos_productos`
  ADD CONSTRAINT `pedidos_productos_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_productos_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto_visitas`
--
ALTER TABLE `producto_visitas`
  ADD CONSTRAINT `producto_visitas_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `valoraciones`
--
ALTER TABLE `valoraciones`
  ADD CONSTRAINT `valoraciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `valoraciones_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
