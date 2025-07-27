<?php
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
$result = $conn->query("
    SELECT ha.date_achat, c.nom AS client, p.nom AS produit, ha.prix_unitaire
    FROM historique_achats ha
    LEFT JOIN clients c ON ha.client_id = c.id
    LEFT JOIN produits p ON ha.produit_id = p.id
    ORDER BY ha.date_achat DESC
    LIMIT 10, 100
");
while ($row = $result->fetch_assoc()):
?>
<ul class="details">
    <li><?= htmlspecialchars($row['date_achat']) ?></li>
    <li><?= htmlspecialchars($row['client'] ?? 'Anonyme') ?></li>
    <li><?= htmlspecialchars($row['produit']) ?></li>
    <li><?= number_format($row['prix_unitaire'], 0, ',', ' ') ?> FCFA</li>
</ul>
<?php endwhile; ?>
