<?php 
session_start();
$searchContext = 'hcommande';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

function safe($val) {
    return htmlspecialchars($val ?? '');
}

// Paramètres filtres + page
$search = trim($_GET['search'] ?? '');
$filtre_statut = $_GET['statut'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Construire la condition WHERE dynamique
$where = " WHERE c.statut IN ('livré', 'échec')";
$params = [];
$types = "";

// Filtre statut
if (in_array($filtre_statut, ['livré', 'échec'])) {
    $where .= " AND c.statut = ?";
    $params[] = $filtre_statut;
    $types .= "s";
}

// search sur client ou livreur
if ($search !== '') {
    $where .= " AND (cl.nom LIKE ? OR l.nom LIKE ? OR c.id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    
    // Si la recherche est numérique, chercher aussi par ID de commande
    if (is_numeric($search)) {
        $params[] = $search;
        $types .= "ssi";
    } else {
        $params[] = "%$search%";
        $types .= "sss";
    }
}

// Date début
if ($date_debut !== '') {
    $where .= " AND c.date_commande >= ?";
    $params[] = $date_debut . " 00:00:00";
    $types .= "s";
}

// Date fin
if ($date_fin !== '') {
    $where .= " AND c.date_commande <= ?";
    $params[] = $date_fin . " 23:59:59";
    $types .= "s";
}

// Requête pour compter total commandes
$sql_count = "SELECT COUNT(*) FROM commandes c
              LEFT JOIN clients cl ON c.client_id = cl.id
              LEFT JOIN livreurs l ON c.livreur_id = l.id
              $where";

$stmt_count = $conn->prepare($sql_count);
if ($params) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$stmt_count->bind_result($total_commandes);
$stmt_count->fetch();
$stmt_count->close();

$total_pages = ceil($total_commandes / $limit);

// Requête principale avec limit & offset
$sql = "SELECT c.*, cl.nom AS client_nom, cl.invite AS client_invite, l.nom AS livreur_nom
        FROM commandes c
        LEFT JOIN clients cl ON c.client_id = cl.id
        LEFT JOIN livreurs l ON c.livreur_id = l.id
        $where
        ORDER BY c.date_commande DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Ajouter limit et offset
if ($params) {
    $types_with_limit = $types . "ii";
    $params_with_limit = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($types_with_limit, ...$params_with_limit);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des Livraisons</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
        }
        .home-content {
            padding: 20px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: center; 
        }
        th { 
            background: #6a1b9a; 
            color: #fff; 
            position: sticky;
            top: 0;
        }
        .btn1 {
            display: inline-block; 
            padding: 8px 12px; 
            background-color: #007bff;
            color: white; 
            border-radius: 4px; 
            text-decoration: none; 
            font-size: 14px;
            transition: background 0.3s;
        }
        .btn1:hover { 
            background-color: #0056b3; 
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .livré { 
            color: green; 
            font-weight: bold; 
        }
        .échec { 
            color: red; 
            font-weight: bold; 
        }
        form.filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        form.filters input, form.filters select {
            padding: 10px;
            margin: 0 10px 10px 0;
            font-size: 14px;
            border: 2px solid #6a1b9a;
            border-radius: 4px;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 4px;
            border: 1px solid #ddd;
            color: #007bff;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .pagination a:hover {
            background-color: #f0f0f0;
        }
        .pagination span.current {
            background-color: #6a1b9a;
            color: white;
            border-color: #6a1b9a;
            cursor: default;
        }
        h2 { 
            color: #6a1b9a; 
            margin: 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #6a1b9a;
        }
        .filters label { 
            font-weight: bold;
            margin-right: 10px;
            color: #333;
        }
        .select, .filters input[type="date"], .filters input[type="text"] {
            border: 2px solid #6a1b9a;
            border-radius: 4px;
            padding: 8px;
        }
        .button {
            background: #6a1b9a;
            padding: 10px 20px;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .button:hover {
            background: #5a0b8a;
        }
        .badge-invite {
            background: purple;
            color: white;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 5px;
        }
       
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f0f0f0;
            flex: 1;
            margin: 0 10px;
        }
        .stat-box h3 {
            margin: 0;
            color: #6a1b9a;
        }
        .stat-box p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0 0;
        }
        .export-btn {
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
        <h2>📜 Historique des Livraisons</h2>

        <!-- Statistiques rapides -->
        <div class="stats">
            <div class="stat-box">
                <h3>Total Commandes</h3>
                <p><?= $total_commandes ?></p>
            </div>
            <div class="stat-box">
                <h3>Commandes Livrées</h3>
                <p>
                    <?php
                    $sql_livree = "SELECT COUNT(*) FROM commandes WHERE statut = 'livré'";
                    $result_livree = $conn->query($sql_livree);
                    echo $result_livree->fetch_row()[0];
                    ?>
                </p>
            </div>
            <div class="stat-box">
                <h3>Commandes Échouées</h3>
                <p>
                    <?php
                    $sql_echec = "SELECT COUNT(*) FROM commandes WHERE statut = 'échec'";
                    $result_echec = $conn->query($sql_echec);
                    echo $result_echec->fetch_row()[0];
                    ?>
                </p>
            </div>
        </div>

        <div class="filter-header">
            <form method="get" class="filters">
                <div>
                    <label>Statut:
                    <select name="statut" class="select">
                        <option value="">Tous statuts</option>
                        <option value="livré" <?= $filtre_statut === 'livré' ? 'selected' : '' ?>>Livré</option>
                        <option value="échec" <?= $filtre_statut === 'échec' ? 'selected' : '' ?>>Échec</option>
                    </select>
                    </label>
                    
                    <label>Date début: <input type="date" name="date_debut" value="<?= safe($date_debut) ?>"></label>
                    <label>Date fin: <input type="date" name="date_fin" value="<?= safe($date_fin) ?>"></label>
                 
                  
                    <button type="submit" class="button">Filtrer</button>
                    <a href="historique_livraison.php" class="btn1" style="background-color:#6c757d;">Réinitialiser</a>
                
                </div>
                
                
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Livreur</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Total (FCFA)</th>
                    <th>Adresse</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($commande = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $commande['id'] ?></td>
                            <td>
                                <?= safe($commande['client_nom'] ?? 'Anonyme') ?>
                                <?php if (!empty($commande['client_invite'])): ?>
                                    <span class="badge-invite">Invité</span>
                                <?php endif; ?>
                            </td>
                            <td><?= safe($commande['livreur_nom'] ?? 'Non assigné') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($commande['date_commande'])) ?></td>
                            <td class="<?= $commande['statut'] === 'livré' ? 'livré' : 'échec' ?>">
                                <?= ucfirst($commande['statut']) ?>
                            </td>
                            <td><?= number_format($commande['total'], 0, ',', ' ') ?></td>
                            <td>
                                <?php
                                if (!empty($commande['adresse'])) {
                                    echo safe($commande['adresse']);
                                } elseif (!empty($commande['latitude']) && !empty($commande['longitude'])) {
                                    echo "Lat: " . safe($commande['latitude']) . ", Lng: " . safe($commande['longitude']);
                                } else {
                                    echo "❌ Non renseignée";
                                }
                                ?>
                            </td>
                            <td>
                                <a href="details_commande.php?id=<?= $commande['id'] ?>" class="btn1">🔍 Voir</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">
                            Aucune livraison trouvée avec les filtres actuels.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Précédent</a>
            <?php endif; ?>

            <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                <?php if ($p == $page): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Suivant &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>