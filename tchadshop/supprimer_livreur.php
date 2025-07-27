<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID du livreur invalide.");
}

$id = intval($_GET['id']);

// Supprimer le livreur
$stmt = $conn->prepare("DELETE FROM livreurs WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['message'] = "✅ Livreur supprimé avec succès.";
    header("Location: livreurs.php");
    exit();
} else {
    echo "❌ Erreur lors de la suppression : " . $conn->error;
}

$stmt->close();
$conn->close();
?>
