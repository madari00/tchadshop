<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commande_id'])) {
    $commande_id = intval($_POST['commande_id']);

    // 📝 Récupérer les infos de la commande
    $sql = "SELECT * FROM commandes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $commande_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($commande = $result->fetch_assoc()) {
        // ✅ Mettre à jour la commande comme livrée ET date_livraison = NOW()
        $update = $conn->prepare("UPDATE commandes SET statut = 'livré', date_livraison = NOW() WHERE id = ?");
        $update->bind_param("i", $commande_id);
        $update->execute();

        // 📝 Récupérer les détails de la commande
        $sql_details = "SELECT * FROM details_commandes WHERE commande_id = ?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param("i", $commande_id);
        $stmt_details->execute();
        $result_details = $stmt_details->get_result();

        $erreurs = 0;
        while ($detail = $result_details->fetch_assoc()) {
            $produit_id = $detail['produit_id'];
            $quantite = $detail['quantite'];
            $prix_unitaire = $detail['prix_unitaire']; // pris depuis details_commandes

            // 📥 Enregistrer dans historique_achats
            $insert = $conn->prepare("INSERT INTO historique_achats 
                (produit_id, commande_id, client_id, quantite, date_achat, prix_unitaire)
                VALUES (?, ?, ?, ?, NOW(), ?)");
            $insert->bind_param(
                "iiiid",
                $produit_id,
                $commande_id,
                $commande['client_id'],
                $quantite,
                $prix_unitaire
            );

            if (!$insert->execute()) {
                $erreurs++;
            } else {
                // 📦 Diminuer le stock du produit
                $update_stock = $conn->prepare("UPDATE produits SET stock = stock - ? WHERE id = ?");
                $update_stock->bind_param("ii", $quantite, $produit_id);
                $update_stock->execute();
            }
        }

        if ($erreurs === 0) {
            $_SESSION['message'] = "✅ La commande #$commande_id a été marquée comme livrée (date livrée : NOW), enregistrée dans l’historique et le stock a été mis à jour.";
        } else {
            $_SESSION['message'] = "⚠ La commande a été livrée, mais $erreurs produit(s) n’ont pas été enregistrés dans l’historique.";
        }
    } else {
        $_SESSION['message'] = "❌ Commande introuvable.";
    }
} else {
    $_SESSION['message'] = "❌ Requête invalide.";
}

header("Location: livraison_en_cours.php");
exit();

?>
