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

if (!isset($_GET['id'])) {
    die("<h2 style='text-align:center;color:red;'>".$trans['order_not_found']."</h2>");
}
$commande_id = (int)$_GET['id'];

$sql = "SELECT c.*, cl.telephone, cl.nom AS client_nom 
        FROM commandes c
        LEFT JOIN clients cl ON cl.id = c.client_id
        WHERE c.id = $commande_id
        LIMIT 1";
$res = $conn->query($sql);
if ($res->num_rows == 0) {
    die("<h2 style='text-align:center;color:red;'>".$trans['order_not_found']."</h2>");
}
$commande = $res->fetch_assoc();

// Requête modifiée pour récupérer les informations de promotion
$sql = "SELECT dc.quantite, dc.prix_unitaire, p.nom, p.prix AS prix_regulier, 
               p.promotion, p.prix_promotion,
               (SELECT image
                FROM images_produit
                WHERE produit_id = p.id 
                ORDER BY id ASC 
                LIMIT 1) AS image_path
        FROM details_commandes dc
        LEFT JOIN produits p ON p.id = dc.produit_id
        WHERE dc.commande_id = $commande_id";
$produits = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $trans['order_details_hash'] . $commande_id ?> - <?php echo $trans['tchadshop'] ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS existant... */
        /* Ajout de styles pour les promotions */
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
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .page-header i {
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

        .page-header h1 {
            color: #6a1b9a;
            font-size: 2rem;
        }

        .page-header p {
            color: #777;
            font-size: 1rem;
            margin-top: 5px;
        }

        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: #f9f5ff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #8e24aa;
        }

        .summary-card h3 {
            color: #6a1b9a;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-card h3 i {
            color: #8e24aa;
        }

        .info-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #eee;
        }

        .info-label {
            flex: 1;
            font-weight: 600;
            color: #555;
        }

        .info-value {
            flex: 1;
            color: #333;
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .status-processing {
            background: #bbdefb;
            color: #2196f3;
        }

        .status-delivered {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-pending {
            background: #ffecb3;
            color: #ff9800;
        }

        .map-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: #e1f5fe;
            color: #0288d1;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .map-link:hover {
            background: #b3e5fc;
            transform: translateY(-2px);
        }

        .products-section {
            margin-top: 40px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #6a1b9a;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .products-table th {
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
            font-weight: 600;
            padding: 16px;
            text-align: left;
        }

        .products-table tr:nth-child(even) {
            background-color: #f9f5ff;
        }

        .products-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #eee;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-image i {
            font-size: 1.5rem;
            color: #9e9e9e;
        }

        .product-name {
            font-weight: 600;
        }

        .order-total-row {
            background: #e8f5e9;
            font-weight: 700;
        }

        .order-total-row td {
            font-size: 1.1rem;
            color: #2e7d32;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-back {
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
        }

        .btn-help {
            background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
            color: white;
        }

        .btn-reorder {
            background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
            color: white;
        }

        /* Timeline de statut */
        .status-timeline {
            position: relative;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px 0;
        }
        
        .status-timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #e0e0e0;
            left: 50px;
            margin-left: -2px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 80px;
        }
        
        .timeline-item.active .timeline-icon {
            background: #8e24aa;
            color: white;
            box-shadow: 0 0 0 6px rgba(142, 36, 170, 0.2);
        }
        
        .timeline-icon {
            position: absolute;
            left: 30px;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid #8e24aa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8e24aa;
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .timeline-content {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            position: relative;
        }
        
        .timeline-content::before {
            content: '';
            position: absolute;
            left: -10px;
            top: 20px;
            width: 0;
            height: 0;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
            border-right: 10px solid white;
        }
        
        .timeline-title {
            font-weight: 600;
            color: #6a1b9a;
            margin-bottom: 5px;
        }
        
        .timeline-date {
            color: #777;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .timeline-desc {
            color: #555;
            line-height: 1.6;
        }

        /* Styles pour la modal d'aide */
        .help-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .help-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .help-modal .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 20px;
            background: linear-gradient(135deg, #6a1b9a 0%, #8e24aa 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #8e24aa;
            box-shadow: 0 0 0 3px rgba(142, 36, 170, 0.2);
            outline: none;
        }

        .contact-methods {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .contact-methods label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: #f5f5f5;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .contact-methods label:hover {
            background: #e9e9e9;
        }

        .contact-methods input[type="radio"] {
            display: none;
        }

        .contact-methods input[type="radio"]:checked + * {
            color: #8e24aa;
            font-weight: 600;
        }

        .btn-submit {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            margin-top: 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .audio-recorder {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }

        .record-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: #f44336;
            color: white;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .record-btn.recording {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(244, 67, 54, 0); }
            100% { box-shadow: 0 0 0 0 rgba(244, 67, 54, 0); }
        }

        .audio-preview {
            flex: 1;
            display: none;
        }

        .audio-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #e1f5fe;
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 600;
        }

        .audio-indicator .wave {
            display: inline-block;
            width: 20px;
            height: 20px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%230288d1" d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path fill="%230288d1" d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>') center/contain no-repeat;
            animation: wave 1.5s infinite;
        }

        @keyframes wave {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .audio-upload {
            margin-top: 15px;
            display: none;
        }

        .audio-upload.active {
            display: block;
        }

        .audio-upload label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        /* Guide d'autorisation */
        .permission-guide {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            display: none;
        }

        .permission-guide.active {
            display: block;
        }

        .permission-guide h4 {
            color: #1565c0;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .permission-steps {
            padding-left: 20px;
        }

        .permission-steps li {
            margin-bottom: 8px;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            body {
                padding-top: 90px;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .page-header i {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
            
            .products-table {
                display: block;
                overflow-x: auto;
            }
            
            .timeline-item {
                padding-left: 60px;
            }
            
            .timeline-icon {
                left: 10px;
            }

            .gallery-container {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }

            .contact-methods {
                flex-direction: column;
                gap: 10px;
            }
            
            .contact-methods label {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .order-summary {
                grid-template-columns: 1fr;
            }
            
            .products-table th, 
            .products-table td {
                padding: 12px 10px;
                font-size: 0.9rem;
            }
            
            .product-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .status-timeline::before {
                left: 20px;
            }
            
            .timeline-item {
                padding-left: 50px;
            }
            
            .timeline-icon {
                left: 0;
            }

            .gallery-container {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
        }
        .promo-badge {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
            animation: pulse 1.5s infinite;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9rem;
        }
        
        .promo-price {
            color: #e74c3c;
            font-weight: 700;
        }
        
        .saving {
            color: #27ae60;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 3px;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
    <div class="content-box">
        <div class="page-header">
            <i class="fas fa-file-invoice"></i>
            <div>
                <h1><?php echo $trans['order_details']; ?>#<?= $commande_id ?></h1>
                <p><?php echo $trans['order_details_subtitle']; ?></p>
            </div>
        </div>
        
        <div class="order-summary">
            <div class="summary-card">
                <h3><i class="fas fa-info-circle"></i> <?php echo $trans['basic_info']; ?></h3>
                <div class="info-row">
                    <div class="info-label"><?php echo $trans['order_date']; ?></div>
                    <div class="info-value"><?= date("d/m/Y H:i", strtotime($commande['date_commande'])) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><?php echo $trans['status']; ?></div>
                    <div class="info-value">
                        <span class="status-badge status-<?= strpos($commande['statut'], 'livré') !== false ? 'delivered' : 
                             (strpos($commande['statut'], 'en cours') !== false ? 'processing' : 'pending') ?>">
                            <?= ucfirst($commande['statut']) ?>
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label"><?php echo $trans['total']; ?></div>
                    <div class="info-value"><?= number_format($commande['total'], 0, ',', ' ') ?> FCFA</div>
                </div>
                <div class="info-row">
                    <div class="info-label"><?php echo $trans['payment_method']; ?></div>
                    <div class="info-value"><?php echo $trans['cash_on_delivery']; ?></div>
                </div>
            </div>
            
            <div class="summary-card">
                <h3><i class="fas fa-user"></i> <?php echo $trans['customer_info']; ?></h3>
                <div class="info-row">
                    <div class="info-label"><?php echo $trans['name']; ?></div>
                    <div class="info-value"><?= htmlspecialchars($commande['client_nom'] ?? 'Non spécifié') ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><?php echo $trans['phone']; ?></div>
                    <div class="info-value"><?= htmlspecialchars($commande['telephone']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><?php echo $trans['address']; ?></div>
                    <div class="info-value"><?= htmlspecialchars($commande['adresse_livraison'] ?? 'Non spécifiée') ?></div>
                </div>
                <?php if (!empty($commande['latitude']) && !empty($commande['longitude'])): ?>
                <div class="info-row">
                    <div class="info-label"><?php echo $trans['location']; ?></div>
                    <div class="info-value">
                        <a href="https://www.google.com/maps?q=<?= $commande['latitude'] ?>,<?= $commande['longitude'] ?>" 
                           class="map-link" target="_blank">
                            <i class="fas fa-map-marker-alt"></i> <?php echo $trans['view_on_map']; ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="products-section">
            <h2 class="section-title">
                <i class="fas fa-box-open"></i> <?php echo $trans['ordered_products']; ?>
            </h2>
            
            <table class="products-table">
                <thead>
                    <tr>
                        <th><?php echo $trans['product']; ?></th>
                        <th><?php echo $trans['unit_price']; ?></th>
                        <th><?php echo $trans['quantity']; ?></th>
                        <th><?php echo $trans['subtotal']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    $total_regular = 0;
                    $total_savings = 0;
                    while ($p = $produits->fetch_assoc()): 
                        $subtotal = $p['prix_unitaire'] * $p['quantite'];
                        $total += $subtotal;
                        
                        // Calcul des économies si le produit était en promotion
                        $savings = 0;
                        $was_on_promo = false;
                        if ($p['promotion'] > 0 && $p['prix_unitaire'] < $p['prix_regulier']) {
                            $savings = ($p['prix_regulier'] - $p['prix_unitaire']) * $p['quantite'];
                            $total_savings += $savings;
                            $was_on_promo = true;
                        }
                        
                        $total_regular += $p['prix_regulier'] * $p['quantite'];
                    ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <div class="product-image">
                                    <?php if (!empty($p['image_path'])): ?>
                                        <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                                    <?php else: ?>
                                        <i class="fas fa-image"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-name">
                                    <?= htmlspecialchars($p['nom']) ?>
                                    <?php if ($was_on_promo): ?>
                                        <span class="promo-badge"><?php echo $trans['promo']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($was_on_promo): ?>
                                <span class="original-price"><?= number_format($p['prix_regulier'], 0, ',', ' ') ?> FCFA</span><br>
                                <span class="promo-price"><?= number_format($p['prix_unitaire'], 0, ',', ' ') ?> FCFA</span>
                            <?php else: ?>
                                <?= number_format($p['prix_unitaire'], 0, ',', ' ') ?> FCFA
                            <?php endif; ?>
                        </td>
                        <td><?= $p['quantite'] ?></td>
                        <td><?= number_format($subtotal, 0, ',', ' ') ?> FCFA</td>
                    </tr>
                    <?php endwhile; ?>
                    <tr class="order-total-row">
                        <td colspan="3" style="text-align: right; font-weight: 600;"><?php echo $trans['total']; ?></td>
                        <td><?= number_format($total, 0, ',', ' ') ?> FCFA</td>
                    </tr>
                    <?php if ($total_savings > 0): ?>
                    <tr class="order-total-row">
                        <td colspan="3" style="text-align: right; font-weight: 600;"><?php echo $trans['total_savings']; ?></td>
                        <td><?= number_format($total_savings, 0, ',', ' ') ?> FCFA</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Timeline de statut -->
        <div class="products-section">
            <h2 class="section-title">
                <i class="fas fa-history"></i> <?php echo $trans['status_history']; ?>
            </h2>
            
            <div class="status-timeline">
                <div class="timeline-item active">
                    <div class="timeline-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title"><?php echo $trans['order_placed']; ?></div>
                        <div class="timeline-date"><?= date("d M Y, H:i", strtotime($commande['date_commande'])) ?></div>
                        <div class="timeline-desc"><?php echo $trans['order_placed_desc']; ?></div>
                    </div>
                </div>
                
                <div class="timeline-item <?= strpos($commande['statut'], 'en cours') !== false ? 'active' : '' ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title"><?php echo $trans['preparing']; ?></div>
                        <div class="timeline-date"><?= date("d M Y, H:i", strtotime($commande['date_commande'] . ' + 15 minutes')) ?></div>
                        <div class="timeline-desc"><?php echo $trans['preparing_desc']; ?></div>
                    </div>
                </div>
                
                <div class="timeline-item <?= strpos($commande['statut'], 'en cours') !== false ? 'active' : '' ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title"><?php echo $trans['out_for_delivery']; ?></div>
                        <div class="timeline-date"><?= date("d M Y, H:i", strtotime($commande['date_commande'] . ' + 30 minutes')) ?></div>
                        <div class="timeline-desc"><?php echo $trans['out_for_delivery_desc']; ?></div>
                    </div>
                </div>
                
                <div class="timeline-item <?= strpos($commande['statut'], 'livré') !== false ? 'active' : '' ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title"><?php echo $trans['delivered']; ?></div>
                        <div class="timeline-date"><?= date("d M Y, H:i", strtotime($commande['date_commande'] . ' + 45 minutes')) ?></div>
                        <div class="timeline-desc"><?php echo $trans['delivered_desc']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="mes_commandes.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> <?php echo $trans['back_to_orders']; ?>
            </a>
            <button id="helpButton" class="btn btn-help">
                <i class="fas fa-question-circle"></i> <?php echo $trans['help_with_order']; ?>
            </button>
            <a href="produits.php" class="btn btn-reorder">
                <i class="fas fa-redo-alt"></i> <?php echo $trans['reorder']; ?>
            </a>
        </div>
    </div>
</div>

<!-- Modal d'aide -->
<div class="help-modal" id="helpModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-headset"></i> <?php echo $trans['support_for_order']; ?><?= $commande_id ?></h3>
            <button class="modal-close" onclick="closeHelpModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <form id="helpForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="commande_id" value="<?= $commande_id ?>">
                <input type="hidden" name="client_id" value="<?= $_SESSION['client_id'] ?? '' ?>">
                
                <div class="form-group">
                    <label for="issueType"><?php echo $trans['issue_type']; ?></label>
                    <select id="issueType" name="sujet" class="form-control">
                        <option value=""><?php echo $trans['select_issue_type']; ?></option>
                        <option value="Problème de livraison"><?php echo $trans['delivery_issue']; ?></option>
                        <option value="Produit manquant ou défectueux"><?php echo $trans['missing_or_defective_product']; ?></option>
                        <option value="Problème de facturation"><?php echo $trans['billing_issue']; ?></option>
                        <option value="Autre problème"><?php echo $trans['other_issue']; ?></option>
                    </select>
                    <small class="text-muted"><?php echo $trans['optional_if_audio']; ?></small>
                </div>
                
                <div class="form-group">
                    <label for="message"><?php echo $trans['describe_problem']; ?></label>
                    <textarea id="message" name="message" class="form-control" rows="5" 
                              placeholder="<?php echo $trans['describe_problem_placeholder']; ?>"></textarea>
                    <small class="text-muted"><?php echo $trans['optional_if_audio']; ?></small>
                </div>
                
                <div class="form-group">
                    <label for="contactMethod"><?php echo $trans['preferred_contact_method']; ?></label>
                    <div class="contact-methods">
                        <label>
                            <input type="radio" name="contactMethod" value="telephone" checked>
                            <i class="fas fa-phone"></i> <?php echo $trans['phone']; ?>
                        </label>
                        <label>
                            <input type="radio" name="contactMethod" value="email">
                            <i class="fas fa-envelope"></i> <?php echo $trans['email']; ?>
                        </label>
                        <label>
                            <input type="radio" name="contactMethod" value="whatsapp">
                            <i class="fab fa-whatsapp"></i> <?php echo $trans['whatsapp']; ?>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?php echo $trans['voice_recording']; ?></label>
                    <div class="audio-recorder">
                        <button type="button" id="recordButton" class="record-btn">
                            <i class="fas fa-microphone"></i> <?php echo $trans['record']; ?>
                        </button>
                        <div class="audio-preview" id="audioPreview"></div>
                    </div>
                    <div class="audio-upload" id="audioUpload">
                        <label><?php echo $trans['or_upload_audio']; ?></label>
                        <input type="file" accept="audio/*" name="audio_file">
                    </div>
                    <div class="permission-guide" id="permissionGuide">
                        <h4><i class="fas fa-info-circle"></i> <?php echo $trans['how_to_allow_microphone']; ?></h4>
                        <ol class="permission-steps">
                            <li><?php echo $trans['step1_lock_icon']; ?></li>
                            <li><?php echo $trans['step2_site_settings']; ?></li>
                            <li><?php echo $trans['step3_allow_microphone']; ?></li>
                            <li><?php echo $trans['step4_refresh']; ?></li>
                        </ol>
                    </div>
                    <small><?php echo $trans['record_60_seconds_max']; ?></small>
                </div>
                
                <div class="audio-only-note" style="background: #e3f2fd; padding: 10px; border-radius: 8px; margin-top: 10px;">
                    <i class="fas fa-info-circle"></i> <?php echo $trans['audio_only_note']; ?>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> <?php echo $trans['send_request']; ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Fonctions pour la modal d'aide
    function openHelpModal() {
        document.getElementById('helpModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeHelpModal() {
        document.getElementById('helpModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Ouvrir la modal d'aide
    document.getElementById('helpButton').addEventListener('click', openHelpModal);
    
    // Fermer en cliquant en dehors du contenu
    document.getElementById('helpModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeHelpModal();
        }
    });
    
    // Enregistrement audio
    let mediaRecorder;
    let audioChunks = [];
    let audioBlob = null;
    
    document.getElementById('recordButton').addEventListener('click', async function() {
        const recordBtn = this;
        
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            // Arrêter l'enregistrement
            mediaRecorder.stop();
            recordBtn.classList.remove('recording');
            recordBtn.innerHTML = '<i class="fas fa-microphone"></i> <?php echo $trans["record"]; ?>';
            return;
        }
        
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            
            // Afficher l'indicateur d'enregistrement
            const audioPreview = document.getElementById('audioPreview');
            audioPreview.style.display = 'flex';
            audioPreview.innerHTML = `
                <div class="audio-indicator">
                    <div class="wave"></div>
                    <span><?php echo $trans["sending"]; ?></span>
                </div>
            `;
            
            // Masquer le guide d'autorisation
            document.getElementById('permissionGuide').classList.remove('active');
            
            recordBtn.classList.add('recording');
            recordBtn.innerHTML = '<i class="fas fa-stop"></i> <?php echo $trans["record"]; ?>';
            
            audioChunks = [];
            mediaRecorder.ondataavailable = event => {
                audioChunks.push(event.data);
            };
            
            mediaRecorder.onstop = () => {
                audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                // Afficher un message de succès
                audioPreview.innerHTML = `
                    <div class="audio-indicator">
                        <i class="fas fa-check-circle" style="color:#4CAF50"></i> <?php echo $trans["record"]; ?>
                    </div>
                `;
                
                // Fermer le flux audio
                stream.getTracks().forEach(track => track.stop());
            };
            
            // Démarrer l'enregistrement avec une limite de 60 secondes
            mediaRecorder.start();
            setTimeout(() => {
                if (mediaRecorder.state === 'recording') {
                    mediaRecorder.stop();
                    recordBtn.classList.remove('recording');
                    recordBtn.innerHTML = '<i class="fas fa-microphone"></i> <?php echo $trans["record"]; ?>';
                }
            }, 60000); // 60 secondes
            
        } catch (error) {
            let errorMessage;
            
            switch(error.name) {
                case 'NotAllowedError':
                    errorMessage = "<?php echo $trans['microphone_blocked']; ?>";
                    // Afficher le guide d'autorisation
                    document.getElementById('permissionGuide').classList.add('active');
                    break;
                case 'NotFoundError':
                    errorMessage = "<?php echo $trans['no_microphone_detected']; ?>";
                    break;
                case 'NotReadableError':
                    errorMessage = "<?php echo $trans['microphone_in_use']; ?>";
                    break;
                default:
                    errorMessage = `<?php echo $trans['microphone_error']; ?> ${error.message}`;
            }
            
            // Afficher dans l'UI
            const audioPreview = document.getElementById('audioPreview');
            audioPreview.style.display = 'block';
            audioPreview.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> ${errorMessage}
                </div>
            `;
            
            // Afficher l'alternative d'upload audio
            document.getElementById('audioUpload').classList.add('active');
        }
    });
    
    // Soumission du formulaire
    document.getElementById('helpForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Vérifier qu'au moins un audio ou un message est fourni
        const messageText = document.getElementById('message').value.trim();
        const audioFile = document.querySelector('input[name="audio_file"]').files[0];
        
        if (!messageText && !audioBlob && !audioFile) {
            showNotification('<?php echo $trans["please_record_audio"]; ?>', 'error');
            return;
        }
        
        // Afficher un indicateur de chargement
        const submitBtn = this.querySelector('.btn-submit');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo $trans["sending"]; ?>';
        submitBtn.disabled = true;
        
        try {
            // Créer un FormData pour envoyer les données
            const formData = new FormData(this);
            
            // Ajouter l'enregistrement audio s'il existe
            if (audioBlob) {
                const audioFile = new File([audioBlob], 'enregistrement.webm', { type: 'audio/webm' });
                formData.append('audio', audioFile);
            }
            
            // Envoyer les données au serveur
            const response = await fetch('envoyer_message_support.php', {
                method: 'POST',
                body: formData
            });
            
            // Vérifier si la réponse est du JSON valide
            const responseText = await response.text();
            let data;
            
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                // Si la réponse n'est pas du JSON, afficher l'erreur
                console.error("<?php echo $trans['invalid_server_response']; ?>:", responseText);
                throw new Error("<?php echo $trans['invalid_server_response']; ?>");
            }
            
            if (data.success) {
                closeHelpModal();
                showNotification('<?php echo $trans["audio_request_sent"]; ?>', 'success');
                
                // Réinitialiser le formulaire
                this.reset();
                document.getElementById('audioPreview').style.display = 'none';
                document.getElementById('audioPreview').innerHTML = '';
                audioBlob = null;
                
                // Rediriger vers la page des messages après 2 secondes
                setTimeout(() => {
                    window.location.href = 'message_support.php';
                }, 2000);
            } else {
                showNotification('<?php echo $trans["error_occurred_sending"]; ?>: ' + (data.error || ''), 'error');
            }
        } catch (error) {
            showNotification('<?php echo $trans["network_error"]; ?> ' + error.message, 'error');
        } finally {
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> <?php echo $trans["send_request"]; ?>';
            submitBtn.disabled = false;
        }
    });
    
    // Fonction pour afficher les notifications
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 4000);
    }
    
    // Styles pour les notifications
    const notificationStyle = document.createElement('style');
    notificationStyle.innerHTML = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .notification.success {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
        }
        
        .notification.error {
            background: linear-gradient(135deg, #f44336 0%, #c62828 100%);
        }
    `;
    document.head.appendChild(notificationStyle);
</script>
<?php include("footer.php"); ?>
</body>
</html>