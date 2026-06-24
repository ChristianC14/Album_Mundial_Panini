<?php

require_once "../includes/conexion.php";

$usuarioId = 1;

$figuritas = $conexion->query("
    SELECT 
        c.id AS coleccion_id,
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
    ORDER BY f.pagina ASC, f.pais_codigo ASC, f.numero ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Figuritas</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>

<div class="contenedor">

    <h1>📘 Mis Figuritas</h1>

    <a href="../index.php" class="boton-volver">
        ← Volver al Dashboard
    </a>

    <div class="buscador">
        <input type="text" id="buscarFigu" placeholder="Buscar: ARG 07, FWC 03, Messi...">
    </div>

    <?php if (empty($figuritas)): ?>

        <div class="mensaje">
            Todavía no cargaste figuritas.
        </div>

    <?php else: ?>

        <div class="grid-figuritas">

            <?php foreach ($figuritas as $figu): ?>

                <?php
                    $codigoCompleto = $figu["pais_codigo"] . " " . str_pad($figu["numero"], 2, "0", STR_PAD_LEFT);
                    $repetidas = max(0, $figu["cantidad"] - 1);
                ?>

                <div class="figu-card"
                     data-busqueda="<?php echo strtolower($codigoCompleto . ' ' . $figu['nombre'] . ' ' . $figu['pais_nombre']); ?>">

                    <div class="figu-img">
                        <?php if (!empty($figu["imagen"])): ?>
                            <img src="../uploads/<?php echo $figu["imagen"]; ?>" alt="<?php echo $codigoCompleto; ?>">
                        <?php else: ?>
                            <span><?php echo $codigoCompleto; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="figu-info">
                        <h3><?php echo $codigoCompleto; ?></h3>
                        <p><?php echo $figu["nombre"]; ?></p>
                        <small><?php echo $figu["pais_nombre"]; ?> · Página <?php echo $figu["pagina"]; ?></small>

                        <?php if ($repetidas > 0): ?>
                            <div class="badge-repetida">
                                🔁 Repetidas: <?php echo $repetidas; ?>
                            </div>
                        <?php else: ?>
                            <div class="badge-ok">
                                ✔ Única
                            </div>
                        <?php endif; ?>
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

        if (data.includes(texto)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
});
</script>

</body>
</html>