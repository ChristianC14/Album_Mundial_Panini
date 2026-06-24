<?php

require_once "../includes/conexion.php";

$usuarioId = 1;

$repetidas = $conexion->query("
    SELECT 
        c.cantidad,
        c.imagen,
        f.pais_codigo,
        f.numero,
        f.nombre,
        f.tipo,
        f.pagina,
        p.nombre AS pais_nombre
    FROM coleccion c
    INNER JOIN figuritas f ON c.figurita_id = f.id
    INNER JOIN paises p ON f.pais_codigo = p.codigo
    WHERE c.usuario_id = $usuarioId
    AND c.cantidad > 1
    ORDER BY f.pagina ASC, f.pais_codigo ASC, f.numero ASC
")->fetchAll(PDO::FETCH_ASSOC);

$totalRepetidas = 0;

foreach ($repetidas as $figu) {
    $totalRepetidas += ($figu["cantidad"] - 1);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Repetidas</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>

<div class="contenedor">

    <h1>🔁 Repetidas para cambiar</h1>

    <a href="../index.php" class="boton-volver">
        ← Volver al Dashboard
    </a>

    <div class="cabecera-resumen">

        <div class="resumen-seccion">
            <h2><?php echo $totalRepetidas; ?></h2>
            <p>Total de figuritas repetidas</p>
        </div>

        <div class="acciones-seccion">
            <a href="pdf_repetidas.php" class="boton-pdf" target="_blank">
                📄 Generar PDF de repetidas
            </a>
        </div>

    </div>

    <div class="buscador">
        <input type="text" id="buscarFigu" placeholder="Buscar: ARG 07, FWC 03...">
    </div>

    <?php if (empty($repetidas)): ?>

        <div class="mensaje">
            Todavía no tenés figuritas repetidas.
        </div>

    <?php else: ?>

        <div class="grid-figuritas">

            <?php foreach ($repetidas as $figu): ?>

                <?php
                    $codigoCompleto = $figu["pais_codigo"] . " " . str_pad($figu["numero"], 2, "0", STR_PAD_LEFT);
                    $cantidadRepetida = $figu["cantidad"] - 1;
                ?>

                <div class="figu-card"
                     data-busqueda="<?php echo strtolower($codigoCompleto . ' ' . $figu['nombre'] . ' ' . $figu['pais_nombre']); ?>">

                    <div class="figu-img">
                        <?php if (!empty($figu["imagen"])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($figu["imagen"]); ?>" alt="<?php echo htmlspecialchars($codigoCompleto); ?>">
                        <?php else: ?>
                            <span><?php echo htmlspecialchars($codigoCompleto); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="figu-info">
                        <h3><?php echo htmlspecialchars($codigoCompleto); ?></h3>
                        <p><?php echo htmlspecialchars($figu["nombre"]); ?></p>
                        <small><?php echo htmlspecialchars($figu["pais_nombre"]); ?> · Página <?php echo htmlspecialchars($figu["pagina"]); ?></small>

                        <div class="badge-repetida">
                            🔁 Para cambiar: <?php echo $cantidadRepetida; ?>
                        </div>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<script>
const inputBuscar = document.getElementById("buscarFigu");
const cards = document.querySelectorAll(".figu-card");

inputBuscar.addEventListener("input", function () {
    const texto = this.value.toLowerCase().trim();

    cards.forEach(card => {
        const data = card.dataset.busqueda;

        card.style.display = data.includes(texto) ? "block" : "none";
    });
});
</script>

</body>
</html>