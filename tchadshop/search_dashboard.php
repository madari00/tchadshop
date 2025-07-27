<?php
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$search = $_GET['query'] ?? '';
$search = $conn->real_escape_string($search);

// Rechercher dans ventes récentes
$ventes = $conn->query("
    SELECT ha.date_achat, c.nom AS client, p.nom AS produit, ha.prix_unitaire
    FROM historique_achats ha
    LEFT JOIN clients c ON ha.client_id = c.id
    LEFT JOIN produits p ON ha.produit_id = p.id
    WHERE c.nom LIKE '%$search%' OR p.nom LIKE '%$search%' OR ha.date_achat LIKE '%$search%'
    ORDER BY ha.date_achat DESC
    LIMIT 10
");

// Rechercher dans produits les plus vendus
$topProduits = $conn->query("
    SELECT p.nom, SUM(ha.quantite) AS total_vendu, SUM(ha.prix_unitaire * ha.quantite) AS total_revenu
    FROM historique_achats ha
    LEFT JOIN produits p ON ha.produit_id = p.id
    WHERE p.nom LIKE '%$search%'
    GROUP BY p.id
    ORDER BY total_vendu DESC
    LIMIT 5
");
?>

<div class="table-box">
    <h2 class="h2">Ventes Récentes</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Produit</th>
                <th>Prix</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $ventes->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['date_achat']) ?></td>
                <td><?= htmlspecialchars($row['client'] ?? 'Anonyme') ?></td>
                <td><?= htmlspecialchars($row['produit']) ?></td>
                <td><?= number_format($row['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="table-box">
    <h2 class="h2">Produits les plus vendus</h2>
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Quantité</th>
                <th>Revenu</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $topProduits->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nom']) ?></td>
                <td><?= $row['total_vendu'] ?></td>
                <td><?= number_format($row['total_revenu'], 0, ',', ' ') ?> FCFA</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
