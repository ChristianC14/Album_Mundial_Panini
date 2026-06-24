<?php

require_once "../includes/conexion.php";
require_once "../tcpdf/tcpdf.php";

$usuarioId = 1;

$datos = $conexion->query("
    SELECT 
        f.pais_codigo,
        f.numero,
        p.nombre AS pais_nombre,
        f.pagina
    FROM figuritas f
    INNER JOIN paises p ON f.pais_codigo = p.codigo
    LEFT JOIN coleccion c 
        ON c.figurita_id = f.id 
        AND c.usuario_id = $usuarioId
    WHERE c.id IS NULL
    ORDER BY f.pagina ASC, f.pais_codigo ASC, f.numero ASC
")->fetchAll(PDO::FETCH_ASSOC);

$agrupado = [];

foreach ($datos as $figu) {
    $codigo = $figu["pais_codigo"] . str_pad($figu["numero"], 2, "0", STR_PAD_LEFT);
    $pais = $figu["pais_nombre"] . " (" . $figu["pais_codigo"] . ")";

    if (!isset($agrupado[$pais])) {
        $agrupado[$pais] = [];
    }

    $agrupado[$pais][] = $codigo;
}

$pdf = new TCPDF();
$pdf->SetCreator("Álbum Panini");
$pdf->SetAuthor("Dante");
$pdf->SetTitle("Figuritas faltantes");
$pdf->SetMargins(12, 12, 12);
$pdf->AddPage();

$pdf->SetFont("helvetica", "B", 18);
$pdf->Cell(0, 10, "Figuritas faltantes", 0, 1, "C");

$pdf->SetFont("helvetica", "", 10);
$pdf->Cell(0, 8, "Fecha: " . date("d/m/Y"), 0, 1, "C");
$pdf->Ln(5);

foreach ($agrupado as $pais => $codigos) {
    $pdf->SetFont("helvetica", "B", 13);
    $pdf->SetTextColor(185, 28, 28);
    $pdf->Cell(0, 8, $pais, 0, 1);

    $pdf->SetFont("helvetica", "", 11);
    $pdf->SetTextColor(0, 0, 0);

    $linea = implode(" - ", $codigos);
    $pdf->MultiCell(0, 7, $linea, 0, "L");
    $pdf->Ln(3);
}

$pdf->Output("faltantes_dante.pdf", "I");