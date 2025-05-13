<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
include '../db.php';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = '';
if ($from && $to) {
    $where = "WHERE date_commande BETWEEN '$from' AND '$to'";
}

$orders = $pdo->query("SELECT * FROM orders $where ORDER BY date_commande DESC")->fetchAll();

$total_paid = 0;
$total_reste = 0;
$total_sent = 0;
$total_qty = 0;

foreach ($orders as $o) {
    $total_paid += $o['montant_paye'];
    $total_reste += $o['reste'];
    $total_qty += $o['qty_total'];

    // calculate total shipped
    $shipment = $pdo->prepare("SELECT 
        SUM(size_40_2 + size_41_2 + size_42_2 + size_43_2 + size_44_2 + size_45_2) as total_sent 
        FROM shipments WHERE order_id = ?");
    $shipment->execute([$o['id']]);
    $total_sent += (int) $shipment->fetchColumn();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Reporting</title>
  <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
  <h3>Reporting des commandes</h3>
  <form method="GET" class="row mb-4">
    <div class="col">
      <label>De :</label>
      <input type="date" name="from" class="form-control" value="<?= $from ?>">
    </div>
    <div class="col">
      <label>À :</label>
      <input type="date" name="to" class="form-control" value="<?= $to ?>">
    </div>
    <div class="col mt-4">
      <button class="btn btn-primary mt-2">Filtrer</button>
    </div>
  </form>

  <h5>Totaux filtrés :</h5>
  <ul>
    <li>Quantité totale commandée : <strong><?= $total_qty ?></strong></li>
    <li>Quantité totale envoyée : <strong><?= $total_sent ?></strong></li>
    <li>Montant total payé : <strong><?= number_format($total_paid, 2) ?> MAD</strong></li>
    <li>Montant restant à payer : <strong><?= number_format($total_reste, 2) ?> MAD</strong></li>
  </ul>

  <a class="btn btn-secondary mt-3" href="orders.php">Retour commandes</a>
</body>
</html>
