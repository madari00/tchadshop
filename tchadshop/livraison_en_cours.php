<?php
session_start();
$searchContext = 'lcommande';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Fonction pour sécuriser les données affichées
function safe($val) {
    return htmlspecialchars($val ?? '');
}

// Par défaut, on affiche que les "en cours"
$filtre_statut = $_GET['filtre_statut'] ?? 'en cours';
$filtre_statut = in_array($filtre_statut, ['en cours', 'livré', 'échec']) ? $filtre_statut : 'en cours';

// Récupérer les commandes selon le statut choisi
$sql = "SELECT c.*, cl.nom AS client_nom, cl.telephone, cl.invite AS client_invite, l.nom AS livreur_nom
        FROM commandes c
        LEFT JOIN clients cl ON c.client_id = cl.id
        LEFT JOIN livreurs l ON c.livreur_id = l.id
        WHERE c.statut = ? AND c.livreur_id IS NOT NULL
        ORDER BY c.date_commande DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $filtre_statut);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Livraisons - PRO+ Alerte</title>
    <style>
        body { font-family: Arial; background-color: #f8f8f8; }
        table { width: 100%; border-collapse: collapse; margin-top: 40px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #6a1b9a; color: #fff; }
        .progress-bar { width: 100%; height: 20px; background: #e0e0e0; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; width: 0%; background: #28a745; text-align: center; color: white; line-height: 20px; transition: width 0.5s ease; }
        .late { color: red; font-weight: bold; }
        .btn1 { background: #007bff; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; }
        .done-btn { background: #28a745; }
        .fail-btn { background: #dc3545; }
        .badge-invite {
            background: purple;
            color: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 5px;
        }
        h2 {
           color: #6a1b9a; 
           margin: 20px;
        }
    </style>
</head>
<body>
     <?php include("header.php"); ?>
    <div class="home-content">
    <h2>📦 Livraisons en cours</h2>

    <!-- Message session -->
    <?php if (isset($_SESSION['message'])): ?>
        <p style="color:green; font-weight:bold;"><?= safe($_SESSION['message']); unset($_SESSION['message']); ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Livreur</th>
                <th>Date commande</th>
                <th>Temps restant</th>
                <th>Retard</th>
                <th>Progression</th>
                <?php if ($filtre_statut == 'en cours'): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php while ($c = $res->fetch_assoc()): 
                $date_commande = strtotime($c['date_commande']);
                $temps_livraison = intval($c['temps_livraison']);
                $deadline = $date_commande + ($temps_livraison * 60);
            ?>
            <tr>
                <td>
                    <?= safe($c['client_nom']) ?>
                    <?php if (!empty($c['client_invite'])): ?>
                        <span class="badge-invite">Invité</span>
                    <?php endif; ?>
                </td>
                <td><?= safe($c['livreur_nom']) ?></td>
                <td><?= safe($c['date_commande']) ?></td>
                <td><span id="timer-<?= $c['id'] ?>">Calcul...</span></td>
                <td><span id="retard-<?= $c['id'] ?>">—</span></td>
                <td>
                    <div class="progress-bar">
                        <div id="progress-<?= $c['id'] ?>" class="progress-fill">0%</div>
                    </div>
                </td>
                <?php if ($filtre_statut == 'en cours'): ?>
                <td>
                    <form method="post" action="marquer_livre.php" style="display:inline;">
                        <input type="hidden" name="commande_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn1 done-btn">✅ Livré</button>
                    </form>
                    <form method="post" action="marquer_echec.php" style="display:inline;" onsubmit="return confirm('Confirmer échec ?');">
                        <input type="hidden" name="commande_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn1 fail-btn">❌ Échec</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            
            <script>
            (function(){
                const deadline = <?= $deadline ?> * 1000;
                const id = <?= $c['id'] ?>;
                const timer = document.getElementById('timer-' + id);
                const retard = document.getElementById('retard-' + id);
                const progress = document.getElementById('progress-' + id);

                function update() {
                    const now = Date.now();
                    const diff = deadline - now;
                    const totalTime = <?= $temps_livraison * 60 * 1000 ?>;
                    let percent = Math.min(100, Math.max(0, ((now - <?= $date_commande * 1000 ?>) / totalTime) * 100));

                    progress.style.width = percent + '%';
                    progress.textContent = Math.round(percent) + '%';

                    if (diff > 0) {
                        let m = Math.floor(diff / 60000);
                        let s = Math.floor((diff % 60000) / 1000);
                        timer.textContent = m + "m " + s + "s restants";
                        retard.textContent = "—";
                    } else {
                        timer.innerHTML = "<span class='late'>⚠ Retard</span>";
                        retard.textContent = Math.abs(Math.floor(diff / 60000)) + " min";
                    }
                    requestAnimationFrame(update);
                }
                update();
            })();
            </script>
            <?php endwhile; ?>
        </tbody>
    </table>
    <br>
    </div>
</body>
</html>
