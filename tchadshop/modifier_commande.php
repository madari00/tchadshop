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

// Récupérer les produits pour sélection
$produits_res = $conn->query("SELECT id, nom, prix, stock FROM produits");

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

    // Gestion adresse ou lat/lng
    $adresse = $conn->real_escape_string($_POST['adresse'] ?? '');
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;

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

    foreach ($produit_ids as $index => $pid) {
        $pid = intval($pid);
        $qte = intval($quantites[$index]);
        $prix = floatval($prix_unitaires[$index]);

        if ($pid > 0 && $qte > 0) {
            $conn->query("INSERT INTO details_commandes (commande_id, produit_id, quantite) VALUES ($id, $pid, $qte)");
            $conn->query("UPDATE produits SET stock = stock - $qte WHERE id = $pid");
            $total += $qte * $prix;
        }
    }

    // Mise à jour commande
    $update_sql = "UPDATE commandes SET client_id=$client_id, statut='$statut', total=$total, temps_livraison=$temps_livraison, ";
    $update_sql .= "date_livraison_prevue=" . ($date_livraison_prevue ? "'$date_livraison_prevue'" : "NULL") . ", ";
    if (!empty($adresse)) {
        $update_sql .= "adresse='$adresse', latitude=NULL, longitude=NULL ";
    } else {
        $update_sql .= "latitude=$latitude, longitude=$longitude, adresse=NULL ";
    }
    $update_sql .= "WHERE id=$id";

    $conn->query($update_sql);

    // ✅ Si le statut est "livré", enregistrer la date de livraison actuelle
    if (strtolower($statut) === 'livré') {
        $date_livraison = date('Y-m-d H:i:s');
        $conn->query("UPDATE commandes SET date_livraison='$date_livraison' WHERE id=$id");
    }

    $message = "✅ Commande mise à jour avec succès.";
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Modifier Commande #<?= $id ?></title>
    <style>
        label { display: inline-block; width: 150px; margin-bottom: 5px; }
        input, select { margin-bottom: 10px; }
        .produit-line { 
            margin-bottom: 15px;
            margin-left: 45px; 
         }
         .select,.input1{
             height: 35px;
            border: 1px solid #ccc;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .select:focus, .input1:focus{
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .remove-btn { 
            color: red; 
            cursor: pointer; 
            margin-left: 10px; }
             h2{
            color: #6a1b9a;
            margin-left: 25px;
            margin-bottom: 15px;
          
        }
        .label{
            margin-left: 45px; 
        }
            h3{
             margin-left: 45px; 
             font-size: 20px;
             color: rgb(255, 153, 0);
        }
         .content1{
            margin-left: 4%;
        }
        .content{
              margin: 1%;
              background-color:rgb(252, 242, 255);
        }
        .button,.button1,.a{
            margin-left: 45px; 
         }
         .button{
            background-color:rgb(99, 102, 102);
            color: white;
            border: none;
            height: 35px;
            width: 220px;
            font-size: 15px;
            border-radius: 10px;
         }
         .button1{
            background-color:rgb(216, 26, 51);
            color: white;
            border: none;
            height: 36px;
            width: 220px;
            font-size: 15px;
            border-radius: 10px;
         }
        
         .button1:hover{
            border-color: #007BFF;
            box-shadow: 0 0 10px rgba(255, 217, 0, 0.5);
             background-color:rgb(50, 60, 197);
             border: 2px solid #ccc;
             transition: border-color 0.3s, box-shadow 0.3s;
         }
         .a{
            font-size: 25px;
        }
        .a:hover{
            color: rgb(4, 0, 255);
            font-weight: bold;
        }
    </style>
    
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
        <div class="content">
        <h2>Modifier la commande #<?= $id ?></h2>
         <div class="content1">
        <?php if ($message): ?>
            <p style="color:green; font-weight:bold;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label class="label">Client :</label>
            <select name="client_id" required>
                <?php
                $clients_res->data_seek(0);
                while ($client = $clients_res->fetch_assoc()): ?>
                    <option value="<?= $client['id'] ?>" <?= $commande['client_id'] == $client['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($client['nom']) ?>
                    </option>
                <?php endwhile; ?>
            </select><br><br>

            <label class="label">Statut :</label>
            <select name="statut" required>
                <option value="en attente" <?= $commande['statut']=='en attente' ? 'selected' : '' ?>>En attente</option>
                <option value="en cours" <?= $commande['statut']=='en cours' ? 'selected' : '' ?>>En cours</option>
                <option value="livré" <?= $commande['statut']=='livré' ? 'selected' : '' ?>>Livré</option>
                <option value="échec" <?= $commande['statut']=='échec' ? 'selected' : '' ?>>Échec</option>
            </select><br><br>

            <!-- ✅ Champ Date prévue de livraison -->
            <label class="label">Date prévue de livraison :</label>
            <input type="datetime-local" name="date_livraison_prevue" value="<?= $commande['date_livraison_prevue'] ? date('Y-m-d\TH:i', strtotime($commande['date_livraison_prevue'])) : '' ?>"><br><br>

            <label class="label">Temps livraison (minutes) :</label>
            <input type="number" name="temps_livraison" min="0" value="<?= intval($commande['temps_livraison']) ?>" required><br><br>

            <?php if (!empty($commande['latitude']) && !empty($commande['longitude'])): ?>
                <label class="label">Latitude :</label>
                <input type="text" name="latitude" value="<?= htmlspecialchars($commande['latitude']) ?>"><br><br>
                <label class="label">Longitude :</label>
                <input type="text" name="longitude" value="<?= htmlspecialchars($commande['longitude']) ?>"><br><br>
            <?php else: ?>
                <label class="label">Adresse :</label>
                <input type="text" name="adresse" value="<?= htmlspecialchars($commande['adresse']) ?>"><br><br>
            <?php endif; ?>

            <h3>Produits</h3>
            <div id="produits">
                <?php if(empty($details)): ?>
                    <div class="produit-line">
                        <select name="produit_id[]" onchange="updatePrix(this)" class="select"  required>
                            <option value="">-- Sélectionner un produit --</option>
                            <?php
                            $produits_res->data_seek(0);
                            while ($p = $produits_res->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>" data-prix="<?= $p['prix'] ?>">
                                    <?= htmlspecialchars($p['nom']) ?> (Stock: <?= $p['stock'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <label class="label">Quantité :</label>
                        <input type="number" name="quantite[]" min="1" value="1" class="input1"  required>
                        <label class="label">Prix unitaire :</label>
                        <input type="number" name="prix[]" step="0.01" class="input1"  readonly required>
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
                ?>
                    <div class="produit-line">
                        <select name="produit_id[]" onchange="updatePrix(this)" class="select"  required>
                            <option value="">-- Sélectionner un produit --</option>
                            <?php
                            $produits_res->data_seek(0);
                            while ($p = $produits_res->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>" data-prix="<?= $p['prix'] ?>" <?= $p['id']==$prod_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nom']) ?> (Stock: <?= $p['stock'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <label class="label">Quantité :</label>
                        <input type="number" name="quantite[]" min="1" value="<?= $det['quantite'] ?>" class="input1" required>
                        <label class="label">Prix unitaire :</label>
                        <input type="number" name="prix[]" step="0.01" value="<?= htmlspecialchars($prod_info['prix'] ?? 0) ?>" class="input1" readonly required>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <button type="button" onclick="addProduit()" class="button">+ Ajouter un autre produit</button><br><br>

            <label class="label">Montant total :</label>
            <input type="number" id="montant_total" step="0.01" readonly><br><br>

            <button type="submit" class="button1">Enregistrer les modifications</button>
        </form>

        <br>
        <a href="toutes_commandes.php" class="a">⬅ Retour</a>
    </div>
    <script>
    function addProduit() {
        const container = document.getElementById('produits');
        const firstProduit = container.children[0];
        const newProduit = firstProduit.cloneNode(true);

        newProduit.querySelectorAll('select, input').forEach(input => {
            if (input.tagName.toLowerCase() === 'select') input.selectedIndex = 0;
            else input.value = '';
        });

        const removeBtn = document.createElement('span');
        removeBtn.textContent = '❌ Retirer';
        removeBtn.className = 'remove-btn';
        removeBtn.onclick = function() {
            newProduit.remove();
            updateMontantTotal();
        };

        newProduit.appendChild(removeBtn);
        container.appendChild(newProduit);
        updateMontantTotal();
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
        document.querySelectorAll('#produits .produit-line').forEach(ligne => {
            const quantite = parseFloat(ligne.querySelector('input[name="quantite[]"]').value) || 0;
            const prix = parseFloat(ligne.querySelector('input[name="prix[]"]').value) || 0;
            total += quantite * prix;
        });
        document.getElementById('montant_total').value = total.toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateMontantTotal();
        document.getElementById('produits').addEventListener('input', updateMontantTotal);
    });
    </script>
</body>
</html>
