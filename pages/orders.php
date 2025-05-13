<?php
function getStatusBadge($status) {
    $colors = [
        'En attente du fournisseur' => 'warning',
        'Prix en attente de validation' => 'info',
        'Prix validé – production en cours' => 'primary',
        'En préparation' => 'secondary',
        'Commande prête à l\'envoi' => 'dark',
        'Expédiée partiellement' => 'warning',
        'Expédiée complètement' => 'success',
        'Clôturée' => 'success',
    ];
    $color = $colors[$status] ?? 'light';
    return "<span class='badge bg-$color'>$status</span>";
}
?>
<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
include '../db.php';

function getFlagImg($country) {
    $flags = [
        "cotedivoire" => 'civ.png',
        "Guinée" => 'gn.png',
    ];
    return isset($flags[$country]) ? "<img src='../flags/" . $flags[$country] . "' alt='$country' style='width:20px; height:auto; margin-right:5px;'> " : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_price'])) {
    $stmt = $pdo->prepare("UPDATE orders SET prix_unit=?, other_fees=?, admin_note=?, order_status='Prix validé – production en cours' WHERE id=?");
    $stmt->execute([
        $_POST['prix_unit'], $_POST['other_fees'], $_POST['admin_note'], $_POST['order_id']
    ]);
    header("Location: orders.php");
    exit;
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Orders</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
      img.thumbnail { width: 80px; height: auto; }
    </style>
</head>
<body class='container mt-5'>
    <h3>Orders List</h3>
    <a href='order_form.php' class='btn btn-primary mb-3'>Add New Order</a>
    <a href='../logout.php' class='btn btn-danger mb-3'>Logout</a>
    <table class='table table-bordered'>
        <thead>
            <tr>
                <th>Image</th><th>ID</th><th>Date</th><th>Request BY</th><th>Country</th><th>Model</th>
                <th>Qty</th><th>Payé</th><th>Reste</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td>
                    <?php if ($order['image_model']): ?>
                        <img class='thumbnail' src='../uploads/<?= $order['image_model'] ?>' alt='Model'>
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
                <td><?= getStatusBadge($order['order_status']) ?></td>
                <td>
                    <?php if ($order['order_status'] === 'Prix en attente de validation'): ?>
                    <form method="POST" class="d-flex flex-column gap-1">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="number" step="0.01" class="form-control" name="prix_unit" value="<?= $order['prix_unit'] ?>" placeholder="Prix unitaire" required>
                        <input type="number" step="0.01" class="form-control" name="other_fees" value="<?= $order['other_fees'] ?>" placeholder="Frais" required>
                        <input type="text" name="admin_note" class="form-control" placeholder="Note au fournisseur" value="<?= $order['admin_note'] ?? "" ?>">
                        <button class="btn btn-sm btn-success" name="validate_price">Valider le prix</button>
                    </form>
                    <?php else: ?>
                    <a class='btn btn-sm btn-warning' href='edit_order.php?id=<?= $order['id'] ?>'>Edit</a>
                    <a class='btn btn-sm btn-danger' href='delete_order.php?id=<?= $order['id'] ?>' onclick="return confirm('Delete this order?')">Delete</a>
                    <a class='btn btn-sm btn-success' href='add_shipment.php?order_id=<?= $order['id'] ?>'>Envoi</a>
                    <a class='btn btn-sm btn-info' href='track_shipments.php?order_id=<?= $order['id'] ?>'>Suivi</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
