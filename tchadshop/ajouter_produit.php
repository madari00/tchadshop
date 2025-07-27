<?php
session_start();
$searchContext = 'ajoutp';
$message = '';

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

// Récupérer le message de session s'il existe
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Supprimer le message après affichage
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $conn->real_escape_string($_POST['nom']);
    $description = $conn->real_escape_string($_POST['description']);
    $prix = floatval($_POST['prix']);
    $stock = intval($_POST['stock']);
    $statut = $conn->real_escape_string($_POST['statut']);
    $created_at = date('Y-m-d H:i:s');

    // Insertion dans la table produits
    $sql = "INSERT INTO produits (nom, description, prix, stock, statut, created_at)
            VALUES ('$nom', '$description', $prix, $stock, '$statut', '$created_at')";

    if ($conn->query($sql) === TRUE) {
        $produit_id = $conn->insert_id; // Récupère l’ID du produit ajouté

        // Gestion des images
        $upload_dir = "uploads/";
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['images']['name'][$key]);
            $target_file = $upload_dir . uniqid() . "_" . $file_name;

            if (move_uploaded_file($tmp_name, $target_file)) {
                $conn->query("INSERT INTO images_produit (produit_id, image) VALUES ($produit_id, '$target_file')");
            }
        }

        $_SESSION['message'] = "<div class='alert success'>✅ Produit ajouté avec succès !</div>";
        header("Location: ajouter_produit.php");
        exit();
    } else {
        $_SESSION['message'] = "<div class='alert error'>❌ Erreur : " . $conn->error . "</div>";
        header("Location: ajouter_produit.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un produit</title>
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .content,.home-content{
            background-color: rgb(214, 221, 221);
        }
        .content h2{
            color: rgb(173, 117, 164);
            margin: 20px;
        }
        .content .label,.button,.a{
            margin-left: 100px;
        }
        .content .button{
            border-radius: 5px;
            width: 170px;
            height: 40px;
            background-color: rgb(40, 42, 201);
            border: none;
            color: white;
            font-size: 16px;
            margin-top: 10px;
        }
        .content .a{
            color: rgb(40, 42, 201);
            font-size: 26px;
        }
        .content .p{
            color: rgb(214, 221, 221);
        }
        .content input:focus,textarea:focus,select:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .content input, .content textarea, .content select {
            border-radius: 10px;
            border: 1px solid #ccc;
            transition: border-color 0.3s, box-shadow 0.3s;
            height: 40px;
            margin-left: 100px;
            width: 80%;
        }
        .content textarea {
            height: 80px;
        }
        .content input[type="file"] {
            box-shadow: none;
            border-radius: none;
            width: 30%;
        }
        .fichier {
            margin-left: 100px;
        }
        .fichier input[type="file"] {
            display: none;
        }
        .fichier1 {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .fichier1:hover {
            background-color: #218838;
        }
        #nom-fichier {
            margin-left: 10px;
            color: #666;
            font-style: italic;
        }
        .fa-solid {
            color: #007bff;
            margin-right: 5px;
        }

        /* Message alert */
        .alert {
            margin: 20px auto;
            width: 80%;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
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
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
        <div class="content">
            <!-- Affichage du message -->
            <?php if (!empty($message)) echo $message; ?>

            <h2>➕ Ajouter un nouveau produit</h2>
            <form action="ajouter_produit.php" method="POST" enctype="multipart/form-data">
                <label for="nom" class="label"><i class="fa-solid fa-plus"></i> Nom du produit :</label><br>
                <input type="text" name="nom" id="nom" required><br><br>

                <label for="description" class="label"><i class="fa-solid fa-pen-to-square"></i> Description :</label><br>
                <textarea name="description" id="description" required></textarea><br><br>

                <label for="prix" class="label"><i class="fa-solid fa-money-bill-wave"></i> Prix (FCFA) :</label><br>
                <input type="number" name="prix" id="prix" step="0.01" required><br><br>

                <label for="stock" class="label"><i class="fa-solid fa-boxes-stacked"></i> Stock :</label><br>
                <input type="number" name="stock" id="stock" required><br><br>

                <label for="statut" class="label"><i class="fa-solid fa-toggle-on"></i> Statut :</label><br>
                <select name="statut" id="statut" required>
                    <option value="disponible">Disponible</option>
                    <option value="rupture">Rupture</option>
                    <option value="bientôt dispo">Bientôt disponible</option>
                </select><br><br>

                <label for="images" class="label">Images du produit :</label><br>
                <div class="fichier">
                    <label for="images" class="fichier1">📁 Sélectionner des fichiers</label>
                    <span id="nom-fichier">Aucun fichier sélectionné</span>
                    <input type="file" name="images[]" id="images" accept="image/*" multiple required>
                </div>
                <button type="submit" class="button">Ajouter le produit</button>
            </form>
            <br>
            <a href="produits.php" class="a">← Retour à la liste des produits</a>
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
    </script>
</body>
</html>
