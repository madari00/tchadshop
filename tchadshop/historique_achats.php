<?php
session_start();
$searchContext = 'achat';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// 🔥 Lire la langue par défaut depuis la configuration
$langQuery = $conn->query("SELECT valeur FROM configuration WHERE parametre = 'default_language' LIMIT 1");
$lang = $langQuery->fetch_assoc()['valeur'] ?? 'fr'; // fallback en français si vide

// 🔥 Charger les traductions
$translations = include 'traductions.php';
$t = $translations[$lang] ?? $translations['fr']; // fallback FR

// Récupérer paramètres de recherche et filtre
$search = $conn->real_escape_string($_GET['search'] ?? '');
$date_achat = $conn->real_escape_string($_GET['date_achat'] ?? '');
$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construire clause WHERE
$where = "1";

if (!empty($search)) {
    $where .= " AND COALESCE(c.nom, 'Anonyme') LIKE '%$search%'";
}
if (!empty($date_achat)) {
    $where .= " AND DATE(ha.date_achat) = '$date_achat'";
}

// Compter total d'achats
$count_sql = "SELECT COUNT(*) AS total FROM historique_achats ha
              LEFT JOIN clients c ON ha.client_id = c.id
              WHERE $where";
$count_resultat_achats = $conn->query($count_sql);
$total_historique_achat = $count_resultat_achats->fetch_assoc();
$total_achats = $total_historique_achat['total'];
$total_pages = ceil($total_achats / $per_page);

// Récupérer les achats avec infos, et si liés à commande, récupérer statut de la commande
$sql = "
SELECT ha.id,
       COALESCE(c.nom, 'Anonyme') AS client,
       ha.date_achat,
       ha.commande_id,
       com.statut
FROM historique_achats ha
LEFT JOIN clients c ON ha.client_id = c.id
LEFT JOIN commandes com ON ha.commande_id = com.id
WHERE $where
ORDER BY ha.date_achat DESC
LIMIT $per_page OFFSET $offset
";

$resultat_achats = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8" />
    <title><?= $t['title1']?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f8f8; }
        .main-content { background: #fff; padding: 20px;  border-radius: 8px; }
        h2 { color: #6a1b9a; }
        .add-btn { float: right; text-decoration: none; color: #fff; background: #4caf50; border-radius: 5px; padding: 10px 20px; }
        .filters input, .filters select { margin: 5px; padding: 6px; }
        .filters { margin-bottom: 15px; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: center; }
        th { background-color: #6a1b9a; color: #fff; }
        tr:hover { background-color: #f1f1f1; }
        a { text-decoration: none; }
        .action-btn { margin: 2px; padding: 5px 5px; border-radius: 5px; text-decoration: none; color: #fff; }
        .view-btn { background: #2196f3; }
        .edit-btn { background: #ff9800; }
        .delete-btn { background: #f44336; }
        .pagination { 
            text-align: center; 
            margin-top: 25px; 
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .pagination a { 
            padding: 8px 15px;
            text-decoration: none; 
            color: #6a1b9a; 
            font-weight: 500;
            border: 1px solid #d9d0e9;
            border-radius: 6px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .pagination a:hover {
            background: #f1e8ff;
        }
        
        .pagination a.active { 
            background: #6a1b9a; 
            color: #fff; 
            border-color: #6a1b9a;
        }
        
        .no-results {
            text-align: center;
            padding: 30px;
            color: #757575;
            font-size: 18px;
        }
        
        .no-results i {
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
            color: #bdbdbd;
        }
        
        .stat-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .stat-commande {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .stat-sans-commande {
            background: #fff8e1;
            color: #f57f17;
        }
        
        .summary {
            margin-bottom: 15px; 
            padding: 15px; 
            background: #e8f5e9; 
            border-radius: 8px; 
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
            
            .add-btn {
                float: none;
                margin-bottom: 15px;
                width: 100%;
                justify-content: center;
            }
            
            .summary {
                flex-direction: column;
                gap: 10px;
            }
        }
        .button {
            background:rgb(212, 54, 244);
            width: 100px;
            height: 40px;
            border: none;
            color: white;
            font-size: 20px;
            border-radius: 10px;
        }
        .select, .filters input {
            border: 2px solid rgb(212, 54, 244);
        }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="home-content">
<div class="main-content">
    <h2>📜 <?= $t['page_title1']?></h2><br>
    <a href="ajouter_achat.php" class="add-btn">➕ <?= $t['add_purchase1']?></a>

    <!-- Filtres de recherche -->
    <form method="GET" class="filters">
 
        <input type="date" name="date_achat" value="<?= htmlspecialchars($date_achat) ?>" />
        <button type="submit" class="button"><?= $t['filter1']?></button>
    </form>

    <!-- Tableau -->
    <table>
        <thead>
            <tr>
                <th><?= $t['purchase_id1']?></th>
                <th><?= $t['client1']?></th>
                <th><?= $t['purchase_date1']?></th>
                <th><?= $t['order_status1']?></th>
                <th><?= $t['purchase_type1']?></th>
                <th><?= $t['actions1']?></th>
            </tr>
        </thead>
        <tbody>
            <?php while ($historique_achat = $resultat_achats->fetch_assoc()): ?>
            <tr>
                <td><?= $historique_achat['id'] ?></td>
                <td><?= htmlspecialchars($historique_achat['client']) ?></td>
                <td><?= $historique_achat['date_achat'] ?></td>
                <td><?= $historique_achat['commande_id'] ? ucfirst($historique_achat['statut']) : '-' ?></td>
                <td>
                            <span class="stat-badge <?= $historique_achat['commande_id'] ? 'stat-commande' : 'stat-sans-commande' ?>">
                                <?= $historique_achat['commande_id'] ? $t['with_order1'] : $t['without_order1'] ?>
                            </span>
                        </td>
                <td>
                    <a href="details_achat.php?id=<?= $historique_achat['id'] ?>" class="action-btn view-btn">👁 <?= $t['view1']?></a> |
                    <a href="modifier_achat.php?id=<?= $historique_achat['id'] ?>" class="action-btn edit-btn">✏ <?= $t['edit1']?></a> |
                    <a href="supprimer_achat.php?id=<?= $historique_achat['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Confirmer la suppression ?');">🗑 <?= $t['delete1']?></a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($total_achats == 0): ?>
            <tr><td colspan="6"><?= $t['no_results1']?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?search=<?= urlencode($search) ?>&date_achat=<?= urlencode($date_achat) ?>&page=<?= $page - 1 ?>">
                    ← <?php echo $t['previous1']; ?>
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                <a href="?search=<?= urlencode($search) ?>&date_achat=<?= urlencode($date_achat) ?>&page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?search=<?= urlencode($search) ?>&date_achat=<?= urlencode($date_achat) ?>&page=<?= $page + 1 ?>">
                    <?php echo $t['next1']; ?> →
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Confirmation avant suppression
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('<?php echo $t['confirm_delete1']; ?>')) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>
