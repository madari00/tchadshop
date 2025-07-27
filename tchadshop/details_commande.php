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

// Récupérer les produits
$produits = $conn->query("
    SELECT p.nom, dc.quantite, p.prix 
    FROM details_commandes dc
    INNER JOIN produits p ON dc.produit_id = p.id
    WHERE dc.commande_id = $id
");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de la commande</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h2, h3 { color: #6a1b9a; }
        table {
            width: 70%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th { background: #6a1b9a; color: #fff; }
        .btn1 {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn1:hover { background-color: #0056b3; }
        .content { margin-left: 10%; }
        .content p { margin: 10px 0; font-size: 16px; }
        .content strong { font-size: 18px; }
        .badge-invite {
            background: purple; color: white; font-size: 12px;
            padding: 2px 5px; border-radius: 4px; margin-left: 5px;
        }
        .statut-livre { color: green; font-weight: bold; }
        .statut-attente { color: orange; font-weight: bold; }
        .statut-echec { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
        <div class="content">
            <h2>📦 Détails de la commande #<?= $commande['id'] ?></h2>
            <p><strong>Client :</strong> 
                <?= safe($commande['client_nom']) ?: 'Sans client' ?>
                <?php if (!empty($commande['client_invite'])): ?>
                    <span class="badge-invite">Invité</span>
                <?php endif; ?>
            </p>
            <p><strong>Téléphone :</strong> <?= safe($commande['client_tel']) ?: '-' ?></p>
            <p><strong>Livreur :</strong> <?= safe($commande['livreur_nom']) ?: 'Non assigné' ?></p>
            <p><strong>Date commande :</strong> <?= safe($commande['date_commande']) ?></p>

            <?php if (strtolower($commande['statut']) === 'livré' && $commande['date_livraison']): ?>
                <p><strong>📦 Date de livraison :</strong> <?= safe($commande['date_livraison']) ?></p>
            <?php endif; ?>

            <p><strong>Adresse :</strong> <?= $commande['adresse'] ? safe($commande['adresse']) : '❌ Non renseignée' ?></p>

            <?php if (!empty($commande['latitude']) && !empty($commande['longitude'])): ?>
                <p><strong>Coordonnées :</strong> Lat: <?= $commande['latitude'] ?>, Lng: <?= $commande['longitude'] ?></p>
                <a class="btn1" href="https://maps.google.com/?q=<?= $commande['latitude'] ?>,<?= $commande['longitude'] ?>" target="_blank">📍 Voir sur la carte</a>
            <?php endif; ?>

            <p><strong>Statut :</strong> 
                <span class="<?=
                    strtolower($commande['statut']) === 'livré' ? 'statut-livre' :
                    (strtolower($commande['statut']) === 'échec' ? 'statut-echec' : 'statut-attente')
                ?>">
                    <?= ucfirst(safe($commande['statut'])) ?>
                </span>
            </p>
            <p><strong>Total :</strong> <?= number_format($commande['total'], 0, ',', ' ') ?> FCFA</p>

            <h3>🛒 Produits commandés</h3>
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix unitaire (FCFA)</th>
                        <th>Sous-total (FCFA)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $total = 0; ?>
                    <?php while ($p = $produits->fetch_assoc()): ?>
                        <tr>
                            <td><?= safe($p['nom']) ?></td>
                            <td><?= $p['quantite'] ?></td>
                            <td><?= number_format($p['prix'], 0, ',', ' ') ?></td>
                            <td><?= number_format($p['prix'] * $p['quantite'], 0, ',', ' ') ?></td>
                        </tr>
                        <?php $total += $p['prix'] * $p['quantite']; ?>
                    <?php endwhile; ?>
                    <tr>
                        <th colspan="3" style="text-align:right;">Total général :</th>
                        <th><?= number_format($total, 0, ',', ' ') ?> FCFA</th>
                    </tr>
                </tbody>
            </table>

            <a href="modifier_commande.php?id=<?= $commande['id'] ?>" class="btn1">📝 Modifier la commande</a>
            <a href="toutes_commandes.php" class="btn1" style="background-color:#6c757d;">⬅ Retour</a>
        </div>
    </div>
</body>
</html>
