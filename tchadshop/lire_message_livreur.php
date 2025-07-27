<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID invalide.");
}

$id = intval($_GET['id']);

// Marquer le message comme lu
$conn->query("UPDATE messages_livreurs SET lu = 1 WHERE id = $id");

// Récupérer le message
$stmt = $conn->prepare("SELECT ml.*, l.nom, l.email, l.telephone 
                        FROM messages_livreurs ml
                        JOIN livreurs l ON ml.livreur_id = l.id
                        WHERE ml.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Message non trouvé.");
}

$message = $result->fetch_assoc();

// Traitement de la réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reponse = trim($_POST['reponse']);
    if (!empty($reponse)) {
        $stmt = $conn->prepare("UPDATE messages_livreurs SET reponse = ?, repondu = 1 WHERE id = ?");
        $stmt->bind_param("si", $reponse, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Réponse envoyée avec succès.";
            header("Location: message_livreur.php");
            exit();
        } else {
            $error = "Erreur lors de l'envoi de la réponse.";
        }
    } else {
        $error = "La réponse ne peut pas être vide.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>📖 Lire Message Livreur</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .btn { background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #0056b3; }
        textarea { width: 100%; height: 150px; margin-top: 10px; }
        .error { color: red; }
        audio { width: 200px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>📖 Lire Message</h1>
    <p><strong>Livreur :</strong> <?= htmlspecialchars($message['nom']) ?> (<?= htmlspecialchars($message['email'] ?? 'N/A') ?>)</p>
    <p><strong>Sujet :</strong> <?= htmlspecialchars($message['sujet']) ?></p>
    <p><strong>Numéro :</strong> <?= htmlspecialchars($message['telephone']) ?></p>
    <p><strong>Message :</strong><br><?= nl2br(htmlspecialchars($message['message'])) ?></p>

    <?php if (!empty($message['audio_path'])): ?>
        <p><strong>Audio :</strong></p>
        <audio controls>
            <source src="<?= htmlspecialchars($message['audio_path']) ?>" type="audio/mpeg">
            Votre navigateur ne supporte pas l'audio.
        </audio>
    <?php endif; ?>

    <p><strong>Date :</strong> <?= $message['created_at'] ?></p>

    <h2>✏ Répondre au livreur</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <textarea name="reponse" placeholder="Tapez votre réponse ici..."><?= htmlspecialchars($message['reponse'] ?? '') ?></textarea><br>
        <button type="submit" class="btn">📤 Envoyer la réponse</button>
    </form>
    <br>
    <a href="message_livreur.php" class="btn">⬅ Retour</a>
</body>
</html>
