<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $supplier = $stmt->fetch();

    if ($supplier) {
        $_SESSION['supplier_logged_in'] = true;
        $_SESSION['supplier_id'] = $supplier['id'];
        $_SESSION['supplier_username'] = $supplier['username'];
        header('Location: supplier_dashboard.php');
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Connexion Fournisseur</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='container mt-5'>
    <?php include_once 'pages/header.php'; ?>
    <h3>Connexion Fournisseur</h3>
    <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <form method='POST'>
        <input type='text' name='username' class='form-control mb-2' placeholder='Nom d’utilisateur' required>
        <input type='password' name='password' class='form-control mb-2' placeholder='Mot de passe' required>
        <button type='submit' class='btn btn-primary'>Se connecter</button>
    </form>
</body>
</html>
