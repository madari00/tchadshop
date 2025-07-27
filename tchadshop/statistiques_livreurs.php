<?php
session_start();
$searchContext = '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer la performance des livreurs
$result = $conn->query("
    SELECT 
        l.nom AS livreur,
        COUNT(c.id) AS total_commandes,
        SUM(CASE WHEN c.statut = 'livré' THEN 1 ELSE 0 END) AS commandes_reussies,
        SUM(CASE WHEN c.statut = 'échec' THEN 1 ELSE 0 END) AS commandes_echouees,
        ROUND(SUM(CASE WHEN c.statut = 'livré' THEN 1 ELSE 0 END) / COUNT(c.id) * 100, 2) AS taux_succes
    FROM commandes c
    JOIN livreurs l ON c.livreur_id = l.id
    GROUP BY c.livreur_id
    ORDER BY taux_succes DESC
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
    <title>Statistiques - Performance des livreurs</title>
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
        <h2><i class="bx bx-bar-chart-square"></i> Performance des livreurs</h2>

        <table>
            <thead>
                <tr>
                    <th>Livreur</th>
                    <th>Total commandes</th>
                    <th>Commandes réussies</th>
                    <th>Commandes échouées</th>
                    <th>Taux de succès (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donnees as $data): ?>
                <tr>
                    <td><?= htmlspecialchars($data['livreur']) ?></td>
                    <td><?= $data['total_commandes'] ?></td>
                    <td><?= $data['commandes_reussies'] ?></td>
                    <td><?= $data['commandes_echouees'] ?></td>
                    <td><?= $data['taux_succes'] ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="chart-container">
            <canvas id="livreursChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('livreursChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($donnees, 'livreur')) ?>,
                datasets: [{
                    label: 'Taux de succès (%)',
                    data: <?= json_encode(array_column($donnees, 'taux_succes')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
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
