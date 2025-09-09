<?php
session_start();
$searchContext = 'modifierp';

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Récupérer l'ID du produit
if (!isset($_GET['id'])) {
    die("ID du produit manquant.");
}
$produit_id = intval($_GET['id']);

// Récupérer les infos du produit avec les données de promotion
$sql = "SELECT * FROM produits WHERE id = $produit_id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    die("Produit introuvable.");
}
$produit = $result->fetch_assoc();

// Récupérer les images associées
$img_sql = "SELECT * FROM images_produit WHERE produit_id = $produit_id";
$img_result = $conn->query($img_sql);

// Mise à jour du produit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $conn->real_escape_string($_POST['nom']);
    $description = $conn->real_escape_string($_POST['description']);
    $prix = floatval($_POST['prix']);
    $stock = intval($_POST['stock']);
    $statut = $conn->real_escape_string($_POST['statut']);
    
    // Récupération des données de promotion
    $promotion = floatval($_POST['promotion']);
    $prix_promotion = !empty($_POST['prix_promotion']) ? floatval($_POST['prix_promotion']) : null;
    $date_debut_promo = !empty($_POST['date_debut_promo']) ? $conn->real_escape_string($_POST['date_debut_promo']) : null;
    $date_fin_promo = !empty($_POST['date_fin_promo']) ? $conn->real_escape_string($_POST['date_fin_promo']) : null;

    $update_sql = "UPDATE produits SET 
                    nom = '$nom', 
                    description = '$description', 
                    prix = $prix, 
                    stock = $stock, 
                    statut = '$statut',
                    promotion = $promotion,
                    prix_promotion = " . ($prix_promotion !== null ? $prix_promotion : 'NULL') . ",
                    date_debut_promo = " . ($date_debut_promo ? "'$date_debut_promo'" : 'NULL') . ",
                    date_fin_promo = " . ($date_fin_promo ? "'$date_fin_promo'" : 'NULL') . "
                  WHERE id = $produit_id";

    if ($conn->query($update_sql) === TRUE) {
        // Gestion des nouvelles images
        $upload_dir = "uploads/";
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if (!empty($_FILES['images']['name'][$key])) {
                $file_name = basename($_FILES['images']['name'][$key]);
                $target_file = $upload_dir . uniqid() . "_" . $file_name;

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $conn->query("INSERT INTO images_produit (produit_id, image) VALUES ($produit_id, '$target_file')");
                }
            }
        }
        $_SESSION['message'] = "<div class='alert success'>✅ Produit mis à jour avec succès !</div>";
        header("Location: modifier_produit.php?id=$produit_id");
        exit();
    } else {
        $_SESSION['message'] = "<div class='alert error'>❌ Erreur : " . $conn->error . "</div>";
        header("Location: modifier_produit.php?id=$produit_id");
        exit();
    }
}

// Suppression d'une image
if (isset($_GET['delete_image'])) {
    $image_id = intval($_GET['delete_image']);
    $img_row = $conn->query("SELECT image FROM images_produit WHERE id = $image_id")->fetch_assoc();
    if ($img_row) {
        unlink($img_row['image']); // Supprime le fichier image
        $conn->query("DELETE FROM images_produit WHERE id = $image_id");
        $_SESSION['message'] = "<div class='alert success'>🗑️ Image supprimée avec succès.</div>";
        header("Location: modifier_produit.php?id=$produit_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le produit</title>
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
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
            padding: 30px;
        }
        
        .alert {
            margin: 0 auto 30px;
            max-width: 800px;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-container1 {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.03);
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .form-label {
            font-weight: 600;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }
        
        textarea.form-input {
            min-height: 120px;
            resize: vertical;
        }
        
        .promo-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .file-upload {
            margin-top: 10px;
        }
        
        .file-upload input[type="file"] {
            display: none;
        }
        
        .file-label {
            display: inline-block;
            padding: 10px 20px;
            background: #4299e1;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .file-label:hover {
            background: #3182ce;
        }
        
        #nom-fichier {
            margin-left: 10px;
            color: #4a5568;
            font-style: italic;
        }
        
        .submit-btn {
            background: linear-gradient(120deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: block;
            width: 100%;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .current-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .image-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .image-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
        }
        
        .delete-image {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            text-align: center;
            padding: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .delete-image:hover {
            background: rgba(220, 38, 38, 1);
        }
        
        .promo-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #f56565;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .promo-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
            font-size: 14px;
            color: #4a5568;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .promo-row {
                grid-template-columns: 1fr;
            }
            
            .back-btn {
                position: relative;
                top: 0;
                right: 0;
                margin-top: 15px;
                width: 100%;
                justify-content: center;
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
        <i>✏️</i> Modifier le produit
      </h2>
      <a href="produits.php" class="back-btn">
        <i>←</i> Retour à la liste
      </a>
    </div>
    
    <div class="content">
      <!-- Affichage du message -->
      <?php if (!empty($message)) echo $message; ?>
      
      <div class="form-container1">
        <form action="modifier_produit.php?id=<?= $produit_id ?>" method="POST" enctype="multipart/form-data">
          <!-- Section Informations de base -->
          <div class="form-section">
            <div class="section-title">
              <i>📋</i> Informations de base
            </div>
            
            <div class="form-row">
              <label class="form-label">
                <i>🏷️</i> Nom du produit
              </label>
              <input type="text" name="nom" class="form-input" value="<?= htmlspecialchars($produit['nom']) ?>" required>
            </div>
            
            <div class="form-row">
              <label class="form-label">
                <i>📝</i> Description
              </label>
              <textarea name="description" class="form-input" required><?= htmlspecialchars($produit['description']) ?></textarea>
            </div>
            
            <div class="form-row">
              <label class="form-label">
                <i>💰</i> Prix (FCFA)
              </label>
              <input type="number" name="prix" class="form-input" step="0.01" value="<?= $produit['prix'] ?>" required>
            </div>
            
            <div class="form-row">
              <label class="form-label">
                <i>📦</i> Stock
              </label>
              <input type="number" name="stock" class="form-input" value="<?= $produit['stock'] ?>" required>
            </div>
            
            <div class="form-row">
              <label class="form-label">
                <i>📊</i> Statut
              </label>
              <select name="statut" class="form-input" required>
                <option value="disponible" <?= $produit['statut'] == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                <option value="rupture" <?= $produit['statut'] == 'rupture' ? 'selected' : '' ?>>Rupture</option>
                <option value="bientôt" <?= $produit['statut'] == 'bientôt' ? 'selected' : '' ?>>Bientôt disponible</option>
              </select>
            </div>
          </div>
          
          <!-- Section Promotion -->
          <div class="form-section">
            <div class="section-title">
              <i>🔥</i> Promotion
            </div>
            
            <div class="form-row">
              <label class="form-label">
                <i>📉</i> Pourcentage de réduction
              </label>
              <input type="number" name="promotion" class="form-input" step="0.01" min="0" max="100" 
                     value="<?= $produit['promotion'] ?>" placeholder="0.00">
            </div>
            
            <div class="form-row">
              <label class="form-label">
                <i>💰</i> Prix promotionnel (FCFA)
              </label>
              <input type="number" name="prix_promotion" class="form-input" step="0.01" 
                     value="<?= $produit['prix_promotion'] ?>" placeholder="Prix après réduction">
            </div>
            
            <div class="promo-row">
              <div class="form-row">
                <label class="form-label">
                  <i>⏱️</i> Date début
                </label>
                <input type="date" name="date_debut_promo" class="form-input" 
                       value="<?= $produit['date_debut_promo'] ?>">
              </div>
              
              <div class="form-row">
                <label class="form-label">
                  <i>⏳</i> Date fin
                </label>
                <input type="date" name="date_fin_promo" class="form-input" 
                       value="<?= $produit['date_fin_promo'] ?>">
              </div>
            </div>
          </div>
          
          <!-- Section Images -->
          <div class="form-section">
            <div class="section-title">
              <i>🖼️</i> Images du produit
            </div>
            
            <div class="form-row">
              <label class="form-label">
                <i>📸</i> Ajouter des images
              </label>
              <div class="file-upload">
                <label for="images" class="file-label">
                  <i>📁</i> Sélectionner des fichiers
                </label>
                <span id="nom-fichier">Aucun fichier sélectionné</span>
                <input type="file" name="images[]" id="images" accept="image/*" multiple>
              </div>
            </div>
            
            <div class="section-title" style="margin-top: 25px;">
              <i>🖼️</i> Images actuelles
            </div>
            
            <div class="current-images">
              <?php while ($img = $img_result->fetch_assoc()): ?>
                <div class="image-item">
                  <img src="<?= $img['image'] ?>" alt="Image produit">
                  <?php if ($produit['promotion'] > 0): ?>
                    <div class="promo-badge">-<?= $produit['promotion'] ?>%</div>
                  <?php endif; ?>
                  <a href="modifier_produit.php?id=<?= $produit_id ?>&delete_image=<?= $img['id'] ?>" 
                     onclick="return confirm('Supprimer cette image ?');" 
                     class="delete-image">
                    <i>🗑️</i> Supprimer
                  </a>
                </div>
              <?php endwhile; ?>
            </div>
          </div>
          
          <button type="submit" class="submit-btn">Mettre à jour le produit</button>
        </form>
      </div>
    </div>
  </div>
  
  <script>
    document.getElementById("images").addEventListener("change", function() {
        let fileName = "Aucun fichier sélectionné";
        if (this.files.length > 0) {
            fileName = this.files.length === 1 ? this.files[0].name : `${this.files.length} fichiers sélectionnés`;
        }
        document.getElementById("nom-fichier").textContent = fileName;
    });
    
    // Calcul automatique du prix promotionnel
    document.querySelector('input[name="promotion"]').addEventListener('input', function() {
        const promotion = parseFloat(this.value) || 0;
        const prix = parseFloat(document.querySelector('input[name="prix"]').value) || 0;
        
        if (promotion > 0 && prix > 0) {
            const prixPromo = prix - (prix * (promotion / 100));
            document.querySelector('input[name="prix_promotion"]').value = prixPromo.toFixed(2);
        } else {
            document.querySelector('input[name="prix_promotion"]').value = '';
        }
    });
    
    // Validation des dates de promotion
    document.querySelector('input[name="date_fin_promo"]').addEventListener('change', function() {
        const dateDebut = document.querySelector('input[name="date_debut_promo"]').value;
        const dateFin = this.value;
        
        if (dateDebut && dateFin && dateDebut > dateFin) {
            alert("La date de fin doit être postérieure à la date de début");
            this.value = '';
        }
    });
  </script>
</body>
</html>