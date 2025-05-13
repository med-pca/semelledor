<?php
session_start();
if (!isset($_SESSION['supplier_logged_in'])) {
    header('Location: supplier_login.php');
    exit;
}
include 'db.php';

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

$orders = $pdo->query("SELECT * FROM orders 
    WHERE order_status IN ('En attente du fournisseur', 'Prix en attente de validation', 'Prix validé – production en cours', 'En préparation', 'Commande prête à l\'envoi') 
    ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Fournisseur</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .card-img-top { max-height: 150px; object-fit: cover; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body class='container mt-5'>
<h3>Bienvenue <?= $_SESSION['supplier_username'] ?></h3>
<h5>Commandes en cours</h5>

<div class='row'>
    <?php foreach ($orders as $order): ?>
    <div class='col-md-6'>
        <div class='card'>
            <?php if (!empty($order['image_model'])): ?>
                <img src='uploads/<?= $order['image_model'] ?>' class='card-img-top' alt='Image modèle'>
            <?php endif; ?>
            <div class='card-body'>
                <h5 class='card-title'>Commande #<?= $order['id'] ?> - <?= $order['model'] ?></h5>
                <p><strong>Couleur :</strong> <?= $order['color'] ?> | <strong>Pays :</strong> <?= getFlagImg($order['country']) ?><?= $order['country'] ?></p>
                <p><strong>Date :</strong> <?= $order['date_commande'] ?> | <strong>Client :</strong> <?= $order['request'] ?></p>
                <p><strong>Quantités :</strong><br>
                    40: <?= $order['size_40_2'] ?>,
                    41: <?= $order['size_41_2'] ?>,
                    42: <?= $order['size_42_2'] ?>,
                    43: <?= $order['size_43_2'] ?>,
                    44: <?= $order['size_44_2'] ?>,
                    45: <?= $order['size_45_2'] ?> <br>
                    <strong>Total :</strong> <?= $order['qty_total'] ?>
                </p>
                <p><strong>Statut :</strong> <?= $order['order_status'] ?></p>
                <?php if (!empty($order['admin_note'])): ?>
                    <div class='alert alert-info'><strong>Note de l'admin :</strong> <?= $order['admin_note'] ?></div>
                <?php endif; ?>
                <?php if ($order['order_status'] === 'En attente du fournisseur'): ?>
                    <a class='btn btn-sm btn-success' href='supplier_update_order.php?order_id=<?= $order['id'] ?>'>Ajouter prix & frais</a>
                <?php endif; ?>
                <a class='btn btn-sm btn-outline-secondary mt-1' href='supplier_view_order.php?id=<?= $order['id'] ?>'>📄 Voir les détails</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>
