<?php

function getTotauxGlobal(PDO $pdo): array {
    $sql = "SELECT 
                SUM(qty_total * prix_unit) AS total_global,
                (SELECT SUM(montant) FROM paiements) AS total_paye, 
                SUM(other_fees) AS total_frais,
                SUM(reste) AS reste_a_payer 
            FROM orders";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total_global' => 0,
        'total_paye' => 0,
        'total_frais' => 0,
        'reste_a_payer' => 0
    ];
}






?>