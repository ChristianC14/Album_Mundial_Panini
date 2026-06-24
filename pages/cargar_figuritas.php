<?php

require_once "../includes/conexion.php";

try {

    $conexion->beginTransaction();

    $sql = "INSERT INTO figuritas
            (pais_codigo, numero, nombre, tipo, pagina)
            VALUES
            (:pais_codigo, :numero, :nombre, :tipo, :pagina)
            ON DUPLICATE KEY UPDATE
            nombre = VALUES(nombre),
            tipo = VALUES(tipo),
            pagina = VALUES(pagina)";

    $stmt = $conexion->prepare($sql);

    // ==============================
    // FIGURITAS DE PAÍSES
    // ==============================

    $consultaPaises = $conexion->query("
        SELECT codigo, nombre, pagina
        FROM paises
        WHERE codigo <> 'FWC'
        ORDER BY pagina ASC
    ");

    $paises = $consultaPaises->fetchAll(PDO::FETCH_ASSOC);

    foreach ($paises as $pais) {

        for ($i = 1; $i <= 20; $i++) {

            if ($i == 1) {
                $tipo = "escudo";
                $nombre = "Escudo " . $pais["nombre"];
            } elseif ($i == 13) {
                $tipo = "equipo";
                $nombre = "Foto grupal " . $pais["nombre"];
            } else {
                $tipo = "jugador";
                $nombre = "Jugador " . str_pad($i, 2, "0", STR_PAD_LEFT) . " " . $pais["nombre"];
            }

            $stmt->execute([
                "pais_codigo" => $pais["codigo"],
                "numero" => $i,
                "nombre" => $nombre,
                "tipo" => $tipo,
                "pagina" => $pais["pagina"]
            ]);
        }
    }

    // ==============================
    // FIGURITAS FWC
    // ==============================

    for ($i = 0; $i <= 19; $i++) {

        if ($i <= 4) {
            $pagina = 1;
        } elseif ($i <= 8) {
            $pagina = 2;
        } elseif ($i <= 13) {
            $pagina = 106;
        } else {
            $pagina = 108;
        }

        $numeroTexto = str_pad($i, 2, "0", STR_PAD_LEFT);

        $stmt->execute([
            "pais_codigo" => "FWC",
            "numero" => $i,
            "nombre" => "FWC " . $numeroTexto,
            "tipo" => "general",
            "pagina" => $pagina
        ]);
    }

    $conexion->commit();

    echo "Figuritas cargadas correctamente. Total esperado: 980.";

} catch (PDOException $e) {

    $conexion->rollBack();

    echo "Error: " . $e->getMessage();
}

?>