<?php
session_start();
if ($_POST['username'] === 'admin' && $_POST['password'] === 'admin') {
    $_SESSION['logged_in'] = true;
    header('Location: pages/orders.php');
    exit;
} else {
    echo "Invalid credentials.";
}
?>