<?php
session_start();
// Enregistrer filtres dans session
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['filter_from'] = $_GET['from'] ?? '';
    $_SESSION['filter_to'] = $_GET['to'] ?? '';
    $_SESSION['filter_status'] = $_GET['status'] ?? '';
    $_SESSION['filter_country'] = $_GET['country'] ?? '';
}

// Charger depuis session si dispo
$_GET['from'] = $_GET['from'] ?? ($_SESSION['filter_from'] ?? '');
$_GET['to'] = $_GET['to'] ?? ($_SESSION['filter_to'] ?? '');
$_GET['status'] = $_GET['status'] ?? ($_SESSION['filter_status'] ?? '');
$_GET['country'] = $_GET['country'] ?? ($_SESSION['filter_country'] ?? '');

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
include '../db.php';

// Résumé financier
$totaux = $pdo->query("SELECT 
    SUM(prix_total) AS total_global, 
    SUM(montant_paye) AS total_paye, 
    SUM(reste) AS reste_a_payer 
FROM orders")->fetch();

// Paiement manuel
if (isset($_POST['ajouter_paiement']) && isset($_POST['order_id']) && isset($_POST['montant'])) {
    $orderId = (int)$_POST['order_id'];
    $montant = (float)$_POST['montant'];
    $pdo->prepare("UPDATE orders SET montant_paye = montant_paye + ?, reste = GREATEST(prix_total - (montant_paye + ?), 0) WHERE id = ?")
        ->execute([$montant, $montant, $orderId]);
    header("Location: orders.php");
    exit;
}

// Lecture commandes

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

$where = "WHERE 1=1";
$params = [];

if (!empty($_GET['from'])) {
    $where .= " AND date_commande >= ?";
    $params[] = $_GET['from'];
}
if (!empty($_GET['to'])) {
    $where .= " AND date_commande <= ?";
    $params[] = $_GET['to'];
}
if (!empty($_GET['status'])) {
    $where .= " AND order_status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['country'])) {
    $where .= " AND country = ?";
    $params[] = $_GET['country'];
}


if ($from && $to) {
    $where = "WHERE date_commande BETWEEN ? AND ?";
    $params = [$from, $to];
} elseif ($from) {
    $where = "WHERE date_commande >= ?";
    $params = [$from];
} elseif ($to) {
    $where = "WHERE date_commande <= ?";
    $params = [$to];
}

$orders_stmt = $pdo->prepare("SELECT * FROM orders $where ORDER BY id DESC");
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll();

$totaux_stmt = $pdo->prepare("SELECT 
    SUM(prix_total) AS total_global, 
    SUM(montant_paye) AS total_paye, 
    SUM(reste) AS reste_a_payer 
FROM orders $where");
$totaux_stmt->execute($params);
$totaux = $totaux_stmt->fetch();


function getFlagImg($country) {
    $normalized = strtolower(trim(str_replace(['’', "'", "`"], "", $country)));
    $normalized = str_replace(["côte", "cote"], "cote", $normalized);
    $map = [
        "cotedivoire" => 'civ.png',
        "guinee" => 'gn.png'
    ];
    $key = str_replace(" ", "", $normalized);
    return isset($map[$key]) ? "<img src='../flags/" . $map[$key] . "' alt='$country' style='width:20px; height:auto; margin-right:5px;'> " : '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Orders</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        img.thumbnail { width: 80px; height: auto; }
    </style>
</head>
<body class='container mt-5'>
    <h3>Liste des commandes</h3>

    <form method="get" class="row mb-4">
    <div class="col">
        <label>Du :</label>
        <input type="date" name="from" class="form-control" value="<?= $_GET['from'] ?? '' ?>">
    </div>
    <div class="col">
        <label>Au :</label>
        <input type="date" name="to" class="form-control" value="<?= $_GET['to'] ?? '' ?>">
    </div>
    <div class="col">
        <label>Statut :</label>
        <select name="status" class="form-control">
            <option value="">Tous</option>
            <option value="En attente du fournisseur" <?= ($_GET['status'] ?? '') == 'En attente du fournisseur' ? 'selected' : '' ?>>En attente du fournisseur</option>
            <option value="Prix en attente de validation" <?= ($_GET['status'] ?? '') == 'Prix en attente de validation' ? 'selected' : '' ?>>Prix en attente de validation</option>
            <option value="Prix validé – production en cours" <?= ($_GET['status'] ?? '') == 'Prix validé – production en cours' ? 'selected' : '' ?>>Prix validé – production en cours</option>
            <option value="En préparation" <?= ($_GET['status'] ?? '') == 'En préparation' ? 'selected' : '' ?>>En préparation</option>
            <option value="Commande prête à l'envoi" <?= ($_GET['status'] ?? '') == "Commande prête à l'envoi" ? 'selected' : '' ?>>Commande prête à l'envoi</option>
            <option value="Expédiée partiellement" <?= ($_GET['status'] ?? '') == 'Expédiée partiellement' ? 'selected' : '' ?>>Expédiée partiellement</option>
            <option value="Expédiée complètement" <?= ($_GET['status'] ?? '') == 'Expédiée complètement' ? 'selected' : '' ?>>Expédiée complètement</option>
        </select>
    </div>
    <div class="col">
        <label>Pays :</label>
        <select name="country" class="form-control">
            <option value="">Tous</option>
            <option value="Côte d'Ivoire" <?= ($_GET['country'] ?? '') == "Côte d'Ivoire" ? 'selected' : '' ?>>Côte d'Ivoire</option>
            <option value="Guinée" <?= ($_GET['country'] ?? '') == "Guinée" ? 'selected' : '' ?>>Guinée</option>
        </select>
    </div>
    <div class="col d-flex align-items-end">
        <button class="btn btn-primary w-100" type="submit">Filtrer</button>
    <a href="orders.php" class="btn btn-secondary w-100 mt-2">Réinitialiser</a>
    </div>
</form>

<div class='alert alert-info'>
        💰 <strong>Total à payer :</strong> <?= $totaux['total_global'] ?? 0 ?> MAD |
        ✅ <strong>Payé :</strong> <?= $totaux['total_paye'] ?? 0 ?> MAD |
        ❗ <strong>Reste :</strong> <span class='text-danger'><?= $totaux['reste_a_payer'] ?? 0 ?> MAD</span>
    </div>

    <a href='order_form.php' class='btn btn-primary mb-3'>➕ Nouvelle commande</a>
    <a href='../logout.php' class='btn btn-danger mb-3'>Déconnexion</a>

    <table class='table table-bordered'>
        <thead>
            <tr>
                <th>Image</th><th>ID</th><th>Date</th><th>Client</th><th>Pays</th><th>Modèle</th>
                <th>Quantité</th><th>Payé</th><th>Reste</th><th>Statut</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td>
                    <?php if ($order['image_model']): ?>
                        <img class='thumbnail' src='../uploads/<?= $order['image_model'] ?>' alt='Modèle'>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= $order['id'] ?></td>
                <td><?= $order['date_commande'] ?></td>
                <td><?= $order['request'] ?></td>
                <td><?= getFlagImg($order['country']) ?><?= $order['country'] ?></td>
                <td><?= $order['model'] ?></td>
                <td><?= $order['qty_total'] ?></td>
                <td><?= $order['montant_paye'] ?></td>
                <td><?= $order['reste'] ?></td>
                <td><span class='badge bg-<?php 
        echo str_contains($order['order_status'], 'partiellement') ? "warning" : (
             str_contains($order['order_status'], 'complètement') ? "success" : (
             str_contains($order['order_status'], 'attente') ? "secondary" : "info")); 
        ?>'><?= $order['order_status'] ?></span></td>
                <td>
                    <a class='btn btn-sm btn-warning' href='edit_order.php?id=<?= $order['id'] ?>'>Modifier</a>
                    <a class='btn btn-sm btn-danger' href='delete_order.php?id=<?= $order['id'] ?>' onclick="return confirm('Supprimer cette commande ?')">Supprimer</a>
                    <a class='btn btn-sm btn-success' href='add_shipment.php?order_id=<?= $order['id'] ?>'>Envoi</a>
                    <a class='btn btn-sm btn-info' href='track_shipments.php?order_id=<?= $order['id'] ?>'>Suivi</a>
                    <a class='btn btn-sm btn-secondary' href='export_order_pdf.php?id=<?= $order['id'] ?>' target='_blank'>PDF</a>

                    <form method="post" action="orders.php" style="margin-top:5px;">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="number" name="montant" class="form-control form-control-sm" placeholder="💰 Paiement" required>
                        <button type="submit" name="ajouter_paiement" class="btn btn-sm btn-outline-success mt-1">Ajouter</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
