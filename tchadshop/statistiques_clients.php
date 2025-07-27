<?php
session_start();
$searchContext = '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer les meilleurs clients
$result = $conn->query("
    SELECT 
        c.nom AS client,
        COUNT(h.id) AS nombre_achats,
        SUM(h.quantite * h.prix_unitaire) AS montant_depense
    FROM clients c
    JOIN historique_achats h ON c.id = h.client_id
    GROUP BY c.id
    ORDER BY nombre_achats DESC
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
    <title>Statistiques - Fidélité des clients</title>
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
        <h2><i class="bx bx-heart-circle"></i> Fidélité des clients</h2>

        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Nombre d’achats</th>
                    <th>Montant dépensé (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donnees as $data): ?>
                <tr>
                    <td><?= htmlspecialchars($data['client']) ?></td>
                    <td><?= $data['nombre_achats'] ?></td>
                    <td><?= number_format($data['montant_depense'], 2) ?> FCFA</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="chart-container">
            <canvas id="clientsChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('clientsChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($donnees, 'client')) ?>,
                datasets: [{
                    label: 'Montant dépensé (FCFA)',
                    data: <?= json_encode(array_column($donnees, 'nombre_achats')) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
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
