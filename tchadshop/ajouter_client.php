<?php
session_start();
$searchContext='aclient';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $adresse = trim($_POST['adresse']);
    $invite = isset($_POST['invite']) ? 1 : 0;

    // Gestion du mot de passe : obligatoire si invite = 0
    if ($invite == 0 && !empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    } else {
        $password = null; // Client invité => pas de mot de passe
    }

    if ($nom && $telephone) {
        $stmt = $conn->prepare("INSERT INTO clients (nom, email, telephone, adresse, password, invite,vu) VALUES (?, ?, ?, ?, ?, ?,0,1)");
        $stmt->bind_param("sssssi", $nom, $email, $telephone, $adresse, $password, $invite);
        if ($stmt->execute()) {
            $_SESSION['message'] = "✅ Nouveau client ajouté avec succès.";
            header("Location: clients.php");
            exit();
        } else {
            $error = "❌ Erreur lors de l'ajout du client.";
        }
    } else {
        $error = "⚠ Veuillez remplir les champs obligatoires (nom et téléphone).";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>➕ Ajouter un client</title>
    <style>
        body { font-family: Arial, sans-serif;  }
        label { display: block; margin-top: 10px; }
        
        .input1, textarea { width: 80%; padding: 8px; margin-top: 5px; }
        .btn1 { background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn1:hover { background: #218838; }
        .error { color: red; margin-top: 10px; }
        .content{
            margin-left: 15%;
        }
        h2{
            color: #6a1b9a;
            margin-left: -55px;
            margin-bottom: 15px;
          
        }
        .input1{
            border: 1px solid #ccc;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .input1:focus,.input2:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .input2{
            width: 20px;
            height: 20px;
            margin-top: 6px;
            border: 1px solid #ccc;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .p{
            color:white;
        }
    </style>
</head>
<body>
    <?php include("header.php") ; ?>
<div class="home-content">
    <div class="content">
    <h2>➕ Ajouter un client</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post" action="" class="form">
        <label>Nom *</label>
        <input type="text" name="nom" required class="input1">

        <label>Email</label>
        <input type="email" name="email" class="input1">

        <label>Téléphone *</label>
        <input type="text" name="telephone" required class="input1">

        <label>Adresse</label>
        <textarea name="adresse" class="input1"></textarea>

        <label class="label">Client invité</label>
            <input type="checkbox" name="invite" value="1" onchange="togglePassword(this)" class="input2"> 

        <div id="passwordField">
            <label>Mot de passe (obligatoire pour client inscrit)</label>
            <input type="password" name="password" class="input1">
        </div><br>

        <button type="submit" class="btn1">Ajouter</button>
        <a href="clients.php" class="btn1" style="background:#6c757d;">Annuler</a>
        <p class="p">.</p>
    </form>

    <script>
        function togglePassword(checkbox) {
            const passwordField = document.getElementById('passwordField');
            if (checkbox.checked) {
                passwordField.style.display = 'none';
            } else {
                passwordField.style.display = 'block';
            }
        }
        window.onload = function() {
            togglePassword(document.querySelector('input[name="invite"]'));
        };
    </script>
</body>
</html>
