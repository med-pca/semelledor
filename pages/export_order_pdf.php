<?php
require_once('fpdf/fpdf.php');
require_once('../db.php');

$id = $_GET['id'] ?? 0;
$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$orderStmt->execute([$id]);
$order = $orderStmt->fetch();

if (!$order) {
    die("Commande introuvable.");
}

$pdf = new FPDF();
$pdf->AddPage();

// Logo
if (file_exists('logo.png')) {
    $pdf->Image('logo.png', 10, 6, 30);
}
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(80);
$pdf->Cell(30, 10, 'Fiche Commande #' . $order['id'], 0, 1, 'C');
$pdf->Ln(10);

// Command info
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Date commande : ' . $order['date_commande'], 0, 1);
$pdf->Cell(0, 10, 'Client : ' . $order['request'], 0, 1);

// Add country and flag
$countryText = $order['country'];
$flagMap = [
    'Cote dIvoire' => '../flags/civ.png',
    'Guinée' => '../flags/gn.png',
];
$flagPath = $flagMap[$countryText] ?? null;

$pdf->Cell(0, 10, 'Pays : ' . $countryText, 0, 1);
if ($flagPath && file_exists($flagPath)) {
    $pdf->Image($flagPath, 30, $pdf->GetY() - 10, 15);
}

$pdf->Cell(0, 10, 'Modele : ' . $order['model'], 0, 1);
$pdf->Cell(0, 10, 'Couleur : ' . $order['color'], 0, 1);
$pdf->Cell(0, 10, 'Quantite totale : ' . $order['qty_total'], 0, 1);
$pdf->Cell(0, 10, 'Prix unitaire : ' . $order['prix_unit'] . ' MAD', 0, 1);
$pdf->Cell(0, 10, 'Prix total : ' . $order['prix_total'] . ' MAD', 0, 1);
$pdf->Cell(0, 10, 'Montant paye : ' . $order['montant_paye'] . ' MAD', 0, 1);
$pdf->Cell(0, 10, 'Reste a payer : ' . $order['reste'] . ' MAD', 0, 1);

// Sizes
$pdf->Ln(5);
$pdf->Cell(0, 10, 'Tailles commandees :', 0, 1);
$pdf->Cell(0, 8, '40 : ' . $order['size_40_2'] . ' paires', 0, 1);
$pdf->Cell(0, 8, '41 : ' . $order['size_41_2'] . ' paires', 0, 1);
$pdf->Cell(0, 8, '42 : ' . $order['size_42_2'] . ' paires', 0, 1);
$pdf->Cell(0, 8, '43 : ' . $order['size_43_2'] . ' paires', 0, 1);
$pdf->Cell(0, 8, '44 : ' . $order['size_44_2'] . ' paires', 0, 1);
$pdf->Cell(0, 8, '45 : ' . $order['size_45_2'] . ' paires', 0, 1);

// Model image
if (!empty($order['image_model']) && file_exists('../uploads/' . $order['image_model'])) {
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Image du modele :', 0, 1);
    $pdf->Image('../uploads/' . $order['image_model'], 10, $pdf->GetY(), 60);
    $pdf->Ln(60);
}

// Recu de paiement
if (!empty($order['recu_paiement']) && file_exists('../uploads/' . $order['recu_paiement'])) {
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'Recu de paiement :', 0, 1);
    $pdf->Image('../uploads/' . $order['recu_paiement'], 10, $pdf->GetY(), 60);
    $pdf->Ln(60);
}

// Shipments
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, '📦 Suivi des envois', 0, 1);
$pdf->SetFont('Arial', '', 12);

$shipmentsStmt = $pdo->prepare("SELECT * FROM shipments WHERE order_id = ?");
$shipmentsStmt->execute([$order['id']]);
$shipments = $shipmentsStmt->fetchAll();

if (count($shipments) === 0) {
    $pdf->Cell(0, 10, 'Aucun envoi enregistre.', 0, 1);
} else {
    foreach ($shipments as $s) {
        $pdf->Ln(5);
        $pdf->Cell(0, 8, '📅 Date : ' . $s['date'], 0, 1);
        $pdf->Cell(0, 8, '📦 Code colis : ' . $s['tracking_code'], 0, 1);
        $pdf->Cell(0, 8, '⚖️ Poids : ' . $s['weight'] . ' kg', 0, 1);
        $pdf->Cell(0, 8, '💰 Frais : ' . $s['shipping_cost'] . ' MAD', 0, 1);
        $pdf->Cell(0, 8, '👟 Tailles envoyees :', 0, 1);
        $pdf->Cell(0, 8, '40 : ' . $s['size_40'] . ' | 41 : ' . $s['size_41'] . ' | 42 : ' . $s['size_42'] .
                         ' | 43 : ' . $s['size_43'] . ' | 44 : ' . $s['size_44'] . ' | 45 : ' . $s['size_45'], 0, 1);

        if (!empty($s['photo_carton']) && file_exists('../uploads/' . $s['photo_carton'])) {
            $pdf->Ln(3);
            $pdf->Cell(0, 8, '🖼️ Photo du carton :', 0, 1);
            $pdf->Image('../uploads/' . $s['photo_carton'], 10, $pdf->GetY(), 60);
            $pdf->Ln(60);
        }

        if (!empty($s['photo_recu']) && file_exists('../uploads/' . $s['photo_recu'])) {
            $pdf->Ln(3);
            $pdf->Cell(0, 8, '🧾 Recu de transport :', 0, 1);
            $pdf->Image('../uploads/' . $s['photo_recu'], 10, $pdf->GetY(), 60);
            $pdf->Ln(60);
        }

        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY()); // separator
    }
}

$pdf->Output();
?>
