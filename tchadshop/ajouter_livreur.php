<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $actif = isset($_POST['actif']) ? 1 : 0;

    if (!empty($nom) && !empty($telephone)) {
        $stmt = $conn->prepare("INSERT INTO livreurs (nom, email, telephone, password, actif) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $nom, $email, $telephone, $password, $actif);

        if ($stmt->execute()) {
            $_SESSION['message'] = "✅ Livreurs ajouté avec succès.";
            header("Location: livreurs.php");
            exit();
        } else {
            $_SESSION['message'] = "❌ Erreur lors de l'ajout.";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "❌ Nom et téléphone sont obligatoires.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>➕ Ajouter un livreur</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 8px; margin-top: 5px; }
        button { margin-top: 15px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; }
        button:hover { background: #0056b3; }
        .back { margin-top: 10px; display: inline-block; text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
    <h1>➕ Ajouter un livreur</h1>
    <form method="post" action="">
        <label>Nom *</label>
        <input type="text" name="nom" required>
        
        <label>Email</label>
        <input type="email" name="email">
        
        <label>Téléphone *</label>
        <input type="text" name="telephone" required>
        
        <label>Mot de passe *</label>
        <input type="password" name="password" required>
        
        <label><input type="checkbox" name="actif" checked> Actif</label>
        
        <button type="submit">Enregistrer</button>
    </form>
    <a href="livreurs.php" class="back">⬅ Retour</a>
</body>
</html>
