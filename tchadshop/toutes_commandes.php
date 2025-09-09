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
$date_commande = $conn->real_escape_string($_GET['date_commande'] ?? '');
$filtre_promo = $conn->real_escape_string($_GET['filtre_promo'] ?? '');

// ---- Construction de la requête WHERE ----
$where = "1"; // par défaut
if (!empty($search)) {
    $where .= " AND (cl.nom LIKE '%$search%' OR c.id LIKE '%$search%' OR c.date_commande LIKE '%$search%' OR cl.telephone LIKE '%$search%')";
}
if (!empty($filtre_statut)) {
    $where .= " AND c.statut = '$filtre_statut'";
}
if (!empty($date_commande)) {
    $where .= " AND DATE(c.date_commande) = '$date_commande'";
}

// Ajouter le filtre promotion à la requête SQL
if (!empty($filtre_promo)) {
    if ($filtre_promo == 'oui') {
        $where .= " AND EXISTS (SELECT 1 FROM details_commandes dc WHERE dc.commande_id = c.id AND dc.promotion = 1)";
    } elseif ($filtre_promo == 'non') {
        $where .= " AND NOT EXISTS (SELECT 1 FROM details_commandes dc WHERE dc.commande_id = c.id AND dc.promotion = 1)";
    }
}

// ---- Compter le nombre total de commandes ----
$total_req = $conn->query("SELECT COUNT(*) AS total FROM commandes c LEFT JOIN clients cl ON c.client_id = cl.id WHERE $where");
$total_lignes = $total_req->fetch_assoc()['total'];
$total_pages = ceil($total_lignes / $par_page);

// ---- Récupérer les commandes avec information sur les promotions ----
$sql = "SELECT 
            c.id, 
            c.total, 
            c.statut, 
            c.date_commande, 
            c.date_livraison_prevue,
            c.adresse,
            c.latitude,
            c.longitude,
            c.audio_path,
            cl.nom AS client_nom, 
            cl.telephone AS client_tel,
            cl.invite AS client_invite,
            l.nom AS livreur_nom,
            EXISTS (SELECT 1 FROM details_commandes dc WHERE dc.commande_id = c.id AND dc.promotion = 1) AS a_promotion
        FROM commandes c
        LEFT JOIN clients cl ON c.client_id = cl.id
        LEFT JOIN livreurs l ON c.livreur_id = l.id
        WHERE $where
        ORDER BY c.date_commande DESC
        LIMIT $debut, $par_page";

$result = $conn->query($sql);
$commandes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $commandes[] = $row;
    }
}
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
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #6a1b9a;
        }
        .filters input, .filters select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-buttons {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }
        .action-btn {
            padding: 5px 8px;
            margin: 1px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: #fff;
            text-decoration: none;
            display: inline-block;
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
        .badge-promo {
            background: linear-gradient(135deg, #ff4081, #e91e63);
            color: #fff;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2{
            color: rgb(212, 54, 244);
            margin-left: 20px;
        }
        .button {
            background: rgb(212, 54, 244);
            padding: 8px 15px;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }
        .button:hover {
            background: rgb(192, 34, 224);
        }
        .reset-btn {
            background: #6c757d;
            padding: 8px 15px;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .reset-btn:hover {
            background: #5a6268;
        }
        .stats {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 200px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #6a1b9a;
        }
        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
        <h2>Toutes les commandes</h2>

        <!-- Statistiques rapides -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?= $total_lignes ?></div>
                <div class="stat-label">Total commandes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $total_pages ?></div>
                <div class="stat-label">Pages</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $page ?></div>
                <div class="stat-label">Page actuelle</div>
            </div>
        </div>

        <!-- Filtres -->
        <form method="get" action="">
            <div class="filters">
                
                
                <div class="filter-group">
                    <label for="filtre_statut">Statut:</label>
                    <select name="filtre_statut" id="filtre_statut">
                        <option value="">Tous les statuts</option>
                        <option value="en attente" <?= $filtre_statut=='en attente'?'selected':'' ?>>En attente</option>
                        <option value="en cours" <?= $filtre_statut=='en cours'?'selected':'' ?>>En cours</option>
                        <option value="livré" <?= $filtre_statut=='livré'?'selected':'' ?>>Livré</option>
                        <option value="échec" <?= $filtre_statut=='échec'?'selected':'' ?>>Échec</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_commande">Date commande:</label>
                    <input type="date" name="date_commande" id="date_commande" value="<?= htmlspecialchars($date_commande) ?>">
                </div>
                
                <div class="filter-group">
                    <label for="filtre_promo">Promotion:</label>
                    <select name="filtre_promo" id="filtre_promo">
                        <option value="">Avec ou sans promo</option>
                        <option value="oui" <?= $filtre_promo=='oui'?'selected':'' ?>>Avec promotion</option>
                        <option value="non" <?= $filtre_promo=='non'?'selected':'' ?>>Sans promotion</option>
                    </select>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="button">🔍 Filtrer</button>
                    <a href="toutes_commandes.php" class="reset-btn">🔄 Réinitialiser</a>
                </div>
            </div>
        </form>

        <a href="ajouter_commande.php" style="display:inline-block;padding:8px 12px;background:#28a745;color:#fff;border-radius:4px;text-decoration:none;margin-bottom:10px;float:right">➕ Ajouter Commande</a>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Livreur</th>
                    <th>Statut</th>
                    <th>Date commande</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($commandes)): ?>
                    <?php foreach ($commandes as $row): ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td>
                                <?php if ($row['client_invite']): ?>
                                    <span class="badge-invite">Invité</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($row['client_nom'] ?? '') ?>
                                <?php endif; ?>
                                <?php if ($row['a_promotion']): ?>
                                    <span class="badge-promo">PROMO</span>
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
                            <td><?= number_format($row['total'], 0, ',', ' ') ?> FCFA</td>
                            <td>
                                <a href="modifier_commande.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">📝</a>
                                <a href="details_commande.php?id=<?= $row['id'] ?>" class="action-btn view-btn">👁</a>
                                <?php if ($row['audio_path']): ?>
                                    <a href="<?= htmlspecialchars($row['audio_path']) ?>" target="_blank" class="action-btn audio-btn">🎤</a>
                                <?php endif; ?>
                                <?php if ($row['latitude'] && $row['longitude']): ?>
                                    <a href="voir_position.php?id=<?= $row['id'] ?>" target="_blank" class="action-btn map-btn">📍</a>
                                <?php endif; ?>
                                <a href="supprimer_commande.php?id=<?= $row['id'] ?>" class="action-btn delete-btn" onclick="return confirm('⚠️ Confirmer la suppression ?');">🗑</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center; padding: 20px;">Aucune commande trouvée.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filtre_statut=<?= urlencode($filtre_statut) ?>&date_commande=<?= urlencode($date_commande) ?>&filtre_promo=<?= urlencode($filtre_promo) ?>" class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>

        <br>
        <a href="dashboard.php">⬅ Retour au tableau de bord</a>
    </div>
</body>
</html>