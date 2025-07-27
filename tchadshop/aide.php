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

    // ✅ Nouveau champ Date prévue de livraison
    $date_livraison_prevue = $_POST['date_livraison_prevue'] ?? null;
    if ($date_livraison_prevue) {
        $date_livraison_prevue = str_replace('T', ' ', $conn->real_escape_string($date_livraison_prevue)) . ':00';
    } else {
        $date_livraison_prevue = null;
    }

    // Gérer le client
    if ($client_type === 'new') {
        $nom = $conn->real_escape_string($_POST['nom_client'] ?? '');
        $telephone = $conn->real_escape_string($_POST['telephone'] ?? '');
        $email = $conn->real_escape_string($_POST['email'] ?? '');
        $conn->query("INSERT INTO clients (nom, telephone, email, invite, vu) VALUES ('$nom', '$telephone', '$email', 0, 1)");
        $client_id = $conn->insert_id;
    } elseif ($client_type === 'existing') {
        $client_id = intval($_POST['client_id'] ?? 0);
        if ($client_id <= 0) {
            $message = "Erreur : client existant non sélectionné !";
        }
    } else {
        $message = "Erreur : veuillez sélectionner un type de client.";
    }

    if (!$message) {
        $date_commande = date('Y-m-d H:i:s');
        $conn->query("INSERT INTO commandes (client_id, date_commande, statut, adresse, date_livraison_prevue, vu) 
              VALUES ($client_id, '$date_commande', '$statut', " . ($adresse ? "'$adresse'" : "NULL") . ", " . ($date_livraison_prevue ? "'$date_livraison_prevue'" : "NULL") . ", 1)");
        $commande_id = $conn->insert_id;

        $total_commande = 0;

        foreach ($_POST['produit_id'] as $index => $produit_id) {
            $produit_id = intval($produit_id);
            $quantite = intval($_POST['quantite'][$index]);
            $prix = floatval($_POST['prix'][$index]);

            if ($produit_id > 0 && $quantite > 0) {
                $conn->query("INSERT INTO details_commandes (commande_id, produit_id, quantite,prix_unitaire) VALUES ($commande_id, $produit_id, $quantite,$prix )");
                $total_commande += $quantite * $prix;
            }
        }

        $conn->query("UPDATE commandes SET total = $total_commande WHERE id = $commande_id");

        $message = "✅ Commande enregistrée avec succès ! Total : " . number_format($total_commande, 2) . " FCFA";
    }
}

$produits = $conn->query("SELECT id, nom, prix, stock FROM produits");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Ajouter une commande</title>
    <style>
        .label,.cl{ display: inline-block;
                margin-bottom: 30px;
                margin-left: 45px; 
            }
        input, select { 
            margin-bottom: 10px; }
        .produit-line { 
            margin-bottom: 15px;
            margin-left: 45px; 
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
         #telephone_recherche:focus, .select:focus, .input1:focus,.input2:focus,.input3:focus,.input4:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .select,.input1{
             height: 35px;
            border: 1px solid #ccc;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
         #telephone_recherche{
            border: none;
            width: 210px;
            height: 35px;
            margin-left: 10px;
            border-radius: 10px;
            border: 1px solid #ccc;
            transition: border-color 0.3s, box-shadow 0.3s;
         }
        .remove-btn { color: red; cursor: pointer; margin-left: 10px; }
        #resultat_client { 
                margin-top: 5px;
                color: green; 
                margin-left: 45px; 
                margin-bottom: 25px;
            }
        #message { margin: 10px 0; font-weight: bold; color: red; }
        h2{
            color: #6a1b9a;
            margin-left: 25px;
            margin-bottom: 15px;
          
        }
        h3{
             margin-left: 45px; 
             font-size: 20px;
             color: rgb(255, 153, 0);
        }
        .cl{
            
            font-size: 20px;
             color: rgb(255, 153, 0);
             font-weight: bold;
             
             
        }
        .content1{
            margin-left: 4%;
        }
        .content{
              
              background-color:rgb(252, 242, 255);
        }
        input[type="radio"]{
            margin-left: 45px; 
            
        }
        .p{
            color: rgb(239, 234, 241);
        }
        .a{
            font-size: 25px;
        }
        .a:hover{
            color: rgb(4, 0, 255);
            font-weight: bold;
        }
        #new_client{
            display: block;
            width: 100%;
        }
        #new_client .label2{
            margin-left: 55px;
            font-size: 17px;
            font-weight: bold;

        }
        .input2,.input3,.input4{
            margin-left: 15px;
            width: 400px;
            height: 30px;
            border-radius: 10px;
            border: 1px solid #ccc;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        #new_client .input2{
            margin-left: 100px;
        }
        #new_client .input3{
            margin-left: 55px;
        }
        #new_client .input4{
            margin-left: 95px;
        }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="home-content">
    <div class="content">
        <h2><i class="bx bx-task"></i> Ajouter une commande</h2>
        <div class="content1">
            <?php if ($message): ?>
                <div id="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <label class="cl">Type de client :</label><br>
                <input type="radio" name="client_type" value="existing" checked onclick="toggleClientForm('existing')"> Client existant
                <input type="radio" name="client_type" value="new" onclick="toggleClientForm('new')"> Nouveau client
                <br><br>

                <div id="existing_client">
                    <label class="label">Numéro de téléphone :</label>
                    <input type="text" id="telephone_recherche" onkeyup="chercherClient()" placeholder="Entrer le numéro...">
                    <div id="resultat_client"></div>
                    <input type="hidden" name="client_id" id="client_id">
                </div>

                <div id="new_client" style="display:none;">
                    <label class="label2">Nom :</label>
                    <input type="text" name="nom_client" class="input2"><br><br>
                    <label class="label2">Téléphone :</label>
                    <input type="text" name="telephone" class="input3"><br><br>
                    <label class="label2">Email :</label>
                    <input type="email" name="email" class="input4">
                </div>

                <h3>Adresse</h3><br>
                <label class="label">Adresse :</label>
                <input type="text" name="adresse" class="input1" placeholder="Quartier, ville, etc."><br><br>

                <!-- ✅ Champ Date prévue de livraison -->
                <label class="label">Date prévue de livraison :</label>
                <input type="datetime-local" name="date_livraison_prevue" class="input1"><br><br>

                <h3>Produits</h3><br>
                <div id="produits">
                    <div class="produit-line">
                        <select name="produit_id[]" onchange="updatePrix(this)" class="select" required>
                            <option value="">-- Sélectionner un produit --</option>
                            <?php
                            $produits->data_seek(0);
                            while ($p = $produits->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>" data-prix="<?= $p['prix'] ?>">
                                    <?= htmlspecialchars($p['nom']) ?> (Stock: <?= $p['stock'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <label class="label">Quantité :</label>
                        <input type="number" name="quantite[]" min="1" value="1" class="input1" required>
                        <label class="label">Prix unitaire :</label>
                        <input type="number" name="prix[]" step="0.01" class="input1" readonly required>
                    </div>
                </div>

                <button type="button" onclick="addProduit()" class="button">+ Ajouter un produit</button><br><br>

                <label class="label">Montant total :</label>
                <input type="number" id="montant_total" step="0.01" class="input1" readonly><br><br>

                <label class="label">Statut de la commande :</label>
                <select name="statut" class="select" required>
                    <option value="en attente">En attente</option>
                    <option value="en cours">En cours</option>
                    <option value="échec">Échec</option>
                    <option value="livré">Livré</option>
                </select>
                <br><br>

                <button type="submit" class="button1">Enregistrer la commande</button>
            </form>

            <br>
            <a href="toutes_commandes.php" class="a">⬅ Retour</a>
             <br><br>
        </div>
    </div>
</div>

<script>
function toggleClientForm(type) {
    document.getElementById('existing_client').style.display = (type === 'existing') ? 'block' : 'none';
    document.getElementById('new_client').style.display = (type === 'new') ? 'block' : 'none';
}

function chercherClient() {
    const telephone = document.getElementById('telephone_recherche').value;
    if (telephone.length >= 6) {
        fetch('chercher_client.php?telephone=' + encodeURIComponent(telephone))
        .then(response => response.json())
        .then(data => {
            if (data.trouve) {
                document.getElementById('resultat_client').innerHTML = "✅ Client trouvé : " + data.nom;
                document.getElementById('client_id').value = data.id;
            } else {
                document.getElementById('resultat_client').innerHTML = "❌ Client introuvable";
                document.getElementById('client_id').value = "";
            }
        });
    } else {
        document.getElementById('resultat_client').innerHTML = "";
        document.getElementById('client_id').value = "";
    }
}

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
    toggleClientForm('existing');
    document.getElementById('produits').addEventListener('input', updateMontantTotal);
});
</script>
</body>
</html>






<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
  <head>
    <meta charset="UTF-8" />
    <title>Connexion admin</title>
    
    <!-- Boxicons CDN Link -->
    <link
      href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css"
      rel="stylesheet"
    />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style> 
    body{
       box-sizing: border-box;
       margin: 0;
       background-color: #393d3dff;
       
    }
    header{
      margin-top: -22px;
      width: 100%;
      height: 80px;
      background-color: #164b83ff;
      text-align: center;
      box-shadow: 0 0 0 0.1rem #80bdff;
    }
    header h1{
        color: #879fc0ff;
        font-weight: bold;
        padding: 20px;
     
    }
    .div1{
      margin: 80px auto;
      width: 30%;
      height: 300px;
      background-color: #4b5252ff;
      border-radius: 10px;
       padding: 10px;
        box-shadow: 0 0 0 0.1rem #80bdff;
       
     

    }
      
        
        
        input:focus {
            outline: 0;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
            background-color: #ffffffff;
        }
    .div1 h2{
      text-align: center;
      font-size: 35px;
      font-weight: bold;
      color: #80bdff;
    }
    .div1 form{
      text-align: center;
     
    }
    input{
       margin:10px;
       width: 70%;
       height: 30px;
       border-radius: 5px;
       border-bottom: 4px solid white;
       background-color: #5e6868ff;
       transition: border-color 0.15s;
        border-color: #80bdff;

     
    }
    label{
      font-weight: bold;
      color: #80bdff;
      margin-left: -200px;
    }
    a{
      color: #96c9ffff;
      margin-left: 30%;
      margin-top: 18%;
      font-size: 20px;
      
    }
    </style>
</head>
  <body>
    <header>
      <h1>AUTHENTIFICATION</h1>
    </header>
    <div class="div1">
      
        <h2>Connexion</h2>
        <form method="post" action="">
          <label for="nom">Nom complet </label><br>
          <input type="text" id="nom" name="nom" require /><br>
          <label for="mot">Mot de passe </label><br>
          <input type="password" id="mot" name="mot" require /><br>
        </form>
      <a href="#">Mot de passe oublié</a>
    </div>

</body>
</html>




















