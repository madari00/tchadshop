<?php
// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    header("Location: produits.php?msg=erreur_id");
    exit();
}

$produit_id = intval($_GET['id']);

// Supprimer les images associées
$img_sql = "SELECT image FROM images_produit WHERE produit_id = $produit_id";
$img_result = $conn->query($img_sql);

while ($img = $img_result->fetch_assoc()) {
    $image_path = $img['image'];
    if (file_exists($image_path)) {
        unlink($image_path);
    }
}

// Supprimer les enregistrements des images
$conn->query("DELETE FROM images_produit WHERE produit_id = $produit_id");

// Supprimer le produit
if ($conn->query("DELETE FROM produits WHERE id = $produit_id") === TRUE) {
    header("Location: produits.php?msg=supprime");
} else {
    header("Location: produits.php?msg=erreur");
}
$conn->close();
exit();
?>
