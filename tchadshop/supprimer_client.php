<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: clients.php");
    exit();
}

// 🔥 Supprimer le client
$stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    $_SESSION['message'] = "✅ Client supprimé avec succès.";
} else {
    $_SESSION['message'] = "❌ Erreur lors de la suppression.";
}
header("Location: clients.php");
exit();
?>
