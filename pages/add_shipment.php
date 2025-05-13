<?php
session_start();
if (!isset($_SESSION['supplier_logged_in'])) {
    header('Location: ../index.php');
    exit;
}
require_once('../db.php');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = $pdo->query("SELECT * FROM orders WHERE id = $order_id")->fetch();

if (!$order) {
    die("Commande introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['tracking_code'] ?? '';
    $weight = $_POST['poids_total'] ?? 0;
    $shipping_cost = $_POST['transport_cost'] ?? 0;
    $sizes = [];
    $qty_sent = 0;

    foreach (['40','41','42','43','44','45'] as $size) {
        $val = (int)($_POST['size_' . $size] ?? 0);
        $sizes[$size] = $val;
        $qty_sent += $val;
    }

    // Upload images
    $photo_carton = '';
    $recu_transport = '';

    if (!empty($_FILES['photo_carton']['name'])) {
        $ext = pathinfo($_FILES['photo_carton']['name'], PATHINFO_EXTENSION);
        $photo_carton = uniqid('carton_') . "." . $ext;
        move_uploaded_file($_FILES['photo_carton']['tmp_name'], '../uploads/' . $photo_carton);
    }

    if (!empty($_FILES['recu_transport']['name'])) {
        $ext = pathinfo($_FILES['recu_transport']['name'], PATHINFO_EXTENSION);
        $recu_transport = uniqid('recu_') . "." . $ext;
        move_uploaded_file($_FILES['recu_transport']['tmp_name'], '../uploads/' . $recu_transport);
    }

    $stmt = $pdo->prepare("INSERT INTO shipments (
        order_id, created_at, tracking_code, poids_total, transport_cost,
        size_40_2, size_41_2, size_42_2, size_43_2, size_44_2, size_45_2,
        photo_carton, recu_transport
    ) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $order_id, $code, $weight, $shipping_cost,
        $sizes['40'], $sizes['41'], $sizes['42'],
        $sizes['43'], $sizes['44'], $sizes['45'],
        $photo_carton, $recu_transport
    ]);

    // Statut auto
    $sh = $pdo->prepare("SELECT * FROM shipments WHERE order_id = ?");
    $sh->execute([$order_id]);
    $all = $sh->fetchAll();
    $sent = 0;
    foreach ($all as $s) {
        foreach (['40','41','42','43','44','45'] as $size) {
            $sent += (int)($s['size_' . $size . '_2'] ?? 0);
        }
    }

    $new_status = $sent >= $order['qty_total'] ? "Expédiée complètement" : "Expédiée partiellement";
    $update = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $update->execute([$new_status, $order_id]);

    header("Location: track_shipments.php?order_id=$order_id&shipment=ok");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ajouter un envoi</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
<h3>Ajouter un envoi - Commande #<?= $order['id'] ?></h3>
<form method='post' enctype='multipart/form-data'>
    <div class='row mb-3'>
        <div class='col'>
            <label>Code colis</label>
            <input name='tracking_code' class='form-control' required>
        </div>
        <div class='col'>
            <label>Poids (kg)</label>
            <input name='poids_total' type='number' step='0.01' class='form-control'>
        </div>
        <div class='col'>
            <label>Frais livraison (MAD)</label>
            <input name='transport_cost' type='number' step='0.01' class='form-control'>
        </div>
    </div>
    <h5>Quantités par taille :</h5>
    <div class='row'>
        <?php foreach (['40','41','42','43','44','45'] as $size): ?>
        <div class='col'>
            <label><?= $size ?></label>
            <input type='number' min='0' name='size_<?= $size ?>' class='form-control' value='0'>
        </div>
        <?php endforeach; ?>
    </div>
    <div class='mb-3 mt-3'>
        <label>📦 Photo du carton :</label>
        <input type='file' name='photo_carton' class='form-control'>
    </div>
    <div class='mb-3'>
        <label>🧾 Reçu de transport :</label>
        <input type='file' name='recu_transport' class='form-control'>
    </div>
    <button type='submit' class='btn btn-success mt-3'>📤 Enregistrer l'envoi</button>
</form>
</body>
</html>
