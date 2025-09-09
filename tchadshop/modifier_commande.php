<?php
session_start();
$searchContext = "";
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID de commande invalide !");
}

// Récupérer la commande
$res = $conn->query("SELECT * FROM commandes WHERE id = $id");
$commande = $res->fetch_assoc();
if (!$commande) {
    die("Commande non trouvée !");
}

// Récupérer les détails produits actuels
$details_res = $conn->query("SELECT * FROM details_commandes WHERE commande_id = $id");
$details = [];
while ($row = $details_res->fetch_assoc()) {
    $details[$row['produit_id']] = $row;
}

// Récupérer les produits pour sélection (avec informations de promotion)
$today = date('Y-m-d');
$produits_res = $conn->query("
    SELECT 
        id, 
        nom, 
        prix, 
        stock, 
        promotion, 
        prix_promotion,
        date_debut_promo,
        date_fin_promo,
        CASE 
            WHEN promotion > 0 AND date_debut_promo <= '$today' AND date_fin_promo >= '$today'
            THEN prix_promotion
            ELSE prix
        END AS prix_actuel
    FROM produits WHERE stock > 0
");

// Récupérer les clients pour dropdown
$clients_res = $conn->query("SELECT id, nom FROM clients");

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les anciens détails pour ajuster le stock
    $anciens_details_res = $conn->query("SELECT * FROM details_commandes WHERE commande_id = $id");
    $anciens_details = [];
    while ($row = $anciens_details_res->fetch_assoc()) {
        $anciens_details[$row['produit_id']] = $row['quantite'];
    }

    $client_id = intval($_POST['client_id']);
    $statut = $conn->real_escape_string($_POST['statut']);
    $total = 0;

    // Gestion adresse ou lat/lng - Correction ici
    $adresse = isset($_POST['adresse']) ? $conn->real_escape_string($_POST['adresse']) : '';
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : 'NULL';
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : 'NULL';

    $date_livraison_prevue = $_POST['date_livraison_prevue'] ?? null;
    if ($date_livraison_prevue) {
        $date_livraison_prevue = str_replace('T', ' ', $conn->real_escape_string($date_livraison_prevue)) . ':00';
    } else {
        $date_livraison_prevue = null;
    }

    $temps_livraison = max(0, intval($_POST['temps_livraison'] ?? 0));

    // Supprimer anciens détails
    $conn->query("DELETE FROM details_commandes WHERE commande_id = $id");

    // Remettre ancien stock
    foreach ($anciens_details as $pid => $old_qte) {
        $conn->query("UPDATE produits SET stock = stock + $old_qte WHERE id = $pid");
    }

    // Réinsérer nouveaux détails
    $produit_ids = $_POST['produit_id'] ?? [];
    $quantites = $_POST['quantite'] ?? [];
    $prix_unitaires = $_POST['prix'] ?? [];
    $promotions = $_POST['promotion'] ?? [];

    foreach ($produit_ids as $index => $pid) {
        $pid = intval($pid);
        $qte = intval($quantites[$index]);
        $prix = floatval($prix_unitaires[$index]);
        $promo = isset($promotions[$index]) ? 1 : 0;

        if ($pid > 0 && $qte > 0) {
            $conn->query("INSERT INTO details_commandes (commande_id, produit_id, quantite, prix_unitaire, promotion) 
                         VALUES ($id, $pid, $qte, $prix, $promo)");
            $conn->query("UPDATE produits SET stock = stock - $qte WHERE id = $pid");
            $total += $qte * $prix;
        }
    }

    // Mise à jour commande - CORRECTION PRINCIPALE ICI
    $update_sql = "UPDATE commandes SET 
        client_id = $client_id, 
        statut = '$statut', 
        total = $total, 
        temps_livraison = $temps_livraison, 
        date_livraison_prevue = " . ($date_livraison_prevue ? "'$date_livraison_prevue'" : "NULL") . ", 
        latitude = $latitude, 
        longitude = $longitude, 
        adresse = " . (!empty($adresse) ? "'$adresse'" : "NULL") . " 
        WHERE id = $id";

    if ($conn->query($update_sql)) {
        // ✅ Si le statut est "livré", enregistrer la date de livraison actuelle
        if (strtolower($statut) === 'livré') {
            $date_livraison = date('Y-m-d H:i:s');
            $conn->query("UPDATE commandes SET date_livraison='$date_livraison' WHERE id=$id");
        }
        $message = "✅ Commande mise à jour avec succès.";
    } else {
        $message = "❌ Erreur lors de la mise à jour: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Modifier Commande #<?= $id ?></title>
    <style>
        :root {
            --primary: #6a1b9a;
            --secondary: #d69e2e;
            --success: #38a169;
            --danger: #e53e3e;
            --info: #4299e1;
            --light: #f8f9fa;
            --dark: #2d3748;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .content {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        #message {
            margin: 15px 0;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.15s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: 0;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        .produit-line {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .btn1 {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .btn1-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn1-primary:hover {
            background-color: #581c87;
            transform: translateY(-2px);
        }
        
        .btn1-success {
            background-color: var(--success);
            color: white;
            padding: 12px 25px;
            font-size: 17px;
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
        }
        
        .btn1-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn1-danger {
            background-color: var(--danger);
            color: white;
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .total-container {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            margin-top: 20px;
        }
        
        .total-value {
            font-size: 24px;
            font-weight: bold;
            color: #212529;
        }
        
        .back-link {
            display: block;
            text-align: center;
            color: var(--primary);
            font-size: 16px;
            margin-top: 20px;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .button-container {
            position: relative;
            z-index: 10;
        }
        
        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: #edf2f7;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .radio-option:hover {
            background: #e2e8f0;
        }
        
        .radio-option input {
            margin-right: 8px;
        }
        
        .section-title {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .datetime-input {
            display: flex;
            gap: 10px;
        }
        
        .datetime-input input {
            flex: 1;
        }
        
        .promo-badge {
            background-color: var(--danger);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .promo-price {
            color: var(--danger);
            font-weight: bold;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .promo-checkbox {
            width: auto !important;
            margin-top: 10px;
        }
        
        .promo-label {
            display: flex;
            align-items: center;
            font-weight: normal;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .produit-line {
                grid-template-columns: 1fr;
            }
            
            .radio-group {
                flex-direction: column;
            }
            
            .datetime-input {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
        <div class="container">
        <div class="content">
            <h2 style="text-align: center; color: var(--primary); margin-bottom: 25px;">
                <i class="bx bx-task"></i> Modifier la commande #<?= $id ?>
            </h2>
            
            <div class="content1">
                <?php if ($message): ?>
                    <div id="message" class="<?= strpos($message, '✅') !== false ? 'success-message' : 'error-message' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="achatForm">
                    <div class="form-section">
                        <div class="section-title">Informations client</div>
                        
                        <div class="form-group">
                            <label for="client_id">Client :</label>
                            <select name="client_id" required>
                                <?php
                                $clients_res->data_seek(0);
                                while ($client = $clients_res->fetch_assoc()): ?>
                                    <option value="<?= $client['id'] ?>" <?= $commande['client_id'] == $client['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($client['nom']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Détails de la commande</div>
                        
                        <div class="form-group">
                            <label>Adresse de livraison :</label>
                            <textarea name="adresse" rows="2" placeholder="Quartier, ville, etc."><?= htmlspecialchars($commande['adresse'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Date prévue de livraison :</label>
                            <div class="datetime-input">
                                <input type="datetime-local" name="date_livraison_prevue" value="<?= $commande['date_livraison_prevue'] ? date('Y-m-d\TH:i', strtotime($commande['date_livraison_prevue'])) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Temps livraison (minutes) :</label>
                            <input type="number" name="temps_livraison" min="0" value="<?= intval($commande['temps_livraison']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Statut de la commande :</label>
                            <select name="statut" required>
                                <option value="en attente" <?= $commande['statut']=='en attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="en cours" <?= $commande['statut']=='en cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="livré" <?= $commande['statut']=='livré' ? 'selected' : '' ?>>Livré</option>
                                <option value="échec" <?= $commande['statut']=='échec' ? 'selected' : '' ?>>Échec</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Produits</div>

                        <div class="form-group">
                            <label for="search-produit">Rechercher un produit :</label>
                            <input type="text" id="search-produit" placeholder="Tapez le nom d'un produit..." oninput="filterProduits()">
                        </div>

                        <div id="produits">
                            <?php if(empty($details)): ?>
                                <div class="produit-line">
                                    <div>
                                        <label>Produit :</label>
                                        <select name="produit_id[]" class="produit-select" onchange="updatePrix(this)" required>
                                            <option value="">-- Sélectionner un produit --</option>
                                            <?php 
                                                $produits_res->data_seek(0);
                                                while ($p = $produits_res->fetch_assoc()): 
                                                    $en_promotion = ($p['promotion'] > 0 && $p['date_debut_promo'] <= $today && $p['date_fin_promo'] >= $today);
                                                    $prix_affichage = $en_promotion ? $p['prix_promotion'] : $p['prix'];
                                            ?>
                                                <option 
                                                    value="<?= $p['id'] ?>" 
                                                    data-prix="<?= $prix_affichage ?>" 
                                                    data-prix-original="<?= $p['prix'] ?>"
                                                    data-promotion="<?= $en_promotion ? 1 : 0 ?>"
                                                    data-prix-promo="<?= $p['prix_promotion'] ?>"
                                                    data-nom="<?= htmlspecialchars($p['nom']) ?>">
                                                    <?= htmlspecialchars($p['nom']) ?> (Stock: <?= $p['stock'] ?>)
                                                    <?php if ($en_promotion): ?>
                                                        <span class="promo-badge">PROMO -<?= $p['promotion'] ?>%</span>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label>Quantité :</label>
                                        <input type="number" name="quantite[]" min="1" value="1" required onchange="updateMontantTotal()">
                                    </div>
                                    
                                    <div>
                                        <label>Prix unitaire :</label>
                                        <input type="number" name="prix[]" step="0.01" readonly required>
                                        <div class="prix_original_display" style="font-size: 0.8em; color: #6c757d;"></div>
                                    </div>
                                    
                                    <div>
                                        <label class="promo-label">
                                            <input type="checkbox" name="promotion[]" class="promo-checkbox" value="1" onchange="togglePromoPrice(this)">
                                            Appliquer promotion
                                        </label>
                                    </div>
                                    
                                    <button type="button" class="btn1 btn1-danger" onclick="removeProduit(this)" style="display: none;">×</button>
                                </div>
                            <?php else:
                                foreach ($details as $prod_id => $det):
                                    $produits_res->data_seek(0);
                                    $prod_info = null;
                                    while ($p = $produits_res->fetch_assoc()) {
                                        if ($p['id'] == $prod_id) {
                                            $prod_info = $p;
                                            break;
                                        }
                                    }
                                    $en_promotion = ($prod_info['promotion'] > 0 && $prod_info['date_debut_promo'] <= $today && $prod_info['date_fin_promo'] >= $today);
                                    $prix_affichage = $en_promotion ? $prod_info['prix_promotion'] : $prod_info['prix'];
                            ?>
                                <div class="produit-line">
                                    <div>
                                        <label>Produit :</label>
                                        <select name="produit_id[]" class="produit-select" onchange="updatePrix(this)" required>
                                            <option value="">-- Sélectionner un produit --</option>
                                            <?php 
                                                $produits_res->data_seek(0);
                                                while ($p = $produits_res->fetch_assoc()): 
                                                    $option_en_promotion = ($p['promotion'] > 0 && $p['date_debut_promo'] <= $today && $p['date_fin_promo'] >= $today);
                                                    $option_prix_affichage = $option_en_promotion ? $p['prix_promotion'] : $p['prix'];
                                            ?>
                                                <option 
                                                    value="<?= $p['id'] ?>" 
                                                    data-prix="<?= $option_prix_affichage ?>" 
                                                    data-prix-original="<?= $p['prix'] ?>"
                                                    data-promotion="<?= $option_en_promotion ? 1 : 0 ?>"
                                                    data-prix-promo="<?= $p['prix_promotion'] ?>"
                                                    data-nom="<?= htmlspecialchars($p['nom']) ?>"
                                                    <?= $p['id'] == $prod_id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($p['nom']) ?> (Stock: <?= $p['stock'] ?>)
                                                    <?php if ($option_en_promotion): ?>
                                                        <span class="promo-badge">PROMO -<?= $p['promotion'] ?>%</span>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label>Quantité :</label>
                                        <input type="number" name="quantite[]" min="1" value="<?= $det['quantite'] ?>" required onchange="updateMontantTotal()">
                                    </div>
                                    
                                    <div>
                                        <label>Prix unitaire :</label>
                                        <input type="number" name="prix[]" step="0.01" value="<?= htmlspecialchars($det['prix_unitaire']) ?>" readonly required>
                                        <div class="prix_original_display" style="font-size: 0.8em; color: #6c757d;">
                                            <?php if ($en_promotion && $det['prix_unitaire'] != $prod_info['prix']): ?>
                                                <span class="original-price"><?= number_format($prod_info['prix'], 2) ?> FCFA</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="promo-label">
                                            <input type="checkbox" name="promotion[]" class="promo-checkbox" value="1" onchange="togglePromoPrice(this)" <?= $det['promotion'] ? 'checked' : '' ?>>
                                            Appliquer promotion
                                        </label>
                                    </div>
                                    
                                    <button type="button" class="btn1 btn1-danger" onclick="removeProduit(this)">×</button>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        
                        <button type="button" class="btn1 btn1-primary" onclick="addProduit()">
                            + Ajouter un autre produit
                        </button>
                    </div>

                    <div class="total-container">
                        <div style="font-weight: bold; margin-bottom: 10px;">Montant total :</div>
                        <div class="total-value" id="montant_total_text"><?= number_format($commande['total'], 2) ?> FCFA</div>
                        <input type="hidden" id="montant_total" name="montant_total" value="<?= $commande['total'] ?>">
                    </div>
                    
                    <div class="button-container">
                        <button type="submit" class="btn1 btn1-success" name="submit_commande">Enregistrer les modifications</button>
                    </div>
                </form>

                <a href="toutes_commandes.php" class="back-link">⬅ Retour aux commandes</a>
            </div>
        </div>
    </div>

    <script>
    // Fonction pour filtrer les produits en fonction de la saisie
    function filterProduits() {
        const searchTerm = document.getElementById('search-produit').value.toLowerCase();
        const selectElements = document.querySelectorAll('.produit-select');

        selectElements.forEach(select => {
            const options = select.options;
            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                const productName = option.dataset.nom ? option.dataset.nom.toLowerCase() : '';
                
                // Si le nom du produit contient le terme de recherche, ou s'il s'agit de l'option par défaut, l'afficher
                if (productName.includes(searchTerm) || option.value === "") {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        });
    }

    // Gérer la mise à jour du prix lorsqu'on coche/décoche la promotion
    function togglePromoPrice(checkbox) {
        const ligne = checkbox.closest('.produit-line');
        const select = ligne.querySelector('.produit-select');
        const selected = select.options[select.selectedIndex];
        
        if (selected.value) {
            const prixOriginal = selected.dataset.prixOriginal || 0;
            const prixPromo = selected.dataset.prixPromo || 0;
            const prixActuel = checkbox.checked ? prixPromo : prixOriginal;
            
            const prixInput = ligne.querySelector('input[name="prix[]"]');
            prixInput.value = prixActuel;
            
            // Mettre à jour l'affichage du prix original
            const prixOriginalDisplay = ligne.querySelector('.prix_original_display');
            if (checkbox.checked && prixPromo != prixOriginal) {
                prixOriginalDisplay.innerHTML = '<span class="original-price">' + parseFloat(prixOriginal).toFixed(2) + ' FCFA</span>';
            } else {
                prixOriginalDisplay.innerHTML = '';
            }
            
            updateMontantTotal();
        }
    }

    function addProduit() {
        const container = document.getElementById('produits');
        const firstProduit = container.children[0];
        const newProduit = firstProduit.cloneNode(true);
        
        // Réinitialiser les valeurs
        newProduit.querySelector('select').selectedIndex = 0;
        newProduit.querySelector('input[name="quantite[]"]').value = 1;
        newProduit.querySelector('input[name="prix[]"]').value = '';
        newProduit.querySelector('.btn1-danger').style.display = 'block';
        newProduit.querySelector('.prix_original_display').innerHTML = '';
        newProduit.querySelector('input[type="checkbox"]').checked = false;
        
        // Ajouter l'écouteur d'événement sur le nouveau checkbox
        newProduit.querySelector('.promo-checkbox').onchange = function() {
            togglePromoPrice(this);
        };

        container.appendChild(newProduit);
        updateMontantTotal();
    }

    function removeProduit(btn1) {
        const produitLine = btn1.closest('.produit-line');
        if (document.querySelectorAll('.produit-line').length > 1) {
            produitLine.remove();
            updateMontantTotal();
        }
    }

    function updatePrix(select) {
        const selected = select.options[select.selectedIndex];
        const prix = selected.dataset.prix || 0;
        const prixOriginal = selected.dataset.prixOriginal || 0;
        const enPromotion = selected.dataset.promotion == 1;
        const ligne = select.closest('.produit-line');
        
        ligne.querySelector('input[name="prix[]"]').value = prix;
        
        // Afficher le prix original si différent du prix actuel
        const prixOriginalDisplay = ligne.querySelector('.prix_original_display');
        if (enPromotion && prix != prixOriginal) {
            prixOriginalDisplay.innerHTML = '<span class="original-price">' + parseFloat(prixOriginal).toFixed(2) + ' FCFA</span>';
        } else {
            prixOriginalDisplay.innerHTML = '';
        }
        
        // Cocher automatiquement la case promotion si le produit est en promotion
        const checkbox = ligne.querySelector('input[type="checkbox"]');
        checkbox.checked = enPromotion;
        
        updateMontantTotal();
    }

    function updateMontantTotal() {
        let total = 0;
        document.querySelectorAll('.produit-line').forEach(ligne => {
            const quantite = parseFloat(ligne.querySelector('input[name="quantite[]"]').value) || 0;
            const prix = parseFloat(ligne.querySelector('input[name="prix[]"]').value) || 0;
            total += quantite * prix;
        });
        
        document.getElementById('montant_total_text').textContent = total.toFixed(2) + ' FCFA';
        document.getElementById('montant_total').value = total.toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Initialisation
        updateMontantTotal();
        
        // Activer les événements pour la mise à jour du montant total
        document.getElementById('produits').addEventListener('change', updateMontantTotal);
        document.getElementById('produits').addEventListener('input', updateMontantTotal);
        
        // Validation avant soumission
        document.getElementById('achatForm').addEventListener('submit', function(e) {
            // Validation des produits
            let validProduit = false;
            document.querySelectorAll('select[name="produit_id[]"]').forEach(select => {
                if (select.value) validProduit = true;
            });
            
            if (!validProduit) {
                alert("Veuillez ajouter au moins un produit!");
                e.preventDefault();
            } else {
                // Afficher un indicateur de chargement
                const submitbtn1 = document.querySelector('button[name="submit_commande"]');
                submitbtn1.disabled = true;
                submitbtn1.innerHTML = '<i class="bx bx-loader bx-spin"></i> Enregistrement...';
            }
        });
    });
    </script>
</body>
</html>