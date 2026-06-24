<?php

require_once "../includes/conexion.php";

$usuarioId = 1;

$estadisticas = $conexion->query("
    SELECT
        p.codigo,
        p.nombre,
        p.grupo,
        p.pagina,
        COUNT(f.id) AS total,
        COUNT(c.id) AS conseguidas
    FROM paises p
    INNER JOIN figuritas f
        ON f.pais_codigo = p.codigo
    LEFT JOIN coleccion c
        ON c.figurita_id = f.id
        AND c.usuario_id = $usuarioId
    GROUP BY p.codigo, p.nombre, p.grupo, p.pagina
    ORDER BY 
        CASE WHEN p.codigo = 'FWC' THEN 0 ELSE 1 END,
        p.pagina ASC
")->fetchAll(PDO::FETCH_ASSOC);

$totalFiguritas = 0;
$totalConseguidas = 0;

foreach ($estadisticas as $item) {
    $totalFiguritas += intval($item["total"]);
    $totalConseguidas += intval($item["conseguidas"]);
}

$porcentajeGeneral = $totalFiguritas > 0
    ? round(($totalConseguidas / $totalFiguritas) * 100, 1)
    : 0;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>

<div class="contenedor">

    <h1>📊 Estadísticas del álbum</h1>

    <a href="../index.php" class="boton-volver">
        ← Volver al Dashboard
    </a>

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
            <h2><?php echo $totalFiguritas - $totalConseguidas; ?></h2>
            <p>Faltantes</p>
        </div>

        <div class="card">
            <h2><?php echo $porcentajeGeneral; ?>%</h2>
            <p>Completado</p>
        </div>

    </div>

    <div class="estadisticas-lista">

        <?php foreach ($estadisticas as $item): ?>

            <?php
                $total = intval($item["total"]);
                $conseguidas = intval($item["conseguidas"]);
                $faltantes = $total - $conseguidas;

                $porcentaje = $total > 0
                    ? round(($conseguidas / $total) * 100, 1)
                    : 0;

                $esFwc = $item["codigo"] === "FWC";
            ?>

            <div class="estadistica-pais <?php echo $esFwc ? 'estadistica-fwc' : ''; ?>">

                <div class="estadistica-info">

                    <div>
                        <h2>
                            <?php echo htmlspecialchars($item["nombre"]); ?>
                            <span><?php echo htmlspecialchars($item["codigo"]); ?></span>
                        </h2>

                        <p>
                            <?php if (!$esFwc): ?>
                                Grupo <?php echo htmlspecialchars($item["grupo"]); ?> ·
                            <?php endif; ?>
                            Página <?php echo htmlspecialchars($item["pagina"]); ?>
                        </p>
                    </div>

                    <div class="estadistica-numeros">
                        <strong><?php echo $conseguidas; ?>/<?php echo $total; ?></strong>
                        <span><?php echo $porcentaje; ?>%</span>
                    </div>

                </div>

                <div class="barra-estadistica">
                    <div class="barra-estadistica-interna"
                         style="width: <?php echo $porcentaje; ?>%;">
                    </div>
                </div>

                <div class="estadistica-detalle">
                    <span>Conseguidas: <?php echo $conseguidas; ?></span>
                    <span>Faltantes: <?php echo $faltantes; ?></span>
                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

</body>
</html>
