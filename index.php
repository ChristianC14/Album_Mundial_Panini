<?php
require_once "includes/conexion.php";

$usuarioId = 1;

$totalFiguritas = $conexion->query("
    SELECT COUNT(*)
    FROM figuritas
")->fetchColumn();

$totalConseguidas = $conexion->query("
    SELECT COUNT(*)
    FROM coleccion
    WHERE usuario_id = $usuarioId
")->fetchColumn();

$totalRepetidas = $conexion->query("
    SELECT COALESCE(SUM(cantidad - 1), 0)
    FROM coleccion
    WHERE usuario_id = $usuarioId
    AND cantidad > 1
")->fetchColumn();

$totalFaltantes = $totalFiguritas - $totalConseguidas;

$porcentaje = 0;

if ($totalFiguritas > 0) {
    $porcentaje = round(($totalConseguidas / $totalFiguritas) * 100, 2);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Album Mundial Panini</title>

    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>

<div class="contenedor">

    <h1>⚽ Album Mundial Panini</h1>

    <p class="subtitulo">
        Colección de Dante
    </p>

    <div class="menu-principal">

        <a href="pages/agregar_figurita.php" class="menu-card">
            ➕
            <span>Agregar Figurita</span>
        </a>

        <a href="pages/carga_repetidas.php" class="menu-card">
            🔁
            <span>Carga Repetidas</span>
        </a>

        <a href="pages/carga_equipo.php" class="menu-card">
            ⚡
            <span>Carga Equipo</span>
        </a>

        <a href="pages/quitar_repetidas.php" class="menu-card">
            ➖
            <span>Quitar Repetidas</span>
        </a>

        <a href="pages/faltantes.php" class="menu-card">
            ❌
            <span>Faltantes</span>
        </a>

        <a href="pages/repetidas.php" class="menu-card">
            🔁
            <span>Repetidas</span>
        </a>

        <a href="pages/album_virtual.php" class="menu-card">
            📕
            <span>Álbum Virtual</span>
        </a>

        <a href="pages/estadisticas.php" class="menu-card">
            📊
            <span>Estadísticas</span>
        </a>

    </div>

    <div class="dashboard">

        <div class="card">
            <h2><?php echo $totalFiguritas; ?></h2>
            <p>Total figuritas</p>
        </div>

        <div class="card">
            <h2><?php echo $totalConseguidas; ?></h2>
            <p>Conseguidas</p>
        </div>

        <div class="card">
            <h2><?php echo $totalFaltantes; ?></h2>
            <p>Faltantes</p>
        </div>

        <div class="card">
            <h2><?php echo $totalRepetidas; ?></h2>
            <p>Repetidas</p>
        </div>

    </div>

    <div class="progreso">

        <div class="barra">
            <div class="barra-interna"
                 style="width: <?php echo $porcentaje; ?>%;">
            </div>
        </div>

        <p>
            <?php echo $porcentaje; ?>% completado
        </p>

    </div>

</div>

</body>
</html>