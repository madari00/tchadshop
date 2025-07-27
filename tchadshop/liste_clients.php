<?php  
session_start();
$searchContext = '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// ✅ Lire tous les nouveaux clients
if (isset($_GET['lire_tout'])) {
    $conn->query("UPDATE clients SET vu = 1 WHERE vu = 0 AND invite = 0");
    header("Location: clients.php"); // redirige vers la liste globale
    exit();
}

// 🔥 Sélectionner les nouveaux clients (invite = 0 et vu = 0)
$sql = "SELECT * FROM clients WHERE invite = 0 AND vu = 0 ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🆕 Nouveaux Clients</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #6a1b9a; color: #fff; }
        .btn1 { padding: 5px 10px; border-radius: 4px; color: #fff; text-decoration: none; margin-right: 5px; }
        .view-btn { background: #17a2b8; }
        .edit-btn { background: #ffc107; color: #000; }
        .delete-btn { background: #dc3545; }
        .btn1:hover { opacity: 0.8; }
        h1 { color: #6a1b9a; }
        .btn-top { margin-top: 10px; margin-bottom: 15px; display: inline-block; }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
    <h1>🆕 Nouveaux Clients</h1>

    <?php if ($result->num_rows > 0): ?>
        <a href="?lire_tout=1" class="btn1 view-btn btn-top">📖 Lire tous les clients</a>
        <table>
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Date Inscription</th>
                   
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nom']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['telephone']) ?></td>
                        <td><?= $row['created_at'] ?></td>
                        
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun nouveau client trouvé.</p>
    <?php endif; ?>

    <br>
    <a href="clients.php" class="btn1 view-btn">⬅ Retour à tous les clients</a>
</body>
</html>
