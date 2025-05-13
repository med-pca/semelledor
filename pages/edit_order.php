<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
include '../db.php';
$id = $_GET['id'];
$order = $pdo->query("SELECT * FROM orders WHERE id = $id")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $totalQty = array_sum([
        $_POST['size_40_2'], $_POST['size_41_2'], $_POST['size_42_2'],
        $_POST['size_43_2'], $_POST['size_44_2'], $_POST['size_45_2']
    ]);

    $stmt = $pdo->prepare("UPDATE orders SET 
        date_commande=?, request=?, country=?, model=?, color=?, qty_total=?, prix_unit=?, 
        other_fees=?, prix_total=?, montant_paye=?, total_transport=?, reste=?, 
        size_40_2=?, size_41_2=?, size_42_2=?, size_43_2=?, size_44_2=?, size_45_2=?, 
        order_status=? WHERE id=?");

    $stmt->execute([
        $_POST['date_commande'], $_POST['request'], $_POST['country'], $_POST['model'],
        $_POST['color'], $totalQty, $_POST['prix_unit'], $_POST['other_fees'],
        $_POST['prix_total'], $_POST['montant_paye'], $_POST['total_transport'], $_POST['reste'],
        $_POST['size_40_2'], $_POST['size_41_2'], $_POST['size_42_2'],
        $_POST['size_43_2'], $_POST['size_44_2'], $_POST['size_45_2'],
        $_POST['order_status'], $id
    ]);
    header("Location: orders.php");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Modifier la commande</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const prixUnitaireInput = document.querySelector("[name='prix_unit']");
    const prixTotalInput = document.querySelector("[name='prix_total']");
    const montantPayeInput = document.querySelector("[name='montant_paye']");
    const resteInput = document.querySelector("[name='reste']");
    const sizeInputs = ['size_40_2', 'size_41_2', 'size_42_2', 'size_43_2', 'size_44_2', 'size_45_2'].map(name => document.querySelector("[name='" + name + "']"));

    function updateTotalPrice() {
        const prixUnitaire = parseFloat(prixUnitaireInput.value) || 0;
        let totalQty = 0;
        sizeInputs.forEach(input => {
            totalQty += parseInt(input.value) || 0;
        });
        const totalPrice = prixUnitaire * totalQty;
        prixTotalInput.value = totalPrice.toFixed(2);
        updateReste();
    }

    function updateReste() {
        const prixTotal = parseFloat(prixTotalInput.value) || 0;
        const montantPaye = parseFloat(montantPayeInput.value) || 0;
        resteInput.value = (prixTotal - montantPaye).toFixed(2);
    }

    prixUnitaireInput.addEventListener('input', updateTotalPrice);
    montantPayeInput.addEventListener('input', updateReste);
    prixTotalInput.addEventListener('input', updateReste);
    sizeInputs.forEach(input => input.addEventListener('input', updateTotalPrice));
});
</script>

    <h3>Modifier commande #<?= $order['id'] ?></h3>
    <?php if ($order['image_model']): ?>
        <div class="mb-3">
            <label>Image du modèle :</label><br>
            <img src="../uploads/<?= $order['image_model'] ?>" width="150" />
        </div>
    <?php endif; ?>

    <form method='POST'>
        <label>Date de commande :</label>
        <input class='form-control mb-2' type='date' name='date_commande' value='<?= $order['date_commande'] ?>' required>

        <label>Demande / Client :</label>
        <input class='form-control mb-2' type='text' name='request' value='<?= $order['request'] ?>'>

        <label>Pays :</label>
<select class='form-control mb-2' name='country' required>
    <option value='cotedivoire' <?= $order['country'] == 'Cote dIvoire' ? 'selected' : '' ?>>cotedivoire</option>
    <option value='Guinée' <?= $order['country'] == "Guinée" ? 'selected' : '' ?>>Guinée</option>
</select>'>

        <label>Modèle :</label>
        <input class='form-control mb-2' type='text' name='model' value='<?= $order['model'] ?>'>

        <label>Couleur :</label>
        <input class='form-control mb-2' type='text' name='color' value='<?= $order['color'] ?>'>

        <label>Quantité par taille :</label>
        <div class='row'>
            <div class='col'><input class='form-control mb-2' type='number' name='size_40_2' placeholder='Taille 40' value='<?= $order['size_40_2'] ?? 0 ?>'></div>
            <div class='col'><input class='form-control mb-2' type='number' name='size_41_2' placeholder='Taille 41' value='<?= $order['size_41_2'] ?? 0 ?>'></div>
            <div class='col'><input class='form-control mb-2' type='number' name='size_42_2' placeholder='Taille 42' value='<?= $order['size_42_2'] ?? 0 ?>'></div>
        </div>
        <div class='row'>
            <div class='col'><input class='form-control mb-2' type='number' name='size_43_2' placeholder='Taille 43' value='<?= $order['size_43_2'] ?? 0 ?>'></div>
            <div class='col'><input class='form-control mb-2' type='number' name='size_44_2' placeholder='Taille 44' value='<?= $order['size_44_2'] ?? 0 ?>'></div>
            <div class='col'><input class='form-control mb-2' type='number' name='size_45_2' placeholder='Taille 45' value='<?= $order['size_45_2'] ?? 0 ?>'></div>
        </div>

        <label>Prix unitaire (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='prix_unit' value='<?= $order['prix_unit'] ?>'>

        <label>Frais additionnels (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='other_fees' value='<?= $order['other_fees'] ?>'>

        <label>Prix total (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='prix_total' value='<?= $order['prix_total'] ?>'>

        <label>Montant payé (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='montant_paye' value='<?= $order['montant_paye'] ?>'>

        <label>Frais de transport (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='total_transport' value='<?= $order['total_transport'] ?>'>

        <label>Reste à payer (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='reste' value='<?= $order['reste'] ?>'>

        
<label>Statut de la commande :</label>
<select name='order_status' class='form-control mb-2'>
    <option value='En attente du fournisseur' <?= $order['order_status'] == 'En attente du fournisseur' ? 'selected' : '' ?>>En attente du fournisseur</option>
    <option value='Prix en attente de validation' <?= $order['order_status'] == 'Prix en attente de validation' ? 'selected' : '' ?>>Prix en attente de validation</option>
    <option value='Prix validé – production en cours' <?= $order['order_status'] == 'Prix validé – production en cours' ? 'selected' : '' ?>>Prix validé – production en cours</option>
    <option value='En préparation' <?= $order['order_status'] == 'En préparation' ? 'selected' : '' ?>>En préparation</option>
    <option value="Commande prête à l'envoi" <?= $order['order_status'] == "Commande prête à l'envoi" ? 'selected' : '' ?>>Commande prête à l'envoi</option>
    <option value='Expédiée partiellement' <?= $order['order_status'] == 'Expédiée partiellement' ? 'selected' : '' ?>>Expédiée partiellement</option>
    <option value='Expédiée complètement' <?= $order['order_status'] == 'Expédiée complètement' ? 'selected' : '' ?>>Expédiée complètement</option>
    <option value='Clôturée' <?= $order['order_status'] == 'Clôturée' ? 'selected' : '' ?>>Clôturée</option>
</select>


        <button class='btn btn-success'>Enregistrer</button>
    </form>
    <a href='orders.php' class='btn btn-secondary mt-3'>Retour</a>
</body>
</html>
