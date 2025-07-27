<?php
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID d'achat invalide.");
}

// Essayer de récupérer dans commandes
$sql_commande = "
SELECT com.id, COALESCE(c.nom, 'Anonyme') AS client, c.telephone, com.total, com.date_commande, com.statut
FROM commandes com
LEFT JOIN clients c ON com.client_id = c.id
WHERE com.id = $id
";
$result_commande = $conn->query($sql_commande);

if ($commande = $result_commande->fetch_assoc()) {
    // Achat lié à une commande
    $sql_produits = "
    SELECT p.nom, dc.quantite, p.prix, (dc.quantite * p.prix) AS sous_total
    FROM details_commandes dc
    JOIN produits p ON dc.produit_id = p.id
    WHERE dc.commande_id = $id
    ";
    $produits = $conn->query($sql_produits);
    $is_commande = true;
} else {
    // Sinon chercher dans historique_achats
    $sql_achat = "
    SELECT ha.id, COALESCE(c.nom, 'Anonyme') AS client, c.telephone, ha.date_achat
    FROM historique_achats ha
    LEFT JOIN clients c ON ha.client_id = c.id
    WHERE ha.id = $id
    ";
    $result_achat = $conn->query($sql_achat);

    if ($achat = $result_achat->fetch_assoc()) {
        $sql_produits = "
        SELECT p.nom, ha.quantite, ha.prix_unitaire, (ha.quantite * ha.prix_unitaire) AS sous_total
        FROM historique_achats ha
        JOIN produits p ON ha.produit_id = p.id
        WHERE ha.id = $id
        ";
        $produits = $conn->query($sql_produits);
        $is_commande = false;
    } else {
        die("Achat non trouvé.");
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de l'achat #<?= $id ?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 70%; margin: 20px auto; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2, p { text-align: center; }
    </style>
</head>
<body>

<?php if ($is_commande): ?>
    <h2>Détails de la commande #<?= $commande['id'] ?></h2>
    <p><strong>Client :</strong> <?= htmlspecialchars($commande['client']) ?></p>
    <?php if (!empty($commande['telephone'])): ?>
        <p><strong>Téléphone :</strong> <?= htmlspecialchars($commande['telephone']) ?></p>
    <?php endif; ?>
    <p><strong>Date de commande :</strong> <?= $commande['date_commande'] ?></p>
    <p><strong>Statut :</strong> <?= ucfirst($commande['statut']) ?></p>
    <?php if (!empty($commande['date_livraison'])): ?>
        <p><strong>Date de livraison :</strong> <?= htmlspecialchars($commande['date_livraison']) ?></p>
    <?php endif; ?>

<?php else: ?>
    <h2>Détails de l'achat direct #<?= $achat['id'] ?></h2>
    <p><strong>Client :</strong> <?= htmlspecialchars($achat['client']) ?></p>
    <?php if (!empty($achat['telephone'])): ?>
        <p><strong>Téléphone :</strong> <?= htmlspecialchars($achat['telephone']) ?></p>
    <?php endif; ?>
    <p><strong>Date :</strong> <?= $achat['date_achat'] ?></p>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Produit</th>
            <th>Quantité</th>
            <th>Prix Unitaire</th>
            <th>Sous-total</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $total = 0;
    while ($row = $produits->fetch_assoc()):
        $total += $row['sous_total'];
    ?>
    <tr>
        <td><?= htmlspecialchars($row['nom']) ?></td>
        <td><?= $row['quantite'] ?></td>
        <td>
            <?php
            $prix_unitaire = $row['prix_unitaire'] ?? $row['prix'] ?? 0;
            echo number_format($prix_unitaire, 0, ',', ' ') . ' FCFA';
            ?>
        </td>
        <td><?= number_format($row['sous_total'], 0, ',', ' ') ?> FCFA</td>
    </tr>
    <?php endwhile; ?>
</tbody>

</table>

<p style="text-align:center;"><strong>Montant total :</strong> <?= number_format($total, 0, ',', ' ') ?> FCFA</p>

<p style="text-align:center;">
    <a href="modifier_achat.php?id=<?= $id ?>">✏ Modifier</a> |
    <a href="supprimer_achat.php?id=<?= $id ?>" onclick="return confirm('Supprimer cet achat ?')">🗑 Supprimer</a> |
    <a href="historique_achats.php">⬅ Retour</a>
</p>

</body>
</html>
