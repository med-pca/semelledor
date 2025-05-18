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

// Calculer qtyRemaining (avant POST) pour affichage form
$sqlOrder = "SELECT size_40_2, size_41_2, size_42_2, size_43_2, size_44_2, size_45_2 FROM orders WHERE id = ?";
$stmtOrder = $pdo->prepare($sqlOrder);
$stmtOrder->execute([$order_id]);
$orderSizes = $stmtOrder->fetch(PDO::FETCH_ASSOC);

$sqlShipment = "
    SELECT 
        SUM(size_40_2) AS size_40_prepared,
        SUM(size_41_2) AS size_41_prepared,
        SUM(size_42_2) AS size_42_prepared,
        SUM(size_43_2) AS size_43_prepared,
        SUM(size_44_2) AS size_44_prepared,
        SUM(size_45_2) AS size_45_prepared
    FROM shipments
    WHERE order_id = ?";
$stmtShipment = $pdo->prepare($sqlShipment);
$stmtShipment->execute([$order_id]);
$shipmentSizes = $stmtShipment->fetch(PDO::FETCH_ASSOC);


$qtyRemaining = [];
foreach (['40','41','42','43','44','45'] as $size) {
    $ordered = isset($orderSizes['size_'.$size.'_2']) ? (int)$orderSizes['size_'.$size.'_2'] : 0;
    $prepared = isset($shipmentSizes['size_'.$size.'_prepared']) ? (int)$shipmentSizes['size_'.$size.'_prepared'] : 0;
    $qtyRemaining[$size] = max(0, $ordered - $prepared);
}


// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['tracking_code'] ?? '';
    $weight = isset($_POST['poids_total']) && is_numeric($_POST['poids_total']) ? (float)$_POST['poids_total'] : 0.0;
    $shipping_cost = isset($_POST['transport_cost']) && is_numeric($_POST['transport_cost']) ? (float)$_POST['transport_cost'] : 0.0;

    $sizes = [];
    $qty_sent = 0;

    foreach (['40','41','42','43','44','45'] as $size) {
        $val = isset($_POST['size_' . $size]) && is_numeric($_POST['size_' . $size]) ? (int)$_POST['size_' . $size] : 0;

        // Validation : ne pas dépasser qtyRemaining côté serveur
        if ($val > $qtyRemaining[$size]) {
            die("Erreur : la quantité pour la taille $size dépasse la quantité restante.");
        }

        $sizes[$size] = $val;
        $qty_sent += $val;
    }

    // Upload images (idem)

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

    // Mise à jour statut
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
        <?php foreach (['40','41','42','43','44','45'] as $size): 
            $maxQty = $qtyRemaining[$size] ?? 0;
        ?>
        <div class='col'>
            <label><?= $size ?></label>
            <input 
                type='number' min='0' max='<?= $maxQty ?>' 
                name='size_<?= $size ?>' class='form-control' 
                value='0'
                <?= $maxQty === 0 ? "disabled" : "" ?>
            >
            <small>Quantité restante : <?= $maxQty ?></small>
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
