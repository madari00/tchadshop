<?php 
session_start();
$searchContext = 'tcommande';

$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// ---- Paramètres de pagination ----
$par_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$debut = ($page - 1) * $par_page;

// ---- Paramètres de recherche et filtre ----
$search = $conn->real_escape_string($_GET['search'] ?? '');
$filtre_statut = $conn->real_escape_string($_GET['filtre_statut'] ?? '');

// ---- Construction de la requête WHERE ----
$where = "1"; // par défaut
if (!empty($search)) {
    $where .= " AND (cl.nom LIKE '%$search%' OR c.id LIKE '%$search%' OR c.date_commande LIKE '%$search%' OR cl.telephone LIKE '%$search%')";
}
if (!empty($filtre_statut)) {
    $where .= " AND c.statut = '$filtre_statut'";
}

// ---- Compter le nombre total de commandes ----
$total_req = $conn->query("SELECT COUNT(*) AS total FROM commandes c LEFT JOIN clients cl ON c.client_id = cl.id WHERE $where");
$total_lignes = $total_req->fetch_assoc()['total'];
$total_pages = ceil($total_lignes / $par_page);

// ---- Récupérer les commandes ----
$sql = "SELECT 
            c.id, 
            c.total, 
            c.statut, 
            c.date_commande, 
            c.date_livraison_prevue,
            c.adresse,
            c.latitude,
            c.longitude,
            c.audio_path, -- ✅ Ajout audio
            cl.nom AS client_nom, 
            cl.telephone AS client_tel,
            cl.invite AS client_invite, -- ✅ Si invité
            l.nom AS livreur_nom
        FROM commandes c
        LEFT JOIN clients cl ON c.client_id = cl.id
        LEFT JOIN livreurs l ON c.livreur_id = l.id
        WHERE $where
        ORDER BY c.date_commande DESC
        LIMIT $debut, $par_page";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Toutes les commandes</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #6a1b9a;
            color: #fff;
        }
        .pagination {
            margin-top: 15px;
            text-align: center;
        }
        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            padding: 5px 10px;
            background: #f2f2f2;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
        }
        .pagination a.active {
            background: #007bff;
            color: #fff;
        }
        .filters {
            margin-top: 20px;
            text-align: center;
        }
        .filters input, .filters select {
            padding: 5px;
            margin-right: 10px;
        }
        .action-btn {
            padding: 5px 8px;
            margin: 1px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: #fff;
            text-decoration: none;
        }
        .edit-btn { background-color: #007bff; }
        .view-btn { background-color: #17a2b8; }
        .delete-btn { background-color: #dc3545; }
        .map-btn { background-color: #28a745; }
        .audio-btn { background-color: #ff5722; }
        .badge-invite {
            background: purple;
            color: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 5px;
        }
        h2{
            color: rgb(212, 54, 244);
            margin-left: 20px;
        }
        .button {
            background: rgb(212, 54, 244);
            width: 100px;
            height: 30px;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
        <h2>Toutes les commandes</h2>

        <div class="filters">
            <form method="get" action="">
                <select name="filtre_statut" class="select">
                    <option value="">-- Filtrer par statut --</option>
                    <option value="en attente" <?= $filtre_statut=='en attente'?'selected':'' ?>>En attente</option>
                    <option value="en cours" <?= $filtre_statut=='en cours'?'selected':'' ?>>En cours</option>
                    <option value="livré" <?= $filtre_statut=='livré'?'selected':'' ?>>Livré</option>
                    <option value="échec" <?= $filtre_statut=='échec'?'selected':'' ?>>Échec</option>
                </select>
                <button type="submit" class="button">🔍 Filtrer</button>
            </form>
        </div>
        <a href="ajouter_commande.php" style="display:inline-block;padding:8px 12px;background:#28a745;color:#fff;border-radius:4px;text-decoration:none;margin-bottom:10px;float:right">➕ Ajouter Commande</a>
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Livreur</th>
                    <th>Statut</th>
                    <th>Date commande</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($row['client_invite']): ?>
                                    <span class="badge-invite">Invité</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($row['client_nom'] ?? '') ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($row['client_tel']): ?>
                                    <a href="https://wa.me/<?= htmlspecialchars($row['client_tel']) ?>" target="_blank">
                                        📱 <?= htmlspecialchars($row['client_tel']) ?> 💬
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['livreur_nom'] ?? 'Non assigné') ?></td>
                            <td><?= ucfirst($row['statut']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['date_commande'])) ?></td>
                            <td>
                                <a href="modifier_commande.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">📝 Modifier</a>
                                <a href="details_commande.php?id=<?= $row['id'] ?>" class="action-btn view-btn">👁 Voir</a>
                                <?php if ($row['audio_path']): ?>
                                    <a href="<?= htmlspecialchars($row['audio_path']) ?>" target="_blank" class="action-btn audio-btn">🎤 Écouter</a>
                                <?php endif; ?>
                                <?php if ($row['latitude'] && $row['longitude']): ?>
                                    <a href="voir_position.php?id=<?= $row['id'] ?>" target="_blank" class="action-btn map-btn">📍 Carte</a>
                                <?php endif; ?>
                                <a href="supprimer_commande.php?id=<?= $row['id'] ?>" class="action-btn delete-btn" onclick="return confirm('⚠️ Confirmer la suppression ?');">🗑 Supprimer</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">Aucune commande trouvée.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filtre_statut=<?= urlencode($filtre_statut) ?>" class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>

        <br>
        <a href="dashboard.php">⬅ Retour au tableau de bord</a>
    </div>
</body>
</html>
