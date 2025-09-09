<?php
session_start();
$isConnected = isset($_SESSION['client_id']);
echo "<script>const isConnected = " . ($isConnected ? 'true' : 'false') . ";</script>";

include("header.php");
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

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

    .produit h3 { margin: 10px 0 5px; }
    .produit .prix { color: #6a1b9a; font-weight: bold; }

    .btn-commander {
      display: inline-block;
      margin-top: 10px;
      background: #4caf50;
      color: white;
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
    }
    .btn-commander:hover { background: #388e3c; }

    .modal-overlay, .modal-content {
      display: none;
      position: fixed;
      z-index: 1001;
    }

    .modal-overlay {
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
    }

    .modal-content {
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      width: 90%; max-width: 400px;
    }

    .modal-content img { max-width: 100%; height: auto; margin-bottom: 10px; }

    .modal-content button, .modal-content a {
      margin-top: 10px;
      display: inline-block;
    }

    .modal-open { overflow: hidden; pointer-events: none; }
    .modal-open .modal-overlay, .modal-open .modal-content { pointer-events: auto; }
  </style>
</head>
<body>

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
    <button class="btn-commander" onclick="ouvrirCommande(<?= $produit['id'] ?>, '<?= htmlspecialchars($produit['nom']) ?>', '<?= htmlspecialchars($produit['images'][0]) ?>')">Commander</button>
  </div>
<?php endforeach; ?>
</div>

<!-- Étapes modales -->
<div class="modal-overlay" id="overlay"></div>

<div class="modal-content" id="step1">
  <h3>📞 Votre numéro</h3>
  <input type="tel" id="numeroClient" placeholder="Ex: 662XXXXXX"><br>
  <button onclick="nextStep1()">Suivant</button>
  <button onclick="fermerModals()">Annuler</button>
</div>

<div class="modal-content" id="step2">
  <h3>📍 Activer la localisation ?</h3>
  <button onclick="autoriserLocalisation()">Oui</button>
  <button onclick="ignorerLocalisation()">Non</button>
</div>

<div class="modal-content" id="step3">
  <h3>Quantité ou message</h3>
  <?php foreach ($produit['images'] as $i => $image): ?>
        <img src="../tchadshop/<?= htmlspecialchars($image) ?>" id="produitImage" class="<?= $i === 0 ? 'active' : '' ?>">
      <?php endforeach; ?>
  <p><strong id="produitNom"></strong></p>
  <input type="number" id="quantite" placeholder="Quantité"><br>
  <p>ou</p>
  <a id="btnWhatsapp" href="#" target="_blank">📩 WhatsApp</a><br>
  <button onclick="validerCommande()">Valider</button>
  <button onclick="fermerModals()">Annuler</button>
</div>

<script>
let carousels = [];
document.querySelectorAll('.carousel').forEach((carousel, index) => {
  const images = carousel.querySelectorAll('img');
  carousels[index] = { images, currentIndex: 0 };
});

function showImage(index, imageIndex) {
  const carousel = carousels[index];
  carousel.images.forEach((img, i) => img.classList.toggle('active', i === imageIndex));
  carousel.currentIndex = imageIndex;
}
function prevSlide(index) {
  const c = carousels[index];
  showImage(index, (c.currentIndex - 1 + c.images.length) % c.images.length);
}
function nextSlide(index) {
  const c = carousels[index];
  showImage(index, (c.currentIndex + 1) % c.images.length);
}

// Variables commande
let produitId = null, produitNom = '', produitImage = '', latitude = null, longitude = null;

function ouvrirCommande(id, nom, image) {
  produitId = id;
  produitNom = nom;
  produitImage = image;
  latitude = longitude = null;

  document.body.classList.add('modal-open');
  document.getElementById('overlay').style.display = 'block';

  if (isConnected) {
    ouvrirStep3();
  } else {
    document.getElementById('numeroClient').value = '';
    document.getElementById('step1').style.display = 'block';
  }
}

function fermerModals() {
  document.body.classList.remove('modal-open');
  document.querySelectorAll('.modal-content').forEach(e => e.style.display = 'none');
  document.getElementById('overlay').style.display = 'none';
}

function nextStep1() {
  const tel = document.getElementById('numeroClient').value.trim();
  if (!tel.match(/^\d{6,}$/)) return alert("Numéro invalide !");
  telephone = tel;
  document.getElementById('step1').style.display = 'none';
  document.getElementById('step2').style.display = 'block';
}

function autoriserLocalisation() {
  navigator.geolocation.getCurrentPosition(pos => {
    latitude = pos.coords.latitude;
    longitude = pos.coords.longitude;
    document.getElementById('step2').style.display = 'none';
    ouvrirStep3();
  }, () => {
    alert("Erreur géolocalisation");
    ignorerLocalisation();
  });
}
function ignorerLocalisation() {
  latitude = longitude = null;
  document.getElementById('step2').style.display = 'none';
  ouvrirStep3();
}

function ouvrirStep3() {
  document.getElementById('produitNom').textContent = produitNom;
  document.getElementById('produitImage').src = produitImage;
  document.getElementById('quantite').value = '';

  const message = `Je veux commander: ${produitNom}`;
  const encoded = encodeURIComponent(message);
  document.getElementById('btnWhatsapp').href = `https://wa.me/?text=${encoded}`;

  document.getElementById('step3').style.display = 'block';
}

function validerCommande() {
  const quantite = document.getElementById('quantite').value || 1;

  fetch('envoyer_commande.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      produit_id: produitId,
      telephone: telephone,
      quantite: quantite,
      latitude: latitude,
      longitude: longitude
    })
  })
  .then(res => res.text())
  .then(msg => {
    alert(msg);
    fermerModals();
  });
}
</script>
</body>
</html>
