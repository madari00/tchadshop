<?php
session_start();
$searchContext = '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer le chiffre d’affaires par mois
$result = $conn->query("
    SELECT 
        DATE_FORMAT(date_achat, '%Y-%m') AS mois, 
        SUM(prix_unitaire * quantite) AS chiffre_affaires, 
        COUNT(DISTINCT commande_id) AS nb_commandes
    FROM historique_achats
    GROUP BY mois
    ORDER BY mois DESC
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
    <title>Statistiques - Chiffre d’affaires</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        .container {
            width: 90%;
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
        <h2><i class="bx bx-dollar"></i> Chiffre d’affaires par mois</h2>

        <table>
            <thead>
                <tr>
                    <th>Mois</th>
                    <th>Nombre d’achats</th>
                    <th>Chiffre d’affaires (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donnees as $data): ?>
                <tr>
                    <td><?= htmlspecialchars($data['mois']) ?></td>
                    <td><?= $data['nb_commandes'] ?></td>
                    <td><?= number_format($data['chiffre_affaires'], 0, ',', ' ') ?> FCFA</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="chart-container">
            <canvas id="chiffreAffairesChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('chiffreAffairesChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($donnees, 'mois')) ?>,
                datasets: [{
                    label: 'Chiffre d’affaires (FCFA)',
                    data: <?= json_encode(array_column($donnees, 'chiffre_affaires')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + " FCFA";
                            }
                        }
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
