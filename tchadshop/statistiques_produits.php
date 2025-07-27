<?php
session_start();
$searchContext = '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer le top 10 des produits les plus vendus
$result = $conn->query("
    SELECT 
        p.nom AS produit,
        SUM(h.quantite) AS total_vendus,
        SUM(h.quantite * h.prix_unitaire) AS chiffre_affaires
    FROM historique_achats h
    JOIN produits p ON h.produit_id = p.id
    GROUP BY h.produit_id
    ORDER BY total_vendus DESC
    LIMIT 10
");
$donnees = [];
while ($row = $result->fetch_assoc()) {
    $donnees[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques - Produits populaires</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #6a1b9a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        th {
            background: #6a1b9a;
            color: #fff;
        }
        tr:nth-child(even) {
            background: #f1f5f9;
        }
        .chart-container {
            margin-top: 40px;
        }
        .content{
            margin-top: 12%;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="container">
        <div class="content">
        <h2><i class="bx bx-star"></i> Produits les plus populaires</h2>

        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Quantité vendue</th>
                    <th>Chiffre d’affaires (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donnees as $data): ?>
                <tr>
                    <td><?= htmlspecialchars($data['produit']) ?></td>
                    <td><?= $data['total_vendus'] ?></td>
                    <td><?= number_format($data['chiffre_affaires'], 0, ',', ' ') ?> FCFA</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="chart-container">
            <canvas id="topProduitsChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('topProduitsChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($donnees, 'produit')) ?>,
                datasets: [{
                    label: 'Quantité vendue',
                    data: <?= json_encode(array_column($donnees, 'total_vendus')) ?>,
                    backgroundColor: 'rgba(255, 206, 86, 0.7)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>
