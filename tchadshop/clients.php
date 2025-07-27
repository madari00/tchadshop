<?php
session_start();
$searchContext = 'client';
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
$parPage = 10; // 10 clients par page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page - 1) * $parPage;

// Compter le nombre total de clients
$resCount = $conn->query("SELECT COUNT(*) AS total FROM clients");
$totalClients = $resCount->fetch_assoc()['total'];
$totalPages = ceil($totalClients / $parPage);

// 🔎 Préparer la requête
$sql = "SELECT * FROM clients";
if (!empty($search)) {
    $sql .= " WHERE nom LIKE ? OR email LIKE ? OR telephone LIKE ?";
    $stmt = $conn->prepare($sql);
    $like = "%" . $search . "%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql .= " ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $start, $parPage);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title><?= $t['Gestion_des_clients3']?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #6a1b9a; color: #fff; }
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
        h2 { color: #6a1b9a; margin: 20px; }
       .content{
        padding: 10px;
       }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="home-content">
    <div class="content">
    <h2><?= $t['Liste_des_clients3']?></h2>
    <a href="ajouter_client.php" class="btn1"><?= $t['Ajouter_un_client3']?></a>

   

    <?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th><?= $t['#ID3']?></th>
                <th><?= $t['Nom3']?></th>
                <th><?= $t['Email3']?></th>
                <th><?= $t['Téléphone3']?></th>
                <th><?= $t['Inscription3']?></th>
                <th><?= $t['Type3']?></th> <!-- ✅ Ajout colonne Type -->
                <th><?= $t['Actions3']?></th>
            </tr>
        </thead>
        <tbody>
            <?php while ($client = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $client['id'] ?></td>
                <td><?= htmlspecialchars($client['nom']) ?></td>
                <td><?= htmlspecialchars($client['email'] ?? '') ?></td>
                <td><?= htmlspecialchars($client['telephone']) ?></td>
                <td><?= $client['created_at'] ?></td>
                <td>
                    <?php if ($client['invite'] == 1): ?>
                        <span style="color:red;"><?= $t['Invite3']?></span>
                    <?php else: ?>
                        <span style="color:green;"><?= $t['Inscrit3']?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="modifier_client.php?id=<?= $client['id'] ?>" class="action-btn edit-btn">✏ <?= $t['Modifier3']?></a>
                    <a href="supprimer_client.php?id=<?= $client['id'] ?>" class="action-btn delete-btn" onclick="return confirm('⚠ Supprimer ce client ?')"><?= $t['Supprimer3']?></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- 📄 Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo; <?= $t['Précédent3']?></a>
        <?php endif; ?>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>" class="<?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>"><?= $t['Suivant3']?> &raquo;</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <p><?= $t['Aucun_client_trouve.3']?></p>
    <?php endif; ?>
</div>
</body>
</html>
