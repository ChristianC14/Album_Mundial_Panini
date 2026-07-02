<?php

require_once "../includes/conexion.php";

$usuarioId = 1;
$mensaje = "";
$error = "";
$erroresArchivos = [];

$directorioUploads = "../uploads/";
$maxSizeImagen = 5 * 1024 * 1024; // 5 MB

if (!is_dir($directorioUploads)) {
    mkdir($directorioUploads, 0777, true);
}

$paises = $conexion->query("
    SELECT codigo, nombre, pagina
    FROM paises
    ORDER BY pagina ASC
")->fetchAll(PDO::FETCH_ASSOC);

$paisSeleccionado = $_GET["pais"] ?? ($_POST["pais_codigo"] ?? "ARG");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $paisSeleccionado = $_POST["pais_codigo"];
    $datos = $_POST["datos"] ?? [];

    $imagenesPorCodigo = [];
    $imagenesNuevasSubidas = [];
    $imagenesViejasParaBorrar = [];

    /*
        1) VALIDACIÓN FUERTE DE IMÁGENES
        - nombre de archivo
        - país correcto
        - peso máximo
        - getimagesize()
        - MIME real
        - evita duplicados dentro de la misma carga
    */
    if (!empty($_FILES["imagenes"]["name"][0])) {

        foreach ($_FILES["imagenes"]["name"] as $i => $nombreOriginal) {

            if ($_FILES["imagenes"]["error"][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($_FILES["imagenes"]["error"][$i] !== UPLOAD_ERR_OK) {
                $erroresArchivos[] = $nombreOriginal . " → error al subir el archivo.";
                continue;
            }

            if ($_FILES["imagenes"]["size"][$i] > $maxSizeImagen) {
                $erroresArchivos[] = $nombreOriginal . " → supera los 5 MB.";
                continue;
            }

            $tmpName = $_FILES["imagenes"]["tmp_name"][$i];

            $infoImagen = getimagesize($tmpName);

            if ($infoImagen === false) {
                $erroresArchivos[] = $nombreOriginal . " → no es una imagen válida.";
                continue;
            }

            $mime = $infoImagen["mime"] ?? "";

            $extensionesPermitidas = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/webp" => "webp"
            ];

            if (!isset($extensionesPermitidas[$mime])) {
                $erroresArchivos[] = $nombreOriginal . " → formato no permitido. Usá JPG, PNG o WEBP.";
                continue;
            }

            $nombreSinExtension = strtoupper(pathinfo($nombreOriginal, PATHINFO_FILENAME));

            if (!preg_match('/^([A-Z]{3})[_\s-]?(\d{1,2})(?:[_\s-].*)?$/', $nombreSinExtension, $match)) {
                $erroresArchivos[] = $nombreOriginal . " → formato inválido. Ejemplo válido: ARG01.jpg";
                continue;
            }

            $codigoPaisArchivo = $match[1];
            $numeroArchivo = intval($match[2]);

            if ($codigoPaisArchivo !== strtoupper($paisSeleccionado)) {
                $erroresArchivos[] =
                    $nombreOriginal .
                    " → corresponde a " .
                    $codigoPaisArchivo .
                    ", pero estás cargando " .
                    strtoupper($paisSeleccionado) .
                    ".";
                continue;
            }

            if (isset($imagenesPorCodigo[$numeroArchivo])) {
                $erroresArchivos[] =
                    $nombreOriginal .
                    " → hay más de una imagen para el número " .
                    str_pad($numeroArchivo, 2, "0", STR_PAD_LEFT) .
                    ". Se usó la primera detectada.";
                continue;
            }

            $extension = $extensionesPermitidas[$mime];
            $nuevoNombre = uniqid("figu_", true) . "." . $extension;
            $destino = $directorioUploads . $nuevoNombre;

            if (!move_uploaded_file($tmpName, $destino)) {
                $erroresArchivos[] = $nombreOriginal . " → no se pudo guardar en uploads.";
                continue;
            }

            $imagenesPorCodigo[$numeroArchivo] = $nuevoNombre;
            $imagenesNuevasSubidas[] = $destino;
        }
    }

    /*
        2) GUARDADO CON TRANSACCIÓN
        Si falla algo en DB:
        - rollBack()
        - se borran las imágenes nuevas que ya se habían subido
    */
    try {

        $conexion->beginTransaction();

        $stmtFigus = $conexion->prepare("
            SELECT *
            FROM figuritas
            WHERE pais_codigo = ?
            ORDER BY numero ASC
        ");
        $stmtFigus->execute([$paisSeleccionado]);
        $figuritasPost = $stmtFigus->fetchAll(PDO::FETCH_ASSOC);

        foreach ($figuritasPost as $figu) {

            $figuritaId = intval($figu["id"]);
            $numero = intval($figu["numero"]);

            $nombreReal = trim($datos[$figuritaId]["nombre_real"] ?? "");
            $nacimiento = trim($datos[$figuritaId]["nacimiento"] ?? "");
            $altura = trim($datos[$figuritaId]["altura"] ?? "");
            $peso = trim($datos[$figuritaId]["peso"] ?? "");
            $club = trim($datos[$figuritaId]["club"] ?? "");

            /*
                3) GUARDAR / ACTUALIZAR DATOS DETALLE
            */
            if ($nombreReal !== "" || $nacimiento !== "" || $altura !== "" || $peso !== "" || $club !== "") {

                $stmtExisteDetalle = $conexion->prepare("
                    SELECT id
                    FROM figuritas_detalle
                    WHERE figurita_id = ?
                ");
                $stmtExisteDetalle->execute([$figuritaId]);
                $detalleExiste = $stmtExisteDetalle->fetch(PDO::FETCH_ASSOC);

                if ($detalleExiste) {

                    $stmtDetalle = $conexion->prepare("
                        UPDATE figuritas_detalle
                        SET 
                            nombre_real = ?,
                            nacimiento = ?,
                            altura = ?,
                            peso = ?,
                            club = ?
                        WHERE figurita_id = ?
                    ");

                    $stmtDetalle->execute([
                        $nombreReal,
                        $nacimiento,
                        $altura,
                        $peso,
                        $club,
                        $figuritaId
                    ]);

                } else {

                    $stmtDetalle = $conexion->prepare("
                        INSERT INTO figuritas_detalle
                        (figurita_id, nombre_real, nacimiento, altura, peso, club)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $stmtDetalle->execute([
                        $figuritaId,
                        $nombreReal,
                        $nacimiento,
                        $altura,
                        $peso,
                        $club
                    ]);
                }
            }

            /*
                4) GUARDAR / REEMPLAZAR IMAGEN
                Si ya tenía imagen:
                - actualiza DB con la nueva
                - guarda la vieja para borrarla después del commit
            */
            if (isset($imagenesPorCodigo[$numero])) {

                $imagenNueva = $imagenesPorCodigo[$numero];

                $stmtColeccion = $conexion->prepare("
                    SELECT id, imagen
                    FROM coleccion
                    WHERE usuario_id = ?
                    AND figurita_id = ?
                ");
                $stmtColeccion->execute([$usuarioId, $figuritaId]);
                $existeColeccion = $stmtColeccion->fetch(PDO::FETCH_ASSOC);

                if ($existeColeccion) {

                    $imagenVieja = $existeColeccion["imagen"] ?? "";

                    $stmtUpdate = $conexion->prepare("
                        UPDATE coleccion
                        SET imagen = ?
                        WHERE id = ?
                    ");

                    $stmtUpdate->execute([
                        $imagenNueva,
                        $existeColeccion["id"]
                    ]);

                    if ($imagenVieja !== "" && $imagenVieja !== $imagenNueva) {
                        $rutaVieja = $directorioUploads . basename($imagenVieja);

                        if (file_exists($rutaVieja)) {
                            $imagenesViejasParaBorrar[] = $rutaVieja;
                        }
                    }

                } else {

                    $stmtInsert = $conexion->prepare("
                        INSERT INTO coleccion
                        (usuario_id, figurita_id, imagen, cantidad)
                        VALUES (?, ?, ?, 1)
                    ");

                    $stmtInsert->execute([
                        $usuarioId,
                        $figuritaId,
                        $imagenNueva
                    ]);
                }
            }
        }

        $conexion->commit();

        /*
            5) BORRAR IMÁGENES VIEJAS
            Se hace después del commit para no perder archivos si falla la DB.
        */
        foreach ($imagenesViejasParaBorrar as $rutaVieja) {
            if (file_exists($rutaVieja)) {
                unlink($rutaVieja);
            }
        }

        $mensaje = "Equipo guardado correctamente.";

        if (!empty($erroresArchivos)) {
            $mensaje .= " Algunos archivos fueron omitidos por validación.";
        }

    } catch (Throwable $e) {

        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        /*
            Si falló la DB, borro las imágenes nuevas que se habían subido
            para no dejar archivos huérfanos.
        */
        foreach ($imagenesNuevasSubidas as $rutaNueva) {
            if (file_exists($rutaNueva)) {
                unlink($rutaNueva);
            }
        }

        $error = "No se pudo guardar el equipo. Error: " . $e->getMessage();
    }
}

$stmt = $conexion->prepare("
    SELECT 
        f.id,
        f.pais_codigo,
        f.numero,
        f.nombre,
        f.tipo,
        f.pagina,
        d.nombre_real,
        d.nacimiento,
        d.altura,
        d.peso,
        d.club,
        c.imagen,
        c.cantidad
    FROM figuritas f
    LEFT JOIN figuritas_detalle d
        ON d.figurita_id = f.id
    LEFT JOIN coleccion c
        ON c.figurita_id = f.id
        AND c.usuario_id = ?
    WHERE f.pais_codigo = ?
    ORDER BY f.numero ASC
");

$stmt->execute([$usuarioId, $paisSeleccionado]);
$figuritas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carga masiva por equipo</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>

<div class="contenedor">

    <h1>⚡ Carga Masiva por Equipo</h1>

    <a href="../index.php" class="boton-volver">
        ← Volver al Dashboard
    </a>

    <?php if ($mensaje): ?>
        <div class="mensaje exito">
            ✔ <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mensaje error">
            ⚠ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($erroresArchivos)): ?>
        <div class="mensaje advertencia">
            <strong>Archivos omitidos:</strong>
            <ul>
                <?php foreach ($erroresArchivos as $errorArchivo): ?>
                    <li><?php echo htmlspecialchars($errorArchivo); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="GET" class="formulario">
        <label>Equipo / País</label>

        <select name="pais" onchange="this.form.submit()">
            <?php foreach ($paises as $pais): ?>
                <option value="<?php echo htmlspecialchars($pais["codigo"]); ?>"
                    <?php echo $pais["codigo"] === $paisSeleccionado ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($pais["codigo"]); ?> - <?php echo htmlspecialchars($pais["nombre"]); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <form method="POST" enctype="multipart/form-data" class="formulario">

        <input type="hidden" name="pais_codigo" value="<?php echo htmlspecialchars($paisSeleccionado); ?>">

        <label>Seleccionar imágenes del equipo</label>
        <input type="file" name="imagenes[]" id="imagenesMasivas" accept="image/png,image/jpeg,image/webp" multiple>

        <p style="color:#94a3b8;">
            Los archivos deben llamarse así: 
            <strong><?php echo htmlspecialchars($paisSeleccionado); ?>01.jpg</strong>,
            <strong><?php echo htmlspecialchars($paisSeleccionado); ?>02.jpg</strong>,
            etc.
            Tamaño máximo: <strong>5 MB</strong>.
        </p>

        <div class="tabla-carga-equipo">

            <?php foreach ($figuritas as $figu): ?>

                <?php
                    $codigo = $figu["pais_codigo"] . str_pad($figu["numero"], 2, "0", STR_PAD_LEFT);
                    $tiene = !empty($figu["cantidad"]);
                    $imagenActual = !empty($figu["imagen"]) ? "../uploads/" . $figu["imagen"] : "";
                ?>

                <div class="fila-carga-equipo" data-codigo="<?php echo htmlspecialchars($codigo); ?>">

                    <div class="codigo-carga">
                        <strong><?php echo htmlspecialchars($codigo); ?></strong>
                        <span><?php echo htmlspecialchars($figu["tipo"]); ?></span>
                        <?php if ($tiene): ?>
                            <small>✔ Ya cargada</small>
                        <?php endif; ?>
                    </div>

                    <div class="preview-carga">
                        <?php if ($imagenActual): ?>
                            <img src="<?php echo htmlspecialchars($imagenActual); ?>" alt="<?php echo htmlspecialchars($codigo); ?>">
                        <?php else: ?>
                            <span>Sin imagen</span>
                        <?php endif; ?>
                    </div>

                    <div class="campos-carga">

                        <input type="text"
                               name="datos[<?php echo $figu["id"]; ?>][nombre_real]"
                               value="<?php echo htmlspecialchars($figu["nombre_real"] ?? ""); ?>"
                               placeholder="Nombre">

                        <input type="text"
                               name="datos[<?php echo $figu["id"]; ?>][nacimiento]"
                               value="<?php echo htmlspecialchars($figu["nacimiento"] ?? ""); ?>"
                               placeholder="Nacimiento">

                        <input type="text"
                               name="datos[<?php echo $figu["id"]; ?>][altura]"
                               value="<?php echo htmlspecialchars($figu["altura"] ?? ""); ?>"
                               placeholder="Altura">

                        <input type="text"
                               name="datos[<?php echo $figu["id"]; ?>][peso]"
                               value="<?php echo htmlspecialchars($figu["peso"] ?? ""); ?>"
                               placeholder="Peso">

                        <input type="text"
                               name="datos[<?php echo $figu["id"]; ?>][club]"
                               value="<?php echo htmlspecialchars($figu["club"] ?? ""); ?>"
                               placeholder="Club">

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <button type="submit">
            Guardar equipo completo
        </button>

    </form>

</div>

<script>
const inputImagenes = document.getElementById("imagenesMasivas");

inputImagenes.addEventListener("change", function () {

    const archivos = Array.from(this.files);

    archivos.forEach(archivo => {

        const nombreArchivo = archivo.name.toUpperCase();

        const coincidencia = nombreArchivo.match(
            /([A-Z]{3})[_\s-]?(\d{1,2})/
        );

        if (!coincidencia) {
            return;
        }

        const pais = coincidencia[1];
        const numero = String(coincidencia[2]).padStart(2, "0");

        const codigo = pais + numero;

        const fila = document.querySelector(
            `.fila-carga-equipo[data-codigo="${codigo}"]`
        );

        if (!fila) {
            return;
        }

        const preview = fila.querySelector(".preview-carga");

        const url = URL.createObjectURL(archivo);

        preview.innerHTML = "";

        const img = document.createElement("img");
        img.src = url;
        img.alt = codigo;

        preview.appendChild(img);

        fila.classList.add("imagen-detectada");
    });
});
</script>

</body>
</html>