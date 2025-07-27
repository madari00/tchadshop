<?php
session_start();
$searchContext = '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Définir le seuil
$seuil_stock = 10;

// Récupérer les produits avec stock faible
$result = $conn->query("
    SELECT 
        nom AS produit,
        stock
    FROM produits
    WHERE stock <= $seuil_stock
    ORDER BY stock ASC
");

$produits_faibles = [];
while ($row = $result->fetch_assoc()) {
    $produits_faibles[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques - Stocks faibles</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0 auto;
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
        <h2><i class="bx bx-package"></i> Produits en stock faible (≤ <?= $seuil_stock ?> unités)</h2>

        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Stock actuel</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produits_faibles as $produit): ?>
                <tr>
                    <td><?= htmlspecialchars($produit['produit']) ?></td>
                    <td><?= $produit['stock'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="chart-container">
            <canvas id="stocksChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('stocksChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($produits_faibles, 'produit')) ?>,
                datasets: [{
                    label: 'Stock actuel',
                    data: <?= json_encode(array_column($produits_faibles, 'stock')) ?>,
                    backgroundColor: '#6a1b9a',
                    borderColor: '#6a1b9a',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
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
