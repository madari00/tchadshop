<?php
session_start();
$searchContext = 'acommande';

ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_type = $_POST['client_type'] ?? '';
    $statut = $conn->real_escape_string($_POST['statut']);
    $adresse = $conn->real_escape_string($_POST['adresse'] ?? '');

    // Date prévue de livraison
    $date_livraison_prevue = $_POST['date_livraison_prevue'] ?? null;
    if ($date_livraison_prevue) {
        $date_livraison_prevue = str_replace('T', ' ', $conn->real_escape_string($date_livraison_prevue)) . ':00';
    } else {
        $date_livraison_prevue = null;
    }

    // Gérer le client
    $client_id = null;
    $client_error = false;
    
    if ($client_type === 'new') {
        $nom = $conn->real_escape_string($_POST['nom_client'] ?? '');
        $telephone = $conn->real_escape_string($_POST['telephone'] ?? '');
        $email = $conn->real_escape_string($_POST['email'] ?? '');
        
        // Vérifier si le client existe déjà
        $check_client = $conn->query("SELECT id FROM clients WHERE telephone = '$telephone'");
        if ($check_client->num_rows > 0) {
            $message = "Erreur : Un client avec ce numéro de téléphone existe déjà!";
            $client_error = true;
        } else {
            if ($conn->query("INSERT INTO clients (nom, telephone, email, invite, vu) VALUES ('$nom', '$telephone', '$email', 0, 1)")) {
                $client_id = $conn->insert_id;
            } else {
                $message = "Erreur lors de la création du client: " . $conn->error;
                $client_error = true;
            }
        }
    } 
    elseif ($client_type === 'existing') {
        $client_id = intval($_POST['client_id'] ?? 0);
        if ($client_id <= 0) {
            $message = "Erreur : Veuillez sélectionner un client existant!";
            $client_error = true;
        } else {
            // Vérifier que le client existe
            $check_client = $conn->query("SELECT id FROM clients WHERE id = $client_id");
            if ($check_client->num_rows === 0) {
                $message = "Erreur : Le client sélectionné n'existe pas!";
                $client_error = true;
            }
        }
    } else {
        $message = "Erreur : veuillez sélectionner un type de client.";
        $client_error = true;
    }

    if (!$client_error && empty($message)) {
        $date_commande = date('Y-m-d H:i:s');
        $total_commande = 0;
        $produits_valides = false;
        $produits_data = [];
        
        // Valider chaque produit
        foreach ($_POST['produit_id'] as $index => $produit_id) {
            $produit_id = intval($produit_id);
            $quantite = intval($_POST['quantite'][$index] ?? 0);
            $prix = floatval($_POST['prix'][$index] ?? 0);
            $promotion = isset($_POST['promotion'][$index]) ? 1 : 0; // Nouveau: état de promotion
            
            if ($produit_id > 0 && $quantite > 0 && $prix > 0) {
                // Vérifier que le produit existe
                $check_produit = $conn->query("SELECT id, stock FROM produits WHERE id = $produit_id");
                if ($check_produit->num_rows > 0) {
                    $produit = $check_produit->fetch_assoc();
                    if ($produit['stock'] >= $quantite) {
                        $produits_valides = true;
                        $total_commande += $quantite * $prix;
                        $produits_data[] = [
                            'id' => $produit_id,
                            'quantite' => $quantite,
                            'prix' => $prix,
                            'promotion' => $promotion, // Nouveau: état de promotion
                            'stock' => $produit['stock']
                        ];
                    } else {
                        $message = "Erreur : Stock insuffisant pour le produit ID $produit_id (Stock: {$produit['stock']}, Demande: $quantite)";
                        break;
                    }
                } else {
                    $message = "Erreur : Produit ID $produit_id introuvable!";
                    break;
                }
            }
        }
        
        if (!$produits_valides && empty($message)) {
            $message = "Erreur : Aucun produit valide n'a été ajouté!";
        }
        
        if (empty($message) && $produits_valides) {
            // Commencer la transaction
            $conn->begin_transaction();
            
            try {
                // Insérer la commande
                $insert_commande = "INSERT INTO commandes (client_id, date_commande, statut, adresse, date_livraison_prevue, vu, total) 
                                    VALUES ($client_id, '$date_commande', '$statut', " . ($adresse ? "'$adresse'" : "NULL") . ", " . ($date_livraison_prevue ? "'$date_livraison_prevue'" : "NULL") . ", 1, $total_commande)";
                
                if (!$conn->query($insert_commande)) {
                    throw new Exception("Erreur lors de l'insertion de la commande: " . $conn->error);
                }
                
                $commande_id = $conn->insert_id;
                
                // Insérer les détails de la commande et mettre à jour le stock
                foreach ($produits_data as $produit) {
                    // Nouveau: inclure l'état de promotion dans l'insertion
                    $insert_detail = "INSERT INTO details_commandes (commande_id, produit_id, quantite, prix_unitaire, promotion) 
                                      VALUES ($commande_id, {$produit['id']}, {$produit['quantite']}, {$produit['prix']}, {$produit['promotion']})";
                    
                    if (!$conn->query($insert_detail)) {
                        throw new Exception("Erreur lors de l'insertion du détail de commande: " . $conn->error);
                    }
                    
                    $update_stock = "UPDATE produits SET stock = stock - {$produit['quantite']} WHERE id = {$produit['id']}";
                    
                    if (!$conn->query($update_stock)) {
                        throw new Exception("Erreur lors de la mise à jour du stock: " . $conn->error);
                    }
                }
                
                $conn->commit();
                $message = "✅ Commande enregistrée avec succès ! Total : " . number_format($total_commande, 2) . " FCFA";
                
                // Réinitialisation du formulaire après succès
                echo '<script>
                    setTimeout(function() {
                        document.getElementById("achatForm").reset();
                        window.location.href = "toutes_commandes.php";
                    }, 3000);
                </script>';
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = "❌ Erreur : " . $e->getMessage();
            }
        }
    }
}

// Nouveau: récupérer les informations de promotion des produits
$produits = $conn->query("
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
            WHEN promotion > 0 AND date_debut_promo <= CURDATE() AND date_fin_promo >= CURDATE() THEN 1
            ELSE 0
        END AS est_en_promotion,
        CASE 
            WHEN promotion > 0 AND date_debut_promo <= CURDATE() AND date_fin_promo >= CURDATE() THEN prix_promotion
            ELSE prix
        END AS prix_actuel
    FROM produits
");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Ajouter une commande</title>
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
                <i class="bx bx-task"></i> Ajouter une commande
            </h2>
            
            <div class="content1">
                <?php if ($message): ?>
                    <div id="message" class="<?= strpos($message, '✅') !== false ? 'success-message' : 'error-message' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="achatForm">
                    <!-- Champ caché pour forcer la soumission des achats sans client -->
                    <input type="hidden" name="client_type" id="client_type_field" value="existing">
                    
                    <div class="form-section">
                        <div class="section-title">Type de client</div>
                        
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="client_type_ui" value="existing" checked onclick="setClientType('existing')">
                                Client existant
                            </label>
                            
                            <label class="radio-option">
                                <input type="radio" name="client_type_ui" value="new" onclick="setClientType('new')">
                                Nouveau client
                            </label>
                        </div>

                        <div id="existing_client">
                            <div class="form-group">
                                <label for="telephone_recherche">Numéro de téléphone :</label>
                                <input type="text" id="telephone_recherche" oninput="chercherClient()" placeholder="Entrer le numéro...">
                                <div id="resultat_client" style="margin-top: 5px; padding: 8px; border-radius: 4px;"></div>
                                <input type="hidden" name="client_id" id="client_id">
                            </div>
                        </div>

                        <div id="new_client" style="display: none;">
                            <div class="form-group">
                                <label>Nom complet :</label>
                                <input type="text" name="nom_client">
                            </div>
                            
                            <div class="form-group">
                                <label>Numéro de téléphone :</label>
                                <input type="text" name="telephone">
                            </div>
                            
                            <div class="form-group">
                                <label>Adresse email :</label>
                                <input type="email" name="email">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Détails de la commande</div>
                        
                        <div class="form-group">
                            <label>Adresse de livraison :</label>
                            <textarea name="adresse" rows="2" placeholder="Quartier, ville, etc."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Date prévue de livraison :</label>
                            <div class="datetime-input">
                                <input type="datetime-local" name="date_livraison_prevue">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Statut de la commande :</label>
                            <select name="statut" required>
                                <option value="en attente">En attente</option>
                                <option value="en cours">En cours</option>
                                <option value="échec">Échec</option>
                                <option value="livré">Livré</option>
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
                            <div class="produit-line">
                                <div>
                                    <label>Produit :</label>
                                    <select name="produit_id[]" class="produit-select" onchange="updatePrix(this)" required>
                                        <option value="">-- Sélectionner un produit --</option>
                                        <?php 
                                            // Réinitialiser le pointeur de la requête pour la boucle suivante
                                            if ($produits->num_rows > 0) {
                                                $produits->data_seek(0);
                                            }
                                            while ($p = $produits->fetch_assoc()): 
                                                $en_promotion = $p['est_en_promotion'] == 1;
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
                        </div>
                        
                        <button type="button" class="btn1 btn1-primary" onclick="addProduit()">
                            + Ajouter un autre produit
                        </button>
                    </div>


                    <div class="total-container">
                        <div style="font-weight: bold; margin-bottom: 10px;">Montant total :</div>
                        <div class="total-value" id="montant_total_text">0.00 FCFA</div>
                        <input type="hidden" id="montant_total" name="montant_total">
                    </div>
                    
                    <div class="button-container">
                        <button type="submit" class="btn1 btn1-success" name="submit_commande">Enregistrer la commande</button>
                    </div>
                </form>

                <a href="toutes_commandes.php" class="back-link">⬅ Retour aux commandes</a>
            </div>
        </div>
    </div>

    <script>
    // Fonction pour définir le type de client
    function setClientType(type) {
        document.getElementById('client_type_field').value = type;
        toggleClientForm(type);
    }

    function toggleClientForm(type) {
        document.getElementById('existing_client').style.display = (type === 'existing') ? 'block' : 'none';
        document.getElementById('new_client').style.display = (type === 'new') ? 'block' : 'none';
        
        // Réinitialiser les champs lorsqu'on change de type
        if (type === 'existing') {
            document.getElementById('telephone_recherche').value = '';
            document.getElementById('resultat_client').innerHTML = '';
            document.getElementById('client_id').value = '';
        }
    }

    function chercherClient() {
        const telephone = document.getElementById('telephone_recherche').value;
        const resultDiv = document.getElementById('resultat_client');
        
        if (telephone.length >= 6) {
            fetch('chercher_client.php?telephone=' + encodeURIComponent(telephone))
            .then(response => {
                if (!response.ok) throw new Error('Erreur réseau');
                return response.json();
            })
            .then(data => {
                if (data.trouve) {
                    resultDiv.innerHTML = "✅ Client trouvé : " + data.nom;
                    resultDiv.style.backgroundColor = '#d4edda';
                    resultDiv.style.color = '#155724';
                    document.getElementById('client_id').value = data.id;
                } else {
                    resultDiv.innerHTML = "❌ Aucun client trouvé avec ce numéro";
                    resultDiv.style.backgroundColor = '#f8d7da';
                    resultDiv.style.color = '#721c24';
                    document.getElementById('client_id').value = "";
                }
            })
            .catch(error => {
                resultDiv.innerHTML = "❌ Erreur lors de la recherche";
                resultDiv.style.backgroundColor = '#f8d7da';
                    resultDiv.style.color = '#721c24';
                console.error('Erreur:', error);
            });
        } else {
            resultDiv.innerHTML = "";
            resultDiv.style.backgroundColor = '';
            resultDiv.style.color = '';
            document.getElementById('client_id').value = "";
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
        newProduit.querySelector('#prix_original_display').innerHTML = '';
        newProduit.querySelector('input[type="checkbox"]').checked = false;
        
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
        const prixOriginalDisplay = ligne.querySelector('#prix_original_display');
        if (enPromotion && prix != prixOriginal) {
            prixOriginalDisplay.innerHTML = '<span class="original-price">' + prixOriginal + ' FCFA</span>';
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

    // Gérer les changements sur les cases à cocher de promotion
    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('.promo-checkbox')) {
            const ligne = e.target.closest('.produit-line');
            const select = ligne.querySelector('select');
            const selected = select.options[select.selectedIndex];
            
            if (selected.value) {
                const prixOriginal = selected.dataset.prixOriginal || 0;
                const prixPromo = selected.dataset.prixPromo || 0;
                const prixActuel = e.target.checked ? prixPromo : prixOriginal;
                
                ligne.querySelector('input[name="prix[]"]').value = prixActuel;
                
                // Mettre à jour l'affichage du prix original
                const prixOriginalDisplay = ligne.querySelector('#prix_original_display');
                if (e.target.checked && prixPromo != prixOriginal) {
                    prixOriginalDisplay.innerHTML = '<span class="original-price">' + prixOriginal + ' FCFA</span>';
                } else {
                    prixOriginalDisplay.innerHTML = '';
                }
                
                updateMontantTotal();
            }
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        // Initialisation
        setClientType('existing');
        updateMontantTotal();
        
        // Activer les événements pour la mise à jour du montant total
        document.getElementById('produits').addEventListener('change', updateMontantTotal);
        document.getElementById('produits').addEventListener('input', updateMontantTotal);
        
        // Validation avant soumission
        document.getElementById('achatForm').addEventListener('submit', function(e) {
            let isValid = true;
            const clientType = document.getElementById('client_type_field').value;
            
            // Validation client existant
            if (clientType === 'existing') {
                const clientId = document.getElementById('client_id').value;
                if (!clientId || clientId <= 0) {
                    alert("Veuillez sélectionner un client existant!");
                    isValid = false;
                }
            }
            
            // Validation des produits
            let validProduit = false;
            document.querySelectorAll('select[name="produit_id[]"]').forEach(select => {
                if (select.value) validProduit = true;
            });
            
            if (!validProduit) {
                alert("Veuillez ajouter au moins un produit!");
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            } else {
                // Afficher un indicateur de chargement
                const submitbtn1 = document.querySelector('button[name="submit_commande"]');
                submitbtn1.disabled = true;
                submitbtn1.innerHTML = '<i class="bx bx-loader bx-spin"></i> Enregistrement...';
            }
        });
    });
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


// Mettre à jour la fonction `addProduit`
function addProduit() {
    const container = document.getElementById('produits');
    const firstProduit = container.children[0];
    const newProduit = firstProduit.cloneNode(true);
    
    // Réinitialiser les valeurs
    newProduit.querySelector('select').selectedIndex = 0;
    newProduit.querySelector('input[name="quantite[]"]').value = 1;
    newProduit.querySelector('input[name="prix[]"]').value = '';
    newProduit.querySelector('.btn1-danger').style.display = 'block';
    newProduit.querySelector('.prix_original_display').innerHTML = ''; // Utilisez la classe
    newProduit.querySelector('input[type="checkbox"]').checked = false;
    
    // Ajouter l'écouteur d'événement sur le nouveau checkbox
    newProduit.querySelector('.promo-checkbox').onchange = function() {
        togglePromoPrice(this);
    };

    container.appendChild(newProduit);
    updateMontantTotal();
}

// Mettre à jour la fonction `updatePrix`
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
    </script>
</body>
</html>