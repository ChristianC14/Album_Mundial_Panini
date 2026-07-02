<?php

$mensaje = "";
$error = "";

$directorio = "../uploads/my_panini/";
$nombreBase = "dante_panini";

if (!is_dir($directorio)) {
    mkdir($directorio, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_FILES["imagen"]) || $_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {
        $error = "No se pudo subir la imagen.";
    } else {

        $archivo = $_FILES["imagen"];
        $maxSize = 5 * 1024 * 1024;

        if ($archivo["size"] > $maxSize) {
            $error = "La imagen supera los 5 MB.";
        } else {

            $infoImagen = getimagesize($archivo["tmp_name"]);

            if ($infoImagen === false) {
                $error = "El archivo seleccionado no es una imagen válida.";
            } else {

                $mime = $infoImagen["mime"];

                $extensionesPermitidas = [
                    "image/png" => "png",
                    "image/jpeg" => "jpg",
                    "image/webp" => "webp"
                ];

                if (!isset($extensionesPermitidas[$mime])) {
                    $error = "Formato no permitido. Usá PNG, JPG o WEBP.";
                } else {

                    $extension = $extensionesPermitidas[$mime];

                    foreach (["png", "jpg", "jpeg", "webp"] as $extVieja) {
                        $archivoViejo = $directorio . $nombreBase . "." . $extVieja;

                        if (file_exists($archivoViejo)) {
                            unlink($archivoViejo);
                        }
                    }

                    $destino = $directorio . $nombreBase . "." . $extension;

                    if (move_uploaded_file($archivo["tmp_name"], $destino)) {
                        $mensaje = "Foto My Panini actualizada correctamente.";
                    } else {
                        $error = "No se pudo guardar la imagen.";
                    }
                }
            }
        }
    }
}

$imagenActual = "";

foreach (["png", "jpg", "jpeg", "webp"] as $ext) {
    $ruta = $directorio . $nombreBase . "." . $ext;

    if (file_exists($ruta)) {
        $imagenActual = $ruta;
        break;
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar My Panini</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>

<div class="contenedor">

    <h1>🧒 My Panini Dante</h1>

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

    <div class="formulario">

        <h2>Foto actual</h2>

        <div class="preview-my-panini">
            <?php if ($imagenActual): ?>
                <img src="<?php echo htmlspecialchars($imagenActual); ?>" alt="My Panini Dante">
            <?php else: ?>
                <p>No hay foto cargada todavía.</p>
            <?php endif; ?>
        </div>

        <form method="POST" enctype="multipart/form-data">

            <label>Seleccionar nueva imagen</label>

            <input type="file" name="imagen" accept="image/png,image/jpeg,image/webp" required>

            <p style="color:#94a3b8;">
                Formatos permitidos: PNG, JPG o WEBP. Tamaño máximo: 5 MB.
            </p>

            <button type="submit">
                Guardar My Panini
            </button>

        </form>

    </div>

</div>

</body>
</html>