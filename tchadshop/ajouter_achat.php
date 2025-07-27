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
                $check_produit = $conn->query("SELECT id, stock FROM produits WHERE id = $produit_id");
                if ($check_produit->num_rows > 0) {
                    $produit = $check_produit->fetch_assoc();
                    if ($produit['stock'] >= $quantite) {
                        $produits_valides = true;
                        $total_achat += $quantite * $prix;
                        $produits_data[] = [
                            'id' => $produit_id,
                            'quantite' => $quantite,
                            'prix' => $prix,
                            'stock' => $produit['stock']
                        ];
                    } else {
                        $message = sprintf($t['error_stock'], $produit_id, $produit['stock'], $quantite);
                        break;
                    }
                } else {
                    $message = sprintf($t['error_product_not_found'], $produit_id);
                    break;
                }
            } else {
                $message = sprintf($t['error_invalid_product'], $index, $produit_id, $quantite, $prix);
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
                $message = $t['success'] . number_format($total_achat, 2) . " " . $t['currency'];
                
                // Réinitialisation du formulaire après succès
                
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = "❌ " . $e->getMessage();
            }
        }
    }
}

$produits = $conn->query("SELECT id, nom, prix, stock FROM produits");
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8" />
    <title><?php echo $t['title']; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            margin: 0;
            padding: 0;
            direction: <?php echo ($current_lang == 'ar') ? 'ltr' : 'ltr'; ?>;
        }
        
      
        
        .content {
            max-width: 1200px;
            margin: 0px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
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
        
        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.15s;
        }
        
        input:focus, select:focus {
            outline: 0;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        .produit-line {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .btn11 {
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
            background-color: #6a1b9a;
            color: white;
        }
        
        .btn1-primary:hover {
            background-color: #581c87;
            transform: translateY(-2px);
        }
        
        .btn1-success {
            background-color: #28a745;
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
            background-color: #dc3545;
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
            color: #6a1b9a;
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
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
        <div class="content">
            <h2 style="text-align: center; color: #6a1b9a; margin-bottom: 25px;">
                <i class="bx bx-cart"></i> <?php echo $t['page_title']; ?>
            </h2>
            
            <div class="content1">
                <?php if ($message): ?>
                    <div id="message" class="<?= strpos($message, '✅') !== false ? 'success-message' : 'error-message' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="achatForm">
                    <input type="hidden" name="client_type" id="client_type_field" value="existing">
                    
                    <div class="form-section">
                        <h3 style="color: #d69e2e; margin-bottom: 20px;"><?php echo $t['client_type']; ?></h3>
                        
                        <div class="form-group" style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center;">
                                <input type="radio" name="client_type_ui" value="anonymous" onclick="setClientType('anonymous')" style="width: auto; margin-right: 5px;">
                                <?php echo $t['anonymous']; ?>
                            </label>
                            
                            <label style="display: flex; align-items: center;">
                                <input type="radio" name="client_type_ui" value="existing" checked onclick="setClientType('existing')" style="width: auto; margin-right: 5px;">
                                <?php echo $t['existing']; ?>
                            </label>
                            
                            <label style="display: flex; align-items: center;">
                                <input type="radio" name="client_type_ui" value="new" onclick="setClientType('new')" style="width: auto; margin-right: 5px;">
                                <?php echo $t['new']; ?>
                            </label>
                        </div>

                        <div id="existing_client">
                            <div class="form-group">
                                <label for="telephone_recherche"><?php echo $t['phone']; ?></label>
                                <input type="text" id="telephone_recherche" oninput="chercherClient()" placeholder="<?php echo $t['phone_placeholder']; ?>">
                                <div id="resultat_client" style="margin-top: 5px; padding: 8px; border-radius: 4px;"></div>
                                <input type="hidden" name="client_id" id="client_id">
                            </div>
                        </div>

                        <div id="new_client" style="display: none;">
                            <div class="form-group">
                                <label><?php echo $t['full_name']; ?></label>
                                <input type="text" name="nom_client">
                            </div>
                            
                            <div class="form-group">
                                <label><?php echo $t['phone']; ?></label>
                                <input type="text" name="telephone">
                            </div>
                            
                            <div class="form-group">
                                <label><?php echo $t['email']; ?></label>
                                <input type="email" name="email">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 style="color: #d69e2e; margin-bottom: 20px;"><?php echo $t['products']; ?></h3>
                        
                        <div id="produits">
                            <div class="produit-line">
                                <div>
                                    <label><?php echo $t['products']; ?> :</label>
                                    <select name="produit_id[]" onchange="updatePrix(this)" required>
                                        <option value=""><?php echo $t['select_product']; ?></option>
                                        <?php 
                                        $produits->data_seek(0); // Réinitialiser le pointeur de résultat
                                        while ($p = $produits->fetch_assoc()): ?>
                                            <option value="<?= $p['id'] ?>" data-prix="<?= $p['prix'] ?>">
                                                <?= htmlspecialchars($p['nom']) ?> (<?php echo $t['quantity']; ?>: <?= $p['stock'] ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label><?php echo $t['quantity']; ?> :</label>
                                    <input type="number" name="quantite[]" min="1" value="1" required onchange="updateMontantTotal()">
                                </div>
                                
                                <div>
                                    <label><?php echo $t['unit_price']; ?> :</label>
                                    <input type="number" name="prix[]" step="0.01" readonly required>
                                </div>
                                
                                <button type="button" class="btn11 btn1-danger" onclick="removeProduit(this)" style="display: none;">×</button>
                            </div>
                        </div>
                        
                        <button type="button" class="btn11 btn1-primary" onclick="addProduit()">
                            <?php echo $t['add_product']; ?>
                        </button>
                    </div>

                    <div class="total-container">
                        <div style="font-weight: bold; margin-bottom: 10px;"><?php echo $t['total_amount']; ?></div>
                        <div class="total-value" id="montant_total_text">0.00 <?php echo $t['currency']; ?></div>
                        <input type="hidden" id="montant_total" name="montant_total">
                    </div>
                    
                    <div class="button-container">
                        <button type="submit" class="btn11 btn1-success" name="submit_achat"><?php echo $t['save_purchase']; ?></button>
                    </div>
                </form>

                <a href="historique_achats.php" class="back-link"><?php echo $t['back_link']; ?></a>
            </div>
        </div>
    </div>

    <script>
    // Traductions JS
    const translations = {
        client_found: "<?php echo $t['client_found']; ?>",
        client_not_found: "<?php echo $t['client_not_found']; ?>",
        search_error: "<?php echo $t['search_error']; ?>",
        validation_select_client: "<?php echo $t['validation_select_client']; ?>",
        validation_add_product: "<?php echo $t['validation_add_product']; ?>",
        saving: "<?php echo $t['saving']; ?>",
        currency: "<?php echo $t['currency']; ?>"
    };

    function setClientType(type) {
        document.getElementById('client_type_field').value = type;
        toggleClientForm(type);
    }

    function toggleClientForm(type) {
        document.getElementById('existing_client').style.display = (type === 'existing') ? 'block' : 'none';
        document.getElementById('new_client').style.display = (type === 'new') ? 'block' : 'none';
        
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
                if (data.trouve) {
                    resultDiv.innerHTML = translations.client_found + data.nom;
                    resultDiv.style.backgroundColor = '#d4edda';
                    resultDiv.style.color = '#155724';
                    document.getElementById('client_id').value = data.id;
                } else {
                    resultDiv.innerHTML = translations.client_not_found;
                    resultDiv.style.backgroundColor = '#f8d7da';
                    resultDiv.style.color = '#721c24';
                    document.getElementById('client_id').value = "";
                }
            })
            .catch(error => {
                resultDiv.innerHTML = translations.search_error;
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
        
        newProduit.querySelector('select').selectedIndex = 0;
        newProduit.querySelector('input[name="quantite[]"]').value = 1;
        newProduit.querySelector('input[name="prix[]"]').value = '';
        newProduit.querySelector('.btn1-danger').style.display = 'block';
        
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
        const ligne = select.closest('.produit-line');
        ligne.querySelector('input[name="prix[]"]').value = prix;
        updateMontantTotal();
    }

    function updateMontantTotal() {
        let total = 0;
        document.querySelectorAll('.produit-line').forEach(ligne => {
            const quantite = parseFloat(ligne.querySelector('input[name="quantite[]"]').value) || 0;
            const prix = parseFloat(ligne.querySelector('input[name="prix[]"]').value) || 0;
            total += quantite * prix;
        });
        
        document.getElementById('montant_total_text').textContent = total.toFixed(2) + ' ' + translations.currency;
        document.getElementById('montant_total').value = total.toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', () => {
        setClientType('existing');
        updateMontantTotal();
        
        document.getElementById('produits').addEventListener('change', updateMontantTotal);
        document.getElementById('produits').addEventListener('input', updateMontantTotal);
        
        document.getElementById('achatForm').addEventListener('submit', function(e) {
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
            document.querySelectorAll('select[name="produit_id[]"]').forEach(select => {
                if (select.value) validProduit = true;
            });
            
            if (!validProduit) {
                alert(translations.validation_add_product);
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            } else {
                const submitbtn1 = document.querySelector('button[name="submit_achat"]');
                submitbtn1.disabled = true;
                submitbtn1.innerHTML = '<i class="bx1 bx-loader bx-spin"></i> ' + translations.saving;
            }
        });
    });
    </script>
</body>
</html>