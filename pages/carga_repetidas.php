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

function analizarCodigos($conexion, $usuarioId, $lista) {

    $preview = [];
    $errores = [];

    $lineas = preg_split("/\r\n|\n|\r/", $lista);

    foreach ($lineas as $linea) {

        $lineaOriginal = trim($linea);
        $linea = strtoupper($lineaOriginal);

        if ($linea === "") {
            continue;
        }

        /*
            Acepta:
            ARG03
            ARG_03
            ARG-03
            ARG 03
            ARG03X2
            ARG03 X2
            ARG_03 X3
        */
        if (!preg_match('/^([A-Z]{3})[_\s-]?(\d{1,2})(?:\s*X\s*(\d+))?$/', $linea, $match)) {
            $errores[] = $lineaOriginal . " → formato inválido";
            continue;
        }

        $paisCodigo = $match[1];
        $numero = intval($match[2]);
        $cantidadASumar = isset($match[3]) && intval($match[3]) > 0
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

        $preview[] = [
            "codigo" => $codigoNormalizado,
            "figurita_id" => $figuritaId,
            "coleccion_id" => $existe["id"] ?? null,
            "cantidad_actual" => $existe ? intval($existe["cantidad"]) : 0,
            "cantidad_sumar" => $cantidadASumar,
            "accion" => $existe ? "sumar" : "crear"
        ];
    }

    return [$preview, $errores];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    [$preview, $errores] = analizarCodigos($conexion, $usuarioId, $listaOriginal);

    $totalValidas = count($preview);
    $totalErrores = count($errores);
    $totalCopias = array_sum(array_column($preview, "cantidad_sumar"));

    if ($accion === "confirmar") {

        foreach ($preview as $item) {

            if ($item["accion"] === "sumar") {

                $stmt = $conexion->prepare("
                    UPDATE coleccion
                    SET cantidad = cantidad + ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $item["cantidad_sumar"],
                    $item["coleccion_id"]
                ]);

                $nuevaCantidad = $item["cantidad_actual"] + $item["cantidad_sumar"];

                $resultados[] =
                    $item["codigo"] .
                    " → se sumaron " .
                    $item["cantidad_sumar"] .
                    ". Total: " .
                    $nuevaCantidad;

            } else {

                /*
                    Si no existe en colección:
                    cantidad inicial = 1 del álbum + repetidas cargadas.
                    Ej:
                    ARG03 = 2
                    ARG03X2 = 3
                    ARG03X3 = 4
                */
                $cantidadInicial = 1 + $item["cantidad_sumar"];

                $stmt = $conexion->prepare("
                    INSERT INTO coleccion
                    (usuario_id, figurita_id, cantidad)
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $usuarioId,
                    $item["figurita_id"],
                    $cantidadInicial
                ]);

                $resultados[] =
                    $item["codigo"] .
                    " → no estaba cargada. Se creó con cantidad " .
                    $cantidadInicial .
                    ".";
            }
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
    <title>Carga Masiva de Repetidas</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>

<div class="contenedor">

    <h1>🔁 Carga Masiva de Repetidas</h1>

    <a href="../index.php" class="boton-volver">
        ← Volver al Dashboard
    </a>

    <form method="POST" class="formulario">

        <label>Pegá los códigos de figuritas repetidas</label>

        <textarea
            name="codigos"
            rows="14"
            placeholder="ARG03&#10;ARG_07&#10;BIH01&#10;FWC07&#10;ECU05X2&#10;CUW20X3"
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
                Procesar repetidas
            </button>

            <?php if ($accion === "procesar" && !empty($preview)): ?>
                <button type="submit" name="accion" value="confirmar">
                    Confirmar carga
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
                    ✔ Repetidas a sumar:
                    <strong><?php echo $totalCopias; ?></strong>
                </p>

                <p>
                    ⚠ Errores:
                    <strong><?php echo $totalErrores; ?></strong>
                </p>

                <?php if (!empty($preview)): ?>
                    <h3>Listos para cargar</h3>

                    <ul class="lista-ok">
                        <?php foreach ($preview as $item): ?>
                            <li>
                                <?php if ($item["accion"] === "sumar"): ?>
                                    <?php echo htmlspecialchars($item["codigo"]); ?>
                                    → se sumarán
                                    <?php echo intval($item["cantidad_sumar"]); ?>.
                                    Total quedaría:
                                    <?php echo intval($item["cantidad_actual"]) + intval($item["cantidad_sumar"]); ?>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($item["codigo"]); ?>
                                    → no está cargada. Se creará con cantidad
                                    <?php echo 1 + intval($item["cantidad_sumar"]); ?>.
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            <?php elseif ($accion === "confirmar"): ?>

                <h2>Resultado final</h2>

                <p>
                    ✔ Registros grabados:
                    <strong><?php echo count($resultados); ?></strong>
                </p>

                <?php if (!empty($resultados)): ?>
                    <h3>Cargadas</h3>

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