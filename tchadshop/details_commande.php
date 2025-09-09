<?php
session_start();
$searchContext = 'dcommande';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("❌ ID de commande invalide.");
}

// Fonction pour échapper les valeurs
function safe($val) {
    return htmlspecialchars($val ?? '');
}

// Récupérer la commande
$req = $conn->prepare("
    SELECT c.*, cl.nom AS client_nom, cl.telephone AS client_tel, cl.invite AS client_invite, l.nom AS livreur_nom 
    FROM commandes c
    LEFT JOIN clients cl ON c.client_id = cl.id
    LEFT JOIN livreurs l ON c.livreur_id = l.id
    WHERE c.id = ?
");
$req->bind_param("i", $id);
$req->execute();
$result = $req->get_result();
$commande = $result->fetch_assoc();

if (!$commande) {
    die("❌ Commande non trouvée.");
}

// Récupérer les produits avec les informations de promotion
$produits = $conn->query("
    SELECT 
        p.nom, 
        p.description,
        dc.quantite, 
        dc.prix_unitaire,
        dc.promotion AS promo_appliquee,
        p.promotion AS promo_actuelle,
        p.prix_promotion AS prix_promo_actuel,
        p.prix AS prix_actuel,
        p.date_debut_promo,
        p.date_fin_promo
    FROM details_commandes dc
    INNER JOIN produits p ON dc.produit_id = p.id
    WHERE dc.commande_id = $id
");

// Calcul du total et des économies
$total_calcule = 0;
$economie_totale = 0;

// Préparer les données des produits
$produits_data = [];
while ($p = $produits->fetch_assoc()) {
    $sous_total = $p['prix_unitaire'] * $p['quantite'];
    $total_calcule += $sous_total;
    
    // Vérifier si une promotion était appliquée
    $promo_appliquee = $p['promo_appliquee'] == 1;
    
    // Calculer l'économie si promotion appliquée
    $economie = 0;
    if ($promo_appliquee && $p['prix_actuel'] > 0) {
        $prix_normal = $p['prix_actuel'];
        $economie = ($prix_normal * $p['quantite']) - $sous_total;
        $economie_totale += $economie;
    }
    
    $produits_data[] = [
        'nom' => $p['nom'],
        'description' => $p['description'],
        'quantite' => $p['quantite'],
        'prix_unitaire' => $p['prix_unitaire'],
        'sous_total' => $sous_total,
        'promo_appliquee' => $promo_appliquee,
        'economie' => $economie,
        'prix_actuel' => $p['prix_actuel']
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de la commande #<?= $id ?></title>
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
            margin: 20px auto;
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

        .home-content {
            padding: 20px;
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
                    Détails de la commande #<?= $id ?>
                </h1>
            </div>
            
            <div class="client-info">
                <div class="info-card">
                    <h3><i class="fas fa-user"></i> Informations client</h3>
                    <div class="info-row">
                        <div class="info-label">Client:</div>
                        <div class="info-value">
                            <?= safe($commande['client_nom']) ?: 'Sans client' ?>
                            <?php if (!empty($commande['client_invite'])): ?>
                                <span class="promo-badge">Invité</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Téléphone:</div>
                        <div class="info-value"><?= safe($commande['client_tel']) ?: '-' ?></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Détails de la commande</h3>
                    <div class="info-row">
                        <div class="info-label">Date commande:</div>
                        <div class="info-value"><?= safe($commande['date_commande']) ?></div>
                    </div>
                    <?php if (strtolower($commande['statut']) === 'livré' && $commande['date_livraison']): ?>
                    <div class="info-row">
                        <div class="info-label">Date livraison:</div>
                        <div class="info-value"><?= safe($commande['date_livraison']) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <div class="info-label">Statut:</div>
                        <div class="info-value">
                            <span class="<?=
                                strtolower($commande['statut']) === 'livré' ? 'statut-livre' :
                                (strtolower($commande['statut']) === 'échec' ? 'statut-echec' : 'statut-attente')
                            ?>">
                                <?= ucfirst(safe($commande['statut'])) ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Livreur:</div>
                        <div class="info-value"><?= safe($commande['livreur_nom']) ?: 'Non assigné' ?></div>
                    </div>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Adresse de livraison</h3>
                    <div class="info-row">
                        <div class="info-label">Adresse:</div>
                        <div class="info-value"><?= $commande['adresse'] ? safe($commande['adresse']) : '❌ Non renseignée' ?></div>
                    </div>
                    <?php if (!empty($commande['latitude']) && !empty($commande['longitude'])): ?>
                    <div class="info-row">
                        <div class="info-label">Coordonnées:</div>
                        <div class="info-value">
                            Lat: <?= $commande['latitude'] ?>, Lng: <?= $commande['longitude'] ?>
                            <a href="https://maps.google.com/?q=<?= $commande['latitude'] ?>,<?= $commande['longitude'] ?>" target="_blank" style="margin-left: 10px;">
                                <i class="fas fa-map-marked-alt"></i> Voir sur la carte
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="products-section">
                <h2 class="section-title">
                    <i class="fas fa-box-open"></i> Produits commandés
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
                        <?php foreach ($produits_data as $produit): ?>
                        <tr>
                            <td>
                                <div><strong><?= safe($produit['nom']) ?></strong></div>
                                <?php if (!empty($produit['description'])): ?>
                                <div style="font-size: 0.9em; color: #666; margin-top: 5px;">
                                    <?= safe($produit['description']) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?= $produit['quantite'] ?></td>
                            <td>
                                <?php if ($produit['promo_appliquee']): ?>
                                    <div class="original-price"><?= number_format($produit['prix_actuel'], 0, ',', ' ') ?> FCFA</div>
                                    <div class="promo-price"><?= number_format($produit['prix_unitaire'], 0, ',', ' ') ?> FCFA</div>
                                <?php else: ?>
                                    <?= number_format($produit['prix_unitaire'], 0, ',', ' ') ?> FCFA
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($produit['promo_appliquee']): ?>
                                    <div class="original-price"><?= number_format($produit['prix_actuel'] * $produit['quantite'], 0, ',', ' ') ?> FCFA</div>
                                    <div class="promo-price"><?= number_format($produit['sous_total'], 0, ',', ' ') ?> FCFA</div>
                                <?php else: ?>
                                    <?= number_format($produit['sous_total'], 0, ',', ' ') ?> FCFA
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($produit['promo_appliquee']): ?>
                                    <span class="promo-badge">Promo</span>
                                    <?php if ($produit['economie'] > 0): ?>
                                    <div class="economy">Économie: <?= number_format($produit['economie'], 0, ',', ' ') ?> FCFA</div>
                                    <?php endif; ?>
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
                            <?php if ($economie_totale > 0): ?>
                                <div class="original-price"><?= number_format($total_calcule + $economie_totale, 0, ',', ' ') ?> FCFA</div>
                            <?php endif; ?>
                            <div class="promo-price"><?= number_format($total_calcule, 0, ',', ' ') ?> FCFA</div>
                        </div>
                    </div>
                    
                    <?php if ($economie_totale > 0): ?>
                    <div class="summary-row economy-row">
                        <div>Économies grâce aux promotions:</div>
                        <div>-<?= number_format($economie_totale, 0, ',', ' ') ?> FCFA</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total-row">
                        <div>Total final:</div>
                        <div><?= number_format($commande['total'], 0, ',', ' ') ?> FCFA</div>
                    </div>
                </div>
            </div>
            
            <div class="actions">
                <a href="modifier_commande.php?id=<?= $commande['id'] ?>" class="action-btn edit-btn">
                    <i class="fas fa-edit"></i> Modifier la commande
                </a>
                <a href="toutes_commandes.php" class="action-btn back-btn">
                    <i class="fas fa-arrow-left"></i> Retour aux commandes
                </a>
            </div>
        </div>
    </div>
</body>
</html>