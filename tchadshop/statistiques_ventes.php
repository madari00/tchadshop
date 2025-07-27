<?php
session_start();
$searchContext =  '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer les ventes pour le tableau
$ventes = $conn->query("
    SELECT ha.date_achat, 
           COALESCE(c.nom, 'Achat sans client') AS client,
           SUM(ha.quantite * ha.prix_unitaire) AS montant_total,
           SUM(ha.quantite) AS nb_articles
    FROM historique_achats ha
    LEFT JOIN clients c ON ha.client_id = c.id
    GROUP BY ha.date_achat, client
    ORDER BY ha.date_achat DESC
    LIMIT 20
");

// Récupérer les ventes pour le graphique (par jour pour le mois en cours)
$ventes_graph = $conn->query("
    SELECT DATE(ha.date_achat) as jour,
           SUM(ha.quantite * ha.prix_unitaire) AS total_journalier
    FROM historique_achats ha
    WHERE MONTH(ha.date_achat) = MONTH(CURDATE())
      AND YEAR(ha.date_achat) = YEAR(CURDATE())
    GROUP BY jour
    ORDER BY jour ASC
");

$jours = [];
$totaux = [];
while ($row = $ventes_graph->fetch_assoc()) {
    $jours[] = $row['jour'];
    $totaux[] = $row['total_journalier'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques des ventes</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.1);
        }
        .content{
            margin-top: 10%;
        }
        h2 {
            color: #6a1b9a;
            
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        table th {
            background: #6a1b9a;
            color: white;
        }
        canvas {
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="container">
        <div class="content">
        <h2>📊 Statistiques des ventes</h2>

        <!-- Graphique -->
        <canvas id="ventesChart" height="100"></canvas>

        <!-- Tableau des ventes -->
        <table>
            <thead>
                <tr>

                    <th>Date</th>
                    <th>Client</th>
                    <th>Montant total (FCFA)</th>
                    <th>Nombre d'articles</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $ventes->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['date_achat']) ?></td>
                    <td><?= htmlspecialchars($row['client']) ?></td>
                    <td><?= number_format($row['montant_total'], 2, ',', ' ') ?></td>
                    <td><?= $row['nb_articles'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
    const ctx = document.getElementById('ventesChart').getContext('2d');
    const ventesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($jours) ?>,
            datasets: [{
                label: 'Total des ventes (FCFA)',
                data: <?= json_encode($totaux) ?>,
                borderColor: '#38a169',
                backgroundColor: 'rgba(56, 161, 105, 0.2)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => value.toLocaleString() + " FCFA"
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Jours'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Évolution des ventes (Mois en cours)',
                    font: {
                        size: 18
                    }
                }
            }
        }
    });
    
    </script>
</body>
</html>
