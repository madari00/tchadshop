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

// 🔥 Récupérer les détails du produit avec les informations de promotion
$sql = "SELECT p.nom, p.description, p.prix, p.prix_promotion, p.stock, p.statut, 
               p.created_at, p.promotion, p.date_debut_promo, p.date_fin_promo,
               i.image
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
    'prix_promotion' => null,
    'stock' => '',
    'statut' => '',
    'created_at' => '',
    'promotion' => 0,
    'date_debut_promo' => null,
    'date_fin_promo' => null,
    'images' => []
];

$is_active_promo = false;
$promo_status = "Aucune promotion";

while ($row = $result->fetch_assoc()) {
    if (empty($produit['nom'])) {
        $produit['nom'] = $row['nom'];
        $produit['description'] = $row['description'];
        $produit['prix'] = $row['prix'];
        $produit['prix_promotion'] = $row['prix_promotion'];
        $produit['stock'] = $row['stock'];
        $produit['statut'] = $row['statut'];
        $produit['created_at'] = $row['created_at'];
        $produit['promotion'] = $row['promotion'];
        $produit['date_debut_promo'] = $row['date_debut_promo'];
        $produit['date_fin_promo'] = $row['date_fin_promo'];
    }
    
    if (!empty($row['image'])) {
        $produit['images'][] = $row['image'];
    }
}

// Déterminer l'état de la promotion
if ($produit['promotion'] > 0) {
    $today = date('Y-m-d');
    $start = $produit['date_debut_promo'];
    $end = $produit['date_fin_promo'];
    
    if ($start && $end) {
        if ($today >= $start && $today <= $end) {
            $is_active_promo = true;
            $promo_status = "Promotion active";
        } elseif ($today < $start) {
            $promo_status = "Promotion future";
        } elseif ($today > $end) {
            $promo_status = "Promotion expirée";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Détails du produit</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body { 
      background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
      min-height: 100vh;
      
    }
    
    .container1 { 
      max-width: 1200px; 
      margin: 0 auto;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }
    
    .header {
      background: linear-gradient(120deg, #bcdfd7ff 0%, #a2a5a3ff 100%);
      color: white;
      padding: 25px 30px;
      position: relative;
    }
    
    .header h2 {
      font-size: 28px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .header h2 i {
      background: rgba(255, 255, 255, 0.2);
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }
    
    .back-btn {
      position: absolute;
      top: 30px;
      right: 30px;
      text-decoration: none;
      background: rgba(255, 255, 255, 0.2);
      color: white;
      padding: 10px 20px;
      border-radius: 50px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .back-btn:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: translateY(-2px);
    }
    
    .content {
      display: flex;
      padding: 30px;
      gap: 30px;
    }
    
    .gallery-section {
      flex: 1;
    }
    
    .info-section {
      flex: 1;
    }
    
    .section-title {
      font-size: 20px;
      color: #2c3e50;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f0f4f8;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 15px;
      margin-top: 10px;
    }
    
    .gallery img {
      width: 100%;
      height: 150px;
      object-fit: cover;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }
    
    .gallery img:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
      border-color: #6a11cb;
    }
    
    .no-image {
      background: #f8f9fa;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      padding: 30px;
      text-align: center;
      color: #6c757d;
    }
    
    .no-image i {
      font-size: 48px;
      margin-bottom: 15px;
      color: #dee2e6;
    }
    
    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    
    .info-card {
      background: #f8fafc;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.03);
    }
    
    .info-card h3 {
      font-size: 16px;
      color: #6c757d;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .info-card .value {
      font-size: 22px;
      font-weight: 600;
      color: #2c3e50;
    }
    
    .status {
      display: inline-block;
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 500;
    }
    
    .status-disponible {
      background: #e8f5e9;
      color: #2e7d32;
    }
    
    .status-rupture {
      background: #ffebee;
      color: #c62828;
    }
    
    .status-bientot {
      background: #fff8e1;
      color: #f57f17;
    }
    
    .promo-section {
      background: #fff8e1;
      border-radius: 12px;
      padding: 20px;
      margin-top: 25px;
      border-left: 4px solid #ffc107;
    }
    
    .promo-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 15px;
    }
    
    .promo-header h3 {
      color: #e65100;
      font-size: 20px;
    }
    
    .promo-badge {
      background: #ff9800;
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
    }
    
    .price-section {
      display: flex;
      align-items: center;
      gap: 15px;
      margin: 15px 0;
    }
    
    .original-price {
      font-size: 22px;
      color: #78909c;
      text-decoration: line-through;
    }
    
    .promo-price {
      font-size: 32px;
      font-weight: 700;
      color: #e65100;
    }
    
    .discount {
      background: #ff5722;
      color: white;
      padding: 5px 12px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 18px;
    }
    
    .promo-dates {
      display: flex;
      gap: 15px;
      margin-top: 15px;
    }
    
    .date-card {
      flex: 1;
      background: white;
      border-radius: 10px;
      padding: 15px;
      text-align: center;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .date-card h4 {
      color: #607d8b;
      font-size: 14px;
      margin-bottom: 8px;
    }
    
    .date-card .date-value {
      font-size: 18px;
      font-weight: 600;
      color: #37474f;
    }
    
    .no-promo {
      text-align: center;
      padding: 20px;
      color: #78909c;
      font-size: 18px;
    }
    
    @media (max-width: 900px) {
      .content {
        flex-direction: column;
      }
      
      .info-grid {
        grid-template-columns: 1fr;
      }
      
      .gallery {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      }
    }
    
    @media (max-width: 480px) {
      .header {
        padding: 20px;
      }
      
      .header h2 {
        font-size: 22px;
      }
      
      .back-btn {
        position: relative;
        top: 0;
        right: 0;
        margin-top: 15px;
        width: 100%;
        justify-content: center;
      }
      
      .content {
        padding: 20px;
      }
      
      .price-section {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .promo-dates {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <?php include("header.php"); ?>
  <div class="home-content">
  <div class="container1">
    <div class="header">
      <h2>
        <i>👁</i> Détails du produit
      </h2>
      <a href="produits.php" class="back-btn">
        <i>←</i> Retour à la liste
      </a>
    </div>
    
    <div class="content">
      <div class="gallery-section">
        <div class="section-title">
          <i>🖼</i> Galerie d'images
        </div>
        
        <?php if (!empty($produit['images'])): ?>
          <div class="gallery">
            <?php foreach ($produit['images'] as $img): ?>
              <img src="<?= $img ?>" alt="Image produit">
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="no-image">
            <i>📷</i>
            <h3>Aucune image disponible</h3>
            <p>Ce produit n'a pas encore d'image associée</p>
          </div>
        <?php endif; ?>
      </div>
      
      <div class="info-section">
        <div class="info-grid">
          <div class="info-card">
            <h3><i>🏷</i> Nom du produit</h3>
            <div class="value"><?= htmlspecialchars($produit['nom']) ?></div>
          </div>
          
          <div class="info-card">
            <h3><i>📦</i> Stock disponible</h3>
            <div class="value"><?= $produit['stock'] ?> unités</div>
          </div>
          
          <div class="info-card">
            <h3><i>📅</i> Date d'ajout</h3>
            <div class="value"><?= date('d/m/Y à H:i', strtotime($produit['created_at'])) ?></div>
          </div>
          
          <div class="info-card">
            <h3><i>🔍</i> Statut</h3>
            <div class="value">
              <?php 
                $status_class = 'status-' . str_replace(' ', '', $produit['statut']);
                $status_text = $produit['statut'];
                if ($status_text == 'disponible') $status_text = 'Disponible';
                elseif ($status_text == 'rupture') $status_text = 'Rupture de stock';
                elseif ($status_text == 'bientôt') $status_text = 'Bientôt disponible';
              ?>
              <span class="status <?= $status_class ?>"><?= $status_text ?></span>
            </div>
          </div>
        </div>
        
        <div class="info-card" style="margin-top: 20px;">
          <h3><i>📝</i> Description</h3>
          <div class="value" style="font-size: 18px; line-height: 1.6; margin-top: 10px;">
            <?= nl2br(htmlspecialchars($produit['description'])) ?>
          </div>
        </div>
        
        <!-- Section Promotion -->
        <div class="promo-section">
          <div class="promo-header">
            <h3>Offre promotionnelle</h3>
            <?php if ($produit['promotion'] > 0): ?>
              <span class="promo-badge">-<?= $produit['promotion'] ?>%</span>
            <?php endif; ?>
          </div>
          
          <?php if ($is_active_promo): ?>
            <div class="price-section">
              <div class="original-price">
                <?= number_format($produit['prix'], 0, ',', ' ') ?> FCFA
              </div>
              <div class="promo-price">
                <?= number_format($produit['prix_promotion'], 0, ',', ' ') ?> FCFA
              </div>
              <div class="discount">
                Économisez <?= number_format($produit['prix'] - $produit['prix_promotion'], 0, ',', ' ') ?> FCFA
              </div>
            </div>
            
            <div class="promo-dates">
              <div class="date-card">
                <h4>Début de la promotion</h4>
                <div class="date-value">
                  <?= date('d/m/Y', strtotime($produit['date_debut_promo'])) ?>
                </div>
              </div>
              <div class="date-card">
                <h4>Fin de la promotion</h4>
                <div class="date-value">
                  <?= date('d/m/Y', strtotime($produit['date_fin_promo'])) ?>
                </div>
              </div>
            </div>
          <?php elseif ($produit['promotion'] > 0): ?>
            <div class="no-promo">
              <p><strong><?= $promo_status ?></strong></p>
              <p style="margin-top: 10px;">
                Prix promotionnel: <?= number_format($produit['prix_promotion'], 0, ',', ' ') ?> FCFA
              </p>
              <div class="promo-dates" style="margin-top: 15px;">
                <div class="date-card">
                  <h4>Début</h4>
                  <div class="date-value">
                    <?= date('d/m/Y', strtotime($produit['date_debut_promo'])) ?>
                  </div>
                </div>
                <div class="date-card">
                  <h4>Fin</h4>
                  <div class="date-value">
                    <?= date('d/m/Y', strtotime($produit['date_fin_promo'])) ?>
                  </div>
                </div>
              </div>
            </div>
          <?php else: ?>
            <div class="no-promo">
              <p>Aucune promotion active pour ce produit</p>
              <p style="margin-top: 10px; font-size: 22px; font-weight: 600;">
                Prix: <?= number_format($produit['prix'], 0, ',', ' ') ?> FCFA
              </p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>