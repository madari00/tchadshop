<?php
session_start();
$searchContext = '';
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

// ---- Statistiques générales ----
$totalCommandes = $conn->query("SELECT COUNT(*) FROM commandes")->fetch_row()[0];
$totalVentes = $conn->query("SELECT SUM(prix_unitaire * quantite) FROM historique_achats")->fetch_row()[0] ?? 0;
$totalProfits = $totalVentes * 0.2;
$totalRevenus = $totalVentes - $totalProfits;
$totalClients = $conn->query("SELECT COUNT(*) FROM clients")->fetch_row()[0];
$totalLivreurs = $conn->query("SELECT COUNT(*) FROM livreurs")->fetch_row()[0];

// ---- Pagination Ventes Récentes ----
$ventesPage = isset($_GET['ventesPage']) ? (int)$_GET['ventesPage'] : 1;
$ventesLimit = 10;
$ventesOffset = ($ventesPage - 1) * $ventesLimit;
$totalVentesRows = $conn->query("SELECT COUNT(*) FROM historique_achats")->fetch_row()[0];
$totalVentesPages = ceil($totalVentesRows / $ventesLimit);
$ventes = $conn->query("
    SELECT ha.date_achat, c.nom AS client, p.nom AS produit, ha.prix_unitaire
    FROM historique_achats ha
    LEFT JOIN clients c ON ha.client_id = c.id
    LEFT JOIN produits p ON ha.produit_id = p.id
    ORDER BY ha.date_achat DESC
    LIMIT $ventesLimit OFFSET $ventesOffset
");

// ---- Pagination Produits les plus vendus ----
$produitsPage = isset($_GET['produitsPage']) ? (int)$_GET['produitsPage'] : 1;
$produitsLimit = 10;
$produitsOffset = ($produitsPage - 1) * $produitsLimit;
$totalProduitsRows = $conn->query("SELECT COUNT(DISTINCT produit_id) FROM historique_achats")->fetch_row()[0];
$totalProduitsPages = ceil($totalProduitsRows / $produitsLimit);
$topProduits = $conn->query("
    SELECT p.nom, SUM(ha.quantite) AS total_vendu, SUM(ha.prix_unitaire * ha.quantite) AS total_revenu
    FROM historique_achats ha
    LEFT JOIN produits p ON ha.produit_id = p.id
    GROUP BY p.id
    ORDER BY total_vendu DESC
    LIMIT $produitsLimit OFFSET $produitsOffset
");
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8" />
    <title><?= $t['title'] ?></title>
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
       .tables-container {
            /*display: flex;*/
            justify-content: space-between;
            margin-top: 20px;
            gap: 20px;
             width: 100%;
        }
        .table-box {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            width: 100%;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #764097ff;
            color: white;
        }
        .pagination {
            margin-top: 10px;
            text-align: center;
        }
        .pagination a {
            color: #6a1b9a;
            padding: 5px 10px;
            text-decoration: none;
            border: 1px solid #6a1b9a;
            border-radius: 5px;
            margin: 0 2px;
        }
        .pagination a.active {
            background: #6a1b9a;
            color: white;
        }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="home-content">
    <!-- Les 6 boîtes en 3 sur 3 -->
    <div class="overview-boxes">
        <div class="box">
            <div class="right-side">
                <div class="box-topic"><?= $t['orders'] ?></div>
                <div class="number"><?= $totalCommandes ?></div>
                <div class="indicator">
                    <i class="bx bx-up-arrow-alt"></i> <?= $t['total'] ?>
                </div>
            </div>
            <i class="fas fa-shopping-cart cart"></i>
        </div>
        <div class="box">
            <div class="right-side">
                <div class="box-topic"><?= $t['sales'] ?></div>
                <div class="number"><?= number_format($totalVentes, 0, ',', ' ') ?> </div>
                <div class="indicator">
                    <i class="bx bx-up-arrow-alt"></i> <?= $t['cumulative'] ?>
                </div>
            </div>
            <i class="fas fa-cash-register cart two"></i>
        </div>
        <div class="box">
            <div class="right-side">
                <div class="box-topic"><?= $t['profits'] ?></div>
                <div class="number"><?= number_format($totalProfits, 0, ',', ' ') ?> </div>
                <div class="indicator">
                    <i class="bx bx-up-arrow-alt"></i> <?= $t['estimated'] ?>
                </div>
            </div>
            <i class="fas fa-chart-line cart three"></i>
        </div>
        <div class="box">
            <div class="right-side">
                <div class="box-topic"><?= $t['revenues'] ?></div>
                <div class="number"><?= number_format($totalRevenus, 0, ',', ' ') ?> </div>
                <div class="indicator">
                    <i class="bx bx-down-arrow-alt down"></i> <?= $t['net'] ?>
                </div>
            </div>
            <i class="fas fa-wallet cart four"></i>
        </div>
        <div class="box">
            <div class="right-side">
                <div class="box-topic"><?= $t['customers'] ?></div>
                <div class="number"><?= $totalClients ?></div>
                <div class="indicator">
                    <i class="bx bx-user"></i> <?= $t['registered_total'] ?>
                </div>
            </div>
            <i class="fas fa-users cart"></i>
        </div>
        <div class="box">
            <div class="right-side">
                <div class="box-topic"><?= $t['delivery_men'] ?></div>
                <div class="number"><?= $totalLivreurs ?></div>
                <div class="indicator">
                    <i class="bx bx-bicycle"></i> <?= $t['active'] ?>
                </div>
            </div>
            <i class="fas fa-motorcycle cart"></i>
        </div>
    </div>

    <!-- Deux tableaux côte à côte -->
    <div class="tables-container">
        <!-- Ventes Récentes -->
        <div class="table-box">
            <h2><?= $t['recent_sales'] ?></h2>
            <table>
                <thead>
                    <tr>
                        <th><?= $t['date'] ?></th>
                        <th><?= $t['client'] ?></th>
                        <th><?= $t['product'] ?></th>
                        <th><?= $t['price'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $ventes->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['date_achat']) ?></td>
                        <td><?= htmlspecialchars($row['client'] ?? 'Anonyme') ?></td>
                        <td><?= htmlspecialchars($row['produit']) ?></td>
                        <td><?= number_format($row['prix_unitaire'], 0, ',', ' ') ?> <?= $t['currency'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <!-- Pagination Ventes Récentes -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalVentesPages; $i++): ?>
                    <a href="?ventesPage=<?= $i ?>&produitsPage=<?= $produitsPage ?>" <?= $i == $ventesPage ? 'class="active"' : '' ?>><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Produits les plus vendus -->
        <div class="table-box">
            <h2><?= $t['top_products'] ?></h2>
            <table>
                <thead>
                    <tr>
                        <th><?= $t['product'] ?></th>
                        <th><?= $t['quantity'] ?></th>
                        <th><?= $t['revenue'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $topProduits->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nom']) ?></td>
                        <td><?= $row['total_vendu'] ?></td>
                        <td><?= number_format($row['total_revenu'], 0, ',', ' ') ?> <?= $t['currency'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <!-- Pagination Produits les plus vendus -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalProduitsPages; $i++): ?>
                    <a href="?produitsPage=<?= $i ?>&ventesPage=<?= $ventesPage ?>" <?= $i == $produitsPage ? 'class="active"' : '' ?>><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
<script>
document.querySelector('.search-box input').addEventListener('keyup', function() {
    let search = this.value;

    fetch('search_dashboard.php?query=' + encodeURIComponent(search))
        .then(response => response.text())
        .then(data => {
            document.querySelector('.tables-container').innerHTML = data;
        });
});
</script>

</body>
</html>
