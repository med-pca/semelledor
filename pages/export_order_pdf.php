<?php
require_once(__DIR__ . '/fpdf/fpdf.php');
require_once(__DIR__ . '/../db.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID invalide.");
}
$order_id = (int)$_GET['id'];
$order = $pdo->query("SELECT * FROM orders WHERE id = $order_id")->fetch();

if (!$order) {
    die("Commande introuvable.");
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,"Fiche Commande #{$order['id']}",0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,"Date : {$order['date_commande']}",0,1);
$pdf->Cell(0,8,"Client : {$order['request']}",0,1);
$pdf->Cell(0,8,"Pays : {$order['country']}",0,1);
$pdf->Cell(0,8,"Modele : {$order['model']}",0,1);
$pdf->Cell(0,8,"Couleur : {$order['color']}",0,1);
$pdf->Ln(3);

$pdf->Cell(0,8,"Quantites par pointure :",0,1);
foreach (['40','41','42','43','44','45'] as $size) {
    $qty = $order['size_' . $size . '_2'];
    $pdf->Cell(0,7,"  - Taille $size : $qty",0,1);
}
$pdf->Ln(3);
$pdf->Cell(0,8,"Total paires : {$order['qty_total']}",0,1);
$pdf->Cell(0,8,"Prix unitaire : {$order['prix_unit']} MAD",0,1);
$pdf->Cell(0,8,"Prix total : {$order['prix_total']} MAD",0,1);
$pdf->Cell(0,8,"Frais : {$order['other_fees']} | Transport : {$order['total_transport']} MAD",0,1);
$pdf->Cell(0,8,"Payé : {$order['montant_paye']} | Reste : {$order['reste']} MAD",0,1);
$pdf->Ln(4);
$pdf->Cell(0,8,"Statut de la commande : {$order['order_status']}",0,1);

if (!empty($order['admin_note'])) {
    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,"Note de l'admin :",0,1);
    $pdf->SetFont('Arial','',11);
    $pdf->MultiCell(0,7, $order['admin_note']);
}

$pdf->Output("I", "Commande_{$order['id']}.pdf");
?>