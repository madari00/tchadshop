<?php
session_start();
$searchContext = 'achat';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID invalide.");
}

// Récupérer les informations de l'achat pour déterminer son type
$sql_achat = "SELECT * FROM historique_achats WHERE id = $id";
$result_achat = $conn->query($sql_achat);

if ($result_achat->num_rows === 0) {
    die("Achat non trouvé.");
}

$achat = $result_achat->fetch_assoc();
$is_commande = !empty($achat['commande_id']);

// Récupérer les produits disponibles
$produits = $conn->query("SELECT id, nom, prix, promotion, prix_promotion FROM produits WHERE statut = 'disponible'");

// --- Si formulaire soumis ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantite = intval($_POST['quantite']);
    $produit_id = intval($_POST['produit_id']);
    
    // Récupérer le prix du produit
    $res_prix = $conn->query("SELECT prix, promotion, prix_promotion FROM produits WHERE id = $produit_id");
    $produit_info = $res_prix->fetch_assoc();
    
    // Déterminer le prix à utiliser (prix normal ou prix promotionnel)
    $prix_unitaire = $produit_info['prix'];
    if (!empty($produit_info['promotion']) && $produit_info['promotion'] > 0) {
        $prix_unitaire = $produit_info['prix_promotion'] ?? $produit_info['prix'] * (1 - ($produit_info['promotion'] / 100));
    }
    
    if ($is_commande) {
        // Mise à jour d'une commande
        $statut = $conn->real_escape_string($_POST['statut']);
        
        // Mettre à jour le statut de la commande
        $conn->query("UPDATE commandes SET statut = '$statut' WHERE id = {$achat['commande_id']}");
        
        // Mettre à jour les détails de la commande
        $conn->query("UPDATE details_commandes 
                     SET produit_id = $produit_id, quantite = $quantite, prix_unitaire = $prix_unitaire 
                     WHERE commande_id = {$achat['commande_id']} AND produit_id = {$achat['produit_id']}");
        
        // Recalculer le total de la commande
        $sql_total = "SELECT SUM(quantite * prix_unitaire) as total 
                     FROM details_commandes 
                     WHERE commande_id = {$achat['commande_id']}";
        $result_total = $conn->query($sql_total);
        $total = $result_total->fetch_assoc()['total'];
        
        $conn->query("UPDATE commandes SET total = $total WHERE id = {$achat['commande_id']}");
    } else {
        // Mise à jour d'un achat direct
        $conn->query("UPDATE historique_achats 
                     SET produit_id = $produit_id, quantite = $quantite, prix_unitaire = $prix_unitaire 
                     WHERE id = $id");
    }
    
    header("Location: details_achat.php?id=$id");
    exit();
}

// Charger les données pour l'affichage
if ($is_commande) {
    $commande = $conn->query("SELECT * FROM commandes WHERE id = {$achat['commande_id']}")->fetch_assoc();
    $details = $conn->query("SELECT * FROM details_commandes WHERE commande_id = {$achat['commande_id']} AND produit_id = {$achat['produit_id']}")->fetch_assoc();
} else {
    $details = $achat;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier l'achat</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
           
            background-color: #f5f7fa;
        }
        
        .container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: #6a1b9a;
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        select, input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .btn1 {
            background: linear-gradient(135deg, #6a1b9a, #4a148c);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn1:hover {
            background: linear-gradient(135deg, #581c87, #3c0d70);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #6a1b9a;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .info-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #6a1b9a;
        }
    </style>
</head>
<body>
    <?php include("header.php") ; ?>
<div class="home-content">
    <div class="container">
        <h2><?= $is_commande ? "Modifier la commande" : "Modifier l'achat direct" ?> #<?= $id ?></h2>
        
        <div class="info-box">
            <strong>Type:</strong> <?= $is_commande ? "Commande" : "Achat direct" ?><br>
            <?php if ($is_commande): ?>
            <strong>Référence commande:</strong> <?= $commande['reference'] ?? 'N/A' ?><br>
            <strong>Statut actuel:</strong> <?= ucfirst($commande['statut'] ?? 'N/A') ?>
            <?php endif; ?>
        </div>

        <form method="POST">
            <?php if ($is_commande): ?>
            <div class="form-group">
                <label for="statut">Statut :</label>
                <select name="statut" id="statut" required>
                    <option value="en attente" <?= ($commande['statut'] ?? '') === 'en attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="en cours" <?= ($commande['statut'] ?? '') === 'en cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="livré" <?= ($commande['statut'] ?? '') === 'livré' ? 'selected' : '' ?>>Livré</option>
                    <option value="échec" <?= ($commande['statut'] ?? '') === 'échec' ? 'selected' : '' ?>>Échec</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="produit_id">Produit :</label>
                <select name="produit_id" id="produit_id" required>
                    <?php while ($p = $produits->fetch_assoc()): 
                        $prix_affichage = $p['prix'];
                        if (!empty($p['promotion']) && $p['promotion'] > 0) {
                            $prix_promo = $p['prix_promotion'] ?? $p['prix'] * (1 - ($p['promotion'] / 100));
                            $prix_affichage = number_format($prix_promo, 2) . " (Promo: -{$p['promotion']}%)";
                        } else {
                            $prix_affichage = number_format($p['prix'], 2);
                        }
                    ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $achat['produit_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nom']) ?> - <?= $prix_affichage ?> FCFA
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantite">Quantité :</label>
                <input type="number" name="quantite" id="quantite" value="<?= $details['quantite'] ?? 1 ?>" min="1" required>
            </div>

            <button type="submit" class="btn1">💾 Enregistrer les modifications</button>
        </form>

        <a href="details_achat.php?id=<?= $id ?>" class="back-link">⬅ Retour aux détails de l'achat</a>
    </div>
</body>
</html>