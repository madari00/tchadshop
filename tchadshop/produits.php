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
$promo_filter = $conn->real_escape_string($_GET['promo'] ?? '');
$page = intval($_GET['page'] ?? 1);
$per_page = 20; // Produits par page
$offset = ($page - 1) * $per_page;

// ---- Construire la clause WHERE ----
$where = "1";
if (!empty($search)) {
    $where .= " AND p.nom LIKE '%$search%'";
}
if ($min_price > 0) {
    $where .= " AND (p.prix >= $min_price OR (p.prix_promotion >= $min_price AND p.prix_promotion IS NOT NULL))";
}
if ($max_price > 0) {
    $where .= " AND (p.prix <= $max_price OR (p.prix_promotion <= $max_price AND p.prix_promotion IS NOT NULL))";
}
if (!empty($statut)) {
    $where .= " AND p.statut = '$statut'";
}
if ($promo_filter === 'active') {
    $where .= " AND p.promotion > 0 AND CURDATE() BETWEEN p.date_debut_promo AND p.date_fin_promo";
} elseif ($promo_filter === 'futures') {
    $where .= " AND p.promotion > 0 AND CURDATE() < p.date_debut_promo";
} elseif ($promo_filter === 'expirees') {
    $where .= " AND p.promotion > 0 AND CURDATE() > p.date_fin_promo";
}

// ---- Compter le nombre total de produits ----
$count_sql = "SELECT COUNT(*) as total FROM produits p WHERE $where";
$total_resultat_produits = $conn->query($count_sql);
$total_produit = $total_resultat_produits->fetch_assoc();
$total_produits = $total_produit['total'];
$total_pages = ceil($total_produits / $per_page);

// ---- Récupérer les produits ----
$sql = "SELECT p.id AS produit_id, p.nom, p.description, p.prix, p.prix_promotion, 
               p.stock, p.statut, p.promotion, p.date_debut_promo, p.date_fin_promo
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
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .filter-group {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .filters label {
        color: blue;
        font-size: 16px;
        white-space: nowrap;
    }
    .filters input, .filters select, .filters button { 
        padding: 8px;
        border: 2px solid rgb(212, 54, 244);
        border-radius: 5px;
    }
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
        font-size: 12px;
    }
    .view-btn { background: #2196f3; }
    .edit-btn { background: #ff9800; }
    .delete-btn { background: #f44336; }
    
    .button{
        background: rgb(212, 54, 244);
        border: none;
        color: white;
        font-size: 16px;
        border-radius: 5px;
        padding: 8px 15px;
        cursor: pointer;
    }
    .no-results {
        text-align: center;
        padding: 30px;
        color: #757575;
        font-size: 18px;
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
    .promo-badge {
        background: #ff4081;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
        margin-left: 5px;
    }
    .original-price {
        text-decoration: line-through;
        color: #757575;
        font-size: 14px;
    }
    .promo-price {
        color: #e91e63;
        font-weight: bold;
        font-size: 16px;
    }
    .active-promo {
        background-color: #fff8e1;
    }
    .promo-dates {
        font-size: 12px;
        color: #666;
    }
    .filter-section {
        background: #f3e5f5;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .filter-section h3 {
        margin: 0 0 10px 0;
        color: #6a1b9a;
    }
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    @media (max-width: 768px) {
        .filters {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-group {
            flex-direction: column;
            align-items: stretch;
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
            text-align: center;
        }
        .summary {
            flex-direction: column;
            gap: 10px;
        }
        .action-btn {
            display: block;
            margin: 5px 0;
            text-align: center;
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

  <!-- 🔎 Filtres avancés -->
  <div class="filter-section">
    <h3>🔍 Filtres de recherche</h3>
    <form method="GET" class="filters">
      <div class="filter-row">
        <div class="filter-group">
          <label><?= $t['min_price2']?> :</label>
          <input type="number" name="min_price" placeholder="Prix min" value="<?= $min_price ?>">
        </div>
        <div class="filter-group">
          <label><?= $t['max_price2']?> :</label>
          <input type="number" name="max_price" placeholder="Prix max" value="<?= $max_price ?>">
        </div>
        <div class="filter-group">
          <label>Statut :</label>
          <select name="statut" class="select">
              <option value=""><?= $t['all_status2']?></option>
              <option value="disponible" <?= $statut == 'disponible' ? 'selected' : '' ?>><?= $t['available2']?></option>
              <option value="rupture" <?= $statut == 'rupture' ? 'selected' : '' ?>><?= $t['out_of_stock2']?></option>
              <option value="bientôt" <?= $statut == 'bientôt' ? 'selected' : '' ?>><?= $t['coming_soon2']?></option>
          </select>
        </div>
        <div class="filter-group">
          <label>Promotions :</label>
          <select name="promo" class="select">
              <option value="">Toutes</option>
              <option value="active" <?= $promo_filter == 'active' ? 'selected' : '' ?>>Actives</option>
              <option value="futures" <?= $promo_filter == 'futures' ? 'selected' : '' ?>>Futures</option>
              <option value="expirees" <?= $promo_filter == 'expirees' ? 'selected' : '' ?>>Expirées</option>
          </select>
        </div>
        <div class="filter-group">
          <button type="submit" class="button"><?= $t['filter2']?></button>
        </div>
      </div>
    </form>
  </div>

  <!-- Résumé des résultats -->
  <div class="summary">
    <div>
      <strong><?= $total_produits ?></strong> produit(s) trouvé(s)
      <?php if (!empty($search)): ?>
        | Recherche : "<?= htmlspecialchars($search) ?>"
      <?php endif; ?>
      <?php if (!empty($promo_filter)): ?>
        | Promotion : 
        <?php 
          $promo_text = '';
          if ($promo_filter == 'active') $promo_text = 'Actives';
          elseif ($promo_filter == 'futures') $promo_text = 'Futures';
          elseif ($promo_filter == 'expirees') $promo_text = 'Expirées';
          echo $promo_text;
        ?>
      <?php endif; ?>
    </div>
    <div>
      Page <?= $page ?> sur <?= $total_pages ?>
    </div>
  </div>

  <!-- 📋 Tableau Produits -->
  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>Description</th>
        <th>Prix</th>
        <th>Stock</th>
        <th>Statut</th>
        <th>Promotion</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php 
    if ($resultat_produits->num_rows > 0):
        while ($produit = $resultat_produits->fetch_assoc()): 
            $is_active_promo = false;
            $is_future_promo = false;
            $is_expired_promo = false;
            
            if ($produit['promotion'] > 0) {
                $today = date('Y-m-d');
                $start = $produit['date_debut_promo'];
                $end = $produit['date_fin_promo'];
                
                if ($start && $end) {
                    if ($today >= $start && $today <= $end) {
                        $is_active_promo = true;
                    } elseif ($today < $start) {
                        $is_future_promo = true;
                    } elseif ($today > $end) {
                        $is_expired_promo = true;
                    }
                }
            }
    ?>
      <tr class="<?= $is_active_promo ? 'active-promo' : '' ?>">
        <td>
          <?= htmlspecialchars($produit['nom']) ?>
          <?php if ($is_active_promo): ?>
            <span class="promo-badge">PROMO</span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars(substr($produit['description'], 0, 50)) . (strlen($produit['description']) > 50 ? '...' : '') ?></td>
        <td>
          <?php if ($is_active_promo): ?>
            <div class="original-price"><?= number_format($produit['prix'], 0, ',', ' ') ?> <?= $t['currency2']?></div>
            <div class="promo-price"><?= number_format($produit['prix_promotion'], 0, ',', ' ') ?> <?= $t['currency2']?></div>
            <div class="promo-dates">-<?= $produit['promotion'] ?>%</div>
          <?php else: ?>
            <?= number_format($produit['prix'], 0, ',', ' ') ?> <?= $t['currency2']?>
          <?php endif; ?>
        </td>
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
          <?php if ($produit['promotion'] > 0): ?>
            <?php if ($is_active_promo): ?>
              <span style="color:green">● Active</span><br>
            <?php elseif ($is_future_promo): ?>
              <span style="color:orange">● Future</span><br>
            <?php elseif ($is_expired_promo): ?>
              <span style="color:red">● Expirée</span><br>
            <?php endif; ?>
            <small>
              <?= date('d/m/Y', strtotime($produit['date_debut_promo'])) ?> - 
              <?= date('d/m/Y', strtotime($produit['date_fin_promo'])) ?>
            </small>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
        <td>
          <a href="details_produit.php?id=<?= $produit['produit_id'] ?>" class="action-btn view-btn">👁 <?= $t['view2']?></a>
          <a href="modifier_produit.php?id=<?= $produit['produit_id'] ?>" class="action-btn edit-btn">✏ <?= $t['edit2']?></a>
          <a href="supprimer_produit.php?id=<?= $produit['produit_id'] ?>" class="action-btn delete-btn" onclick="return confirm('Confirmer la suppression ?');">🗑 <?= $t['delete2']?></a>
        </td>
      </tr>
    <?php 
        endwhile;
    else: 
    ?>
      <tr>
        <td colspan="7" class="no-results">
          <i>📭</i>
          Aucun produit trouvé avec les critères sélectionnés
        </td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>

  <!-- 📄 Pagination -->
  <div class="pagination">
      <?php if ($page > 1): ?>
          <a href="?search=<?= urlencode($search) ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&statut=<?= urlencode($statut) ?>&promo=<?= urlencode($promo_filter) ?>&page=<?= $page - 1 ?>">
              ← <?php echo $t['previous1']; ?>
          </a>
      <?php endif; ?>
      
      <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
          <a href="?search=<?= urlencode($search) ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&statut=<?= urlencode($statut) ?>&promo=<?= urlencode($promo_filter) ?>&page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
              <?= $i ?>
          </a>
      <?php endfor; ?>
      
      <?php if ($page < $total_pages): ?>
          <a href="?search=<?= urlencode($search) ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&statut=<?= urlencode($statut) ?>&promo=<?= urlencode($promo_filter) ?>&page=<?= $page + 1 ?>">
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