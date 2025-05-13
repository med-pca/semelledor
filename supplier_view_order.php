<?php
session_start();
if (!isset($_SESSION['supplier_logged_in'])) {
    header('Location: supplier_login.php');
    exit;
}
include 'db.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = $pdo->query("SELECT * FROM orders WHERE id = $order_id")->fetch();

if (!$order) {
    echo "<div class='alert alert-danger'>Commande introuvable.</div>";
    exit;
}

function getFlagImg($country) {
    $normalized = strtolower(trim(str_replace(['’', "'", "`"], "", $country)));
    $normalized = str_replace(["côte", "cote"], "cote", $normalized);
    $map = [
        "cotedivoire" => 'civ.png',
        "guinee" => 'gn.png'
    ];
    $key = str_replace(" ", "", $normalized);
    return isset($map[$key]) ? "<img src='flags/" . $map[$key] . "' alt='$country' style='width:20px; height:auto; margin-right:5px;'> " : '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Détail de la commande</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
    <h3>Détail de la commande #<?= $order['id'] ?></h3>
    <a class='btn btn-secondary mb-3' href='supplier_dashboard.php'>⬅ Retour</a>
    <a class='btn btn-outline-danger mb-3' href='/shoe_order_crm_clean/pages/export_order_pdf.php?id=<?= $order['id'] ?>' target='_blank'>📄 Télécharger PDF</a>

    <?php if (!empty($order['image_model'])): ?>
        <img src='uploads/<?= $order['image_model'] ?>' alt='Image modèle' style='max-width:200px;'><br><br>
    <?php endif; ?>

    <ul class='list-group'>
        <li class='list-group-item'><strong>Date :</strong> <?= $order['date_commande'] ?></li>
        <li class='list-group-item'><strong>Client :</strong> <?= $order['request'] ?></li>
        <li class='list-group-item'><strong>Pays :</strong> <?= getFlagImg($order['country']) ?><?= $order['country'] ?></li>
        <li class='list-group-item'><strong>Modèle :</strong> <?= $order['model'] ?> | <strong>Couleur :</strong> <?= $order['color'] ?></li>
        <li class='list-group-item'>
            <strong>Quantités :</strong><br>
            40: <?= $order['size_40_2'] ?>,
            41: <?= $order['size_41_2'] ?>,
            42: <?= $order['size_42_2'] ?>,
            43: <?= $order['size_43_2'] ?>,
            44: <?= $order['size_44_2'] ?>,
            45: <?= $order['size_45_2'] ?> <br>
            <strong>Total :</strong> <?= $order['qty_total'] ?>
        </li>
        <li class='list-group-item'><strong>Prix unitaire :</strong> <?= $order['prix_unit'] ?> MAD</li>
        <li class='list-group-item'><strong>Total :</strong> <?= $order['prix_total'] ?> MAD</li>
        <li class='list-group-item'><strong>Frais autres :</strong> <?= $order['other_fees'] ?> MAD | <strong>Transport :</strong> <?= $order['total_transport'] ?> MAD</li>
        <li class='list-group-item'><strong>Montant payé :</strong> <?= $order['montant_paye'] ?> MAD | <strong>Reste :</strong> <?= $order['reste'] ?> MAD</li>
        <li class='list-group-item'><strong>Statut :</strong> <?= $order['order_status'] ?></li>
        <?php if (!empty($order['admin_note'])): ?>
        <li class='list-group-item text-info'><strong>Note de l'admin :</strong> <?= $order['admin_note'] ?></li>
        <?php endif; ?>
    </ul>
</body>
</html>
