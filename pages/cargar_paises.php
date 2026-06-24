<?php

require_once "../includes/conexion.php";

$sql = "INSERT INTO paises
(codigo, nombre, grupo, pagina)
VALUES
('MEX', 'Mexico', 'A', 8),
('RSA', 'South Africa', 'A', 10),
('KOR', 'Korea Republic', 'A', 12),
('CZE', 'Czechia', 'A', 14),

('CAN', 'Canada', 'B', 16),
('BIH', 'Bosnia-Herzegovina', 'B', 18),
('QAT', 'Qatar', 'B', 20),
('SUI', 'Switzerland', 'B', 22),

('BRA', 'Brazil', 'C', 24),
('MAR', 'Morocco', 'C', 26),
('HAI', 'Haiti', 'C', 28),
('SCO', 'Scotland', 'C', 30),

('USA', 'USA', 'D', 32),
('PAR', 'Paraguay', 'D', 34),
('AUS', 'Australia', 'D', 36),
('TUR', 'Türkiye', 'D', 38),

('GER', 'Germany', 'E', 40),
('CUW', 'Curaçao', 'E', 42),
('CIV', 'Côte d’Ivoire', 'E', 44),
('ECU', 'Ecuador', 'E', 46),

('NED', 'Netherlands', 'F', 48),
('JPN', 'Japan', 'F', 50),
('SWE', 'Sweden', 'F', 52),
('TUN', 'Tunisia', 'F', 54),

('BEL', 'Belgium', 'G', 58),
('EGY', 'Egypt', 'G', 60),
('IRN', 'IR Iran', 'G', 62),
('NZL', 'New Zealand', 'G', 64),

('ESP', 'Spain', 'H', 66),
('CPV', 'Cabo Verde', 'H', 68),
('KSA', 'Saudi Arabia', 'H', 70),
('URU', 'Uruguay', 'H', 72),

('FRA', 'France', 'I', 74),
('SEN', 'Senegal', 'I', 76),
('IRQ', 'Iraq', 'I', 78),
('NOR', 'Norway', 'I', 80),

('ARG', 'Argentina', 'J', 82),
('ALG', 'Algeria', 'J', 84),
('AUT', 'Austria', 'J', 86),
('JOR', 'Jordan', 'J', 88),

('POR', 'Portugal', 'K', 90),
('COD', 'Congo DR', 'K', 92),
('UZB', 'Uzbekistan', 'K', 94),
('COL', 'Colombia', 'K', 96),

('ENG', 'England', 'L', 98),
('CRO', 'Croatia', 'L', 100),
('GHA', 'Ghana', 'L', 102),
('PAN', 'Panama', 'L', 104),

('FWC', 'Generales Mundial', NULL, 0)

ON DUPLICATE KEY UPDATE
nombre = VALUES(nombre),
grupo = VALUES(grupo),
pagina = VALUES(pagina);
";

try {
    $conexion->exec($sql);
    echo "Países actualizados correctamente.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>