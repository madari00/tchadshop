<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID invalide.");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM messages_clients WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Message supprimé avec succès.";
} else {
    $_SESSION['message'] = "Erreur lors de la suppression.";
}

header("Location: message_client.php");
exit();
?>
