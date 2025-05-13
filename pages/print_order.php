<?php
session_start();
if (!isset($_SESSION['logged_in']) && !isset($_SESSION['supplier_logged_in'])) {
    header('Location: ../index.php');
    exit;
}
include '../db.php';

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    echo "ID manquant."; exit;
}

$order = $pdo->query("SELECT * FROM orders WHERE id = $order_id")->fetch();
if (!$order) {
    echo "Commande introuvable."; exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Commande #<?= $order['id'] ?></title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h3>Commande #<?= $order['id'] ?> - <?= $order['model'] ?></h3>
    <p><strong>Date :</strong> <?= $order['date_commande'] ?> | <strong>Client :</strong> <?= $order['request'] ?> | <strong>Pays :</strong> <?= $order['country'] ?></p>
    <p><strong>Modèle :</strong> <?= $order['model'] ?> | <strong>Couleur :</strong> <?= $order['color'] ?></p>
    <p><strong>Statut :</strong> <?= $order['order_status'] ?></p>
    <?php if ($order['image_model']): ?>
        <img src='../uploads/<?= $order['image_model'] ?>' width='200'><br><br>
    <?php endif; ?>

    <h5>Quantités par taille :</h5>
    <ul>
        <li>Taille 40 : <?= $order['size_40_2'] ?></li>
        <li>Taille 41 : <?= $order['size_41_2'] ?></li>
        <li>Taille 42 : <?= $order['size_42_2'] ?></li>
        <li>Taille 43 : <?= $order['size_43_2'] ?></li>
        <li>Taille 44 : <?= $order['size_44_2'] ?></li>
        <li>Taille 45 : <?= $order['size_45_2'] ?></li>
    </ul>
    <p><strong>Total :</strong> <?= $order['qty_total'] ?> paires</p>

    <h5>Détails de paiement :</h5>
    <ul>
        <li>Prix unitaire : <?= $order['prix_unit'] ?> MAD</li>
        <li>Frais : <?= $order['other_fees'] ?> MAD</li>
        <li>Transport : <?= $order['total_transport'] ?> MAD</li>
        <li>Total : <?= $order['prix_total'] ?> MAD</li>
        <li>Payé : <?= $order['montant_paye'] ?> MAD</li>
        <li>Reste : <?= $order['reste'] ?> MAD</li>
    </ul>

    <button class="btn btn-primary no-print" onclick="window.print()">Imprimer</button>
</body>
</html>
