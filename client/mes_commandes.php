<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Vérifier si la connexion existe déjà (peut-être déjà établie par header.php)
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die("Erreur de connexion : " . $conn->connect_error);
}

// Vérification connexion
$isConnected = isset($_SESSION['client_id']);

if ($isConnected) {
    $client_id = $_SESSION['client_id'];
} else {
    if (isset($_SESSION['telephone'])) {
        $tel = $_SESSION['telephone'];
        $sql = "SELECT id FROM clients WHERE telephone = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $tel);
        $stmt->execute();
        $res = $stmt->get_result();
        $client_id = $res->num_rows > 0 ? $res->fetch_assoc()['id'] : null;
    } else {
        $client_id = null;
    }
}

if (!$client_id) {
    echo "<h2 style='text-align:center;color:red;'>⚠ " . $trans['please_login_orders'] . "</h2>";
    exit;
}

// Récupération des commandes en cours
$sql_processing = "SELECT c.id, c.date_commande, c.statut, c.total, c.temps_livraison, c.date_livraison
                   FROM commandes c
                   WHERE c.client_id = ? AND (c.statut LIKE '%en cours%' OR c.statut LIKE '%expédié%')
                   ORDER BY c.date_commande DESC";
$stmt_processing = $conn->prepare($sql_processing);
$stmt_processing->bind_param("i", $client_id);
$stmt_processing->execute();
$processingOrders = $stmt_processing->get_result()->fetch_all(MYSQLI_ASSOC);

// Récupération des commandes en attente
$sql_pending = "SELECT c.id, c.date_commande, c.statut, c.total, c.temps_livraison, c.date_livraison
                FROM commandes c
                WHERE c.client_id = ? AND c.statut LIKE '%en attente%'
                ORDER BY c.date_commande DESC";
$stmt_pending = $conn->prepare($sql_pending);
$stmt_pending->bind_param("i", $client_id);
$stmt_pending->execute();
$pendingOrders = $stmt_pending->get_result()->fetch_all(MYSQLI_ASSOC);

// Fonction pour récupérer les détails d'une commande
function getOrderDetails($conn, $order_id) {
    $sql = "SELECT dc.produit_id, p.nom, dc.quantite, dc.prix_unitaire, p.prix AS prix_original
            FROM details_commandes dc
            JOIN produits p ON dc.produit_id = p.id
            WHERE dc.commande_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Traitement des commandes
foreach ($processingOrders as &$order) {
    $details = getOrderDetails($conn, $order['id']);
    
    $order['produits'] = [];
    $order['nb_promotions'] = 0;
    
    foreach ($details as $detail) {
        $order['produits'][] = $detail['nom'];
        
        // Déterminer si le produit était en promotion
        if ($detail['prix_unitaire'] < $detail['prix_original']) {
            $order['nb_promotions']++;
        }
    }
    
    $order['produits'] = implode(', ', $order['produits']);
}

foreach ($pendingOrders as &$order) {
    $details = getOrderDetails($conn, $order['id']);
    
    $order['produits'] = [];
    $order['nb_promotions'] = 0;
    
    foreach ($details as $detail) {
        $order['produits'][] = $detail['nom'];
        
        // Déterminer si le produit était en promotion
        if ($detail['prix_unitaire'] < $detail['prix_original']) {
            $order['nb_promotions']++;
        }
    }
    
    $order['produits'] = implode(', ', $order['produits']);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
<?php echo $trans['order_tracking']; ?> - TchadShop</title>
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS amélioré avec gestion des promotions */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f9f7ff 0%, #eef2ff 100%); color: #333; min-height: 100vh; padding-top: 110px; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .content-box { background: white; border-radius: 16px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08); padding: 30px; margin-bottom: 30px; position: relative; overflow: hidden; animation: fadeIn 0.6s ease-out; }
        .page-title { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
        .page-title i { font-size: 2.5rem; color: #8e24aa; background: rgba(142, 36, 170, 0.1); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .page-title h1 { color: #6a1b9a; font-size: 2.2rem; }
        .page-title p { color: #777; font-size: 1rem; margin-top: 5px; }
        .orders-container { overflow-x: auto; margin-top: 30px; }
        .orders-table { width: 100%; border-collapse: collapse; min-width: 600px; }
        .orders-table th { background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%); color: white; font-weight: 600; padding: 16px 20px; text-align: left; }
        .orders-table tr:nth-child(even) { background-color: #f9f5ff; }
        .orders-table td { padding: 14px 20px; border-bottom: 1px solid #eee; }
        .order-id { font-weight: 700; color: #6a1b9a; }
        .order-date { color: #555; }
        .order-status { display: inline-flex; align-items: center; gap: 8px; padding: 7px 15px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        .status-pending { background: #ffecb3; color: #ff9800; }
        .status-processing { background: #bbdefb; color: #2196f3; animation: pulse 1.5s infinite; }
        .status-shipped { background: #c8e6c9; color: #4caf50; }
        .status-delivered { background: #e8f5e9; color: #2e7d32; }
        .status-cancelled { background: #ffcdd2; color: #f44336; }
        .order-total { font-weight: 700; color: #333; }
        .delivery-time { background: #e1f5fe; color: #0288d1; padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; }
        .delivery-time.effective { background: #e8f5e9; color: #2e7d32; }
        .btn-details { display: inline-flex; align-items: center; gap: 8px; padding: 8px 15px; background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%); color: white; border-radius: 50px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .btn-details:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
        
        /* Nouveaux styles pour les promotions */
        .promo-badge {
            display: inline-block;
            background: linear-gradient(135deg, #e74c3c 0%, #e67e22 100%);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 8px;
            animation: pulse 1.5s infinite;
        }
        .promo-text {
            color: #e74c3c;
            font-weight: 600;
            margin-top: 5px;
        }
        .product-list {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        @keyframes pulse { 
            0% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.4); } 
            70% { box-shadow: 0 0 0 10px rgba(33, 150, 243, 0); } 
            100% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0); } 
        }
        .orders-cards { display: none; grid-template-columns: 1fr; gap: 20px; }
        .order-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); padding: 20px; position: relative; transition: transform 0.3s ease; border-left: 4px solid #8e24aa; }
        .order-card.processing { border-left: 4px solid #2196F3; animation: card-pulse 2s infinite; }
        .order-card.delivered { border-left: 4px solid #4CAF50; }
        .order-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .order-card-id { font-weight: 700; color: #6a1b9a; font-size: 1.1rem; }
        .order-card-date { color: #777; font-size: 0.9rem; }
        .card-details { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .detail-item { display: flex; flex-direction: column; }
        .detail-label { font-size: 0.85rem; color: #777; margin-bottom: 5px; }
        .detail-value { font-weight: 600; color: #333; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .empty-state { text-align: center; padding: 50px 20px; background: #f8f5ff; border-radius: 12px; margin: 30px 0; }
        .empty-state i { font-size: 4rem; color: #b39ddb; margin-bottom: 20px; }
        .empty-state h3 { color: #6a1b9a; margin-bottom: 15px; font-size: 1.8rem; }
        .empty-state p { color: #666; max-width: 500px; margin: 0 auto 30px; line-height: 1.6; }
        .btn-shop { display: inline-flex; align-items: center; gap: 10px; padding: 12px 30px; background: linear-gradient(135deg, #8e24aa 0%, #6a1b9a 100%); color: white; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 1.1rem; transition: all 0.3s ease; }
        .btn-shop:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(142, 36, 170, 0.3); }
        @keyframes card-pulse { 
            0% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.3); } 
            70% { box-shadow: 0 0 0 12px rgba(33, 150, 243, 0); } 
            100% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0); } 
        }
        @media (max-width: 768px) {
            .orders-table { display: none; }
            .orders-cards { display: grid; }
            .page-title { flex-direction: column; text-align: center; gap: 10px; }
            .page-title i { width: 60px; height: 60px; font-size: 2rem; }
        }
        @media (max-width: 480px) {
            .card-details { grid-template-columns: 1fr; }
            .card-footer { flex-direction: column; gap: 15px; align-items: flex-start; }
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
   <div class="container">
    <div class="content-box">
        <div class="page-title">
            <i class="fas fa-box-open"></i>
            <div>
                <h1><?php echo $trans['my_orders']; ?></h1>
                <p><?php echo $trans['orders_subtitle']; ?></p>
            </div>
        </div>
        
        <div class="orders-section">
            <h2 style="margin-bottom: 20px; color: #6a1b9a; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-sync-alt fa-spin"></i> <?php echo $trans['current_orders']; ?>
            </h2>
            
            <?php if (!empty($processingOrders)): ?>
                <div class="orders-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th><?php echo $trans['order']; ?></th>
                                <th><?php echo $trans['date']; ?></th>
                                <th><?php echo $trans['products']; ?></th>
                                <th><?php echo $trans['status']; ?></th>
                                <th><?php echo $trans['delivery_time']; ?></th>
                                <th><?php echo $trans['total']; ?></th>
                                <th><?php echo $trans['actions']; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processingOrders as $row): ?>
                                <?php
                                $statusClass = 'status-processing';
                                $statusIcon = 'fa-sync-alt fa-spin';
                                $deliveryTimeDisplay = $row['temps_livraison'] . ' ' . $trans['min_estimated'];
                                $deliveryTimeClass = '';
                                
                                // Gestion des promotions
                                $promotionBadge = $row['nb_promotions'] > 0 ? '<span class="promo-badge">' . $trans['promo'] . '</span>' : '';
                                $promotionText = $row['nb_promotions'] > 0 ? 
                                    '<div class="promo-text">' . $row['nb_promotions'] . ' ' . $trans['products_on_promo'] . '</div>' : '';
                                ?>
                                <tr>
                                    <td>
                                        <span class="order-id">CMD-<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        <?= $promotionBadge ?>
                                    </td>
                                    <td class="order-date"><?= date("d/m/Y H:i", strtotime($row['date_commande'])) ?></td>
                                    <td>
                                        <div class="product-list"><?= $row['produits'] ?></div>
                                        <?= $promotionText ?>
                                    </td>
                                    <td>
                                        <span class="order-status <?= $statusClass ?>">
                                            <i class="fas <?= $statusIcon ?>"></i>
                                            <?= $trans['order_status_' . $row['statut']] ?? ucfirst($row['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="delivery-time <?= $deliveryTimeClass ?>">
                                            <i class="fas fa-clock"></i> 
                                            <?= $deliveryTimeDisplay ?>
                                        </span>
                                    </td>
                                    <td class="order-total"><?= number_format($row['total'], 0, ',', ' ') ?> FCFA</td>
                                    <td><a class="btn-details" href="details_commande.php?id=<?= $row['id'] ?>"><i class="fas fa-eye"></i> <?php echo $trans['details']; ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="orders-cards">
                    <?php foreach ($processingOrders as $row): ?>
                        <?php
                        $statusClass = 'status-processing';
                        $cardClass = 'processing';
                        $statusIcon = 'fa-sync-alt fa-spin';
                        $deliveryTimeDisplay = $row['temps_livraison'] . ' ' . $trans['min_estimated'];
                        $deliveryTimeClass = '';
                        
                        // Gestion des promotions
                        $promotionBadge = $row['nb_promotions'] > 0 ? '<span class="promo-badge">' . $trans['promo'] . '</span>' : '';
                        $promotionText = $row['nb_promotions'] > 0 ? 
                            '<div class="promo-text">' . $row['nb_promotions'] . ' ' . $trans['products_on_promo'] . '</div>' : '';
                        ?>
                        <div class="order-card <?= $cardClass ?>">
                            <div class="card-header">
                                <div>
                                    <div class="order-card-id">
                                        CMD-<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?>
                                        <?= $promotionBadge ?>
                                    </div>
                                    <div class="order-card-date"><?= date("d/m/Y H:i", strtotime($row['date_commande'])) ?></div>
                                </div>
                            </div>
                            <div class="card-details">
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $trans['products']; ?></span>
                                    <span class="detail-value">
                                        <?= $row['produits'] ?>
                                        <?= $promotionText ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $trans['status']; ?></span>
                                    <span class="detail-value">
                                        <span class="order-status <?= $statusClass ?>">
                                            <i class="fas <?= $statusIcon ?>"></i>
                                            <?= $trans['order_status_' . $row['statut']] ?? ucfirst($row['statut']) ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $trans['delivery_time']; ?></span>
                                    <span class="detail-value">
                                        <span class="delivery-time <?= $deliveryTimeClass ?>">
                                            <i class="fas fa-clock"></i> 
                                            <?= $deliveryTimeDisplay ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $trans['total']; ?></span>
                                    <span class="detail-value"><?= number_format($row['total'], 0, ',', ' ') ?> FCFA</span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a class="btn-details" href="details_commande.php?id=<?= $row['id'] ?>"><i class="fas fa-eye"></i> <?php echo $trans['details']; ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box"></i>
                    <h3><?php echo $trans['no_current_orders']; ?></h3>
                    <p><?php echo $trans['current_orders_hint']; ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <hr style="margin: 50px 0; border: 1px solid #eee;">
        
        <div class="orders-section">
            <h2 style="margin-bottom: 20px; color: #6a1b9a; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-clock"></i> <?php echo $trans['pending_orders']; ?>
            </h2>
            
            <?php if (!empty($pendingOrders)): ?>
                <div class="orders-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th><?php echo $trans['order']; ?></th>
                                <th><?php echo $trans['date']; ?></th>
                                <th><?php echo $trans['products']; ?></th>
                                <th><?php echo $trans['status']; ?></th>
                                <th><?php echo $trans['total']; ?></th>
                                <th><?php echo $trans['actions']; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingOrders as $row): ?>
                                <?php
                                $statusClass = 'status-pending';
                                $statusIcon = 'fa-clock';
                                
                                // Gestion des promotions
                                $promotionBadge = $row['nb_promotions'] > 0 ? '<span class="promo-badge">' . $trans['promo'] . '</span>' : '';
                                $promotionText = $row['nb_promotions'] > 0 ? 
                                    '<div class="promo-text">' . $row['nb_promotions'] . ' ' . $trans['products_on_promo'] . '</div>' : '';
                                ?>
                                <tr>
                                    <td>
                                        <span class="order-id">CMD-<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                        <?= $promotionBadge ?>
                                    </td>
                                    <td class="order-date"><?= date("d/m/Y H:i", strtotime($row['date_commande'])) ?></td>
                                    <td>
                                        <div class="product-list"><?= $row['produits'] ?></div>
                                        <?= $promotionText ?>
                                    </td>
                                    <td>
                                        <span class="order-status <?= $statusClass ?>">
                                            <i class="fas <?= $statusIcon ?>"></i>
                                            <?= $trans['order_status_' . $row['statut']] ?? ucfirst($row['statut']) ?>
                                        </span>
                                    </td>
                                    <td class="order-total"><?= number_format($row['total'], 0, ',', ' ') ?> FCFA</td>
                                    <td><a class="btn-details" href="details_commande.php?id=<?= $row['id'] ?>"><i class="fas fa-eye"></i> <?php echo $trans['details']; ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="orders-cards">
                    <?php foreach ($pendingOrders as $row): ?>
                        <?php
                        $statusClass = 'status-pending';
                        $cardClass = '';
                        $statusIcon = 'fa-clock';
                        
                        // Gestion des promotions
                        $promotionBadge = $row['nb_promotions'] > 0 ? '<span class="promo-badge">' . $trans['promo'] . '</span>' : '';
                        $promotionText = $row['nb_promotions'] > 0 ? 
                            '<div class="promo-text">' . $row['nb_promotions'] . ' ' . $trans['products_on_promo'] . '</div>' : '';
                        ?>
                        <div class="order-card <?= $cardClass ?>">
                            <div class="card-header">
                                <div>
                                    <div class="order-card-id">
                                        CMD-<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?>
                                        <?= $promotionBadge ?>
                                    </div>
                                    <div class="order-card-date"><?= date("d/m/Y H:i", strtotime($row['date_commande'])) ?></div>
                                </div>
                            </div>
                            <div class="card-details">
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $trans['products']; ?></span>
                                    <span class="detail-value">
                                        <?= $row['produits'] ?>
                                        <?= $promotionText ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $trans['status']; ?></span>
                                    <span class="detail-value">
                                        <span class="order-status <?= $statusClass ?>">
                                            <i class="fas <?= $statusIcon ?>"></i>
                                            <?= $trans['order_status_' . $row['statut']] ?? ucfirst($row['statut']) ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $trans['total']; ?></span>
                                    <span class="detail-value"><?= number_format($row['total'], 0, ',', ' ') ?> FCFA</span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a class="btn-details" href="details_commande.php?id=<?= $row['id'] ?>"><i class="fas fa-eye"></i> <?php echo $trans['details']; ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h3><?php echo $trans['no_pending_orders']; ?></h3>
                    <p><?php echo $trans['first_order_hint']; ?></p>
                    <a href="produits.php" class="btn-shop"><i class="fas fa-cart-plus"></i> <?php echo $trans['discover_products']; ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    <?php include("footer.php"); ?>
</body>
</html>