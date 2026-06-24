<?php

require_once "../includes/conexion.php";

$usuarioId = 1;
$mensaje = "";

$paises = $conexion->query("
    SELECT *
    FROM paises
    ORDER BY pagina ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $paisCodigo = $_POST["pais_codigo"];
    $numero = intval($_POST["numero"]);

    $nombreReal = trim($_POST["nombre_real"] ?? "");
    $nacimiento = trim($_POST["nacimiento"] ?? "");
    $altura = trim($_POST["altura"] ?? "");
    $peso = trim($_POST["peso"] ?? "");
    $club = trim($_POST["club"] ?? "");

    $stmt = $conexion->prepare("
        SELECT *
        FROM figuritas
        WHERE pais_codigo = ?
        AND numero = ?
    ");

    $stmt->execute([$paisCodigo, $numero]);
    $figurita = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($figurita) {

        $figuritaId = $figurita["id"];

        if ($nombreReal !== "" || $nacimiento !== "" || $altura !== "" || $peso !== "" || $club !== "") {

            $stmt = $conexion->prepare("
                INSERT INTO figuritas_detalle
                (figurita_id, nombre_real, nacimiento, altura, peso, club)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    nombre_real = VALUES(nombre_real),
                    nacimiento = VALUES(nacimiento),
                    altura = VALUES(altura),
                    peso = VALUES(peso),
                    club = VALUES(club)
            ");

            $stmt->execute([
                $figuritaId,
                $nombreReal,
                $nacimiento,
                $altura,
                $peso,
                $club
            ]);
        }

        $stmt = $conexion->prepare("
            SELECT *
            FROM coleccion
            WHERE usuario_id = ?
            AND figurita_id = ?
        ");

        $stmt->execute([$usuarioId, $figuritaId]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);

        $nombreImagen = null;

        if (!empty($_FILES["imagen"]["name"])) {

            $extension = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
            $extensionesPermitidas = ["jpg", "jpeg", "png", "webp"];

            if (in_array($extension, $extensionesPermitidas)) {

                $nombreImagen = uniqid("figu_") . "." . $extension;

                move_uploaded_file(
                    $_FILES["imagen"]["tmp_name"],
                    "../uploads/" . $nombreImagen
                );
            }
        }

        if (!$existe) {

            $stmt = $conexion->prepare("
                INSERT INTO coleccion
                (usuario_id, figurita_id, imagen, cantidad)
                VALUES (?, ?, ?, 1)
            ");

            $stmt->execute([
                $usuarioId,
                $figuritaId,
                $nombreImagen
            ]);

            $mensaje = "FIGURITA_AGREGADA";

        } else {

            $stmt = $conexion->prepare("
                UPDATE coleccion
                SET 
                    cantidad = cantidad + 1,
                    imagen = COALESCE(?, imagen)
                WHERE id = ?
            ");

            $stmt->execute([
                $nombreImagen,
                $existe["id"]
            ]);

            $mensaje = "FIGURITA_REPETIDA";
        }

    } else {

        $mensaje = "ERROR_FIGURITA_INEXISTENTE";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Agregar Figurita</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>

<div class="contenedor">

    <h1>➕ Agregar Figurita</h1>

    <a href="../index.php" class="boton-volver">
        ← Volver al Dashboard
    </a>

    <?php if ($mensaje == "FIGURITA_AGREGADA"): ?>
        <div class="mensaje exito">
            ✔ Figurita agregada correctamente.
        </div>
    <?php endif; ?>

    <?php if ($mensaje == "FIGURITA_REPETIDA"): ?>
        <div class="mensaje repetida">
            🔁 Ya la tenías. Se sumó como repetida para cambiar.
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="formulario">

        <label>País / Categoría</label>

        <select name="pais_codigo" id="pais_codigo" required>
            <?php foreach ($paises as $pais): ?>
                <option value="<?php echo htmlspecialchars($pais["codigo"]); ?>">
                    <?php echo htmlspecialchars($pais["codigo"]); ?> - <?php echo htmlspecialchars($pais["nombre"]); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Número</label>

        <select name="numero" id="numero" required>
            <!-- Se carga por JavaScript -->
        </select>

        <div class="detalle-estado" id="detalleEstado">
            Seleccioná país y número para consultar los datos.
        </div>

        <div class="carga-figurita-layout"
            style="display:grid; grid-template-columns:420px 1fr; gap:30px; align-items:start; margin-top:20px;">

            <div class="panel-foto"
                style="display:flex; flex-direction:column; gap:14px;">

                <label>Foto de la figurita</label>

                <input type="file"
                       name="imagen"
                       id="imagen"
                       accept="image/*">

                <div class="preview-box"
                     style="width:100%; height:500px; max-height:500px; overflow:hidden; display:flex; justify-content:center; align-items:center; background:#334155; border:2px dashed #64748b; border-radius:14px;">

                    <img id="previewImagen"
                         src=""
                         alt="Vista previa"
                         style="display:none; width:auto; height:auto; max-width:95%; max-height:480px; object-fit:contain;">

                    <span id="previewTexto">
                        Vista previa de la figurita
                    </span>

                </div>

                <div class="ficha-seleccionada" id="fichaSeleccionada">
                    <strong>Figurita seleccionada</strong>
                    <span id="fichaCodigo">---</span>
                    <span id="fichaTipo">---</span>
                    <span id="fichaPagina">---</span>
                </div>

            </div>

            <div class="panel-datos"
                style="display:flex; flex-direction:column; gap:14px;">

                <h2 class="form-subtitulo">
                    Datos reales de la figurita
                </h2>

                <label>Nombre</label>
                <input type="text"
                       name="nombre_real"
                       id="nombre_real"
                       placeholder="Ej: Lionel Messi">

                <label>Nacimiento</label>
                <input type="text"
                       name="nacimiento"
                       id="nacimiento"
                       placeholder="Ej: 24/06/1987">

                <label>Altura</label>
                <input type="text"
                       name="altura"
                       id="altura"
                       placeholder="Ej: 1.70 m">

                <label>Peso</label>
                <input type="text"
                       name="peso"
                       id="peso"
                       placeholder="Ej: 72 kg">

                <label>Club</label>
                <input type="text"
                       name="club"
                       id="club"
                       placeholder="Ej: Inter Miami">

                <button type="submit">
                    Guardar Figurita
                </button>

            </div>

        </div>

    </form>

</div>

<div id="modalError" class="modal-error">
    <div class="modal-contenido">
        <h2>⚠ Figurita inexistente</h2>
        <p>Esa figurita no existe en el álbum.</p>
        <button onclick="cerrarModal()">Aceptar</button>
    </div>
</div>

<script>
const selectPais = document.getElementById("pais_codigo");
const selectNumero = document.getElementById("numero");

const detalleEstado = document.getElementById("detalleEstado");

const inputNombre = document.getElementById("nombre_real");
const inputNacimiento = document.getElementById("nacimiento");
const inputAltura = document.getElementById("altura");
const inputPeso = document.getElementById("peso");
const inputClub = document.getElementById("club");

const inputImagen = document.getElementById("imagen");
const previewImagen = document.getElementById("previewImagen");
const previewTexto = document.getElementById("previewTexto");

const fichaCodigo = document.getElementById("fichaCodigo");
const fichaTipo = document.getElementById("fichaTipo");
const fichaPagina = document.getElementById("fichaPagina");

function cargarNumeros() {

    const pais = selectPais.value;

    selectNumero.innerHTML = "";

    const inicio = pais === "FWC" ? 0 : 1;
    const fin = pais === "FWC" ? 19 : 20;

    for (let i = inicio; i <= fin; i++) {

        const option = document.createElement("option");

        option.value = i;
        option.textContent = String(i).padStart(2, "0");

        selectNumero.appendChild(option);
    }

    cargarDetalleFigurita();
}

async function cargarDetalleFigurita() {

    const pais = selectPais.value;
    const numero = selectNumero.value;

    if (!pais || numero === "") {
        return;
    }

    detalleEstado.textContent = "Consultando datos de la figurita...";

    inputNombre.value = "";
    inputNacimiento.value = "";
    inputAltura.value = "";
    inputPeso.value = "";
    inputClub.value = "";

    previewImagen.src = "";
    previewImagen.style.display = "none";
    previewTexto.style.display = "block";

    try {

        const respuesta = await fetch(
            `../ajax/obtener_detalle_figurita.php?pais=${encodeURIComponent(pais)}&numero=${encodeURIComponent(numero)}`
        );

        const datos = await respuesta.json();

        if (!datos || !datos.figurita_id) {
            detalleEstado.textContent = "⚠ Esa figurita no existe en el álbum.";

            fichaCodigo.textContent = "---";
            fichaTipo.textContent = "---";
            fichaPagina.textContent = "---";

            return;
        }

        fichaCodigo.textContent = pais + " " + String(numero).padStart(2, "0");
        fichaTipo.textContent = "Tipo: " + (datos.tipo ?? "---");
        fichaPagina.textContent = "Página: " + (datos.pagina ?? "---");

        inputNombre.value = datos.nombre_real ?? "";
        inputNacimiento.value = datos.nacimiento ?? "";
        inputAltura.value = datos.altura ?? "";
        inputPeso.value = datos.peso ?? "";
        inputClub.value = datos.club ?? "";

        if (datos.imagen) {
            previewImagen.src = "../uploads/" + datos.imagen;

            previewImagen.style.display = "block";
            previewImagen.style.width = "auto";
            previewImagen.style.height = "auto";
            previewImagen.style.maxWidth = "95%";
            previewImagen.style.maxHeight = "480px";
            previewImagen.style.objectFit = "contain";

            previewTexto.style.display = "none";
        }

        if (parseInt(datos.cantidad ?? 0) > 0) {
            detalleEstado.textContent = "✔ Ya tenés esta figurita en tu colección. Cantidad: " + datos.cantidad;
        } else if (
            datos.nombre_real ||
            datos.nacimiento ||
            datos.altura ||
            datos.peso ||
            datos.club
        ) {
            detalleEstado.textContent = "✔ Datos encontrados. Todavía no la tenés pegada.";
        } else {
            detalleEstado.textContent = "ℹ Figurita válida. Todavía no hay datos cargados.";
        }

    } catch (error) {

        console.error(error);
        detalleEstado.textContent = "No se pudieron consultar los datos.";
    }
}

function cerrarModal() {
    document.getElementById("modalError").style.display = "none";
}

selectPais.addEventListener("change", cargarNumeros);
selectNumero.addEventListener("change", cargarDetalleFigurita);

inputImagen.addEventListener("change", function () {

    const archivo = this.files[0];

    previewImagen.removeAttribute("style");
    previewTexto.removeAttribute("style");

    if (!archivo) {
        previewImagen.src = "";

        previewImagen.style.display = "none";

        previewTexto.style.display = "block";

        return;
    }

    const url = URL.createObjectURL(archivo);

    previewImagen.src = url;

    previewImagen.style.display = "block";
    previewImagen.style.width = "auto";
    previewImagen.style.height = "auto";
    previewImagen.style.maxWidth = "95%";
    previewImagen.style.maxHeight = "480px";
    previewImagen.style.objectFit = "contain";

    previewTexto.style.display = "none";
});

cargarNumeros();

<?php if ($mensaje == "ERROR_FIGURITA_INEXISTENTE"): ?>
    document.getElementById("modalError").style.display = "flex";
<?php endif; ?>
</script>

</body>
</html>