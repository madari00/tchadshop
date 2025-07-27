<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if (!isset($_POST['id']) && !isset($_GET['id'])) {
    // Pas d'id fourni, redirection
    header("Location: configuration.php#security");
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : intval($_GET['id']);

// Traitement formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];

    if (!empty($mot_de_passe)) {
        // Modifier mot de passe
        $hashed = password_hash($mot_de_passe, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE admin SET nom=?, email=?, mot_de_passe=? WHERE id=?");
        $stmt->bind_param("sssi", $nom, $email, $hashed, $id);
    } else {
        // Modifier sans changer le mdp
        $stmt = $conn->prepare("UPDATE admin SET nom=?, email=? WHERE id=?");
        $stmt->bind_param("ssi", $nom, $email, $id);
    }
    $stmt->execute();
    header("Location: configuration.php#security");
    exit;
}

// Récupérer les infos actuelles
$stmt = $conn->prepare("SELECT nom, email FROM admin WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Administrateur non trouvé.";
    exit;
}
$admin = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Modifier administrateur</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 30px;
        }
        .form-container {
            background: white;
            max-width: 500px;
            margin: auto;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
            color: #236459;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            color: #343a40;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            font-size: 16px;
            margin-top: 5px;
        }
        button {
            margin-top: 25px;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            background-color: #236459;
            color: white;
            font-weight: 700;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #2d7d6f;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #236459;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Modifier administrateur</h2>
        <form method="POST" action="admin_modifier.php">
            <input type="hidden" name="id" value="<?= $id ?>">
            <label for="nom">Nom</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($admin['nom']) ?>" required>

            <label for="email">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>

            <label for="mot_de_passe">Mot de passe <small>(laisser vide pour ne pas changer)</small></label>
            <input type="password" name="mot_de_passe" placeholder="Nouveau mot de passe">

            <button type="submit" name="update">💾 Enregistrer</button>
        </form>
        <a href="configuration.php#security">← Retour à la gestion des administrateurs</a>
    </div>
</body>
</html>
