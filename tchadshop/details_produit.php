<?php
session_start();
$searchContext = 'detailp';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

// ✅ Vérifier l'ID du produit
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: produits.php");
    exit();
}

$id = intval($_GET['id']);

// 🔥 Récupérer les détails du produit
$sql = "SELECT p.nom, p.description, p.prix, p.stock, p.statut, p.created_at, i.image
        FROM produits p
        LEFT JOIN images_produit i ON p.id = i.produit_id
        WHERE p.id = $id";

$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "<h2>❌ Produit introuvable</h2>";
    exit();
}

// 👉 Regrouper les données
$produit = [
    'nom' => '',
    'description' => '',
    'prix' => '',
    'stock' => '',
    'statut' => '',
    'created_at' => '',
    'images' => []
];

while ($row = $result->fetch_assoc()) {
    $produit['nom'] = $row['nom'];
    $produit['description'] = $row['description'];
    $produit['prix'] = $row['prix'];
    $produit['stock'] = $row['stock'];
    $produit['statut'] = $row['statut'];
    $produit['created_at'] = $row['created_at'];
    if (!empty($row['image'])) {
        $produit['images'][] = $row['image'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Détails du produit</title>
  <style>
    body { font-family: Arial; background: #f5f5f5; margin: 0; padding: 0; }
    .container { width: 80%; margin: 20px auto; background: #fff; border-radius: 8px; }
    h2 { color: #6a1b9a; }
    .info { margin-bottom: 15px; }
    .info strong { display: inline-block; width: 150px; color: #555; }
    .gallery { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .gallery img { width: 150px; height: 150px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd; }
    .back-btn { display: inline-block; margin-top: 20px; text-decoration: none; background: #6a1b9a; color: #fff; padding: 10px 20px; border-radius: 5px; }
    .back-btn:hover { background: #4a148c; }
  </style>
</head>
<body>
    <?php include("header.php") ; ?>
<div class="home-content">
<div class="container">
  <h2>👁 Détails du produit</h2>

  <div class="info"><strong>Nom :</strong> <?= htmlspecialchars($produit['nom']) ?></div>
  <div class="info"><strong>Description :</strong> <?= htmlspecialchars($produit['description']) ?></div>
  <div class="info"><strong>Prix :</strong> <?= number_format($produit['prix'], 0, ',', ' ') ?> FCFA</div>
  <div class="info"><strong>Stock :</strong> <?= $produit['stock'] ?></div>
  <div class="info"><strong>Statut :</strong> <?= ucfirst($produit['statut']) ?></div>
  <div class="info"><strong>Date d'ajout :</strong> <?= date('d/m/Y H:i', strtotime($produit['created_at'])) ?></div>

  <h3>🖼 Images du produit :</h3>
  <?php if (!empty($produit['images'])): ?>
    <div class="gallery">
      <?php foreach ($produit['images'] as $img): ?>
        <img src="<?= $img ?>" alt="Image produit">
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>Aucune image disponible pour ce produit.</p>
  <?php endif; ?>

  <a href="produits.php" class="back-btn">⬅ Retour à la liste</a>
</div>
</body>
</html>
