<?php

require_once "../includes/conexion.php";

$usuarioId = 1;

$accion = $_POST["accion"] ?? "";
$listaOriginal = $_POST["codigos"] ?? "";

$preview = [];
$resultados = [];
$errores = [];

$totalValidas = 0;
$totalErrores = 0;
$totalCopias = 0;

function analizarBajas($conexion, $usuarioId, $lista) {

    $preview = [];
    $errores = [];

    $lineas = preg_split("/\r\n|\n|\r/", $lista);

    foreach ($lineas as $linea) {

        $lineaOriginal = trim($linea);
        $linea = strtoupper($lineaOriginal);

        if ($linea === "") {
            continue;
        }

        if (!preg_match('/^([A-Z]{3})[_\s-]?(\d{1,2})(?:\s*X\s*(\d+))?$/', $linea, $match)) {
            $errores[] = $lineaOriginal . " → formato inválido";
            continue;
        }

        $paisCodigo = $match[1];
        $numero = intval($match[2]);

        $cantidadAQuitar = isset($match[3]) && intval($match[3]) > 0
            ? intval($match[3])
            : 1;

        $codigoNormalizado = $paisCodigo . str_pad($numero, 2, "0", STR_PAD_LEFT);

        $stmt = $conexion->prepare("
            SELECT id
            FROM figuritas
            WHERE pais_codigo = ?
            AND numero = ?
        ");

        $stmt->execute([$paisCodigo, $numero]);
        $figurita = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$figurita) {
            $errores[] = $codigoNormalizado . " → no existe en el álbum";
            continue;
        }

        $figuritaId = intval($figurita["id"]);

        $stmt = $conexion->prepare("
            SELECT id, cantidad
            FROM coleccion
            WHERE usuario_id = ?
            AND figurita_id = ?
        ");

        $stmt->execute([$usuarioId, $figuritaId]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existe) {
            $errores[] = $codigoNormalizado . " → no está cargada en la colección";
            continue;
        }

        $cantidadActual = intval($existe["cantidad"]);
        $repetidasDisponibles = max(0, $cantidadActual - 1);

        if ($repetidasDisponibles <= 0) {
            $errores[] = $codigoNormalizado . " → no tiene repetidas para quitar";
            continue;
        }

        if ($cantidadAQuitar > $repetidasDisponibles) {
            $errores[] =
                $codigoNormalizado .
                " → querés quitar " .
                $cantidadAQuitar .
                ", pero solo tiene " .
                $repetidasDisponibles .
                " repetida/s disponible/s";
            continue;
        }

        $preview[] = [
            "codigo" => $codigoNormalizado,
            "coleccion_id" => intval($existe["id"]),
            "cantidad_actual" => $cantidadActual,
            "cantidad_quitar" => $cantidadAQuitar,
            "cantidad_final" => $cantidadActual - $cantidadAQuitar
        ];
    }

    return [$preview, $errores];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    [$preview, $errores] = analizarBajas($conexion, $usuarioId, $listaOriginal);

    $totalValidas = count($preview);
    $totalErrores = count($errores);
    $totalCopias = array_sum(array_column($preview, "cantidad_quitar"));

    if ($accion === "confirmar") {

        foreach ($preview as $item) {

            $stmt = $conexion->prepare("
                UPDATE coleccion
                SET cantidad = cantidad - ?
                WHERE id = ?
            ");

            $stmt->execute([
                $item["cantidad_quitar"],
                $item["coleccion_id"]
            ]);

            $resultados[] =
                $item["codigo"] .
                " → se quitaron " .
                $item["cantidad_quitar"] .
                ". Total actual: " .
                $item["cantidad_final"];
        }

        $preview = [];
        $listaOriginal = "";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Quitar Repetidas</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>

<div class="contenedor">

    <h1>➖ Quitar Repetidas</h1>

    <a href="../index.php" class="boton-volver">
        ← Volver al Dashboard
    </a>

    <form method="POST" class="formulario">

        <label>Pegá los códigos de repetidas entregadas</label>

        <textarea
            name="codigos"
            rows="14"
            placeholder="ARG03&#10;ARG_07&#10;BIH01&#10;FWC07&#10;CUW20X2"
            class="textarea-repetidas"
            autocomplete="off"
            spellcheck="false"
            required><?php echo htmlspecialchars($listaOriginal); ?></textarea>

        <p style="color:#94a3b8;">
            Formatos aceptados:
            <strong>ARG03</strong>,
            <strong>ARG_03</strong>,
            <strong>ARG-03</strong>,
            <strong>ARG 03</strong>,
            <strong>ARG03X2</strong>,
            <strong>ARG03 X3</strong>.
        </p>

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button type="submit" name="accion" value="procesar">
                Procesar bajas
            </button>

            <?php if ($accion === "procesar" && !empty($preview)): ?>
                <button type="submit" name="accion" value="confirmar">
                    Confirmar baja
                </button>
            <?php endif; ?>
        </div>

    </form>

    <?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>

        <div class="resultado-repetidas">

            <?php if ($accion === "procesar"): ?>

                <h2>Previsualización</h2>

                <p>
                    ✔ Códigos válidos:
                    <strong><?php echo $totalValidas; ?></strong>
                </p>

                <p>
                    ➖ Repetidas a quitar:
                    <strong><?php echo $totalCopias; ?></strong>
                </p>

                <p>
                    ⚠ Errores:
                    <strong><?php echo $totalErrores; ?></strong>
                </p>

                <?php if (!empty($preview)): ?>
                    <h3>Listas para quitar</h3>

                    <ul class="lista-ok">
                        <?php foreach ($preview as $item): ?>
                            <li>
                                <?php echo htmlspecialchars($item["codigo"]); ?>
                                → se quitarán
                                <?php echo intval($item["cantidad_quitar"]); ?>.
                                Cantidad actual:
                                <?php echo intval($item["cantidad_actual"]); ?>.
                                Quedaría:
                                <?php echo intval($item["cantidad_final"]); ?>.
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            <?php elseif ($accion === "confirmar"): ?>

                <h2>Resultado final</h2>

                <p>
                    ✔ Registros actualizados:
                    <strong><?php echo count($resultados); ?></strong>
                </p>

                <?php if (!empty($resultados)): ?>
                    <h3>Quitadas</h3>

                    <ul class="lista-ok">
                        <?php foreach ($resultados as $resultado): ?>
                            <li><?php echo htmlspecialchars($resultado); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            <?php endif; ?>

            <?php if (!empty($errores)): ?>
                <h3>Errores</h3>

                <ul class="lista-error">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>