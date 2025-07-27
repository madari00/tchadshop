<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commande_id = intval($_POST['commande_id'] ?? 0);
    if ($commande_id > 0) {
        // Remise à zéro du temps de livraison (par ex. 0 ou valeur par défaut)
        $sql = "UPDATE commandes SET temps_livraison = 0 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $commande_id);
        $stmt->execute();

        $_SESSION['message'] = "Temps de livraison réinitialisé.";
    }
}

header("Location: livraison_en_cours.php?filtre_statut=en cours");
exit;
