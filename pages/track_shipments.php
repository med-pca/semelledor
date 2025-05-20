<?php
session_start();
if (!isset($_SESSION['supplier_logged_in']) && !isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
include '../db.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = $pdo->query("SELECT * FROM orders WHERE id = $order_id")->fetch();

if (!$order) {
    echo "<div class='container mt-5 alert alert-danger'>❌ Commande introuvable.</div>";
    exit;
}

$shipments = $pdo->prepare("SELECT * FROM shipments WHERE order_id = ?");
$shipments->execute([$order_id]);
$all = $shipments->fetchAll();

$totalEnvoye = 0;
foreach ($all as $s) {
    foreach (['40','41','42','43','44','45'] as $size) {
        $totalEnvoye += (int)($s['size_' . $size . '_2'] ?? 0);
    }
}
$reste = $order['qty_total'] - $totalEnvoye;
$badgeClass = $reste <= 0 ? 'success' : 'warning';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Suivi des envois</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
<h3>Suivi des envois - Commande #<?= $order['id'] ?></h3>
<?php $return_url = isset($_SESSION['logged_in']) ? 'orders.php' : '../supplier_dashboard.php'; ?>
<a href='<?= $return_url ?>' class='btn btn-secondary mb-3'>⬅ Retour</a>

<div class='alert alert-info'>
    🧤 <strong>Total attendu :</strong> <?= $order['qty_total'] ?> <br>
    🚚 <strong>Total envoyé :</strong> <span class='badge bg-primary'><?= $totalEnvoye ?></span> <br>
    📦 <strong>Reste :</strong> <span class='badge bg-<?= $badgeClass ?>'><?= $reste ?></span>
</div>

<?php if (isset($_GET['shipment']) && $_GET['shipment'] === 'ok'): ?>
    <div class='alert alert-success'>📦 Envoi bien enregistré et statut mis à jour automatiquement.</div>
<?php endif; ?>

<?php if (count($all) === 0): ?>
    <div class='alert alert-warning'>Aucun envoi trouvé pour cette commande.</div>
<?php else: ?>
<table class='table table-bordered'>
    <thead>
        <tr>
            <th>Date</th><th>Code colis</th><th>Poids</th><th>Frais</th><th>Tailles</th><th>Images</th>
        <td>
    <?php if (!empty($shipment['photo_carton'])): ?>
        <div class='mb-1'>
            📦 <a href="../uploads/<?= $shipment['photo_carton'] ?>" target="_blank">
                <img src="../uploads/<?= $shipment['photo_carton'] ?>" style="width:60px; height:auto;" alt="Carton">
            </a>
        </div>
    <?php endif; ?>
    <?php if (!empty($shipment['recu_transport'])): ?>
        <div>
            🧾 <a href="../uploads/<?= $shipment['recu_transport'] ?>" target="_blank">
                <img src="../uploads/<?= $shipment['recu_transport'] ?>" style="width:60px; height:auto;" alt="Reçu">
            </a>
        </div>
    <?php endif; ?>
</td></tr>
    </thead>
    <tbody>
        <?php foreach ($all as $shipment): ?>
        <tr>
            <td><?= $shipment['created_at'] ?? '-' ?></td>
            <td><?= $shipment['tracking_code'] ?? '-' ?></td>
            <td><?= $shipment['poids_total'] ?? 0 ?> kg</td>
            <td><?= $shipment['transport_cost'] ?? 0 ?> MAD</td>
            <td>
                <?php foreach (['40','41','42','43','44','45'] as $size): ?>
                    <?= $size ?>: <?= $shipment['size_' . $size . '_2'] ?? 0 ?>&nbsp;
                <?php endforeach; ?>
            </td>
        <td>
    <?php if (!empty($shipment['photo_carton'])): ?>
        <div class='mb-1'>
            📦 <a href="../uploads/<?= $shipment['photo_carton'] ?>" target="_blank">
                <img src="../uploads/<?= $shipment['photo_carton'] ?>" style="width:60px; height:auto;" alt="Carton">
            </a>
        </div>
    <?php endif; ?>
    <?php if (!empty($shipment['recu_transport'])): ?>
        <div>
            🧾 <a href="../uploads/<?= $shipment['recu_transport'] ?>" target="_blank">
                <img src="../uploads/<?= $shipment['recu_transport'] ?>" style="width:60px; height:auto;" alt="Reçu">
            </a>
        </div>
    <?php endif; ?>
</td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</body>
</html>


<?php
$paiements = $pdo->prepare("SELECT * FROM paiements WHERE order_id = ?");
$paiements->execute([$order['id']]);
$paiements = $paiements->fetchAll();


$paiements = $pdo->prepare("SELECT * FROM paiements WHERE order_id = ?");
    $paiements->execute([$order['id']]);
    $paiements = $paiements->fetchAll();

    $paiements_stmt = $pdo->prepare("SELECT SUM(montant) AS total_montant FROM paiements WHERE order_id = ?");
    $paiements_stmt->execute([$order['id']]);
    $paiement_total = $paiements_stmt->fetchColumn();

    if ($paiement_total === null) {
        $paiement_total = 0;
    }

    $total_order_without_fees = $order['qty_total'] * $order['prix_unit'];

    $total_order = $total_order_without_fees + $order['other_fees'];

    $reste_order = $total_order - $paiement_total;

?>

<div class="alert alert-info mt-4">
    💰 <strong>Total commande :</strong> <?=  number_format($total_order, 2, ',', ' ') . ' MAD';  ?> |
    ✅ <strong>Payé :</strong> <?=  number_format($paiement_total, 2, ',', ' ') . ' MAD';  ?> |
    ❗ <strong>Reste :</strong> <span class="text-danger"><?=  number_format($reste_order, 2, ',', ' ') . ' MAD';  ?></span>
</div>

<?php if (count($paiements) > 0): ?>
    

    <h5 class="mt-4">💵 Paiements reçus :</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Date</th>
                <th>Montant</th>
                <th>Reçu</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($paiements as $p): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($p['date_paiement'])) ?></td>
                <td><?= $p['montant'] ?> MAD</td>
                <td>
                    <?php if ($p['recu_image']): ?>
                        <a href="../uploads/<?= $p['recu_image'] ?>" target="_blank">
                            <img src="../uploads/<?= $p['recu_image'] ?>" width="80">
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>