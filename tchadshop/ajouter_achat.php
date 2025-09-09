<?php
session_start();
$searchContext = 'ajout';
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Charger la configuration
$config = [];
$result = $conn->query("SELECT * FROM configuration");
while ($row = $result->fetch_assoc()) {
    $config[$row['parametre']] = $row['valeur'];
}

// Gérer le changement de langue
$current_lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : $config['default_language']);
$_SESSION['lang'] = $current_lang;

// Inclure les traductions
$translations = include 'traductions.php';
$t = $translations[$current_lang];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_type = $_POST['client_type'] ?? 'anonymous';
    
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
            $message = $t['error_existing_phone'];
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
            $message = $t['error_select_client'];
            $client_error = true;
        } else {
            // Vérifier que le client existe
            $check_client = $conn->query("SELECT id FROM clients WHERE id = $client_id");
            if ($check_client->num_rows === 0) {
                $message = $t['error_client_not_exist'];
                $client_error = true;
            }
        }
    }

    // Vérifier les produits seulement si pas d'erreur avec le client
    if (!$client_error && empty($message)) {
        $total_achat = 0;
        $produits_valides = false;
        $produits_data = [];
        
        // Valider chaque produit
        foreach ($_POST['produit_id'] as $index => $produit_id) {
            $produit_id = intval($produit_id);
            $quantite = intval($_POST['quantite'][$index] ?? 0);
            $prix = floatval($_POST['prix'][$index] ?? 0);
            
            if ($produit_id > 0 && $quantite > 0 && $prix > 0) {
                // Vérifier que le produit existe
                $check_produit = $conn->query("SELECT id, stock, nom FROM produits WHERE id = $produit_id");
                if ($check_produit->num_rows > 0) {
                    $produit = $check_produit->fetch_assoc();
                    if ($produit['stock'] >= $quantite) {
                        $produits_valides = true;
                        $total_achat += $quantite * $prix;
                        $produits_data[] = [
                            'id' => $produit_id,
                            'quantite' => $quantite,
                            'prix' => $prix,
                            'stock' => $produit['stock'],
                            'nom' => $produit['nom']
                        ];
                    } else {
                        $message = sprintf($t['error_stock'], htmlspecialchars($produit['nom']), $produit['stock'], $quantite);
                        break;
                    }
                } else {
                    $message = sprintf($t['error_product_not_found'], $produit_id);
                    break;
                }
            } else {
                // Si la ligne n'est pas remplie, on l'ignore (utile pour la première ligne dupliquée)
            }
        }
        
        if (!$produits_valides && empty($message)) {
            $message = $t['error_no_valid_products'];
        }
        
        if (empty($message) && $produits_valides) {
            $date_achat = date('Y-m-d H:i:s');
            $client_sql = is_null($client_id) ? "NULL" : $client_id;
            
            // Commencer la transaction
            $conn->begin_transaction();
            
            try {
                foreach ($produits_data as $produit) {
                    // Insérer dans historique_achats
                    $insert_query = "INSERT INTO historique_achats (produit_id, commande_id, client_id, quantite, prix_unitaire, date_achat) 
                                     VALUES ({$produit['id']}, NULL, $client_sql, {$produit['quantite']}, {$produit['prix']}, '$date_achat')";
                    
                    if (!$conn->query($insert_query)) {
                        throw new Exception($t['error_insert'] . $conn->error);
                    }
                    
                    // Mettre à jour le stock
                    $update_query = "UPDATE produits SET stock = stock - {$produit['quantite']} WHERE id = {$produit['id']}";
                    
                    if (!$conn->query($update_query)) {
                        throw new Exception($t['error_update'] . $conn->error);
                    }
                }
                
                $conn->commit();
                $message = "✅ " . $t['success'] . number_format($total_achat, 2) . " " . $t['currency'];
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = "❌ " . $e->getMessage();
            }
        }
    }
}

// Récupérer les produits avec leurs promotions
$produits = $conn->query("
    SELECT p.id, p.nom, p.prix, p.stock, 
           p.promotion, p.prix_promotion, 
           p.date_debut_promo, p.date_fin_promo
    FROM produits p
");
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8" />
    <title><?php echo $t['title']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Styles existants... */
        :root {
            --primary-color: #6a1b9a;
            --secondary-color: #9c27b0;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ff9800;
            --info-color: #2196f3;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .content-wrapper {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .page-title {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            padding-bottom: 15px;
            margin-top: -30px;
            border-bottom: 2px solid #eaeaea;
            position: relative;
        
        }
        
        .page-title i {
            background-color: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(120deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }
        
        .card-header i {
            margin-right: 10px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--info-color);
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.25);
        }
        
        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .radio-option input {
            width: auto;
            margin-right: 8px;
        }
        
        .produit-line {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            position: relative;
            border: 1px solid #e9ecef;
        }
        
        .btn1 {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }
        
        .btn1-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn1-primary:hover {
            background-color: #581c87;
            transform: translateY(-2px);
        }
        
        .btn1-success {
            background-color: var(--success-color);
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
            background-color: var(--danger-color);
            color: white;
            padding: 8px 15px;
            font-size: 14px;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn1-danger:hover {
            background-color: #bd2130;
        }
        
        .total-container {
            background: linear-gradient(120deg, #bcdfd7ff 0%, #a2a5a3ff 100%);
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            margin-top: 20px;
            color: white;
        }
        
        .total-label {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .total-value {
            font-size: 28px;
            font-weight: bold;
        }
        
        .back-link {
            display: block;
            text-align: center;
            color: var(--primary-color);
            font-size: 16px;
            margin-top: 20px;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .message {
            margin: 15px 0;
            padding: 15px;
            border-radius: var(--border-radius);
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
        
        .promo-badge {
            background-color: var(--warning-color);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .price-display {
            display: flex;
            flex-direction: column;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 14px;
        }
        
        .promo-price {
            color: var(--warning-color);
            font-weight: 600;
            font-size: 16px;
        }
        
        .client-result {
            margin-top: 5px;
            padding: 10px;
            border-radius: var(--border-radius);
            background-color: #e9ecef;
            display: none;
        }
        
        .client-found {
            background-color: #d4edda;
            color: #155724;
        }
        
        .client-not-found {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .produit-line {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Ajouts pour l'amélioration de la sélection */
        .search-container {
            margin-bottom: 15px;
            position: relative;
        }
        
        .search-container i {
            position: absolute;
            left: 12px;
            top: 12px;
            color: #6c757d;
        }
        
        .product-search {
            padding-left: 35px;
            width: 100%;
            border-radius: var(--border-radius);
            border: 1px solid #ced4da;
            height: 38px;
        }
        
        .product-count {
            font-size: 14px;
            color: #6c757d;
            margin-top: 8px;
        }
        
        /* Styles pour Select2 */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
        <div class="container">
            <div class="content-wrapper">
                <h1 class="page-title">
                    <i class="fas fa-cart-plus"></i> <?php echo $t['page_title']; ?>
                </h1>
                
                <div class="card">
                    <?php if ($message): ?>
                        <div class="message <?= strpos($message, '✅') !== false ? 'success-message' : 'error-message' ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="achatForm">
                        <input type="hidden" name="client_type" id="client_type_field" value="existing">
                        
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-user"></i> <?php echo $t['client_type']; ?>
                            </div>
                            <div class="card-body">
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="client_type_ui" value="anonymous" onclick="setClientType('anonymous')">
                                        <?php echo $t['anonymous']; ?>
                                    </label>
                                    
                                    <label class="radio-option">
                                        <input type="radio" name="client_type_ui" value="existing" checked onclick="setClientType('existing')">
                                        <?php echo $t['existing']; ?>
                                    </label>
                                    
                                    <label class="radio-option">
                                        <input type="radio" name="client_type_ui" value="new" onclick="setClientType('new')">
                                        <?php echo $t['new']; ?>
                                    </label>
                                </div>

                                <div id="existing_client">
                                    <div class="form-group">
                                        <label for="telephone_recherche"><?php echo $t['phone']; ?></label>
                                        <input type="text" id="telephone_recherche" class="form-control" oninput="chercherClient()" placeholder="<?php echo $t['phone_placeholder']; ?>">
                                        <div id="resultat_client" class="client-result"></div>
                                        <input type="hidden" name="client_id" id="client_id">
                                    </div>
                                </div>

                                <div id="new_client" class="hidden">
                                    <div class="form-group">
                                        <label><?php echo $t['full_name']; ?></label>
                                        <input type="text" name="nom_client" class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><?php echo $t['phone']; ?></label>
                                        <input type="text" name="telephone" class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><?php echo $t['email']; ?></label>
                                        <input type="email" name="email" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-boxes"></i> <?php echo $t['products']; ?>
                            </div>
                            <div class="card-body">
                                <div class="search-container">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="productSearch" class="product-search" placeholder="<?php echo $t['search_product_placeholder'] ?? 'Rechercher un produit...'; ?>">
                                </div>
                                
                                <div class="product-count" id="productCount"></div>
                                
                                <div id="produits">
                                    <div class="produit-line">
                                        <div class="form-group">
                                            <label><?php echo $t['products']; ?></label>
                                            <select name="produit_id[]" class="form-control product-select" onchange="updatePrix(this)" required>
                                                <option value=""><?php echo $t['select_product']; ?></option>
                                                <?php 
                                                $produits->data_seek(0);
                                                while ($p = $produits->fetch_assoc()): 
                                                    $today = date('Y-m-d');
                                                    $promo_active = false;
                                                    $promo_price = $p['prix'];
                                                    
                                                    if ($p['promotion'] > 0 && $p['date_debut_promo'] && $p['date_fin_promo']) {
                                                        if ($today >= $p['date_debut_promo'] && $today <= $p['date_fin_promo']) {
                                                            $promo_active = true;
                                                            $promo_price = $p['prix_promotion'];
                                                        }
                                                    }
                                                ?>
                                                    <option value="<?= $p['id'] ?>" 
                                                            data-prix="<?= $p['prix'] ?>" 
                                                            data-promo="<?= $promo_active ? '1' : '0' ?>"
                                                            data-prix-promo="<?= $promo_price ?>">
                                                        <?= htmlspecialchars($p['nom']) ?> 
                                                        (<?php echo $t['quantity']; ?>: <?= $p['stock'] ?>)
                                                        <?php if ($promo_active): ?>
                                                            <span class="promo-badge">PROMO</span>
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label><?php echo $t['quantity']; ?></label>
                                            <input type="number" name="quantite[]" class="form-control quantite-input" min="1" value="1" required onchange="updateMontantTotal()">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label><?php echo $t['unit_price']; ?></label>
                                            <div class="price-display">
                                                <input type="number" name="prix[]" class="form-control prix-input" step="0.01" readonly required>
                                                <span class="original-price" style="display: none;"></span>
                                            </div>
                                        </div>
                                        
                                        <button type="button" class="btn1 btn1-danger" onclick="removeProduit(this)" style="display: none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn1 btn1-primary" onclick="addProduit()">
                                    <i class="fas fa-plus"></i> <?php echo $t['add_product']; ?>
                                </button>
                            </div>
                        </div>

                        <div class="total-container">
                            <div class="total-label"><?php echo $t['total_amount']; ?></div>
                            <div class="total-value" id="montant_total_text">0.00 <?php echo $t['currency']; ?></div>
                            <input type="hidden" id="montant_total" name="montant_total">
                        </div>
                        
                        <button type="submit" class="btn1 btn1-success" name="submit_achat">
                            <i class="fas fa-save"></i> <?php echo $t['save_purchase']; ?>
                        </button>
                    </form>

                    <a href="historique_achats.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> <?php echo $t['back_link']; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    const translations = {
        client_found: "<?php echo $t['client_found']; ?>",
        client_not_found: "<?php echo $t['client_not_found']; ?>",
        search_error: "<?php echo $t['search_error']; ?>",
        validation_select_client: "<?php echo $t['validation_select_client']; ?>",
        validation_add_product: "<?php echo $t['validation_add_product']; ?>",
        saving: "<?php echo $t['saving']; ?>",
        currency: "<?php echo $t['currency']; ?>",
        select_product: "<?php echo $t['select_product']; ?>",
        no_products_found: "<?php echo $t['no_products_found'] ?? 'Aucun produit trouvé'; ?>",
    };

    function setClientType(type) {
        document.getElementById('client_type_field').value = type;
        toggleClientForm(type);
    }

    function toggleClientForm(type) {
        document.getElementById('existing_client').style.display = (type === 'existing') ? 'block' : 'none';
        document.getElementById('new_client').classList.toggle('hidden', type !== 'new');
        
        if (type === 'anonymous') {
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
                resultDiv.style.display = 'block';
                if (data.trouve) {
                    resultDiv.innerHTML = translations.client_found + data.nom;
                    resultDiv.className = 'client-result client-found';
                    document.getElementById('client_id').value = data.id;
                } else {
                    resultDiv.innerHTML = translations.client_not_found;
                    resultDiv.className = 'client-result client-not_found';
                    document.getElementById('client_id').value = "";
                }
            })
            .catch(error => {
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = translations.search_error;
                resultDiv.className = 'client-result client-not_found';
                console.error('Erreur:', error);
            });
        } else {
            resultDiv.style.display = 'none';
            resultDiv.innerHTML = "";
            document.getElementById('client_id').value = "";
        }
    }

    // Fonction de mise à jour du prix et du total
    function updatePrix(select) {
        const selectedOption = select.options[select.selectedIndex];
        const quantiteInput = select.closest('.produit-line').querySelector('.quantite-input');
        const prixInput = select.closest('.produit-line').querySelector('.prix-input');
        const originalPriceSpan = select.closest('.produit-line').querySelector('.original-price');
        
        if (selectedOption.value) {
            let prix = parseFloat(selectedOption.dataset.prix);
            const promoActive = selectedOption.dataset.promo === '1';
            
            if (promoActive) {
                const prixPromo = parseFloat(selectedOption.dataset.prixPromo);
                prixInput.value = prixPromo.toFixed(2);
                originalPriceSpan.textContent = prix.toFixed(2) + ' ' + translations.currency;
                originalPriceSpan.style.display = 'block';
            } else {
                prixInput.value = prix.toFixed(2);
                originalPriceSpan.style.display = 'none';
            }
            
            // Mettre le stock max de l'input quantité
            const stock = parseInt(selectedOption.text.match(/\d+/)[0]);
            quantiteInput.max = stock;
            if (parseInt(quantiteInput.value) > stock) {
                quantiteInput.value = stock;
            }
        } else {
            prixInput.value = '';
            originalPriceSpan.style.display = 'none';
        }
        updateMontantTotal();
    }
    
    // Fonction d'ajout de produit
    function addProduit() {
        const container = document.getElementById('produits');
        const firstProduit = container.children[0];
        const newProduit = firstProduit.cloneNode(true);
        
        // Réinitialiser les valeurs et détruire Select2
        const select = newProduit.querySelector('.product-select');
        $(select).select2('destroy');
        newProduit.querySelector('input[name="quantite[]"]').value = 1;
        newProduit.querySelector('input[name="prix[]"]').value = '';
        newProduit.querySelector('.btn1-danger').style.display = 'flex';
        newProduit.querySelector('.original-price').style.display = 'none';
        
        // Insérer la nouvelle ligne
        container.appendChild(newProduit);
        
        // Réinitialiser Select2 sur le nouveau select
        $(select).select2({
            placeholder: translations.select_product,
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() { return translations.no_products_found; }
            }
        });
        updateMontantTotal();
    }

    function removeProduit(btn1) {
        const produitLine = btn1.closest('.produit-line');
        if (document.querySelectorAll('.produit-line').length > 1) {
            $(produitLine.querySelector('.product-select')).select2('destroy');
            produitLine.remove();
            updateMontantTotal();
        }
    }

    function updateMontantTotal() {
        let total = 0;
        document.querySelectorAll('.produit-line').forEach(ligne => {
            const quantite = parseFloat(ligne.querySelector('.quantite-input').value) || 0;
            const prix = parseFloat(ligne.querySelector('.prix-input').value) || 0;
            total += quantite * prix;
        });
        
        document.getElementById('montant_total_text').textContent = total.toFixed(2) + ' ' + translations.currency;
        document.getElementById('montant_total').value = total.toFixed(2);
    }
    
    // Fonction de recherche de produits pour Select2
    function filterProducts(event) {
        const searchText = event.target.value.toLowerCase();
        
        // Cible la première liste déroulante
        const selectElement = document.querySelector('.product-select');
        const $select2 = $(selectElement).data('select2');
        
        if ($select2) {
            $select2.dataAdapter.query({
                term: searchText,
                callback: function (data) {
                    const filteredResults = data.results.filter(item => item.text.toLowerCase().includes(searchText));
                    // Mise à jour de la liste de Select2 avec les résultats filtrés
                    $select2.results.show({ results: filteredResults });
                }
            });
        }
        
        // Mettre à jour le compteur
        updateProductCount(searchText);
    }
    
    // Mettre à jour le compteur de produits visibles
    function updateProductCount(searchText) {
        let count = 0;
        document.querySelectorAll('.product-select option').forEach(option => {
            if (option.value === '') return;
            if (option.text.toLowerCase().includes(searchText.toLowerCase())) {
                count++;
            }
        });
        document.getElementById('productCount').textContent = `${count} produits disponibles`;
    }

    // Écouter la recherche
    document.getElementById('productSearch').addEventListener('input', (event) => {
        // Déclenche une recherche Select2 avec le terme
        $('.product-select').select2('open');
        const term = event.target.value;
        $('.select2-search__field').val(term).trigger('input');
    });

    // Initialisation de Select2 et des listeners
    document.addEventListener('DOMContentLoaded', () => {
        setClientType('existing');
        updateMontantTotal();
        
        document.getElementById('produits').addEventListener('change', (event) => {
            if (event.target.classList.contains('quantite-input')) {
                updateMontantTotal();
            }
        });
        
        $('#achatForm').on('submit', function(e) {
            let isValid = true;
            const clientType = document.getElementById('client_type_field').value;
            
            if (clientType === 'existing') {
                const clientId = document.getElementById('client_id').value;
                if (!clientId || clientId <= 0) {
                    alert(translations.validation_select_client);
                    isValid = false;
                }
            }
            
            let validProduit = false;
            document.querySelectorAll('.produit-line').forEach(line => {
                if (line.querySelector('.product-select').value) validProduit = true;
            });
            
            if (!validProduit) {
                alert(translations.validation_add_product);
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            } else {
                const submitBtn = document.querySelector('button[name="submit_achat"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + translations.saving;
            }
        });
        
        // Initialisation de Select2 avec la logique de recherche personnalisée
        $('.product-select').select2({
            placeholder: translations.select_product,
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() { return translations.no_products_found; }
            },
            
            // Cette partie gère la recherche interne
            templateResult: function (data) {
                // Fonction de rendu pour les résultats de la recherche (facultatif)
                return data.text;
            },
            matcher: function(params, data) {
                // Si pas de terme de recherche, on affiche tout
                if ($.trim(params.term) === '') {
                    return data;
                }
                
                // On s'assure que data.text existe
                if (typeof data.text === 'undefined') {
                    return null;
                }
                
                // Recherche insensible à la casse
                const text = data.text.toLowerCase();
                const term = params.term.toLowerCase();
                
                // Vérifie si le nom du produit contient le terme de recherche
                if (text.indexOf(term) > -1) {
                    return data;
                }
                return null;
            }
        });
        
        // Masquer la barre de recherche par défaut de Select2
        $('.select2-search--dropdown').hide();
        
        // Mettre à jour le compteur au chargement initial
        updateProductCount('');
    });
    </script>
</body>
</html>