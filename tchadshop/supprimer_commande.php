<?php
session_start();
$searchContext = '';

$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID de commande invalide !");
}

// Supprimer les détails de la commande
$conn->query("DELETE FROM details_commandes WHERE commande_id = $id");

// Supprimer la commande
if ($conn->query("DELETE FROM commandes WHERE id = $id")) {
    header("Location: toutes_commandes.php?msg=suppression_ok");
    exit;
} else {
    die("Erreur lors de la suppression : " . $conn->error);
}
?>
