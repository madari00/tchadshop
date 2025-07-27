<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$commande_id = intval($_POST['commande_id'] ?? 0);

if ($commande_id > 0) {
    // Met à jour le statut et la date de livraison
    $sql = "UPDATE commandes 
            SET statut = 'livré', date_livraison = NOW() 
            WHERE id = $commande_id";
    if ($conn->query($sql)) {
        $_SESSION['message'] = "✅ Livraison marquée comme terminée.";
    } else {
        $_SESSION['message'] = "❌ Erreur lors de la mise à jour.";
    }
}

header("Location: livraison_en_cours.php");
exit;
