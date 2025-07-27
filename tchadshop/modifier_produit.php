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

// Récupérer les infos du produit
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

    $update_sql = "UPDATE produits SET 
                    nom = '$nom', 
                    description = '$description', 
                    prix = $prix, 
                    stock = $stock, 
                    statut = '$statut' 
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
            width: 220px;
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

        <h2>✏️ Modifier le produit</h2>
        <form action="modifier_produit.php?id=<?= $produit_id ?>" method="POST" enctype="multipart/form-data">
            <label for="nom" class="label"><i class="fa-solid fa-plus"></i> Nom du produit :</label>
            <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($produit['nom']) ?>" required><br><br>

            <label for="description" class="label"><i class="fa-solid fa-pen-to-square"></i> Description :</label>
            <textarea name="description" id="description" required><?= htmlspecialchars($produit['description']) ?></textarea><br><br>

            <label for="prix" class="label"><i class="fa-solid fa-money-bill-wave"></i> Prix (FCFA) :</label>
            <input type="number" name="prix" id="prix" step="0.01" value="<?= $produit['prix'] ?>" required><br><br>

            <label for="stock" class="label"><i class="fa-solid fa-boxes-stacked"></i> Stock :</label>
            <input type="number" name="stock" id="stock" value="<?= $produit['stock'] ?>" required><br><br>

            <label for="statut" class="label"><i class="fa-solid fa-toggle-on"></i> Statut :</label>
            <select name="statut" id="statut" required>
                <option value="disponible" <?= $produit['statut'] == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                <option value="rupture" <?= $produit['statut'] == 'rupture' ? 'selected' : '' ?>>Rupture</option>
                <option value="bientôt dispo" <?= $produit['statut'] == 'bientôt dispo' ? 'selected' : '' ?>>Bientôt disponible</option>
            </select><br><br>

            <label for="images" class="label">Ajouter de nouvelles images :</label>
            <div class="fichier">
                <label for="images" class="fichier1">
                    📁 Sélectionner des fichiers
                </label>
                <span id="nom-fichier">Aucun fichier sélectionné</span>
                <input type="file" name="images[]" id="images" accept="image/*" multiple>
            </div><br>

            <button type="submit" class="button">Mettre à jour le produit</button>
        </form><br><br>

        <h3 class="label">Images actuelles :</h3><br>
        <?php while ($img = $img_result->fetch_assoc()): ?>
            <div style="display: inline-block; margin-left: 98px;">
                <img src="<?= $img['image'] ?>" width="80" height="80" style="display: block; margin-bottom: 5px;">
                <a href="modifier_produit.php?id=<?= $produit_id ?>&delete_image=<?= $img['id'] ?>" 
                   onclick="return confirm('Supprimer cette image ?');" 
                   style="color: red;">Supprimer</a>
            </div>
        <?php endwhile; ?>
        <br><br>
        <a href="produits.php" class="a">← Retour à la liste des produits</a>
        <br><br>
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
