<?php

require_once "../includes/conexion.php";

$usuarioId = 1;

$faltantes = $conexion->query("
    SELECT 
        f.pais_codigo,
        f.numero,
        f.nombre,
        f.tipo,
        f.pagina,
        p.nombre AS pais_nombre,
        p.grupo
    FROM figuritas f
    INNER JOIN paises p ON f.pais_codigo = p.codigo
    LEFT JOIN coleccion c 
        ON c.figurita_id = f.id 
        AND c.usuario_id = $usuarioId
    WHERE c.id IS NULL
    ORDER BY f.pagina ASC, f.pais_codigo ASC, f.numero ASC
")->fetchAll(PDO::FETCH_ASSOC);

$totalFaltantes = count($faltantes);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Faltantes</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>

<div class="contenedor">

    <h1>❌ Figuritas faltantes</h1>

    <a href="../index.php" class="boton-volver">
        ← Volver al Dashboard
    </a>

    <div class="cabecera-resumen">

        <div class="resumen-seccion">
            <h2><?php echo $totalFaltantes; ?></h2>
            <p>Total de figuritas faltantes</p>
        </div>

        <div class="acciones-seccion">
            <a href="pdf_faltantes.php" class="boton-pdf" target="_blank">
                📄 Generar PDF de faltantes
            </a>
        </div>

    </div>

    <div class="buscador">
        <input type="text" id="buscarFigu" placeholder="Buscar: ARG 07, FWC 03, Argentina...">
    </div>

    <?php if (empty($faltantes)): ?>

        <div class="mensaje exito">
            Álbum completo. Dante ya puede negociar con Panini directamente.
        </div>

    <?php else: ?>

        <div class="lista-faltantes">

            <?php $grupoActual = ""; ?>

            <?php foreach ($faltantes as $figu): ?>

                <?php
                    $codigoCompleto = $figu["pais_codigo"] . " " . str_pad($figu["numero"], 2, "0", STR_PAD_LEFT);
                    $claveGrupo = $figu["pais_codigo"];
                ?>

                <?php if ($grupoActual != $claveGrupo): ?>

                    <?php if ($grupoActual != ""): ?>
                        </div>
                    <?php endif; ?>

                    <h2 class="titulo-grupo-faltantes">
                        <?php echo htmlspecialchars($figu["pais_nombre"]); ?>
                        <span>
                            <?php echo htmlspecialchars($figu["pais_codigo"]); ?>
                            · Página <?php echo htmlspecialchars($figu["pagina"]); ?>
                        </span>
                    </h2>

                    <div class="grupo-faltantes">

                    <?php $grupoActual = $claveGrupo; ?>

                <?php endif; ?>

                <div class="item-faltante"
                     data-busqueda="<?php echo strtolower($codigoCompleto . ' ' . $figu['nombre'] . ' ' . $figu['pais_nombre']); ?>">

                    <strong><?php echo htmlspecialchars($codigoCompleto); ?></strong>
                    <span><?php echo htmlspecialchars($figu["nombre"]); ?></span>

                </div>

            <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

</div>

<script>
const inputBuscar = document.getElementById("buscarFigu");
const items = document.querySelectorAll(".item-faltante");

inputBuscar.addEventListener("input", function () {
    const texto = this.value.toLowerCase().trim();

    items.forEach(item => {
        const data = item.dataset.busqueda;

        item.style.display = data.includes(texto) ? "flex" : "none";
    });
});
</script>

</body>
</html>