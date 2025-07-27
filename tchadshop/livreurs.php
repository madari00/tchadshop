<?php
session_start();
$searchContext = 'livreur';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$search = $_GET['search'] ?? '';

// 🔥 Lire la langue par défaut depuis la configuration
$langQuery = $conn->query("SELECT valeur FROM configuration WHERE parametre = 'default_language' LIMIT 1");
$lang = $langQuery->fetch_assoc()['valeur'] ?? 'fr'; // fallback en français si vide

// 🔥 Charger les traductions
$translations = include 'traductions.php';
$t = $translations[$lang] ?? $translations['fr'];

// Pagination
$parPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page - 1) * $parPage;

// Compter le total (avec ou sans filtre)
if (!empty($search)) {
    $like = "%$search%";
    $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM livreurs WHERE nom LIKE ? OR email LIKE ? OR telephone LIKE ?");
    $stmtCount->bind_param("sss", $like, $like, $like);
    $stmtCount->execute();
    $totalLivreurs = $stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    $sql = "SELECT * FROM livreurs WHERE nom LIKE ? OR email LIKE ? OR telephone LIKE ? ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $like, $like, $like, $start, $parPage);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $resCount = $conn->query("SELECT COUNT(*) AS total FROM livreurs");
    $totalLivreurs = $resCount->fetch_assoc()['total'];

    $sql = "SELECT * FROM livreurs ORDER BY created_at DESC LIMIT $start, $parPage";
    $result = $conn->query($sql);
}

$totalPages = ceil($totalLivreurs / $parPage);

function safe($val) {
    return htmlspecialchars($val ?? '');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $t['Gestion_des_livreurs4']?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .btn1 { float: right; text-decoration: none; color: #fff; background: #4caf50; border-radius: 5px; padding: 10px 20px; }
        .btn1:hover { background: #0056b3; }
        .action-btn { margin: 2px; padding: 5px 10px; border-radius: 5px; text-decoration: none; color: #fff; }
       
        .edit-btn { background: #ff9800; }
        .delete-btn { background: #f44336; }
        .search-box { margin-bottom: 15px; }
        .pagination { margin: 15px 0; text-align: center; }
        .pagination a {
            margin: 0 5px; padding: 5px 10px;
            border: 1px solid #007bff; color: #007bff; text-decoration: none; border-radius: 4px;
        }
        .pagination a.active { background: #007bff; color: white; }
        h2{
           color: #6a1b9a; 
           margin: 20px;
        }
        table th { background: #6a1b9a; color: #fff; }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
<div class="home-content">
    <h2><?= $t['Liste_des_livreurs4']?></h2>
    <a href="ajouter_livreur.php" class="btn1"><?= $t['Ajouter_un_livreur4']?></a>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= $t['#ID3']?></th>
                    <th><?= $t['Nom3']?></th>
                    <th><?= $t['Email3']?></th>
                    <th><?= $t['Téléphone3']?></th>
                    <th><?= $t['Actif4']?></th>
                    <th><?= $t['Date_inscription4']?></th>
                    <th><?= $t['Actions3']?></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($livreur = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $livreur['id'] ?></td>
                        <td><?= safe($livreur['nom']) ?></td>
                        <td><?= safe($livreur['email'] ?? '') ?></td>
                        <td><?= safe($livreur['telephone']) ?></td>
                        <td><?= $livreur['actif'] ? '✅ '.$t['Oui4'] : '❌ '.$t['Non4'] ?></td>
                        <td><?= $livreur['created_at'] ?></td>
                        <td>
                            <a href="modifier_livreur.php?id=<?= $livreur['id'] ?>" class="action-btn edit-btn"><?= $t['Modifier3']?></a>
                            <a href="supprimer_livreur.php?id=<?= $livreur['id'] ?>" class="action-btn delete-btn" onclick="return confirm('<?= $t['Supprimer_ce_livreur4']?>')"><?= $t['Supprimer3']?></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">&laquo; <?= $t['précédent3']?></a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>" class="<?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"><?= $t['Suivant3']?> &raquo;</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p><?= $t['Aucun_livreur_trouve4']?></p>
    <?php endif; ?>

    
</body>
</html>
