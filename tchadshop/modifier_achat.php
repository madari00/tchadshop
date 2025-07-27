<?php
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur : " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID invalide.");
}

// Déterminer si c’est une commande ou un achat direct
$sql_check = "SELECT id FROM commandes WHERE id = $id";
$result_check = $conn->query($sql_check);

$is_commande = $result_check->num_rows > 0;

// --- Si formulaire soumis ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statut = $conn->real_escape_string($_POST['statut']);

    if ($is_commande) {
        // --- Mise à jour commandes ---
        $conn->query("UPDATE commandes SET statut = '$statut' WHERE id = $id");

        // Supprimer les anciens détails
        $conn->query("DELETE FROM details_commandes WHERE commande_id = $id");

        $total = 0;
        foreach ($_POST['produit_id'] as $index => $produit_id) {
            $produit_id = intval($produit_id);
            $quantite = intval($_POST['quantite'][$index]);

            if ($produit_id > 0 && $quantite > 0) {
                $res = $conn->query("SELECT prix FROM produits WHERE id = $produit_id");
                $prix = $res->fetch_assoc()['prix'];

                $conn->query("INSERT INTO details_commandes (commande_id, produit_id, quantite) VALUES ($id, $produit_id, $quantite)");
                $total += $quantite * $prix;
            }
        }

        $conn->query("UPDATE commandes SET total = $total WHERE id = $id");

    } else {
        // --- Mise à jour achats directs ---
        $conn->query("UPDATE historique_achats SET date_achat = NOW() WHERE id = $id");

        // Supprimer l'ancien produit
        $conn->query("DELETE FROM historique_achats WHERE id = $id");

        foreach ($_POST['produit_id'] as $index => $produit_id) {
            $produit_id = intval($produit_id);
            $quantite = intval($_POST['quantite'][$index]);

            if ($produit_id > 0 && $quantite > 0) {
                $res = $conn->query("SELECT prix FROM produits WHERE id = $produit_id");
                $prix = $res->fetch_assoc()['prix'];

                $conn->query("INSERT INTO historique_achats (produit_id, client_id, quantite, prix_unitaire) VALUES ($produit_id, NULL, $quantite, $prix)");
            }
        }
    }

    header("Location: details_achat.php?id=$id");
    exit();
}

// Charger données
if ($is_commande) {
    $achat = $conn->query("SELECT * FROM commandes WHERE id = $id")->fetch_assoc();
    $details = $conn->query("SELECT * FROM details_commandes WHERE commande_id = $id");
} else {
    $achat = $conn->query("SELECT * FROM historique_achats WHERE id = $id")->fetch_assoc();
    $details = $conn->query("SELECT * FROM historique_achats WHERE id = $id");
}
$produits = $conn->query("SELECT id, nom FROM produits");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier l'achat</title>
</head>
<body>
    <h2><?= $is_commande ? "Modifier la commande" : "Modifier l'achat direct" ?> #<?= $id ?></h2>

    <form method="POST">
        <?php if ($is_commande): ?>
        <label>Statut :</label>
        <select name="statut" required>
            <option value="en attente" <?= $achat['statut'] === 'en attente' ? 'selected' : '' ?>>En attente</option>
            <option value="livré" <?= $achat['statut'] === 'livré' ? 'selected' : '' ?>>Livrée</option>
        </select><br><br>
        <?php endif; ?>

        <h3>Produits</h3>
        <?php while ($row = $details->fetch_assoc()): ?>
            <div>
                <select name="produit_id[]">
                    <?php while ($p = $produits->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $row['produit_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nom']) ?></option>
                    <?php endwhile; $produits->data_seek(0); ?>
                </select>
                <input type="number" name="quantite[]" value="<?= $row['quantite'] ?>" min="1">
            </div>
        <?php endwhile; ?>

        <button type="submit">💾 Enregistrer</button>
    </form>

    <p><a href="details_achat.php?id=<?= $id ?>">⬅ Retour</a></p>
</body>
</html>
