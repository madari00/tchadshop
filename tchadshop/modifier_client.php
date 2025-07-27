<?php
session_start();
$searchContext='mclient';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: clients.php");
    exit();
}

// 🔥 Récupérer les données existantes
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
if (!$client) {
    header("Location: clients.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $adresse = trim($_POST['adresse']);
    $invite = isset($_POST['invite']) ? 1 : 0;

    // Si le champ mot de passe est rempli, on le met à jour
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE clients SET nom = ?, email = ?, telephone = ?, adresse = ?, password = ?, invite = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $nom, $email, $telephone, $adresse, $password, $invite, $id);
    } else {
        // Sinon, on garde le mot de passe existant
        $stmt = $conn->prepare("UPDATE clients SET nom = ?, email = ?, telephone = ?, adresse = ?, invite = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $nom, $email, $telephone, $adresse, $invite, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "✅ Client mis à jour avec succès.";
        header("Location: clients.php");
        exit();
    } else {
        $error = "❌ Erreur lors de la mise à jour.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>✏ Modifier client</title>
    <style>
        body { font-family: Arial, sans-serif; }
        label { display: block; margin-top: 10px; }
        .input1, .textarea { width: 80%; padding: 8px; margin-top: 5px; }
        .btn1 { background: #ffc107; color: black; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn1:hover { background: #e0a800; }
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
        .input1:focus,.input2:focus,.textarea:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .textarea{
            border: 1px solid #ccc;
            transition: border-color 0.3s, box-shadow 0.3s;
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
    <h2>✏ Modifier client #<?= $id ?></h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <label>Nom *</label>
        <input type="text" name="nom" value="<?= htmlspecialchars($client['nom']) ?>" required class="input1">
        
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($client['email']) ?>" class="input1">
        
        <label>Téléphone *</label>
        <input type="text" name="telephone" value="<?= htmlspecialchars($client['telephone']) ?>" required class="input1">
        
        <label>Adresse</label>
        <textarea name="adresse" class="textarea"><?= htmlspecialchars($client['adresse'] ?? '') ?></textarea>

        <label>Client invité</label>
            <input type="checkbox" name="invite" value="1" <?= $client['invite'] ? 'checked' : '' ?> onchange="togglePassword(this)" class="input2"> 
            <br>

        <div id="passwordField">
            <label>Mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" name="password" class="input1">
        </div><br>

        <button type="submit" class="btn1">Mettre à jour</button>
        <a href="clients.php" class="btn1" style="background:#6c757d;">Annuler</a>
        <p class="p">.<p>
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
