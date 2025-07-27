<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['commande_id'] ?? 0);
    $nouveau_temps = intval($_POST['nouveau_temps'] ?? 0);

    if ($id > 0 && $nouveau_temps > 0) {
        $stmt = $conn->prepare("UPDATE commandes SET temps_livraison=? WHERE id=?");
        $stmt->bind_param("ii", $nouveau_temps, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "⏱ Temps de livraison modifié avec succès.";
        } else {
            $_SESSION['message'] = "❌ Erreur lors de la modification.";
        }
    } else {
        $_SESSION['message'] = "❌ Données invalides.";
    }
}

header("Location: livraison_en_cours.php"); // Redirige vers la page des livraisons
exit;
