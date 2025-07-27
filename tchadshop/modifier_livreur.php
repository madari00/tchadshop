<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: livreurs.php");
    exit();
}

// Récupérer les infos du livreur
$stmt = $conn->prepare("SELECT * FROM livreurs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$livreur = $res->fetch_assoc();
$stmt->close();

if (!$livreur) {
    $_SESSION['message'] = "❌ Livreur introuvable.";
    header("Location: livreurs.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $actif = isset($_POST['actif']) ? 1 : 0;

    if (!empty($nom) && !empty($telephone)) {
        if (!empty($_POST['password'])) {
            $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE livreurs SET nom=?, email=?, telephone=?, password=?, actif=? WHERE id=?");
            $stmt->bind_param("ssssii", $nom, $email, $telephone, $password, $actif, $id);
        } else {
            $stmt = $conn->prepare("UPDATE livreurs SET nom=?, email=?, telephone=?, actif=? WHERE id=?");
            $stmt->bind_param("sssii", $nom, $email, $telephone, $actif, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = "✅ Modifications enregistrées.";
            header("Location: livreurs.php");
            exit();
        } else {
            $_SESSION['message'] = "❌ Erreur lors de la mise à jour.";
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
    <title>✏ Modifier le livreur</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 8px; margin-top: 5px; }
        button { margin-top: 15px; padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; }
        button:hover { background: #218838; }
        .back { margin-top: 10px; display: inline-block; text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
    <h1>✏ Modifier le livreur</h1>
    <form method="post" action="">
        <label>Nom *</label>
        <input type="text" name="nom" value="<?= htmlspecialchars($livreur['nom']) ?>" required>
        
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($livreur['email'] ?? '') ?>">
        
        <label>Téléphone *</label>
        <input type="text" name="telephone" value="<?= htmlspecialchars($livreur['telephone']) ?>" required>
        
        <label>Nouveau mot de passe (laisser vide pour conserver l’ancien)</label>
        <input type="password" name="password">
        
        <label><input type="checkbox" name="actif" <?= $livreur['actif'] ? 'checked' : '' ?>> Actif</label>
        
        <button type="submit">Enregistrer</button>
    </form>
    <a href="livreurs.php" class="back">⬅ Retour</a>
</body>
</html>
