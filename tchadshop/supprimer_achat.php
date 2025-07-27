<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID d'achat invalide.");
}

$id = intval($_GET['id']);

// Préparer la requête pour éviter injection SQL
$stmt = $conn->prepare("DELETE FROM historique_achats WHERE id = ?");
$stmt->bind_param("i", $id);


if ($stmt->execute()) {
    // Suppression réussie, redirection vers la liste des achats
    header("Location: historique_achats.php?msg=Suppression réussie");
    exit();
} else {
    echo "Erreur lors de la suppression : " . $conn->error;
}

$stmt->close();
$conn->close();
?>
