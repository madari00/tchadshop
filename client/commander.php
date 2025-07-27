<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) die("Erreur : " . $conn->connect_error);

$produit_id = intval($_GET['produit_id'] ?? 0);
$message = "";

// Récupérer les infos du produit
$produit = null;
if ($produit_id > 0) {
    $stmt = $conn->prepare("SELECT id, nom, description, prix, stock FROM produits WHERE id = ?");
    $stmt->bind_param("i", $produit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produit = $result->fetch_assoc();
    $stmt->close();
}

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telephone = trim($_POST['telephone'] ?? '');
    $quantite = intval($_POST['quantite'] ?? 1);
    $message_client = $_POST['message'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $date_commande = date('Y-m-d H:i:s');

    if ($produit && $telephone && $quantite > 0) {
        // Insérer dans table commandes (client invité)
        $stmt = $conn->prepare("INSERT INTO commandes (client_id, date_commande, total, statut, localisation) VALUES (NULL, ?, ?, 'en attente', ?)");
        $total = $quantite * $produit['prix'];
        $localisation = "$latitude,$longitude";
        $stmt->bind_param("sds", $date_commande, $total, $localisation);
        $stmt->execute();
        $commande_id = $conn->insert_id;
        $stmt->close();

        // Enregistrer détail commande
        $stmt = $conn->prepare("INSERT INTO details_commandes (commande_id, produit_id, quantite) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $commande_id, $produit_id, $quantite);
        $stmt->execute();
        $stmt->close();

        // Enregistrer dans historique_achats
        $stmt = $conn->prepare("INSERT INTO historique_achats (produit_id, commande_id, client_id, quantite, prix_unitaire, date_achat) 
                                VALUES (?, ?, NULL, ?, ?, ?)");
        $stmt->bind_param("iiids", $produit_id, $commande_id, $quantite, $produit['prix'], $date_commande);
        $stmt->execute();
        $stmt->close();

        // Réduire le stock
        $conn->query("UPDATE produits SET stock = stock - $quantite WHERE id = $produit_id");

        $message = "✅ Votre commande a été enregistrée avec succès !";
    } else {
        $message = "❌ Veuillez remplir tous les champs correctement.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commander un produit</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        label { display: block; margin-top: 10px; }
        input, textarea { width: 100%; padding: 8px; margin-top: 5px; }
        .message { margin: 15px 0; font-weight: bold; color: green; }
    </style>
    <script>
    // Obtenir la localisation
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById('latitude').value = position.coords.latitude;
                document.getElementById('longitude').value = position.coords.longitude;
            }, function(error) {
                alert("Localisation refusée ou indisponible.");
            });
        } else {
            alert("La géolocalisation n'est pas supportée.");
        }
    }
    </script>
</head>
<body onload="getLocation()">

    <h2>🛒 Commander un produit</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($produit): ?>
        <h3><?= htmlspecialchars($produit['nom']) ?> - <?= number_format($produit['prix'], 0, ',', ' ') ?> FCFA</h3>

        <form method="POST">
            <label>📞 Votre numéro de téléphone *</label>
            <input type="text" name="telephone" required>

            <label>🔢 Quantité *</label>
            <input type="number" name="quantite" min="1" max="<?= $produit['stock'] ?>" required>

            <label>📝 Message (facultatif) ou demande vocale</label>
            <textarea name="message" placeholder="Écrivez ici ou utilisez WhatsApp ci-dessous..."></textarea>

            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">

            <br><br>
            <button type="submit">✅ Valider ma commande</button>
        </form>

        <br>
        <a href="https://wa.me/00235600000000?text=Je veux commander <?= urlencode($produit['nom']) ?>" target="_blank">📱 Commander via WhatsApp</a>

    <?php else: ?>
        <p>❌ Produit introuvable.</p>
    <?php endif; ?>

</body>
</html>
