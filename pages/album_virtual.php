<?php

require_once "../includes/conexion.php";

$usuarioId = 1;

$paginas = $conexion->query("
    SELECT DISTINCT pagina
    FROM figuritas
    WHERE pagina IS NOT NULL
    ORDER BY pagina ASC
")->fetchAll(PDO::FETCH_COLUMN);

$paginaActual = isset($_GET["pagina"]) ? intval($_GET["pagina"]) : intval($paginas[0]);

$indiceActual = array_search($paginaActual, array_map('intval', $paginas));

if ($indiceActual === false) {
    $indiceActual = 0;
    $paginaActual = intval($paginas[0]);
}

$paginaAnterior = $indiceActual > 0 ? $paginas[$indiceActual - 1] : null;
$paginaSiguiente = $indiceActual < count($paginas) - 1 ? $paginas[$indiceActual + 1] : null;

$stmt = $conexion->prepare("
    SELECT 
        f.id,
        f.pais_codigo,
        f.numero,
        f.nombre,
        f.tipo,
        f.pagina,
        p.nombre AS pais_nombre,
        p.grupo,
        c.cantidad,
        c.imagen,
        fd.nombre_real,
        fd.nacimiento,
        fd.altura,
        fd.peso,
        fd.club
    FROM figuritas f
    INNER JOIN paises p 
        ON f.pais_codigo = p.codigo
    LEFT JOIN coleccion c 
        ON c.figurita_id = f.id 
        AND c.usuario_id = ?
    LEFT JOIN figuritas_detalle fd
        ON fd.figurita_id = f.id
    WHERE f.pagina = ?
    ORDER BY f.pais_codigo ASC, f.numero ASC
");

$stmt->execute([$usuarioId, $paginaActual]);
$figuritas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$paisesIndice = $conexion->query("
    SELECT codigo, nombre, grupo, pagina
    FROM paises
    WHERE codigo <> 'FWC'
    ORDER BY pagina ASC
")->fetchAll(PDO::FETCH_ASSOC);

$tituloPagina = "Página " . $paginaActual;

if ($paginaActual == 1) {
    $tituloPagina = "Índice del álbum";
} elseif (!empty($figuritas)) {
    if ($figuritas[0]["pais_codigo"] == "FWC") {
        $tituloPagina = "FWC - Generales";
    } else {
        $tituloPagina = $figuritas[0]["pais_nombre"];
    }
}

$paginasEspeciales = [
    1 => [
        "clase" => "pagina1",
        "numeros" => [0, 1, 2, 3, 4]
    ],
    2 => [
        "clase" => "pagina2",
        "numeros" => [5, 6, 7, 8]
    ],
    106 => [
        "clase" => "pagina106",
        "numeros" => [9, 10, 11, 12, 13]
    ],
    108 => [
        "clase" => "pagina108",
        "numeros" => [14, 15, 16, 17, 18, 19]
    ]
];

function banderaPais($codigo) {
    $banderas = [
        "MEX" => "🇲🇽",
        "RSA" => "🇿🇦",
        "KOR" => "🇰🇷",
        "CZE" => "🇨🇿",
        "CAN" => "🇨🇦",
        "BIH" => "🇧🇦",
        "QAT" => "🇶🇦",
        "SUI" => "🇨🇭",
        "BRA" => "🇧🇷",
        "MAR" => "🇲🇦",
        "HAI" => "🇭🇹",
        "SCO" => "🏴",
        "USA" => "🇺🇸",
        "PAR" => "🇵🇾",
        "AUS" => "🇦🇺",
        "TUR" => "🇹🇷",
        "GER" => "🇩🇪",
        "CUW" => "🇨🇼",
        "CIV" => "🇨🇮",
        "ECU" => "🇪🇨",
        "NED" => "🇳🇱",
        "JPN" => "🇯🇵",
        "SWE" => "🇸🇪",
        "TUN" => "🇹🇳",
        "BEL" => "🇧🇪",
        "EGY" => "🇪🇬",
        "IRN" => "🇮🇷",
        "NZL" => "🇳🇿",
        "ESP" => "🇪🇸",
        "CPV" => "🇨🇻",
        "KSA" => "🇸🇦",
        "URU" => "🇺🇾",
        "FRA" => "🇫🇷",
        "SEN" => "🇸🇳",
        "IRQ" => "🇮🇶",
        "NOR" => "🇳🇴",
        "ARG" => "🇦🇷",
        "ALG" => "🇩🇿",
        "AUT" => "🇦🇹",
        "JOR" => "🇯🇴",
        "POR" => "🇵🇹",
        "COD" => "🇨🇩",
        "UZB" => "🇺🇿",
        "COL" => "🇨🇴",
        "ENG" => "🏴",
        "CRO" => "🇭🇷",
        "GHA" => "🇬🇭",
        "PAN" => "🇵🇦"
    ];

    return $banderas[$codigo] ?? "🏳️";
}

function renderSlot($figu) {

    $codigo = $figu["pais_codigo"] . " " . str_pad($figu["numero"], 2, "0", STR_PAD_LEFT);
    $tiene = !empty($figu["cantidad"]);
    $cantidad = $tiene ? intval($figu["cantidad"]) : 0;
    $repetidas = $tiene ? max(0, $cantidad - 1) : 0;
    $imagen = !empty($figu["imagen"]) ? "../uploads/" . $figu["imagen"] : "";

    $nombreMostrar = !empty($figu["nombre_real"])
        ? $figu["nombre_real"]
        : $figu["nombre"];

    ob_start();
?>

    <div class="slot-figurita
            slot-num-<?php echo intval($figu["numero"]); ?>
            <?php echo strtolower($figu["pais_codigo"]) . str_pad($figu["numero"], 2, "0", STR_PAD_LEFT); ?>
            <?php echo $figu["tipo"] == 'equipo' ? 'slot-horizontal' : ''; ?>
            <?php echo $tiene ? 'conseguida' : 'faltante'; ?>"
         data-tiene="<?php echo $tiene ? '1' : '0'; ?>"
         data-codigo="<?php echo htmlspecialchars($codigo); ?>"
         data-nombre="<?php echo htmlspecialchars($nombreMostrar); ?>"
         data-pais="<?php echo htmlspecialchars($figu["pais_nombre"]); ?>"
         data-tipo="<?php echo htmlspecialchars($figu["tipo"]); ?>"
         data-pagina="<?php echo htmlspecialchars($figu["pagina"]); ?>"
         data-cantidad="<?php echo $cantidad; ?>"
         data-repetidas="<?php echo $repetidas; ?>"
         data-imagen="<?php echo htmlspecialchars($imagen); ?>"
         data-nacimiento="<?php echo htmlspecialchars($figu["nacimiento"] ?? ""); ?>"
         data-altura="<?php echo htmlspecialchars($figu["altura"] ?? ""); ?>"
         data-peso="<?php echo htmlspecialchars($figu["peso"] ?? ""); ?>"
         data-club="<?php echo htmlspecialchars($figu["club"] ?? ""); ?>">

        <?php if ($tiene && !empty($figu["imagen"])): ?>

            <img src="../uploads/<?php echo htmlspecialchars($figu["imagen"]); ?>" alt="<?php echo htmlspecialchars($codigo); ?>">

        <?php else: ?>

            <div class="slot-placeholder">
                <strong><?php echo htmlspecialchars($codigo); ?></strong>
                <span><?php echo htmlspecialchars($nombreMostrar); ?></span>
            </div>

        <?php endif; ?>

        <div class="slot-info">
            <strong><?php echo htmlspecialchars($codigo); ?></strong>

            <?php if ($repetidas > 0): ?>
                <span class="mini-repetida">🔁 x<?php echo $repetidas; ?></span>
            <?php endif; ?>
        </div>

    </div>

<?php
    return ob_get_clean();
}

function obtenerHotspotIndice($pais, $contadorGrupo) {

    $bases = [
        "A" => ["left" => 595, "top" => 228],
        "B" => ["left" => 767, "top" => 228],
        "C" => ["left" => 595, "top" => 294],
        "D" => ["left" => 767, "top" => 294],
        "E" => ["left" => 595, "top" => 360],
        "F" => ["left" => 767, "top" => 360],
        "G" => ["left" => 595, "top" => 426],
        "H" => ["left" => 767, "top" => 426],
        "I" => ["left" => 595, "top" => 496],
        "J" => ["left" => 767, "top" => 496],
        "K" => ["left" => 595, "top" => 569],
        "L" => ["left" => 767, "top" => 569],
    ];

    $grupo = strtoupper($pais["grupo"]);

    if (!isset($bases[$grupo])) {
        return null;
    }

    $fila = $contadorGrupo[$grupo] ?? 0;

    return [
        "left" => $bases[$grupo]["left"],
        "top" => $bases[$grupo]["top"] + ($fila * 15),
        "width" => 140,
        "height" => 12
    ];
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Álbum Virtual</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>

<div class="contenedor album-contenedor">

    <h1>📕 Álbum Virtual</h1>

    <a href="../index.php" class="boton-volver">
        ← Volver al Dashboard
    </a>

    <div class="album-navegacion">

        <?php if ($paginaAnterior !== null): ?>
            <a class="flecha-album izquierda" href="album_virtual.php?pagina=<?php echo $paginaAnterior; ?>">‹</a>
        <?php endif; ?>

        <?php if ($paginaSiguiente !== null): ?>
            <a class="flecha-album derecha" href="album_virtual.php?pagina=<?php echo $paginaSiguiente; ?>">›</a>
        <?php endif; ?>

        <div class="pagina-album" id="paginaAlbum">
            <div class="hoja-album" id="hojaAlbum">

                <?php if (isset($paginasEspeciales[$paginaActual])): ?>

                <?php
                    $configEspecial = $paginasEspeciales[$paginaActual];
                    $claseEspecial = $configEspecial["clase"];
                ?>

                <div class="pagina-especial <?php echo htmlspecialchars($claseEspecial); ?>"
                     data-pagina="Página <?php echo $paginaActual; ?>">

                    <?php foreach ($figuritas as $figu): ?>
                        <?php echo renderSlot($figu); ?>
                    <?php endforeach; ?>

                    <?php if ($paginaActual == 1): ?>

                        <?php
                            $myPaniniPath = "";
                            $myPaniniBase = "../uploads/my_panini/dante_panini";
                            $extensionesMyPanini = ["png", "jpg", "jpeg", "webp"];

                            foreach ($extensionesMyPanini as $ext) {
                                if (file_exists($myPaniniBase . "." . $ext)) {
                                    $myPaniniPath = $myPaniniBase . "." . $ext;
                                    break;
                                }
                            }
                        ?>

                        <a href="cargar_my_panini.php" class="slot-my-panini" title="Cambiar foto My Panini">
                            <?php if ($myPaniniPath !== ""): ?>
                                <img src="<?php echo htmlspecialchars($myPaniniPath); ?>" alt="My Panini Dante">
                            <?php else: ?>
                                <span class="slot-my-panini-vacio">Cargar<br>My Panini</span>
                            <?php endif; ?>
                        </a>
                        <?php
                            $contadorGrupo = [];
                        ?>

                        <div class="hotspots-indice-paises">

                            <?php foreach ($paisesIndice as $pais): ?>

                                <?php
                                    $grupo = strtoupper($pais["grupo"]);

                                    if (!isset($contadorGrupo[$grupo])) {
                                        $contadorGrupo[$grupo] = 0;
                                    }

                                    $hotspot = obtenerHotspotIndice($pais, $contadorGrupo);

                                    $contadorGrupo[$grupo]++;

                                    if ($hotspot === null) {
                                        continue;
                                    }
                                ?>

                                <a
                                    href="album_virtual.php?pagina=<?php echo intval($pais["pagina"]); ?>"
                                    class="hotspot-indice"
                                    title="<?php echo htmlspecialchars($pais["codigo"] . " - " . $pais["nombre"]); ?>"
                                    style="
                                        left: <?php echo intval($hotspot["left"]); ?>px;
                                        top: <?php echo intval($hotspot["top"]); ?>px;
                                        width: <?php echo intval($hotspot["width"]); ?>px;
                                        height: <?php echo intval($hotspot["height"]); ?>px;
                                    ">
                                </a>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <?php
                    $esPais = !empty($figuritas) && $figuritas[0]["pais_codigo"] !== "FWC";
                ?>

                <?php if ($esPais): ?>

                    <div class="cabecera-pais-fisica">

                        <div class="cabecera-bandera">
                            <small>
                                <?php echo $figuritas[0]["pais_codigo"]; ?>
                            </small>

                            <div>
                                <h2><?php echo htmlspecialchars($figuritas[0]["pais_nombre"]); ?></h2>
                                <p>
                                    Grupo <?php echo htmlspecialchars($figuritas[0]["grupo"]); ?>
                                </p>
                            </div>

                            <?php
                                $codigoBandera = strtolower($figuritas[0]["pais_codigo"]);
                                $rutaBandera = "../assets/banderas/" . $codigoBandera . ".png";

                                if (!file_exists($rutaBandera)) {
                                    $rutaBandera = "../assets/banderas/default.png";
                                }
                            ?>

                            <img
                                src="<?php echo $rutaBandera; ?>"
                                class="bandera-pais-img"
                                alt="<?php echo htmlspecialchars($figuritas[0]["pais_nombre"]); ?>"
                            >
                        </div>

                    </div>

                    <div class="album-pais-fisico pagina-pais-fija" data-pagina="Página <?php echo $paginaActual; ?>">

                        <?php foreach ($figuritas as $figu): ?>
                            <?php echo renderSlot($figu); ?>
                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <?php
                        $claseFwc = "pagina-fwc-generales";
                    ?>

                    <div class="<?php echo $claseFwc; ?>" data-pagina="Página <?php echo $paginaActual; ?>">

                        <h2>FWC - Generales</h2>

                        <div class="grilla-album">
                            <?php foreach ($figuritas as $figu): ?>
                                <?php echo renderSlot($figu); ?>
                            <?php endforeach; ?>
                        </div>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>

    </div>

</div>

</div>

<div id="modalFigurita" class="modal-figurita">

    <div class="modal-figurita-contenido">

        <button class="modal-cerrar" onclick="cerrarModalFigurita()">×</button>

        <div class="modal-figu-img" id="modalFiguImg"></div>

        <div class="modal-figu-info">

            <h2 id="modalCodigo"></h2>
            <h3 id="modalNombre"></h3>

            <p id="modalPais"></p>

            <div id="modalDatosCompletos"></div>

            <div id="modalEstado" class="modal-estado"></div>
            <div id="modalRepetidas" class="modal-repetidas"></div>

        </div>

    </div>

</div>


<script>
const paginaAlbum = document.getElementById("paginaAlbum");
const hojaAlbum = document.getElementById("hojaAlbum") || paginaAlbum;


let inicioX = 0;
let moviendo = false;
let huboArrastre = false;
let navegando = false;

const paginaAnterior = <?php echo $paginaAnterior !== null ? json_encode("album_virtual.php?pagina=" . $paginaAnterior) : "null"; ?>;
const paginaSiguiente = <?php echo $paginaSiguiente !== null ? json_encode("album_virtual.php?pagina=" . $paginaSiguiente) : "null"; ?>;

/* =========================
ENTRADA DESPUÉS DE CAMBIAR PÁGINA
========================= */

document.addEventListener("DOMContentLoaded", function () {
    sessionStorage.removeItem("album_transicion_entrada");

    hojaAlbum.classList.remove(
        "entrando-desde-derecha",
        "entrando-desde-izquierda",
        "pasando-siguiente",
        "pasando-anterior",
        "oculta-durante-transicion"
    );
});

/* =========================
NAVEGACIÓN CON EFECTO HOJA
========================= */

async function navegarConAnimacion(url, direccion) {
    if (!url || navegando) return;

    navegando = true;

    const hojaReal = document.getElementById("hojaAlbum") || paginaAlbum;
    const rect = hojaReal.getBoundingClientRect();

    /*
        1. Traemos la página destino sin navegar todavía.
        Esto nos permite mostrarla debajo durante la animación.
    */
    let hojaDestino = null;

    try {
        const respuesta = await fetch(url, {
            cache: "no-store"
        });

        const html = await respuesta.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");

        hojaDestino = doc.getElementById("hojaAlbum");
    } catch (error) {
        window.location.href = url;
        return;
    }

    if (!hojaDestino) {
        window.location.href = url;
        return;
    }

    /*
        2. Creamos el fondo con la página siguiente/anterior.
    */
    const fondoDestino = hojaDestino.cloneNode(true);

    fondoDestino.removeAttribute("id");
    fondoDestino.classList.remove(
        "entrando-desde-derecha",
        "entrando-desde-izquierda",
        "pasando-siguiente",
        "pasando-anterior",
        "oculta-durante-transicion"
    );

    fondoDestino.classList.add("fondo-pagina-destino");

    fondoDestino.style.left = rect.left + "px";
    fondoDestino.style.top = rect.top + "px";
    fondoDestino.style.width = rect.width + "px";
    fondoDestino.style.height = rect.height + "px";

    document.body.appendChild(fondoDestino);

    requestAnimationFrame(() => {
        fondoDestino.classList.add("mostrar-fondo-destino");
    });
    
    /*
        3. Clonamos la página actual.
        Este clon será la media hoja que se dobla.
    */
    const clon = hojaReal.cloneNode(true);

    clon.removeAttribute("id");

    clon.classList.remove(
        "pasando-siguiente",
        "pasando-anterior",
        "entrando-desde-derecha",
        "entrando-desde-izquierda",
        "oculta-durante-transicion"
    );

    clon.classList.add("clon-media-hoja");

    if (direccion === "siguiente") {
        clon.classList.add("clon-media-derecha");
    } else {
        clon.classList.add("clon-media-izquierda");
    }

    clon.style.left = rect.left + "px";
    clon.style.top = rect.top + "px";
    clon.style.width = rect.width + "px";
    clon.style.height = rect.height + "px";

    document.body.appendChild(clon);

    /*
        4. Ocultamos la hoja real actual.
        Si no hacemos esto, atrás se seguiría viendo la misma página.
    */
    hojaReal.classList.add("oculta-durante-transicion");

    /*
        5. Sombra del lomo central.
    */
    const sombraCentro = document.createElement("div");
    sombraCentro.classList.add("sombra-lomo-transicion");

    sombraCentro.style.left = (rect.left + (rect.width / 2) - 2) + "px";
    sombraCentro.style.top = rect.top + "px";
    sombraCentro.style.height = rect.height + "px";

    document.body.appendChild(sombraCentro);

    /*
        6. Forzamos que el navegador pinte el fondo y el clon
        antes de iniciar la animación.
    */
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            if (direccion === "siguiente") {
                clon.classList.add("animar-media-derecha");
            } else {
                clon.classList.add("animar-media-izquierda");
            }

            sombraCentro.classList.add("animar-sombra-lomo");
        });
    });

    let yaNavego = false;

    function ir() {
        if (yaNavego) return;

        yaNavego = true;

        clon.remove();
        fondoDestino.remove();
        sombraCentro.remove();

        window.location.href = url;
    }

    clon.addEventListener("animationend", ir);

    setTimeout(ir, 1250);
}

/* Flechas del álbum */
document.querySelectorAll(".flecha-album").forEach(flecha => {
    flecha.addEventListener("click", function (e) {
        e.preventDefault();

        const url = this.getAttribute("href");

        if (this.classList.contains("derecha")) {
            navegarConAnimacion(url, "siguiente");
        } else {
            navegarConAnimacion(url, "anterior");
        }
    });
});

/* =========================
SWIPE / ARRASTRE
========================= */

function iniciarMovimiento(x) {
    inicioX = x;
    moviendo = true;
    huboArrastre = false;
}

function terminarMovimiento(x) {
    if (!moviendo) return;

    const diferencia = x - inicioX;
    const minimo = 80;

    if (Math.abs(diferencia) > 10) {
        huboArrastre = true;
    }

    if (diferencia < -minimo && paginaSiguiente) {
        navegarConAnimacion(paginaSiguiente, "siguiente");
    }

    if (diferencia > minimo && paginaAnterior) {
        navegarConAnimacion(paginaAnterior, "anterior");
    }

    moviendo = false;
}

paginaAlbum.addEventListener("mousedown", e => {
    if (e.target.closest(".slot-figurita, .hotspot-indice, .slot-my-panini")) {
        return;
    }

    iniciarMovimiento(e.clientX);
});

paginaAlbum.addEventListener("mouseup", e => terminarMovimiento(e.clientX));

paginaAlbum.addEventListener("mouseleave", () => {
    moviendo = false;
});

paginaAlbum.addEventListener("touchstart", e => {
    if (e.target.closest(".slot-figurita, .hotspot-indice, .slot-my-panini")) {
        return;
    }

    iniciarMovimiento(e.touches[0].clientX);
});

paginaAlbum.addEventListener("touchend", e => terminarMovimiento(e.changedTouches[0].clientX));

/* =========================
MODAL FIGURITA
========================= */

const slots = document.querySelectorAll(".slot-figurita");

document.querySelectorAll("img").forEach(img => {
    img.setAttribute("draggable", "false");
});

slots.forEach(slot => {
    slot.addEventListener("dragstart", e => e.preventDefault());

    slot.addEventListener("click", function () {

        if (huboArrastre) return;

        if (this.dataset.tiene !== "1") {
            return;
        }

        abrirModalFigurita(this);
    });
});

function abrirModalFigurita(slot) {

    const codigo = slot.dataset.codigo;
    const nombre = slot.dataset.nombre;
    const pais = slot.dataset.pais;
    const nacimiento = slot.dataset.nacimiento;
    const altura = slot.dataset.altura;
    const peso = slot.dataset.peso;
    const club = slot.dataset.club;
    const repetidas = parseInt(slot.dataset.repetidas);
    const imagen = slot.dataset.imagen;

    document.getElementById("modalCodigo").textContent = codigo;
    document.getElementById("modalNombre").textContent = nombre;
    document.getElementById("modalPais").textContent = "País / sección: " + pais;

    const datosCompletos = document.getElementById("modalDatosCompletos");
    datosCompletos.innerHTML = "";

    let html = "";

    if (nacimiento) {
        html += `<p><strong>Nacimiento:</strong> ${nacimiento}</p>`;
    }

    if (altura) {
        html += `<p><strong>Altura:</strong> ${altura}</p>`;
    }

    if (peso) {
        html += `<p><strong>Peso:</strong> ${peso}</p>`;
    }

    if (club) {
        html += `<p><strong>Club:</strong> ${club}</p>`;
    }

    if (html === "") {
        html = `<p class="sin-datos">Todavía no se cargaron datos para esta figurita.</p>`;
    }

    datosCompletos.innerHTML = html;

    const modalEstado = document.getElementById("modalEstado");
    const modalRepetidas = document.getElementById("modalRepetidas");
    const modalFiguImg = document.getElementById("modalFiguImg");

    modalFiguImg.innerHTML = "";

    if (imagen) {
        const img = document.createElement("img");
        img.src = imagen;
        img.alt = codigo;
        modalFiguImg.appendChild(img);
    } else {
        const span = document.createElement("span");
        span.textContent = codigo;
        modalFiguImg.appendChild(span);
    }

    modalEstado.className = "modal-estado estado-ok";
    modalEstado.textContent = "✔ Conseguida";

    if (repetidas > 0) {
        modalRepetidas.className = "modal-repetidas estado-repetida";
        modalRepetidas.textContent = "🔁 Repetidas para cambiar: " + repetidas;
    } else {
        modalRepetidas.className = "modal-repetidas estado-unica";
        modalRepetidas.textContent = "Única en la colección";
    }

    document.getElementById("modalFigurita").style.display = "flex";
}

function cerrarModalFigurita() {
    document.getElementById("modalFigurita").style.display = "none";
}

document.getElementById("modalFigurita").addEventListener("click", function(e) {
    if (e.target.id === "modalFigurita") {
        cerrarModalFigurita();
    }
});
</script>


</body>
</html>