<?php
session_start();
$searchContext = '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

function safe($val) {
    return htmlspecialchars($val ?? '');
}

// 🔥 Filtre par date
$filtre_date = $_GET['filtre_date'] ?? 'toutes';
$date_choisie = $_GET['date_choisie'] ?? '';
$where_date = "";

if ($filtre_date == 'aujourdhui') {
    $today = date('Y-m-d');
    $where_date = "AND DATE(c.date_livraison_prevue) = '$today'";
} elseif ($filtre_date == 'date' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_choisie)) {
    $where_date = "AND DATE(c.date_livraison_prevue) = '$date_choisie'";
}

// 🔥 Filtre par audio
$filtre_audio = $_GET['filtre_audio'] ?? 'tous';
$where_audio = "";

if ($filtre_audio == 'avec') {
    $where_audio = "AND c.audio_path IS NOT NULL AND c.audio_path != ''";
} elseif ($filtre_audio == 'sans') {
    $where_audio = "AND (c.audio_path IS NULL OR c.audio_path = '')";
}

// 📝 Traitement assignation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commande_id = intval($_POST['commande_id'] ?? 0);
    $livreur_id = intval($_POST['livreur_id'] ?? 0);
    $temps_livraison = intval($_POST['temps_livraison'] ?? 30);

    if ($commande_id > 0 && $livreur_id > 0) {
        $stmt = $conn->prepare("UPDATE commandes SET livreur_id = ?, temps_livraison = ?, statut = 'en cours' WHERE id = ?");
        $stmt->bind_param("iii", $livreur_id, $temps_livraison, $commande_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "✅ Livreur assigné pour la commande #$commande_id.";
        } else {
            $_SESSION['message'] = "❌ Erreur lors de l'assignation.";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "⚠ Veuillez sélectionner une commande et un livreur.";
    }
    header("Location: assigner_livreur.php?filtre_date=" . urlencode($filtre_date) . "&date_choisie=" . urlencode($date_choisie) . "&filtre_audio=" . urlencode($filtre_audio));
    exit();
}

// 📦 Récupérer les livreurs
$sql_livreurs = "SELECT id, nom FROM livreurs ORDER BY nom ASC";
$res_livreurs = $conn->query($sql_livreurs);
$livreurs = [];
while ($liv = $res_livreurs->fetch_assoc()) {
    $livreurs[$liv['id']] = $liv['nom'];
}

// 📦 Récupérer commandes en attente
$sql_attente = "SELECT c.id, c.date_commande, c.date_livraison_prevue, c.audio_path, cl.nom AS client_nom, cl.invite AS client_invite
                FROM commandes c
                LEFT JOIN clients cl ON c.client_id = cl.id
                WHERE c.statut = 'en attente' $where_date $where_audio
                ORDER BY c.date_commande ASC";
$res_attente = $conn->query($sql_attente);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Assigner un livreur</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: center; }
        th { background: #6a1b9a; color: #fff; }
        .btn1 { background: #007bff; color: white; padding: 5px 5px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn1:hover { background: #0056b3; }
        .message { margin: 10px 0; padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; text-align: center; }
        .badge-invite { background: purple; color: white; font-size: 11px; padding: 2px 5px; border-radius: 4px; margin-left: 5px; }
        .badge-red { background: #dc3545; color: #fff; padding: 2px 5px; border-radius: 4px; }
        audio { width: 120px; }
        h2 { color: #6a1b9a; margin: 20px; }
        .form { text-align: center; margin: 30px; }
        .form label { color: #6a1b9a; font-size: 18px; }
        .form .select, .form input { padding: 5px; margin: 0 5px; }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="home-content">
    <h2>📦 Assigner un livreur aux commandes</h2>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="message"><?= safe($_SESSION['message']); ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- 🔎 Filtres -->
    <form method="get" action="assigner_livreur.php" class="form">
        <label>📅 Date : </label>
        <select name="filtre_date" onchange="this.form.submit()" class="select">
            <option value="toutes" <?= $filtre_date == 'toutes' ? 'selected' : '' ?>>Toutes</option>
            <option value="aujourdhui" <?= $filtre_date == 'aujourdhui' ? 'selected' : '' ?>>Aujourd'hui</option>
            <option value="date" <?= $filtre_date == 'date' ? 'selected' : '' ?>>Choisir une date</option>
        </select>
        <?php if ($filtre_date == 'date'): ?>
            <input type="date" name="date_choisie" value="<?= safe($date_choisie) ?>" onchange="this.form.submit()">
        <?php endif; ?>

        <label>🎤 Audio : </label>
        <select name="filtre_audio" onchange="this.form.submit()" class="select">
            <option value="tous" <?= $filtre_audio == 'tous' ? 'selected' : '' ?>>Tous</option>
            <option value="avec" <?= $filtre_audio == 'avec' ? 'selected' : '' ?>>Avec audio</option>
            <option value="sans" <?= $filtre_audio == 'sans' ? 'selected' : '' ?>>Sans audio</option>
        </select>
    </form>

    <?php if ($res_attente->num_rows === 0): ?>
        <p>Aucune commande trouvée pour ce filtre.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Audio</th>
                    <th>Date prévue</th>
                    <th>Livreur</th>
                    <th>Temps (min)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($cmd = $res_attente->fetch_assoc()): 
                    $date_prevue = safe($cmd['date_livraison_prevue'] ?: '—');
                    $badge = '';
                    if (!empty($cmd['date_livraison_prevue']) && strtotime($cmd['date_livraison_prevue']) <= strtotime(date('Y-m-d'))) {
                        $badge = " <span class='badge-red'>⚠</span>";
                    }
                ?>
                <tr>
                    <td><?= $cmd['id'] ?></td>
                    <td>
                        <?= safe($cmd['client_nom'] ?? 'Anonyme') ?>
                        <?php if (!empty($cmd['client_invite'])): ?>
                            <span class="badge-invite">Invité</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($cmd['audio_path'])): ?>
                            <audio controls>
                                <source src="<?= safe($cmd['audio_path']) ?>" type="audio/mpeg">
                            </audio>
                        <?php else: ?>
                            ❌
                        <?php endif; ?>
                    </td>
                    <td><?= $date_prevue . $badge ?></td>
                    <td>
                        <form method="post" action="assigner_livreur.php">
                            <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                            <select name="livreur_id" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach ($livreurs as $id => $nom): ?>
                                    <option value="<?= $id ?>"><?= safe($nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                    </td>
                    <td><input type="number" name="temps_livraison" value="30" min="5" required></td>
                    <td>
                            <button type="submit" class="btn1">✅ Assigner</button>
                            <a href="details_commande.php?id=<?= $cmd['id'] ?>" class="btn1 view-btn">👁 Voir</a>
                            <a href="modifier_commande.php?id=<?= $cmd['id'] ?>" class="btn1 edit-btn">✏ Modifier</a>
                            <a href="supprimer_commande.php?id=<?= $cmd['id'] ?>" class="btn1 delete-btn" onclick="return confirm('⚠ Supprimer cette commande ?');">🗑 Supprimer</a>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
