<?php
$host = '127.0.0.1';
$db   = 'shoe_orders';
$user = 'root';
$pass = 'root';
$port = 8889;
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Success: A proper connection to MySQL was made.<br>";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
