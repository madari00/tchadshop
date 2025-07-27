<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$commande_id = intval($_POST['commande_id'] ?? 0);

if ($commande_id > 0) {
    $stmt = $conn->prepare("UPDATE commandes SET statut = 'échec' WHERE id = ?");
    $stmt->bind_param("i", $commande_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "❌ Livraison marquée comme échec (Commande #$commande_id).";
    } else {
        $_SESSION['message'] = "⚠️ Erreur : impossible de marquer comme échec.";
    }
    $stmt->close();
} else {
    $_SESSION['message'] = "❗ Commande invalide.";
}

header("Location: livraison_en_cours.php");
exit();
?>
