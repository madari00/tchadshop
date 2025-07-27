<?php
session_start();
$searchContext = '';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// ✅ Bouton "Marquer tous comme vus"
if (isset($_GET['lire_tout'])) {
    $conn->query("UPDATE produits SET vu = 1 WHERE stock <= 5 AND vu = 0");
    header("Location: liste_produits.php");
    exit();
}

// 🔥 Récupérer tous les produits critiques non vus
$sql = "SELECT * FROM produits WHERE stock <= 5  ORDER BY stock ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🚨 Produits Critiques</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #6a1b9a; color: #fff; }
        .btn1 { padding: 6px 12px; border-radius: 4px; color: #fff; text-decoration: none; margin-right: 5px; }
        .view-btn { background: #17a2b8; }
        .edit-btn { background: #ffc107; color: #000; }
        .delete-btn { background: #6c757d; }
        .btn1:hover { opacity: 0.8; }
        h1 { color: #6a1b9a; }
        .btn-top { margin-top: 10px; margin-bottom: 15px; display: inline-block; }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="home-content">
    <h1>🚨 Produits Critiques (Stock ≤ 5)</h1>

    <?php if ($result->num_rows > 0): ?>
        <a href="?lire_tout=1" class="btn1 view-btn btn-top">📖 Marquer tous comme vus</a>
        <table>
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Nom Produit</th>
                    <th>Stock</th>
                    <th>Prix (FCFA)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nom']) ?></td>
                        <td><?= $row['stock'] ?></td>
                        <td><?= number_format($row['prix'], 2) ?></td>
                        <td>
                            <a href="voir_produit.php?id=<?= $row['id'] ?>" class="btn1 view-btn">👁 Voir</a>
                            <a href="modifier_produit.php?id=<?= $row['id'] ?>" class="btn1 edit-btn">✏ Modifier</a>
                            <a href="supprimer_produit.php?id=<?= $row['id'] ?>" class="btn1 delete-btn" onclick="return confirm('⚠ Supprimer ce produit ?');">🗑 Supprimer</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun produit critique trouvé (tous les stocks sont supérieurs à 5).</p>
    <?php endif; ?>

    <br>
    <a href="produits.php" class="btn1 view-btn">⬅ Retour à tous les produits</a>
</div>
</body>
</html>
