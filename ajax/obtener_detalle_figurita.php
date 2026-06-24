<?php

require_once "../includes/conexion.php";

$usuarioId = 1;

$pais = $_GET["pais"] ?? "";
$numero = intval($_GET["numero"] ?? 0);

$stmt = $conexion->prepare("
    SELECT 
        f.id AS figurita_id,
        f.nombre AS nombre_placeholder,
        f.tipo,
        f.pagina,

        d.nombre_real,
        d.nacimiento,
        d.altura,
        d.peso,
        d.club,

        c.cantidad,
        c.imagen

    FROM figuritas f

    LEFT JOIN figuritas_detalle d
        ON d.figurita_id = f.id

    LEFT JOIN coleccion c
        ON c.figurita_id = f.id
        AND c.usuario_id = ?

    WHERE f.pais_codigo = ?
    AND f.numero = ?
");

$stmt->execute([$usuarioId, $pais, $numero]);

$datos = $stmt->fetch(PDO::FETCH_ASSOC);

header("Content-Type: application/json; charset=utf-8");

echo json_encode($datos ?: []);