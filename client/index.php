<?php
include("header.php");
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// 🔄 Récupérer les produits et leurs images
$sql = "SELECT p.id, p.nom, p.description, p.prix, p.stock, GROUP_CONCAT(i.image SEPARATOR '|') AS images
        FROM produits p
        LEFT JOIN images_produit i ON p.id = i.produit_id
        WHERE p.statut = 'disponible'
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT 12";

$result = $conn->query($sql);
$produits = [];
while ($row = $result->fetch_assoc()) {
    $row['images'] = explode('|', $row['images']);
    $produits[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Accueil - TchadShop</title>
  <style>
    .produits-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      padding: 30px;
    }

    .produit {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 15px;
      text-align: center;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }

    .carousel {
      position: relative;
      width: 100%;
      height: 180px;
      overflow: hidden;
      border-radius: 8px;
    }

    .carousel img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      display: none;
    }

    .carousel img.active {
      display: block;
    }

    .carousel .arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(0,0,0,0.4);
      color: white;
      font-weight: bold;
      font-size: 18px;
      padding: 5px 10px;
      border: none;
      cursor: pointer;
      z-index: 10;
      border-radius: 4px;
    }

    .carousel .prev { left: 5px; }
    .carousel .next { right: 5px; }

    .produit h3 {
      margin: 10px 0 5px;
    }

    .produit .prix {
      color: #6a1b9a;
      font-weight: bold;
    }

    .btn-commander {
      display: inline-block;
      margin-top: 10px;
      background: #4caf50;
      color: white;
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
    }

    .btn-commander:hover {
      background: #388e3c;
    }
  </style>
</head>
<body>
<div class="content">
  <h2>🛍 Bienvenue sur TchadShop</h2>
  <p>Commandez facilement depuis chez vous !</p>

  <div class="produits-container">
    <?php foreach ($produits as $index => $produit): ?>
      <div class="produit">
        <div class="carousel" id="carousel-<?= $index ?>">
          <?php foreach ($produit['images'] as $i => $image): ?>
            <img src="../tchadshop/<?= htmlspecialchars($image) ?>" class="<?= $i === 0 ? 'active' : '' ?>">
          <?php endforeach; ?>
          <button class="arrow prev" onclick="prevSlide(<?= $index ?>)">&#10094;</button>
          <button class="arrow next" onclick="nextSlide(<?= $index ?>)">&#10095;</button>
        </div>

        <h3><?= htmlspecialchars($produit['nom']) ?></h3>
        <p class="prix"><?= number_format($produit['prix'], 0, ',', ' ') ?> FCFA</p>
        <a href="commander.php?produit_id=<?= $produit['id'] ?>" class="btn-commander">Commander</a>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
let carousels = [];

document.querySelectorAll('.carousel').forEach((carousel, index) => {
  const images = carousel.querySelectorAll('img');
  carousels[index] = {
    images,
    currentIndex: 0
  };
});

function showImage(index, imageIndex) {
  const carousel = carousels[index];
  carousel.images.forEach((img, i) => {
    img.classList.toggle('active', i === imageIndex);
  });
  carousel.currentIndex = imageIndex;
}

function prevSlide(index) {
  const carousel = carousels[index];
  const newIndex = (carousel.currentIndex - 1 + carousel.images.length) % carousel.images.length;
  showImage(index, newIndex);
}

function nextSlide(index) {
  const carousel = carousels[index];
  const newIndex = (carousel.currentIndex + 1) % carousel.images.length;
  showImage(index, newIndex);
}
</script>

</body>
</html>
