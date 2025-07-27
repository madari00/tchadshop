<?php
session_start();
$searchContext = "messagel";
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$search = $_GET['search'] ?? '';
$filtre = $_GET['filtre'] ?? 'tous'; // tous | non_lus | repondus

// 🔎 Requête principale
$sql = "SELECT m.*, l.nom AS livreur_nom, l.email
        FROM messages_livreurs m
        LEFT JOIN livreurs l ON m.livreur_id = l.id";

$where = [];
$params = [];
$types = "";

// 🔍 Recherche
if (!empty($search)) {
    $where[] = "(l.nom LIKE ? OR l.email LIKE ? OR m.sujet LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}

// 🎯 Filtres
if ($filtre === 'non_lus') {
    $where[] = "m.lu = 0";
} elseif ($filtre === 'repondus') {
    $where[] = "m.repondu = 1";
}

// 🔗 Ajouter WHERE si nécessaire
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY m.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>📨 Messages Livreurs</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #6a1b9a; color: #fff; }
        .btn1 { background: #007bff; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; }
        .btn1:hover { background: #0056b3; }
        .delete-btn { background: #dc3545; }
        .view-btn { background: #28a745; }
        .read { background: #f9f9f9; }
        .unread { background: #ffeeba; }
        audio { width: 150px; }
        h2 { color: #6a1b9a; margin: 20px; }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="home-content">
    <h2>📨 Messages des livreurs</h2>

    <!-- Filtres -->
    <div>
        <a href="?filtre=tous" class="btn1">📄 Tous</a>
        <a href="?filtre=non_lus" class="btn1">📩 Non lus</a>
        <a href="?filtre=repondus" class="btn1">✅ Répondus</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Livreur</th>
                <th>Email</th>
                <th>Sujet</th>
                <th>Audio</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="<?= $row['lu'] ? 'read' : 'unread' ?>">
                <td><?= htmlspecialchars($row['livreur_nom'] ?? 'Inconnu') ?></td>
                <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['sujet']) ?></td>
                <td>
                    <?php if (!empty($row['audio_path'])): ?>
                        <audio controls>
                            <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/mpeg">
                            Votre navigateur ne supporte pas l'audio.
                        </audio>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= $row['created_at'] ?></td>
                <td>
                    <?= $row['lu'] ? '✅ Lu' : '📩 Non lu' ?>
                    <?= $row['repondu'] ? '✔ Répondu' : '' ?>
                </td>
                <td>
                    <a href="lire_message_livreur.php?id=<?= $row['id'] ?>" class="btn1 view-btn">👁 Lire</a>
                    <a href="supprimer_message_livreur.php?id=<?= $row['id'] ?>" class="btn1 delete-btn" onclick="return confirm('Supprimer ce message ?')">🗑 Supprimer</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Aucun message trouvé.</p>
    <?php endif; ?>
    <br><a href="tableau_bord.php" class="btn1">⬅ Retour au tableau de bord</a>
</div>
</body>
</html>
