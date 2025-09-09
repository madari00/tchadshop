<?php
// Mot de passe à hacher (remplacez par votre mot de passe)
$mot_de_passe = "1234";

// Hachage sécurisé du mot de passe
$mot_de_passe_hashe = password_hash($mot_de_passe, PASSWORD_DEFAULT);

// Affichage pour insertion en base de données

echo "Mot de passe haché: " . $mot_de_passe_hashe . "\n";
?>