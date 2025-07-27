<?php
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if (isset($_GET['id']) && isset($_GET['statut'])) {
    $commande_id = intval($_GET['id']);
    $nouveau_statut = $conn->real_escape_string($_GET['statut']);

    // Mettre à jour le statut de la commande
    $update_sql = "UPDATE commandes SET statut='$nouveau_statut' WHERE id=$commande_id";
    if ($conn->query($update_sql) === TRUE) {

        // Si la commande est marquée comme "livré"
        if ($nouveau_statut == 'livré') {
            // Récupérer les produits de la commande
            $produits_sql = "SELECT produit_id, quantite FROM details_commandes WHERE commande_id=$commande_id";
            $produits_result = $conn->query($produits_sql);

            if ($produits_result->num_rows > 0) {
                while ($prod = $produits_result->fetch_assoc()) {
                    $produit_id = $prod['produit_id'];
                    $quantite = $prod['quantite'];

                    // Diminuer le stock du produit
                    $update_stock = "UPDATE produits SET stock = stock - $quantite WHERE id=$produit_id";
                    $conn->query($update_stock);

                    // Enregistrer dans historique_achats
                    $insert_historique = "INSERT INTO historique_achats (produit_id, commande_id, quantite, date_achat)
                                          VALUES ($produit_id, $commande_id, $quantite, NOW())";
                    $conn->query($insert_historique);
                }
            }
        }

        header("Location: livraisons_en_cours.php?success=1");
        exit;
    } else {
        echo "Erreur lors de la mise à jour : " . $conn->error;
    }
} else {
    echo "Paramètres manquants.";
}

$conn->close();
?>
