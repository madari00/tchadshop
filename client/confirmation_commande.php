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
$commande_id = (int)$_GET['id'];

// Récupérer les détails de la commande
$sql = "SELECT c.id, c.date_commande, c.total, c.statut, c.date_livraison_prevue,
               cl.nom AS client_nom, cl.telephone, cl.email
        FROM commandes c
        JOIN clients cl ON c.client_id = cl.id
        WHERE c.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $commande_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Commande introuvable");
}

$commande = $result->fetch_assoc();

// Récupérer les produits de la commande AVEC IMAGES
$sqlDetails = "SELECT dc.quantite, dc.prix_unitaire, dc.promotion,
                      p.nom AS produit_nom, p.description,
                      (SELECT image FROM images_produit WHERE produit_id = p.id LIMIT 1) AS image
               FROM details_commandes dc
               JOIN produits p ON dc.produit_id = p.id
               WHERE dc.commande_id = ?";
               
$stmtDetails = $conn->prepare($sqlDetails);
$stmtDetails->bind_param("i", $commande_id);
$stmtDetails->execute();
$details = $stmtDetails->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmation de commande - TchadShop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #8E44AD;
      --secondary: #3498DB;
      --accent: #E74C3C;
      --success: #27AE60;
      --warning: #F39C12;
      --light: #F8F9FA;
      --dark: #212529;
      --gray: #6C757D;
    }
    
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
      display: flex;
      flex-direction: column;
      padding: 20px;
    }

    .mobile-header {
      padding: 15px;
      position: sticky;
      top: 0;
      z-index: 100;
      background: white;
      border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .btn-back-mobile {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--primary);
      color: white;
      padding: 10px 20px;
      border-radius: 50px;
      font-weight: 600;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    
    .btn-back-mobile:hover {
      background: #7D3C98;
      transform: translateY(-3px);
    }

    .container {
      max-width: 1000px;
      width: 100%;
      margin: 20px auto;
      text-align: center;
      flex: 1;
    }
    
    .card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
    }
    
    .card-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      padding: 30px;
      position: relative;
    }
    
    .card-header::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 20px;
      background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23ffffff' fill-opacity='1' d='M0,160L48,149.3C96,139,192,117,288,122.7C384,128,480,160,576,181.3C672,203,768,213,864,197.3C960,181,1056,139,1152,128C1248,117,1344,139,1392,149.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
      background-size: cover;
    }

    .icon-success {
      font-size: 5rem;
      color: white;
      margin-bottom: 20px;
      animation: bounce 1.5s infinite;
    }

    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
      40% {transform: translateY(-20px);}
      60% {transform: translateY(-10px);}
    }
    
    .confirmation-title {
      font-size: 2.5rem;
      margin-bottom: 15px;
      text-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .confirmation-subtitle {
      font-size: 1.2rem;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto;
    }
    
    .card-body {
      padding: 30px;
    }
    
    .order-overview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .order-card {
      background: var(--light);
      border-radius: 15px;
      padding: 20px;
      text-align: left;
      border-left: 4px solid var(--primary);
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .order-card h3 {
      color: var(--gray);
      font-size: 1rem;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .order-card p {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--dark);
    }
    
    .status-badge {
      display: inline-block;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: 600;
      background: var(--warning);
      color: white;
    }
    
    .status-badge.confirmed {
      background: var(--success);
    }
    
    .section-title {
      text-align: left;
      font-size: 1.5rem;
      color: var(--primary);
      margin: 30px 0 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid var(--light);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .products-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }
    
    .products-table th {
      background: var(--light);
      padding: 15px;
      text-align: left;
      color: var(--gray);
      font-weight: 600;
    }
    
    .products-table td {
      padding: 15px;
      border-bottom: 1px solid var(--light);
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
      object-fit: cover;
      background: #f5f5f5;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--gray);
      overflow: hidden;
    }
    
    .product-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .promo-tag {
      background: var(--accent);
      color: white;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 10px;
    }
    
    .actions {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 30px;
      flex-wrap: wrap;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 16px 30px;
      border-radius: 50px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 1.1rem;
      border: none;
      cursor: pointer;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      box-shadow: 0 5px 15px rgba(142, 68, 173, 0.3);
    }
    
    .btn-outline {
      background: white;
      color: var(--primary);
      border: 2px solid var(--primary);
    }
    
    .btn-whatsapp {
      background: #25D366;
      color: white;
      box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
    }
    
    .btn:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
    
    .delivery-info {
      background: #e8f4ff;
      border-radius: 15px;
      padding: 20px;
      margin-top: 30px;
      text-align: left;
      border-left: 4px solid var(--secondary);
    }
    
    .delivery-info h3 {
      color: var(--secondary);
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    /* Styles pour tablettes */
    @media (max-width: 1024px) {
      .card-header {
        padding: 25px;
      }
      
      .confirmation-title {
        font-size: 2.2rem;
      }
      
      .icon-success {
        font-size: 4rem;
      }
    }

    /* Styles pour téléphones */
    @media (max-width: 768px) {
      .mobile-header {
        position: static;
      }
      
      .card {
        border-radius: 15px;
      }
      
      .confirmation-title {
        font-size: 2rem;
      }
      
      .confirmation-subtitle {
        font-size: 1.1rem;
      }
      
      .order-overview {
        grid-template-columns: 1fr;
      }
      
      .products-table th, .products-table td {
        padding: 10px;
        font-size: 0.9rem;
      }
      
      .btn {
        width: 100%;
        padding: 14px;
      }
    }
    
    /* Téléphones très petits */
    @media (max-width: 480px) {
      .card-header {
        padding: 20px;
      }
      
      .confirmation-title {
        font-size: 1.8rem;
      }
      
      .icon-success {
        font-size: 3.5rem;
      }
      
      .product-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
      }
    }
  </style>
</head>
<body>
  <!-- En-tête mobile avec bouton retour -->
<div class="mobile-header">
    <a href="index.php" class="btn-back-mobile">
        <i class="fas fa-arrow-left"></i> <?php echo $trans['home']; ?>
    </a>
</div>

<div class="container">
    <div class="card">
        <div class="card-header">
            <div class="icon-success">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1 class="confirmation-title"><?php echo $trans['order_confirmed']; ?> !</h1>
            
            <p class="confirmation-subtitle">
                <?php echo $trans['order_registered_success']; ?>
            </p>
        </div>
        
        <div class="card-body">
            <div class="order-overview">
                <div class="order-card">
                    <h3><i class="fas fa-hashtag"></i> <?php echo $trans['order_number']; ?></h3>
                    <p>#<?= $commande['id'] ?></p>
                </div>
                
                <div class="order-card">
                    <h3><i class="fas fa-calendar-alt"></i> <?php echo $trans['order_date']; ?></h3>
                    <p><?= date('d/m/Y H:i', strtotime($commande['date_commande'])) ?></p>
                </div>
                
                <div class="order-card">
                    <h3><i class="fas fa-truck"></i> <?php echo $trans['status']; ?></h3>
                    <p><span class="status-badge confirmed"><?= ucfirst($commande['statut']) ?></span></p>
                </div>
                
                <div class="order-card">
                    <h3><i class="fas fa-receipt"></i> <?php echo $trans['total_amount']; ?></h3>
                    <p><?= number_format($commande['total'], 0, ',', ' ') ?> FCFA</p>
                </div>
            </div>
            
            <h2 class="section-title"><i class="fas fa-box-open"></i> <?php echo $trans['ordered_products']; ?></h2>
            
            <table class="products-table">
                <thead>
                    <tr>
                        <th><?php echo $trans['product']; ?></th>
                        <th><?php echo $trans['unit_price']; ?></th>
                        <th><?php echo $trans['quantity']; ?></th>
                        <th><?php echo $trans['total']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $detail): ?>
                        <tr>
                            <td>
                                <div class="product-info">
                                    <div class="product-image">
                                        <?php if (!empty($detail['image'])): ?>
                                            <img src="<?= htmlspecialchars($detail['image']) ?>" alt="<?= htmlspecialchars($detail['produit_nom']) ?>">
                                        <?php else: ?>
                                            <i class="fas fa-box"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?= $detail['produit_nom'] ?></strong>
                                        <?php if ($detail['promotion']): ?>
                                            <span class="promo-tag"><?php echo $trans['promo']; ?></span>
                                        <?php endif; ?>
                                        <p style="color: var(--gray); font-size: 0.9rem; margin-top: 5px;">
                                            <?= substr($detail['description'], 0, 50) ?>...
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td><?= number_format($detail['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
                            <td><?= $detail['quantite'] ?></td>
                            <td><strong><?= number_format($detail['prix_unitaire'] * $detail['quantite'], 0, ',', ' ') ?> FCFA</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="delivery-info">
                <h3><i class="fas fa-calendar-check"></i> <?php echo $trans['expected_delivery']; ?></h3>
                <p><?php echo $trans['your_order_will_be_delivered']; ?> <strong><?= date('d/m/Y', strtotime($commande['date_livraison_prevue'])) ?></strong>.</p>
                <p style="margin-top: 10px;"><?php echo $trans['we_will_contact_you_at']; ?> <strong><?= $commande['telephone'] ?></strong> <?php echo $trans['to_confirm_delivery']; ?>.</p>
            </div>
            
            <div class="actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> <?php echo $trans['back_to_home']; ?>
                </a>
                <a href="produits.php" class="btn btn-outline">
                    <i class="fas fa-shopping-cart"></i> <?php echo $trans['continue_shopping']; ?>
                </a>
                <a href="https://wa.me/23560000000" class="btn btn-whatsapp">
                    <i class="fab fa-whatsapp"></i> <?php echo $trans['contact_support']; ?>
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>