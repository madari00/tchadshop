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

// 🟢 Marquer le message comme lu
$conn->query("UPDATE messages_clients SET lu = 1 WHERE id = $id");

// 🔎 Récupérer le message
$stmt = $conn->prepare("SELECT mc.*, cl.nom, cl.email, cl.telephone
                        FROM messages_clients mc
                        JOIN clients cl ON mc.client_id = cl.id
                        WHERE mc.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Message non trouvé.");
}

$message = $result->fetch_assoc();

// ✉ Réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reponse = trim($_POST['reponse']);
    if (!empty($reponse)) {
        $stmt = $conn->prepare("UPDATE messages_clients SET reponse = ?, repondu = 1 WHERE id = ?");
        $stmt->bind_param("si", $reponse, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Réponse envoyée avec succès.";
            header("Location: message_client.php");
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
    <title>📖 Lire Message Client</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .btn { background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #0056b3; }
        textarea { width: 100%; height: 150px; margin-top: 10px; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>📖 Lire Message</h1>
    <p><strong>Client :</strong> <?= htmlspecialchars($message['nom']) ?> (<?= htmlspecialchars($message['email']) ?>)</p>
    <p><strong>Numéro :</strong> <?= htmlspecialchars($message['telephone']) ?></p>
    <p><strong>Sujet :</strong> <?= htmlspecialchars($message['sujet']) ?></p>
    <p><strong>Message :</strong><br><?= nl2br(htmlspecialchars($message['message'])) ?></p>
    <p><strong>Date :</strong> <?= $message['created_at'] ?></p>

    <h2>✏ Répondre au client</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <textarea name="reponse" placeholder="Tapez votre réponse ici..."><?= htmlspecialchars($message['reponse'] ?? '') ?></textarea><br>
        <button type="submit" class="btn">📤 Envoyer la réponse</button>
    </form>
    <br>
    <a href="message_client.php" class="btn">⬅ Retour</a>
</body>
</html>
