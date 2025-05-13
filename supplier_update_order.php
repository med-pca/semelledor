<?php
session_start();
if (!isset($_SESSION['supplier_logged_in'])) {
    header('Location: supplier_login.php');
    exit;
}
require_once('db.php');

$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    echo "<div class='alert alert-danger'>Commande introuvable.</div>";
    exit;
}

// traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prix_unit = $_POST['prix_unit'];
    $other_fees = $_POST['other_fees'] ?? 0;
    $total_transport = $_POST['total_transport'] ?? 0;
    $qty_total = $order['qty_total'];
    $prix_total = $prix_unit * $qty_total;

    $update = $pdo->prepare("UPDATE orders SET prix_unit = ?, other_fees = ?, total_transport = ?, prix_total = ?, order_status = 'Prix en attente de validation' WHERE id = ?");
    $update->execute([$prix_unit, $other_fees, $total_transport, $prix_total, $order_id]);

    header("Location: supplier_dashboard.php?updated=1");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ajouter prix & frais - Commande #<?= $order['id'] ?></title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
<h3>Ajouter prix & frais pour la commande #<?= $order['id'] ?></h3>

<form method='post' action=''>
    <div class='mb-3'>
        <label for='prix_unit' class='form-label'>Prix unitaire</label>
        <input type='number' step='0.01' name='prix_unit' id='prix_unit' class='form-control' required>
    </div>
    <div class='mb-3'>
        <label for='other_fees' class='form-label'>Frais supplémentaires</label>
        <input type='number' step='0.01' name='other_fees' id='other_fees' class='form-control'>
    </div>
    <div class='mb-3'>
        <label for='total_transport' class='form-label'>Frais de transport</label>
        <input type='number' step='0.01' name='total_transport' id='total_transport' class='form-control'>
    </div>
    <button type='submit' class='btn btn-success'>Enregistrer</button>
</form>
</body>
</html>
