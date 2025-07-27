<?php 
session_start();
$searchContext = 'produit';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

// ---- Récupérer les paramètres de recherche et de filtre ----
$search = $conn->real_escape_string($_GET['search'] ?? '');
$min_price = floatval($_GET['min_price'] ?? 0);
$max_price = floatval($_GET['max_price'] ?? 0);
$statut = $conn->real_escape_string($_GET['statut'] ?? '');
$page = intval($_GET['page'] ?? 1);
$per_page = 20; // Produits par page
$offset = ($page - 1) * $per_page;

// ---- Construire la clause WHERE ----
$where = "1";
if (!empty($search)) {
    $where .= " AND p.nom LIKE '%$search%'";
}
if ($min_price > 0) {
    $where .= " AND p.prix >= $min_price";
}
if ($max_price > 0) {
    $where .= " AND p.prix <= $max_price";
}
if (!empty($statut)) {
    $where .= " AND p.statut = '$statut'";
}

// ---- Compter le nombre total de produits ----
$count_sql = "SELECT COUNT(*) as total FROM produits p WHERE $where";
$total_resultat_produits = $conn->query($count_sql);
$total_produit = $total_resultat_produits->fetch_assoc();
$total_produits = $total_produit['total'];
$total_pages = ceil($total_produits / $per_page);

// ---- Récupérer les produits ----
$sql = "SELECT p.id AS produit_id, p.nom, p.description, p.prix, p.stock, p.statut
        FROM produits p
        WHERE $where
        ORDER BY p.created_at DESC
        LIMIT $per_page OFFSET $offset";

$resultat_produits = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title2']?></title>
  <style>
    body { font-family: Arial; background-color: #f8f8f8; }
    .main-content {  background: #fff; padding: 20px; border-radius: 8px; }
    .main-content h2 { color: #6a1b9a; }
    .btn1 { float: right; text-decoration: none; color: #fff; background: #4caf50; border-radius: 5px; padding: 10px 20px; }
    .filters { 
       margin-bottom: 15px;
       text-align: center;
     }
    .filters input, .filters select { margin-right: 10px; padding: 5px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    table th, table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
    table th { background: #6a1b9a; color: #fff; }
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
     .action-btn { 
        margin: 0 4px; 
        padding: 5px 5px; 
        border-radius: 6px; 
        text-decoration: none; 
        color: #fff;
        font-weight: 400;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .view-btn { background: #2196f3; }
    .edit-btn { background: #ff9800; }
    .delete-btn { background: #f44336; }
    
    .button{
      background:rgb(212, 54, 244);
      width: 100px;
      height: 30px;
      border: none;
      color: white;
      font-size: 20px;
     border-radius: 10px;
    }
    .filters label{
      color: blue;
      font-size: 20px;
    }
    .select,.filters input{
      border: 2px solid rgb(212, 54, 244);
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
        .btn1 {
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
  </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="home-content">
<div class="main-content">
  <h2>📦 <?= $t['title2']?></h2><br>
  <a href="ajouter_produit.php" class="btn1">➕ <?= $t['add_product2']?></a>

  <!-- 🔎 Recherche et Filtres -->
  <form method="GET" class="filters">
    <label><?= $t['min_price2']?> :</label>
    <input type="number" name="min_price" placeholder="Prix min" value="<?= $min_price ?>">
    <label><?= $t['max_price2']?> :</label>
    <input type="number" naclass="select"me="max_price" placeholder="Prix max" value="<?= $max_price ?>">
    <select name="statut" class="select">
        <option value=""><?= $t['all_status2']?></option>
        <option value="disponible" <?= $statut == 'disponible' ? 'selected' : '' ?>><?= $t['available2']?></option>
        <option value="rupture" <?= $statut == 'rupture' ? 'selected' : '' ?>><?= $t['out_of_stock2']?></option>
        <option value="bientot" <?= $statut == 'bientôt' ? 'selected' : '' ?>><?= $t['coming_soon2']?></option>
    </select>
    <button type="submit" class="button"><?= $t['filter2']?></button>
  </form>

  <!-- 📋 Tableau Produits -->
  <table>
    <thead>
      <tr>
        <th><?= $t['name2']?></th>
        <th><?= $t['description2']?></th>
        <th><?= $t['price2']?></th>
        <th><?= $t['stock2']?></th>
        <th><?= $t['status2']?></th>
        <th><?= $t['actions2']?></th>
      </tr>
    </thead>
    <tbody>
    <?php while ($produit = $resultat_produits->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($produit['nom']) ?></td>
        <td><?= htmlspecialchars($produit['description']) ?></td>
        <td><?= number_format($produit['prix'], 0, ',', ' ') ?> <?= $t['currency2']?></td>
        <td><?= $produit['stock'] ?></td>
        <td>
          <?php 
              $status_text = $produit['statut'];
              if ($status_text == 'disponible') echo $t['available2'];
              elseif ($status_text == 'rupture') echo $t['out_of_stock2'];
              elseif ($status_text == 'bientôt') echo $t['coming_soon2'];
              else echo ucfirst($status_text);
          ?>
      </td>
        <td>
          <a href="details_produit.php?id=<?= $produit['produit_id'] ?>" class="action-btn view-btn">👁 <?= $t['view2']?></a>
          <a href="modifier_produit.php?id=<?= $produit['produit_id'] ?>" class="action-btn edit-btn">✏ <?= $t['edit2']?></a>
          <a href="supprimer_produit.php?id=<?= $produit['produit_id'] ?>" class="action-btn delete-btn" onclick="return confirm('Confirmer la suppression ?');">🗑 <?= $t['delete2']?></a>
        </td>
      </tr>
          <?php endwhile; ?>
            </tbody>
        </table>

  <!-- 📄 Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?search=<?= urlencode($search) ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&statut=<?= urlencode($statut) ?>&page=<?= $page - 1 ?>">
                    ← <?php echo $t['previous1']; ?>
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                <a href="?search=<?= urlencode($search) ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&statut=<?= urlencode($statut) ?>&page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?search=<?= urlencode($search) ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&statut=<?= urlencode($statut) ?>&page=<?= $page + 1 ?>">
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
