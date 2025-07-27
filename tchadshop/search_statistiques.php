<?php
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$search = $_GET['query'] ?? '';
$search = $conn->real_escape_string($search);

$ventes = $conn->query("
    SELECT ha.date_achat, 
           COALESCE(c.nom, 'Achat sans client') AS client,
           SUM(ha.quantite * ha.prix_unitaire) AS montant_total,
           SUM(ha.quantite) AS nb_articles
    FROM historique_achats ha
    LEFT JOIN clients c ON ha.client_id = c.id
    WHERE ha.date_achat LIKE '%$search%' 
       OR c.nom LIKE '%$search%'
       OR SUM(ha.quantite * ha.prix_unitaire) LIKE '%$search%'
       OR SUM(ha.quantite) LIKE '%$search%'
    GROUP BY ha.date_achat, client
    ORDER BY ha.date_achat DESC
    LIMIT 20
");

while ($row = $ventes->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['date_achat']) . "</td>";
    echo "<td>" . htmlspecialchars($row['client']) . "</td>";
    echo "<td>" . number_format($row['montant_total'], 2, ',', ' ') . "</td>";
    echo "<td>" . $row['nb_articles'] . "</td>";
    echo "</tr>";
}
?>
