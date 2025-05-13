<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagePath = '';
    if (!empty($_FILES['image_model']['name'])) {
        $targetDir = "../uploads/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        $filename = time() . "_" . basename($_FILES['image_model']['name']);
        $targetFile = $targetDir . $filename;
        move_uploaded_file($_FILES['image_model']['tmp_name'], $targetFile);
        $imagePath = $filename;
    }

    $totalQty = array_sum([
        $_POST['size_40_2'], $_POST['size_41_2'], $_POST['size_42_2'],
        $_POST['size_43_2'], $_POST['size_44_2'], $_POST['size_45_2']
    ]);

    $stmt = $pdo->prepare("INSERT INTO orders 
        (date_commande, request, country, model, color, qty_total, prix_unit, other_fees, prix_total, montant_paye, total_transport, reste, order_status, image_model,
         size_40_2, size_41_2, size_42_2, size_43_2, size_44_2, size_45_2)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['date_commande'], $_POST['request'], $_POST['country'], $_POST['model'],
        $_POST['color'], $totalQty, $_POST['prix_unit'], $_POST['other_fees'],
        $_POST['prix_total'], $_POST['montant_paye'], $_POST['total_transport'], $_POST['reste'],
        'En attente du fournisseur', $imagePath,
        $_POST['size_40_2'], $_POST['size_41_2'], $_POST['size_42_2'],
        $_POST['size_43_2'], $_POST['size_44_2'], $_POST['size_45_2']
    ]);
    header("Location: orders.php");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ajouter une commande</title>
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

    <h3>Nouvelle commande</h3>
    <form method='POST' enctype='multipart/form-data'>
        <label>Date de commande :</label>
        <input class='form-control mb-2' type='date' name='date_commande' required>

        <label>Demande / Client :</label>
        <input class='form-control mb-2' type='text' name='request' placeholder='Demande / Client' required>

        <label>Pays :</label>
<select class='form-control mb-2' name='country' required>
    <option value=''>-- Sélectionner un pays --</option>
    <option value="Cote dIvoire">Cote dIvoire</option>
    <option value="Guinée">Guinée</option>
</select>

        <label>Modèle :</label>
        <input class='form-control mb-2' type='text' name='model' placeholder='Modèle' required>

        <label>Couleur :</label>
        <input class='form-control mb-2' type='text' name='color' placeholder='Couleur' required>

        <label>Image du modèle :</label>
        <input class='form-control mb-2' type='file' name='image_model' accept='image/*'>

        <label>Quantité par taille :</label>
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

        <label>Prix unitaire (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='prix_unit' placeholder='Prix Unitaire'>

        <label>Frais additionnels (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='other_fees' placeholder='Autres frais'>

        <label>Prix total (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='prix_total' placeholder='Prix Total'>

        <label>Montant payé (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='montant_paye' placeholder='Montant Payé'>

        <label>Frais de transport (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='total_transport' placeholder='Transport'>

        <label>Reste à payer (MAD) :</label>
        <input class='form-control mb-2' type='number' step='0.01' name='reste' placeholder='Reste à payer'>

        <button class='btn btn-success'>Créer la commande</button>
    </form>
    <a href='orders.php' class='btn btn-secondary mt-3'>Retour</a>
</body>
</html>
