<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
include '../db.php';
$id = $_GET['id'];
$pdo->query("DELETE FROM orders WHERE id = $id");
header('Location: orders.php');
?>
