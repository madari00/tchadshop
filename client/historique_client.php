<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('header.php');
// Vérifier si la connexion existe déjà (peut-être déjà établie par header.php)
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die("Erreur de connexion : " . $conn->connect_error);
}

// Vérification connexion
$isConnected = isset($_SESSION['client_id']);
// Récupération du mode maintenance
$sql = "SELECT valeur FROM configuration WHERE parametre = 'maintenance_mode' LIMIT 1";
$res = $conn->query($sql);
$maintenance = ($res && $res->num_rows > 0) ? $res->fetch_assoc()['valeur'] : "off";

// ⚡ Vérification mode maintenance
if ($maintenance === "on") {
    // Autoriser les admins connectés
    if ($isConnected && !isset($_SESSION['admin_id'])) {
        header("Location: maintenance.php");
        exit();
    }
}
// Si connecté → récupérer son ID
if ($isConnected) {
    $client_id = $_SESSION['client_id'];
} else {
    if (isset($_SESSION['telephone'])) {
        $tel = $_SESSION['telephone'];
        $sql = "SELECT id FROM clients WHERE telephone = '$tel' LIMIT 1";
        $res = $conn->query($sql);
        if ($res->num_rows > 0) {
            $client_id = $res->fetch_assoc()['id'];
        } else {
            $client_id = null;
        }
    } else {
        $client_id = null;
    }
}

if (!$client_id) {
    echo "<h2 style='text-align:center;color:red;'>" .$trans['please_login_history']."</h2>";
    exit;
}

// Récupération de toutes les commandes SAUF celles en cours et en attente
$sql = "SELECT id, date_commande, statut, total, temps_livraison, date_livraison 
        FROM commandes 
        WHERE client_id = $client_id 
          AND statut NOT LIKE '%en cours%'
          AND statut NOT LIKE '%en attente%'
        ORDER BY date_commande DESC";
$res = $conn->query($sql);

// Calculer le nombre total de commandes
$totalCommandes = $res->num_rows;

// Calculer le montant total dépensé
$montantTotal = 0;
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
    $montantTotal += $row['total'];
}

// Récupérer les notifications pour les commandes livrées
$notifications = [];
if ($res->num_rows > 0) {
    $res->data_seek(0);
    while ($row = $res->fetch_assoc()) {
        if (strpos($row['statut'], 'livré') !== false) {
            // Calcul du temps effectif de livraison
            $deliveryTime = 'N/A';
            if ($row['date_commande'] && $row['date_livraison']) {
                $start = new DateTime($row['date_commande']);
                $end = new DateTime($row['date_livraison']);
                $diff = $start->diff($end);
                
                $minutes = ($diff->days * 24 * 60) + 
                           ($diff->h * 60) + 
                           $diff->i;
                
                $deliveryTime = $minutes . ' min';
            }
            
            $notifications[] = [
                'type' => $trans['delivered'],
                'message' => $trans['your_order']. " #{$row['id']} " . $trans['was_delivered_in']. " $deliveryTime!",
                'order_id' => $row['id'],
                'delivery_time' => $deliveryTime
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $trans['order_history_title']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f9f7ff 0%, #eef2ff 100%);
            color: #333;
            min-height: 100vh;
            padding-top: 110px;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .content-box {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        /* Statistiques */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #8e24aa;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #6a1b9a;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
        }

        /* Page title */
        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .page-title i {
            font-size: 2.5rem;
            color: #8e24aa;
            background: rgba(142, 36, 170, 0.1);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-title h1 {
            color: #6a1b9a;
            font-size: 2.2rem;
        }

        .page-title p {
            color: #777;
            font-size: 1rem;
            margin-top: 5px;
        }
        
        /* Table styles */
        .orders-container {
            overflow-x: auto;
            margin-top: 30px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .orders-table th {
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
            font-weight: 600;
            padding: 16px 20px;
            text-align: left;
        }

        .orders-table tr:nth-child(even) {
            background-color: #f9f5ff;
        }

        .orders-table td {
            padding: 14px 20px;
            border-bottom: 1px solid #eee;
        }

        .order-id {
            font-weight: 700;
            color: #6a1b9a;
        }

        .order-date {
            color: #555;
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .status-delivered {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-cancelled {
            background: #ffcdd2;
            color: #f44336;
        }

        .status-returned {
            background: #ffecb3;
            color: #ff9800;
        }

        .order-total {
            font-weight: 700;
            color: #333;
        }

        .delivery-time {
            background: #e1f5fe;
            color: #0288d1;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .delivery-time.effective {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .btn-details {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-details:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Card styles for mobile */
        .orders-cards {
            display: none;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 20px;
            position: relative;
            transition: transform 0.3s ease;
            border-left: 4px solid #8e24aa;
        }

        .order-card.delivered {
            border-left: 4px solid #4CAF50;
        }

        .order-card.cancelled {
            border-left: 4px solid #f44336;
        }

        .order-card.returned {
            border-left: 4px solid #ff9800;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-card-id {
            font-weight: 700;
            color: #6a1b9a;
            font-size: 1.1rem;
        }

        .order-card-date {
            color: #777;
            font-size: 0.9rem;
        }

        .card-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: #f8f5ff;
            border-radius: 12px;
            margin: 30px 0;
        }

        .empty-state i {
            font-size: 4rem;
            color: #b39ddb;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #6a1b9a;
            margin-bottom: 15px;
            font-size: 1.8rem;
        }

        .empty-state p {
            color: #666;
            max-width: 500px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }

        .btn-shop {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #8e24aa 0%, #6a1b9a 100%);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-shop:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(142, 36, 170, 0.3);
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .orders-table {
                display: none;
            }
            
            .orders-cards {
                display: grid;
            }
            
            .page-title {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .page-title i {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .card-details {
                grid-template-columns: 1fr;
            }
            
            .card-footer {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .content-box {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 40px auto;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #8e24aa;
            left: 50%;
            margin-left: -1px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 50px;
        }
        
        .timeline-content {
            position: relative;
            width: 45%;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .timeline-item:nth-child(odd) .timeline-content {
            left: 0;
        }
        
        .timeline-item:nth-child(even) .timeline-content {
            left: 55%;
        }
        
        .timeline-content h3 {
            margin-bottom: 10px;
            color: #6a1b9a;
        }
        
        .timeline-content .date {
            color: #8e24aa;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .timeline-content .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }
        
        .timeline-content .status.delivered {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .timeline-content .status.cancelled {
            background: #ffcdd2;
            color: #f44336;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: #8e24aa;
            border: 3px solid white;
            border-radius: 50%;
            top: 20px;
            left: 50%;
            margin-left: -10px;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            .timeline::before {
                left: 31px;
            }
            
            .timeline-item::after {
                left: 31px;
            }
            
            .timeline-content {
                width: auto;
                margin-left: 70px;
                left: 0 !important;
            }
        }
    </style>
</head>
<body>
    

    <div class="container">
    <div class="content-box">
        <div class="page-title">
            <i class="fas fa-history"></i>
            <div>
                <h1><?php echo $trans['order_history']; ?></h1>
                <p><?php echo $trans['order_history_subtitle']; ?></p>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-shopping-bag"></i>
                <div class="stat-value"><?= $totalCommandes ?></div>
                <div class="stat-label"><?php echo $trans['orders_placed']; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-money-bill-wave"></i>
                <div class="stat-value"><?= number_format($montantTotal, 0, ',', ' ') ?> FCFA</div>
                <div class="stat-label"><?php echo $trans['total_amount_spent']; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-truck"></i>
                <div class="stat-value"><?= $totalCommandes > 0 ? round($montantTotal / $totalCommandes, 0) : 0 ?> FCFA</div>
                <div class="stat-label"><?php echo $trans['average_basket']; ?></div>
            </div>
        </div>

        <h2 style="margin-bottom: 20px; color: #6a1b9a; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-list"></i> <?php echo $trans['all_past_orders']; ?>
        </h2>

        <?php if ($res->num_rows > 0): ?>
            <div class="orders-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th><?php echo $trans['order']; ?></th>
                            <th><?php echo $trans['date']; ?></th>
                            <th><?php echo $trans['status']; ?></th>
                            <th><?php echo $trans['delivery_time']; ?></th>
                            <th><?php echo $trans['total']; ?></th>
                            <th><?php echo $trans['actions']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res->data_seek(0); // Reset result pointer
                        while ($row = $res->fetch_assoc()): 
                            // Déterminer la classe CSS en fonction du statut
                            $statusClass = '';
                            $statusIcon = '';
                            
                            if (strpos($row['statut'], 'livré') !== false) {
                                $statusClass = 'status-delivered';
                                $statusIcon = 'fa-check-circle';
                            } elseif (strpos($row['statut'], 'annulé') !== false) {
                                $statusClass = 'status-cancelled';
                                $statusIcon = 'fa-times-circle';
                            } elseif (strpos($row['statut'], 'retour') !== false) {
                                $statusClass = 'status-returned';
                                $statusIcon = 'fa-undo';
                            }
                            
                            // Calcul du temps de livraison effectif
                            $deliveryTimeDisplay = $row['temps_livraison'] . ' min (' . $trans['estimated'] . ')';
                            $deliveryTimeClass = '';
                            
                            if (strpos($row['statut'], 'livré') !== false && $row['date_commande'] && $row['date_livraison']) {
                                $start = new DateTime($row['date_commande']);
                                $end = new DateTime($row['date_livraison']);
                                $diff = $start->diff($end);
                                
                                $minutes = ($diff->days * 24 * 60) + 
                                           ($diff->h * 60) + 
                                           $diff->i;
                                
                                $deliveryTimeDisplay = $minutes . ' min (' . $trans['effective'] . ')';
                                $deliveryTimeClass = 'effective';
                            }
                        ?>
                            <tr>
                                <td><span class="order-id">CMD-<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                                <td class="order-date"><?= date("d/m/Y H:i", strtotime($row['date_commande'])) ?></td>
                                <td>
                                    <span class="order-status <?= $statusClass ?>">
                                        <i class="fas <?= $statusIcon ?>"></i>
                                        <?= $trans['status_' . str_replace(' ', '_', strtolower($row['statut']))] ?? ucfirst($row['statut']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($deliveryTimeClass): ?>
                                    <span class="delivery-time <?= $deliveryTimeClass ?>">
                                        <i class="fas fa-clock"></i> 
                                        <?= $deliveryTimeDisplay ?>
                                    </span>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td class="order-total"><?= number_format($row['total'], 0, ',', ' ') ?> FCFA</td>
                                <td><a class="btn-details" href="details_commande.php?id=<?= $row['id'] ?>"><i class="fas fa-eye"></i> <?php echo $trans['details']; ?></a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="orders-cards">
                <?php 
                $res->data_seek(0); // Reset result pointer
                while ($row = $res->fetch_assoc()): 
                    // Déterminer la classe CSS en fonction du statut
                    $statusClass = '';
                    $cardClass = '';
                    $statusIcon = '';
                    
                    if (strpos($row['statut'], 'livré') !== false) {
                        $statusClass = 'status-delivered';
                        $cardClass = 'delivered';
                        $statusIcon = 'fa-check-circle';
                    } elseif (strpos($row['statut'], 'annulé') !== false) {
                        $statusClass = 'status-cancelled';
                        $cardClass = 'cancelled';
                        $statusIcon = 'fa-times-circle';
                    } elseif (strpos($row['statut'], 'retour') !== false) {
                        $statusClass = 'status-returned';
                        $cardClass = 'returned';
                        $statusIcon = 'fa-undo';
                    }
                    
                    // Calcul du temps de livraison effectif
                    $deliveryTimeDisplay = $row['temps_livraison'] . ' min (' . $trans['estimated'] . ')';
                    $deliveryTimeClass = '';
                    
                    if (strpos($row['statut'], 'livré') !== false && $row['date_commande'] && $row['date_livraison']) {
                        $start = new DateTime($row['date_commande']);
                        $end = new DateTime($row['date_livraison']);
                        $diff = $start->diff($end);
                        
                        $minutes = ($diff->days * 24 * 60) + 
                                   ($diff->h * 60) + 
                                   $diff->i;
                        
                        $deliveryTimeDisplay = $minutes . ' min (' . $trans['effective'] . ')';
                        $deliveryTimeClass = 'effective';
                    }
                ?>
                    <div class="order-card <?= $cardClass ?>">
                        <div class="card-header">
                            <div class="order-card-id">CMD-<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></div>
                            <div class="order-card-date"><?= date("d/m/Y H:i", strtotime($row['date_commande'])) ?></div>
                        </div>
                        
                        <div class="card-details">
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $trans['status']; ?></span>
                                <span class="detail-value">
                                    <span class="order-status <?= $statusClass ?>">
                                        <i class="fas <?= $statusIcon ?>"></i>
                                        <?= $trans['status_' . str_replace(' ', '_', strtolower($row['statut']))] ?? ucfirst($row['statut']) ?>
                                    </span>
                                </span>
                            </div>
                            
                            <?php if ($deliveryTimeClass): ?>
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $trans['delivery_time']; ?></span>
                                <span class="detail-value">
                                    <span class="delivery-time <?= $deliveryTimeClass ?>">
                                        <i class="fas fa-clock"></i> 
                                        <?= $deliveryTimeDisplay ?>
                                    </span>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $trans['total']; ?></span>
                                <span class="detail-value"><?= number_format($row['total'], 0, ',', ' ') ?> FCFA</span>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <a class="btn-details" href="details_commande.php?id=<?= $row['id'] ?>"><i class="fas fa-eye"></i> <?php echo $trans['view_details']; ?></a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <h3><?php echo $trans['no_order_history']; ?></h3>
                <p><?php echo $trans['empty_history_message']; ?></p>
                <a href="mes_commandes.php" class="btn-shop"><i class="fas fa-shopping-cart"></i> <?php echo $trans['view_current_orders']; ?></a>
            </div>
        <?php endif; ?>
        
        <!-- Timeline historique -->
        <h2 style="margin: 40px 0 20px; color: #6a1b9a; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-stream"></i> <?php echo $trans['order_timeline']; ?>
        </h2>
        
        <div class="timeline">
            <?php 
            $res->data_seek(0); 
            $counter = 0;
            while ($row = $res->fetch_assoc()): 
                $counter++;
                $statusClass = strpos($row['statut'], 'livré') !== false ? 'delivered' : 
                              (strpos($row['statut'], 'annulé') !== false ? 'cancelled' : 'returned');
            ?>
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="date"><?= date("d M Y, H:i", strtotime($row['date_commande'])) ?></div>
                    <div class="status <?= $statusClass ?>"><?= $trans['status_' . str_replace(' ', '_', strtolower($row['statut']))] ?? ucfirst($row['statut']) ?></div>
                    <h3><?php echo $trans['order']; ?> #CMD-<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></h3>
                    <p><strong><?php echo $trans['total']; ?>:</strong> <?= number_format($row['total'], 0, ',', ' ') ?> FCFA</p>
                    <a class="btn-details" href="details_commande.php?id=<?= $row['id'] ?>"><i class="fas fa-eye"></i> <?php echo $trans['view_details']; ?></a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
    // Données des notifications
    const notifications = <?php echo json_encode($notifications); ?>;
    
    // Initialisation
    document.addEventListener('DOMContentLoaded', () => {
        // Animation pour les cartes de commande
        const cards = document.querySelectorAll('.order-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Animation pour la timeline
        const timelineItems = document.querySelectorAll('.timeline-content');
        timelineItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.2}s`;
        });
    });
</script>
    <?php include("footer.php"); ?>
</body>
</html>