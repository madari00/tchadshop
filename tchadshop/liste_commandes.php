<?php  
session_start();
$searchContext = '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// ✅ Bouton "Lire tout"
if (isset($_GET['lire_tout'])) {
    $conn->query("UPDATE commandes SET vu = 1 WHERE vu = 0");
    header("Location: assigner_livreur.php");
    exit();
}

// 🔥 Sélectionner seulement les nouvelles commandes (vu = 0)
$sql = "SELECT c.*, cl.nom, cl.email, cl.telephone, cl.invite AS client_invite
        FROM commandes c
        LEFT JOIN clients cl ON c.client_id = cl.id
        WHERE c.vu = 0
        ORDER BY c.date_commande DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🆕 Nouvelles Commandes</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #6a1b9a; color: #fff; }
        .btn1 { padding: 5px 10px; border-radius: 4px; color: #fff; text-decoration: none; margin-right: 5px; }
        .view-btn { background: #17a2b8; }
        .edit-btn { background: #ffc107; color: #000; }
        .delete-btn { background: #dc3545; }
        .btn1:hover { opacity: 0.8; }
        h1 { color: #6a1b9a; }
        audio { width: 150px; }
        .btn-top { margin-top: 10px; margin-bottom: 15px; display: inline-block; }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
    <h1>🆕 Nouvelles Commandes</h1>

    <?php if ($result->num_rows > 0): ?>
        <a href="?lire_tout=1" class="btn1 view-btn btn-top">📖 Lire toutes les commandes</a>
        <table>
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Vocal</th>
                    <th>Date Commande</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td>
                            <?php if ($row['client_invite']): ?>
                                <span class="badge-invite">Invité</span>
                            <?php else: ?>
                                <?= htmlspecialchars($row['nom'] ?? '') ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['telephone']): ?>
                                <a href="https://wa.me/<?= htmlspecialchars($row['telephone']) ?>" target="_blank">
                                    📱 <?= htmlspecialchars($row['telephone']) ?> 💬
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['audio_path'])): ?>
                                <audio controls>
                                    <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/mpeg">
                                    Votre navigateur ne supporte pas l'audio.
                                </audio>
                            <?php else: ?>
                                ❌ Aucun vocal
                            <?php endif; ?>
                        </td>
                        <td><?= $row['date_commande'] ?></td>
                        <td>
                            <a href="voir_commande.php?id=<?= $row['id'] ?>" class="btn1 view-btn">👁 Voir</a>
                            <a href="modifier_commande.php?id=<?= $row['id'] ?>" class="btn1 edit-btn">✏ Modifier</a>
                            <a href="supprimer_commande.php?id=<?= $row['id'] ?>" class="btn1 delete-btn" onclick="return confirm('⚠ Supprimer cette commande ?');">🗑 Supprimer</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucune nouvelle commande trouvée.</p>
    <?php endif; ?>

    <br>
    <a href="toutes_commandes.php" class="btn1 view-btn">⬅ Retour aux commandes</a>
</body>
</html>
