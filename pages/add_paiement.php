<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    echo "ID de commande manquant.";
    exit;
}

$order = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$order->execute([$order_id]);
$order = $order->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $montant = (float)$_POST['montant'];
    $recu_file = '';
    if (!empty($_FILES['recu']['name'])) {
        $ext = pathinfo($_FILES['recu']['name'], PATHINFO_EXTENSION);
        $recu_file = uniqid('recu_') . "." . $ext;
        move_uploaded_file($_FILES['recu']['tmp_name'], '../uploads/' . $recu_file);
    }

    $pdo->prepare("INSERT INTO paiements (order_id, montant, recu_image) VALUES (?, ?, ?)")
        ->execute([$order_id, $montant, $recu_file]);

    // mise à jour du total payé + reste
    $total_paye = $order['montant_paye'] + $montant;
    $reste = $order['prix_total'] - $total_paye;
    $pdo->prepare("UPDATE orders SET montant_paye = ?, reste = ? WHERE id = ?")
        ->execute([$total_paye, $reste, $order_id]);

    header("Location: edit_order.php?id=" . $order_id);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ajouter un paiement</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
    <h3>Ajouter un paiement pour la commande #<?= $order['id'] ?></h3>
    <form method='POST' enctype='multipart/form-data'>
        <div class='mb-3'>
            <label>Montant payé (MAD)</label>
            <input class='form-control' type='number' step='0.01' name='montant' required>
        </div>
        <div class='mb-3'>
            <label>📎 Reçu de paiement (image)</label>
            <input class='form-control' type='file' name='recu'>
        </div>
        <button class='btn btn-success'>💾 Enregistrer le paiement</button>
        <a href='edit_order.php?id=<?= $order['id'] ?>' class='btn btn-secondary'>Retour</a>
    </form>
</body>
</html>
