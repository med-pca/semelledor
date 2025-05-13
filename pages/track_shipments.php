<?php
session_start();

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_close']) && isset($_SESSION['logged_in'])) {
    $pdo->prepare("UPDATE orders SET order_status = 'Clôturée' WHERE id = ?")->execute([$order_id]);
    $order['order_status'] = 'Clôturée';
}
?>

if (!isset($_SESSION['logged_in']) && !isset($_SESSION['supplier_logged_in'])) {
    header('Location: ../index.php');
    exit;
}
include '../db.php';

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    echo "Order ID missing."; exit;
}

$order = $pdo->query("SELECT * FROM orders WHERE id = $order_id")->fetch();
if (!$order) {
    echo "Commande non trouvée."; exit;
}

$shipments = $pdo->query("SELECT * FROM shipments WHERE order_id = $order_id ORDER BY shipment_date ASC")->fetchAll();

$sent = ['size_40_2' => 0, 'size_41_2' => 0, 'size_42_2' => 0, 'size_43_2' => 0, 'size_44_2' => 0, 'size_45_2' => 0];
foreach ($shipments as $s) {
    foreach ($sent as $size => $_) {
        $sent[$size] += (int)$s[$size];
    }
}

$qty_total = (int)$order['qty_total'];
$per_size_target = $qty_total / count($sent);
$remaining = [];
$completed = true;

foreach ($sent as $size => $qte) {
    $remaining[$size] = max(0, $per_size_target - $qte);
    if ($remaining[$size] > 0) {
        $completed = false;
    }
}

if ($completed && count($shipments) > 0 && $order['order_status'] !== 'Complétée') {
    $pdo->prepare("UPDATE orders SET order_status = 'Complétée' WHERE id = ?")->execute([$order_id]);
    $order['order_status'] = 'Complétée';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Suivi des envois</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
<?php if (isset($_GET['shipment']) && $_GET['shipment'] === 'ok'): ?>
<div class='alert alert-success'>📦 L'envoi a bien été enregistré et le statut mis à jour automatiquement.</div>
<?php endif; ?>
    <h3>Suivi des envois - Commande #<?= $order['id'] ?> (<?= $order['model'] ?>)</h3>
    <p>Status : <strong><?= $order['order_status'] ?></strong></p>
<?php if ($order['order_status'] === 'Complétée'): ?>
<?php if (isset($_SESSION['logged_in'])): ?>
<form method="POST" class="mt-3">
    <button name="manual_close" class="btn btn-outline-dark">✅ Confirmer la clôture manuelle</button>
</form>
<?php endif; ?>
<div class='alert alert-success'>✅ Cette commande a été automatiquement marquée comme <strong>complétée</strong> car toutes les tailles ont été envoyées.</div>
<?php endif; ?>

    <h5>Quantités envoyées / restantes</h5>
    

<?php
// Résumé des quantités envoyées vs attendues
$order = $pdo->query("SELECT * FROM orders WHERE id = $order_id")->fetch();
$expected = (int)$order['qty_total'];

$sent = 0;
foreach ($pdo->query("SELECT * FROM shipments WHERE order_id = $order_id") as $s) {
    foreach (['40','41','42','43','44','45'] as $size) {
        $sent += (int)$s['size_' . $size];
    }
}
$reste = $expected - $sent;

$badge_class = $reste <= 0 ? 'success' : 'warning';
?>
<div class='alert alert-info'>
    👟 <strong>Quantité totale attendue :</strong> <?= $expected ?> <br>
    🚚 <strong>Total envoyé :</strong> <span class='badge bg-primary'><?= $sent ?></span> <br>
    📦 <strong>Reste à envoyer :</strong> <span class='badge bg-<?= $badge_class ?>'><?= $reste ?></span>
</div>


<table class='table table-bordered'>
        <thead><tr>
            <th>Taille</th><th>Envoyée</th><th>Restante</th>
        </tr></thead>
        <tbody>
            <?php foreach ($sent as $size => $qty): ?>
            <tr>
                <td><?= str_replace('_', '.', substr($size, 5)) ?></td>
                <td><?= $qty ?></td>
                <td><?= $remaining[$size] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h5>Historique des envois</h5>
    <table class='table table-striped'>
        <thead>
            <tr><th>Date</th><th>Carton</th><th>Poids</th><th>Transport (€)</th><th>Commentaire</th></tr>
        </thead>
        <tbody>
            <?php foreach ($shipments as $s): ?>
            <tr>
                <td><?= $s['shipment_date'] ?></td>
                <td><?= $s['code_carton'] ?></td>
                <td><?= $s['poids_total'] ?> kg</td>
                <td><?= $s['transport_cost'] ?></td>
                <td><?= $s['commentaire'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (isset($_SESSION['supplier_logged_in'])): ?>
    <a class='btn btn-secondary mt-3' href='../supplier_dashboard.php'>Retour</a>
    <?php else: ?>
    <a class='btn btn-secondary mt-3' href='orders.php'>Retour</a>
    <?php endif; ?>
</body>
</html>
