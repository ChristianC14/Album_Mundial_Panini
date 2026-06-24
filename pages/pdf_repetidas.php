<?php

require_once "../includes/conexion.php";
require_once "../tcpdf/tcpdf.php";

$usuarioId = 1;

$datos = $conexion->query("
    SELECT 
        c.cantidad,
        f.pais_codigo,
        f.numero,
        p.nombre AS pais_nombre,
        f.pagina
    FROM coleccion c
    INNER JOIN figuritas f ON c.figurita_id = f.id
    INNER JOIN paises p ON f.pais_codigo = p.codigo
    WHERE c.usuario_id = $usuarioId
    AND c.cantidad > 1
    ORDER BY f.pagina ASC, f.pais_codigo ASC, f.numero ASC
")->fetchAll(PDO::FETCH_ASSOC);

$agrupado = [];

foreach ($datos as $figu) {
    $codigo = $figu["pais_codigo"] . str_pad($figu["numero"], 2, "0", STR_PAD_LEFT);
    $repetidas = intval($figu["cantidad"]) - 1;

    if ($repetidas > 1) {
        $codigo .= " x" . $repetidas;
    }

    $pais = $figu["pais_nombre"] . " (" . $figu["pais_codigo"] . ")";

    if (!isset($agrupado[$pais])) {
        $agrupado[$pais] = [];
    }

    $agrupado[$pais][] = $codigo;
}

$pdf = new TCPDF();
$pdf->SetCreator("Álbum Panini");
$pdf->SetAuthor("Dante");
$pdf->SetTitle("Repetidas para intercambio");
$pdf->SetMargins(12, 12, 12);
$pdf->AddPage();

$pdf->SetFont("helvetica", "B", 18);
$pdf->Cell(0, 10, "Repetidas para intercambio", 0, 1, "C");

$pdf->SetFont("helvetica", "", 10);
$pdf->Cell(0, 8, "Fecha: " . date("d/m/Y"), 0, 1, "C");
$pdf->Ln(5);

foreach ($agrupado as $pais => $codigos) {
    $pdf->SetFont("helvetica", "B", 13);
    $pdf->SetTextColor(30, 64, 175);
    $pdf->Cell(0, 8, $pais, 0, 1);

    $pdf->SetFont("helvetica", "", 11);
    $pdf->SetTextColor(0, 0, 0);

    $linea = implode(" - ", $codigos);
    $pdf->MultiCell(0, 7, $linea, 0, "L");
    $pdf->Ln(3);
}

$pdf->Output("repetidas_dante.pdf", "I");