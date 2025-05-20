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
<!DOCTYPE html>
<html>
<head>
    <title>Détail de la commande</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>

    <?php include_once 'pages/header.php'; ?>
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
            40: <?= $order['size_40_2'] ?><br>
            41: <?= $order['size_41_2'] ?><br>
            42: <?= $order['size_42_2'] ?><br>
            43: <?= $order['size_43_2'] ?><br>
            44: <?= $order['size_44_2'] ?><br>
            45: <?= $order['size_45_2'] ?> <br>
            <strong>Total des tailles :</strong> <?= $order['qty_total'] ?>
        </li>
        <li class='list-group-item'><strong>Prix unitaire :</strong> <?= number_format($order['prix_unit'], 2, ',', ' ') . ' MAD';   ?></li>
        <li class='list-group-item'><strong>Total sans frais :</strong> <?=  number_format($total_order_without_fees, 2, ',', ' ') . ' MAD';  ?> </li>
        <li class='list-group-item'><strong>Frais autres :</strong> <?=  number_format($order['other_fees'], 2, ',', ' ') . ' MAD';  ?>  | <strong>Transport :</strong> <?= number_format($order['total_transport'], 2, ',', ' ') . ' MAD';  ?> </li>
        
        <li class='list-group-item'><strong>Statut :</strong> <?= $order['order_status'] ?></li>
        <?php if (!empty($order['admin_note'])): ?>
        <li class='list-group-item text-info'><strong>Note de l'admin :</strong> <?= $order['admin_note'] ?></li>
        <?php endif; ?>
    </ul>


<div class="alert alert-info mt-4">
    💰 <strong>Total commande :</strong> <?=  number_format($total_order, 2, ',', ' ') . ' MAD';  ?> |
    ✅ <strong>Payé :</strong> <?=  number_format($paiement_total, 2, ',', ' ') . ' MAD';  ?> |
    ❗ <strong>Reste :</strong> <span class="text-danger"><?=  number_format($reste_order, 2, ',', ' ') . ' MAD';  ?></span>
</div>

<?php if (count($paiements) > 0): ?>
    <h5 class="mt-4">📎 Paiements reçus :</h5>
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
                <td><?=  number_format($p['montant'], 2, ',', ' ') . ' MAD';  ?></td>
                <td>
                    <?php if ($p['recu_image']): ?>
                        <a href="uploads/<?= $p['recu_image'] ?>" target="_blank">
                            <img src="uploads/<?= $p['recu_image'] ?>" width="80">
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

</body>
</html>
