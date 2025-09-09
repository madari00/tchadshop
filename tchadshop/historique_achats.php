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

// CORRECTION: Récupérer les achats avec info sur les promotions
$sql = "
SELECT 
    ha.id,
    COALESCE(c.nom, 'Anonyme') AS client,
    ha.date_achat,
    ha.commande_id,
    com.statut,
    p.nom AS produit_nom,
    p.promotion,
    p.date_debut_promo,
    p.date_fin_promo,
    ha.prix_unitaire,
    p.prix AS prix_original,
    p.prix_promotion AS prix_promo
FROM historique_achats ha
LEFT JOIN clients c ON ha.client_id = c.id
LEFT JOIN commandes com ON ha.commande_id = com.id
LEFT JOIN produits p ON ha.produit_id = p.id
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
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f8f8f8; 
            margin: 0;
            padding: 0;
            color: #333;
        }
        .home-content {
            padding: 20px;
        }
        .main-content { 
            background: #fff; 
            padding: 25px;  
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        h2 { 
            color: #6a1b9a; 
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 28px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .add-btn { 
            float: right; 
            text-decoration: none; 
            color: #fff; 
            background: #4caf50; 
            border-radius: 8px; 
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: -95px; 
        }
        .add-btn:hover {
            background: #3d8b40;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .filters { 
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin: 25px 0;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 10px;
        }
        .filters label {
            font-weight: 500;
            color: #555;
        }
        .filters input, .filters select { 
            padding: 10px 15px; 
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .filters input:focus {
            border-color: #6a1b9a;
            outline: none;
            box-shadow: 0 0 0 2px rgba(106, 27, 154, 0.2);
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-top: -95px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        th, td { 
            padding: 14px 18px; 
            text-align: left; 
            border-bottom: 1px solid #eee;
        }
        th { 
            background-color: #6a1b9a; 
            color: #fff; 
            font-weight: 600;
            font-size: 15px;
        }
        tr:nth-child(even) { 
            background-color: #fafafa; 
        }
        tr:hover { 
            background-color: #f5f0ff; 
        }
        a { 
            text-decoration: none; 
        }
        .action-btn { 
            padding: 8px 14px; 
            border-radius: 6px; 
            text-decoration: none; 
            color: #fff;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            margin: 3px;
            font-weight: 500;
        }
        .view-btn { 
            background: #2196f3; 
        }
        .view-btn:hover {
            background: #0d8bf2;
            transform: translateY(-2px);
        }
        .edit-btn { 
            background: #ff9800; 
        }
        .edit-btn:hover {
            background: #e68a00;
            transform: translateY(-2px);
        }
        .delete-btn { 
            background: #f44336; 
        }
        .delete-btn:hover {
            background: #e53935;
            transform: translateY(-2px);
        }
        .pagination { 
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 35px;
            flex-wrap: wrap;
        }
        .pagination a { 
            padding: 10px 18px;
            text-decoration: none; 
            color: #6a1b9a; 
            font-weight: 600;
            border: 1px solid #e0d0f0;
            border-radius: 8px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
        }
        .pagination a:hover {
            background: #f1e8ff;
            transform: translateY(-2px);
        }
        .pagination a.active { 
            background: #6a1b9a; 
            color: #fff; 
            border-color: #6a1b9a;
            box-shadow: 0 2px 5px rgba(106, 27, 154, 0.3);
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #757575;
            font-size: 18px;
            background: #fafafa;
            border-radius: 10px;
            margin: 20px 0;
        }
        .no-results i {
            font-size: 60px;
            display: block;
            margin-bottom: 20px;
            color: #d9d9d9;
        }
        .stat-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        .stat-commande {
            background: #e3f2fd;
            color: #1976d2;
        }
        .stat-sans-commande {
            background: #fff8e1;
            color: #f57f17;
        }
        .promo-badge {
            background: linear-gradient(135deg, #ff4081, #e91e63);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 5px;
            box-shadow: 0 2px 5px rgba(233, 30, 99, 0.2);
        }
        .button {
            background: linear-gradient(135deg, #6a1b9a, #4a148c);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .button:hover {
            background: linear-gradient(135deg, #581c87, #3c0d70);
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .summary {
            background: linear-gradient(135deg, #f5f0ff, #e8e2ff);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 3px 8px rgba(106, 27, 154, 0.1);
            border: 1px solid #eee7ff;
        }
        .summary-item {
            text-align: center;
            padding: 10px 20px;
            min-width: 150px;
        }
        .summary-value {
            font-size: 32px;
            font-weight: 700;
            color: #6a1b9a;
            margin-bottom: 8px;
        }
        .summary-label {
            font-size: 15px;
            color: #7b6d94;
            font-weight: 500;
        }
        .price-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-top: 8px;
        }
        .original-price {
            text-decoration: line-through;
            color: #888;
            font-size: 14px;
        }
        .discount-price {
            color: #e91e63;
            font-weight: 600;
            font-size: 16px;
        }
        .discount-percent {
            background: #ffebee;
            color: #e91e63;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 5px;
        }
        .product-name {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        .promo-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e91e63;
            display: inline-block;
            margin-right: 8px;
            box-shadow: 0 0 8px rgba(233, 30, 99, 0.4);
        }
        .actions-cell {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            .filters > div {
                width: 100%;
            }
            table {
                display: block;
                overflow-x: auto;
            }
            .add-btn {
                float: none;
                margin-top: 15px;
                width: 100%;
                justify-content: center;
                margin-bottom: 15px;
            }
            .summary {
                flex-direction: column;
                gap: 15px;
            }
            .summary-item {
                width: 100%;
            }
            .actions-cell {
                flex-direction: column;
            }
            .action-btn {
                width: 100%;
                justify-content: center;
            }
            h2 {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="home-content">
    <div class="main-content">
        <h2>
            <span>📜</span> <?= $t['page_title1']?>
            
        </h2>
        
        <!-- Résumé des achats -->
        <div class="summary">
            <div class="summary-item">
                <div class="summary-value"><?= $total_achats ?></div>
                <div class="summary-label">Total des achats</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?= $total_pages ?></div>
                <div class="summary-label">Pages</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?= $page ?></div>
                <div class="summary-label">Page actuelle</div>
            </div>
        </div>

        <!-- Filtres de recherche -->
        <form method="GET" class="filters">
            
            <div>
                <label>Date d'achat:</label>
                <input type="date" name="date_achat" value="<?= htmlspecialchars($date_achat) ?>" />
            </div>
            <div>
                <button type="submit" class="button">
                    <span>🔍</span> <?= $t['filter1']?>
                </button>
            </div>
        </form>
         <a href="ajouter_achat.php" class="add-btn">
                <span>➕</span> <?= $t['add_purchase1']?>
            </a><br>
        <!-- Tableau -->
        <table>
            <thead>
                <tr>
                    <th><?= $t['purchase_id1']?></th>
                    <th><?= $t['client1']?></th>
                    <th><?= $t['purchase_date1']?></th>
                    <th>Produit</th>
                    <th>Prix</th>
                    
                    <th><?= $t['purchase_type1']?></th>
                    <th>Promotion</th>
                    <th><?= $t['actions1']?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_achats > 0): ?>
                    <?php while ($achat = $resultat_achats->fetch_assoc()): 
                        // Déterminer si le produit était en promotion au moment de l'achat
                        $dateAchat = strtotime($achat['date_achat']);
                        $dateDebutPromo = $achat['date_debut_promo'] ? strtotime($achat['date_debut_promo']) : null;
                        $dateFinPromo = $achat['date_fin_promo'] ? strtotime($achat['date_fin_promo']) : null;
                        
                        $estEnPromotion = false;
                        $prixOriginal = $achat['prix_original'];
                        $prixPromo = $achat['prix_promo'];
                        
                        if ($achat['promotion'] > 0 && $dateDebutPromo && $dateFinPromo) {
                            if ($dateAchat >= $dateDebutPromo && $dateAchat <= $dateFinPromo) {
                                $estEnPromotion = true;
                                $prixPromo = $prixOriginal * (1 - ($achat['promotion'] / 100));
                            }
                        }
                    ?>
                        <tr>
                            <td><?= $achat['id'] ?></td>
                            <td><?= htmlspecialchars($achat['client']) ?></td>
                            <td><?= date('d/m/Y H:i', $dateAchat) ?></td>
                            <td>
                                <div class="product-name">
                                    <?php if ($estEnPromotion): ?>
                                        <span class="promo-indicator"></span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($achat['produit_nom'] ?? 'Produit inconnu') ?>
                                </div>
                            </td>
                            <td>
                                <div class="price-info">
                                    <?php if ($estEnPromotion): ?>
                                        <span class="original-price"><?= number_format($prixOriginal, 2) ?> CFA</span>
                                        <span class="discount-price"><?= number_format($prixPromo, 2) ?> CFA</span>
                                    <?php else: ?>
                                        <span><?= number_format($achat['prix_unitaire'], 2) ?> CFA</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                           
                            <td>
                                <span class="stat-badge <?= $achat['commande_id'] ? 'stat-commande' : 'stat-sans-commande' ?>">
                                    <?= $achat['commande_id'] ? $t['with_order1'] : $t['without_order1'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($estEnPromotion): ?>
                                    <span class="promo-badge">
                                        <?= $achat['promotion'] ?>% OFF
                                        <span class="discount-percent">Économie: <?= number_format($prixOriginal - $prixPromo, 2) ?> CFA</span>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <a href="details_achat.php?id=<?= $achat['id'] ?>" class="action-btn view-btn">
                                    <span>👁</span> <?= $t['view1']?>
                                </a>
                                <a href="modifier_achat.php?id=<?= $achat['id'] ?>" class="action-btn edit-btn">
                                    <span>✏</span> <?= $t['edit1']?>
                                </a>
                                <a href="supprimer_achat.php?id=<?= $achat['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Confirmer la suppression ?');">
                                    <span>🗑</span> <?= $t['delete1']?>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="no-results">
                            <div>📭</div>
                            <?= $t['no_results1']?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?search=<?= urlencode($search) ?>&date_achat=<?= urlencode($date_achat) ?>&page=<?= $page - 1 ?>">
                        ← <?= $t['previous1']?>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                    <a href="?search=<?= urlencode($search) ?>&date_achat=<?= urlencode($date_achat) ?>&page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?search=<?= urlencode($search) ?>&date_achat=<?= urlencode($date_achat) ?>&page=<?= $page + 1 ?>">
                        <?= $t['next1']?> →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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