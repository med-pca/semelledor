<?php
session_start();
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
    echo "Order not found."; exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO shipments (
        order_id, shipment_date, code_carton, poids_total, transport_cost, commentaire,
        size_40_2, size_41_2, size_42_2, size_43_2, size_44_2, size_45_2
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $order_id, $_POST['shipment_date'], $_POST['code_carton'], $_POST['poids_total'],
        $_POST['transport_cost'], $_POST['commentaire'],
        $_POST['size_40_2'], $_POST['size_41_2'], $_POST['size_42_2'],
        $_POST['size_43_2'], $_POST['size_44_2'], $_POST['size_45_2']
    ]);
    if (isset($_SESSION['supplier_logged_in'])) {
    header("Location: ../supplier_dashboard.php");
} else {
    header("Location: orders.php");
}
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

<?php if (isset($new_status)): ?>
<div class='alert alert-success text-center mt-3'>
    ✅ Statut mis à jour automatiquement : <strong><?= $new_status ?></strong>
</div>
<?php endif; ?>

  <h3>Ajouter un envoi pour la commande #<?= $order['id'] ?> (<?= $order['model'] ?>)</h3>
  <form method='POST'>
    <input class='form-control mb-2' type='date' name='shipment_date' required>
    <input class='form-control mb-2' type='text' name='code_carton' placeholder='Code carton' required>
    <input class='form-control mb-2' type='number' step='0.01' name='poids_total' placeholder='Poids (kg)' required>
    <input class='form-control mb-2' type='number' step='0.01' name='transport_cost' placeholder='Coût de transport (€)' required>
    <textarea class='form-control mb-2' name='commentaire' placeholder='Commentaire'></textarea>

    <div class='row'>
      <div class='col'><input class='form-control mb-2' type='number' name='size_40_2' placeholder='Taille 40' value='0'></div>
      <div class='col'><input class='form-control mb-2' type='number' name='size_41_2' placeholder='Taille 41' value='0'></div>
      <div class='col'><input class='form-control mb-2' type='number' name='size_42_2' placeholder='Taille 42' value='0'></div>
    </div>
    <div class='row'>
      <div class='col'><input class='form-control mb-2' type='number' name='size_43_2' placeholder='Taille 43' value='0'></div>
      <div class='col'><input class='form-control mb-2' type='number' name='size_44_2' placeholder='Taille 44' value='0'></div>
      <div class='col'><input class='form-control mb-2' type='number' name='size_45_2' placeholder='Taille 45' value='0'></div>
    </div>

    <button class='btn btn-success'>Ajouter l'envoi</button>
  </form>
  <?php if (isset($_SESSION['supplier_logged_in'])): ?>
<a href='../supplier_dashboard.php' class='btn btn-secondary mt-3'>Retour</a>
<?php else: ?>
<a href='orders.php' class='btn btn-secondary mt-3'>Retour</a>
<?php endif; ?>
</body>
</html>
