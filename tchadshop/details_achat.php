<?php
session_start();
$searchContext = 'achat';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID d'achat invalide.");
}

// Récupération des détails de l'achat depuis historique_achats
$sql_achat = "
SELECT 
    ha.*, 
    COALESCE(c.nom, 'Anonyme') AS client, 
    c.telephone,
    c.email,
    p.nom AS produit_nom,
    p.description AS produit_description,
    p.promotion,
    p.date_debut_promo,
    p.date_fin_promo,
    p.prix AS prix_original,
    p.prix_promotion AS prix_promo,
    com.statut,
    com.id AS reference_commande,
    com.date_commande,
    com.date_livraison,
    com.total AS total_commande,
    com.adresse
FROM historique_achats ha
LEFT JOIN clients c ON ha.client_id = c.id
LEFT JOIN produits p ON ha.produit_id = p.id
LEFT JOIN commandes com ON ha.commande_id = com.id
WHERE ha.id = $id
";

$result_achat = $conn->query($sql_achat);

if (!$result_achat || $result_achat->num_rows === 0) {
    die("Achat non trouvé.");
}

$achat = $result_achat->fetch_assoc();
$is_commande = !empty($achat['commande_id']);

// Récupérer tous les produits de la commande si c'est une commande
$produits = [];
if ($is_commande) {
    $commande_id = $achat['commande_id'];
    $sql_produits_commande = "
    SELECT 
        p.nom, 
        p.description,
        dc.quantite, 
        dc.prix_unitaire,
        (dc.quantite * dc.prix_unitaire) AS sous_total,
        p.promotion,
        p.date_debut_promo,
        p.date_fin_promo,
        p.prix AS prix_original,
        p.prix_promotion AS prix_promo
    FROM details_commandes dc
    JOIN produits p ON dc.produit_id = p.id
    WHERE dc.commande_id = $commande_id
    ";
    $result_produits = $conn->query($sql_produits_commande);
    while ($row = $result_produits->fetch_assoc()) {
        $produits[] = $row;
    }
} else {
    // Pour un achat direct, on n'a qu'un seul produit
    $produits[] = [
        'nom' => $achat['produit_nom'],
        'description' => $achat['produit_description'],
        'quantite' => $achat['quantite'],
        'prix_unitaire' => $achat['prix_unitaire'],
        'sous_total' => $achat['quantite'] * $achat['prix_unitaire'],
        'promotion' => $achat['promotion'],
        'date_debut_promo' => $achat['date_debut_promo'],
        'date_fin_promo' => $achat['date_fin_promo'],
        'prix_original' => $achat['prix_original'],
        'prix_promo' => $achat['prix_promo']
    ];
}

// Calcul du total et des économies
$total_achat = 0;
$economie_total = 0;
$achat_date = $is_commande ? $achat['date_commande'] : $achat['date_achat'];

foreach ($produits as &$produit) {
    $prix_normal = $produit['prix_original'] ?? $produit['prix_unitaire'];
    $promo_active = false;
    
    // Vérifier si la promotion est active à la date de l'achat
    if (!empty($produit['promotion']) && $produit['promotion'] > 0 && 
        !empty($produit['date_debut_promo']) && !empty($produit['date_fin_promo']) &&
        $achat_date >= $produit['date_debut_promo'] && 
        $achat_date <= $produit['date_fin_promo']) {
        
        $promo_active = true;
        $prix_promo = $prix_normal * (1 - ($produit['promotion'] / 100));
        $sous_total_promo = $prix_promo * $produit['quantite'];
        $economie = ($prix_normal * $produit['quantite']) - $sous_total_promo;
        $economie_total += $economie;
        
        // Mettre à jour le sous-total pour ce produit
        $produit['sous_total'] = $sous_total_promo;
        $produit['prix_promo'] = $prix_promo;
        $produit['economie'] = $economie;
    } else {
        $sous_total_promo = $prix_normal * $produit['quantite'];
        $produit['sous_total'] = $sous_total_promo;
        $produit['prix_promo'] = $prix_normal;
        $produit['economie'] = 0;
    }
    
    $produit['promo_active'] = $promo_active;
    $total_achat += $produit['sous_total'];
}
unset($produit); // casser la référence

// Ajout de la gestion des promotions pour l'achat principal
$achat['promo_active'] = false;
if (!empty($achat['promotion']) && $achat['promotion'] > 0 && 
    !empty($achat['date_debut_promo']) && !empty($achat['date_fin_promo']) &&
    $achat_date >= $achat['date_debut_promo'] && 
    $achat_date <= $achat['date_fin_promo']) {
    $achat['promo_active'] = true;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de l'achat #<?= $id ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
           
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(120deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .header h1 i {
            background: rgba(255, 255, 255, 0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .client-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 25px;
            background: #f8fafc;
            border-bottom: 1px solid #eee;
        }
        
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
        
        .info-card h3 {
            color: #6a1b9a;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f4f8;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 12px;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 150px;
        }
        
        .info-value {
            color: #333;
        }
        
        .products-section {
            padding: 25px;
        }
        
        .section-title {
            font-size: 22px;
            color: #6a1b9a;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f4f8;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #6a1b9a;
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f9f9f9;
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
        
        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 14px;
        }
        
        .promo-price {
            color: #e91e63;
            font-weight: bold;
            font-size: 16px;
        }
        
        .economy {
            color: #4caf50;
            font-weight: 600;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .summary {
            background: #f8fafc;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .total-row {
            font-size: 20px;
            font-weight: bold;
            color: #6a1b9a;
            padding-top: 15px;
            margin-top: 5px;
            border-top: 2px solid #eee;
        }
        
        .economy-row {
            color: #4caf50;
            font-weight: 600;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 25px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }
        
        .edit-btn {
            background: #ff9800;
            color: white;
        }
        
        .delete-btn {
            background: #f44336;
            color: white;
        }
        
        .back-btn {
            background: #6a1b9a;
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 768px) {
            .client-info {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
                align-items: center;
            }
            
            .action-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
            
            th, td {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
<div class="home-content">
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-receipt"></i>
                Détails de l'achat #<?= $id ?>
            </h1>
        </div>
        
        <div class="client-info">
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Informations client</h3>
                <div class="info-row">
                    <div class="info-label">Client:</div>
                    <div class="info-value"><?= htmlspecialchars($achat['client']) ?></div>
                </div>
                <?php if (!empty($achat['telephone'])): ?>
                <div class="info-row">
                    <div class="info-label">Téléphone:</div>
                    <div class="info-value"><?= htmlspecialchars($achat['telephone']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($achat['email'])): ?>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?= htmlspecialchars($achat['email']) ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Détails de l'achat</h3>
                <div class="info-row">
                    <div class="info-label">Type:</div>
                    <div class="info-value">
                        <?= $is_commande ? 'Commande' : 'Achat direct' ?>
                        <?php if ($achat['promo_active']): ?>
                            <span class="promo-badge">Promo active</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date:</div>
                    <div class="info-value">
                        <?= $is_commande ? $achat['date_commande'] : $achat['date_achat'] ?>
                    </div>
                </div>
                <?php if ($is_commande): ?>
                <div class="info-row">
                    <div class="info-label">Référence:</div>
                    <div class="info-value">
                        <?= !empty($achat['reference_commande']) ? htmlspecialchars($achat['reference_commande']) : 'N/A' ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Statut:</div>
                    <div class="info-value">
                        <?= !empty($achat['statut']) ? ucfirst($achat['statut']) : 'N/A' ?>
                    </div>
                </div>
                <?php if (!empty($achat['adresse'])): ?>
                <div class="info-row">
                    <div class="info-label">Adresse:</div>
                    <div class="info-value">
                        <?= htmlspecialchars($achat['adresse']) ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($achat['date_livraison'])): ?>
                <div class="info-row">
                    <div class="info-label">Livraison:</div>
                    <div class="info-value">
                        <?= htmlspecialchars($achat['date_livraison']) ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="products-section">
            <h2 class="section-title">
                <i class="fas fa-box-open"></i> Produits achetés
            </h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix Unitaire</th>
                        <th>Sous-total</th>
                        <th>Promotion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $produit): ?>
                    <tr>
                        <td>
                            <div><strong><?= htmlspecialchars($produit['nom']) ?></strong></div>
                            <?php if (!empty($produit['description'])): ?>
                            <div style="font-size: 0.9em; color: #666; margin-top: 5px;">
                                <?= htmlspecialchars($produit['description']) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?= $produit['quantite'] ?></td>
                        <td>
                            <?php if ($produit['promo_active']): ?>
                                <div class="original-price"><?= number_format($produit['prix_original'], 0, ',', ' ') ?> FCFA</div>
                                <div class="promo-price"><?= number_format($produit['prix_promo'], 0, ',', ' ') ?> FCFA</div>
                            <?php else: ?>
                                <?= number_format($produit['prix_unitaire'], 0, ',', ' ') ?> FCFA
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($produit['promo_active']): ?>
                                <div class="original-price"><?= number_format($produit['prix_original'] * $produit['quantite'], 0, ',', ' ') ?> FCFA</div>
                                <div class="promo-price"><?= number_format($produit['sous_total'], 0, ',', ' ') ?> FCFA</div>
                            <?php else: ?>
                                <?= number_format($produit['sous_total'], 0, ',', ' ') ?> FCFA
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($produit['promo_active']): ?>
                                <span class="promo-badge">-<?= $produit['promotion'] ?>%</span>
                                <div class="economy">Économie: <?= number_format($produit['economie'], 0, ',', ' ') ?> FCFA</div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="summary">
                <div class="summary-row">
                    <div>Sous-total:</div>
                    <div>
                        <?php if ($economie_total > 0): ?>
                            <div class="original-price"><?= number_format($total_achat + $economie_total, 0, ',', ' ') ?> FCFA</div>
                        <?php endif; ?>
                        <div class="promo-price"><?= number_format($total_achat, 0, ',', ' ') ?> FCFA</div>
                    </div>
                </div>
                
                <?php if ($economie_total > 0): ?>
                <div class="summary-row economy-row">
                    <div>Économies grâce aux promotions:</div>
                    <div>-<?= number_format($economie_total, 0, ',', ' ') ?> FCFA</div>
                </div>
                <?php endif; ?>
                
                <div class="summary-row total-row">
                    <div>Total final:</div>
                    <div><?= number_format($total_achat, 0, ',', ' ') ?> FCFA</div>
                </div>
            </div>
        </div>
        
        <div class="actions">
            <a href="modifier_achat.php?id=<?= $id ?>" class="action-btn edit-btn">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="supprimer_achat.php?id=<?= $id ?>" class="action-btn delete-btn" onclick="return confirm('Supprimer cet achat ?')">
                <i class="fas fa-trash"></i> Supprimer
            </a>
            <a href="historique_achats.php" class="action-btn back-btn">
                <i class="fas fa-arrow-left"></i> Retour à l'historique
            </a>
        </div>
    </div>
</body>
</html>