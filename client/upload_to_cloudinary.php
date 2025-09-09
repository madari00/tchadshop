<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Cloudinary;

// ✅ Créer l’instance Cloudinary AVEC la configuration directement
$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => 'dut2whqwx',
        'api_key'    => '642216384116788',
        'api_secret' => 'QlGVQ1rac6Y0zzYfMV_aAamdBFs'
    ],
    'url' => ['secure' => true]
]);

// 🛢 Connexion à la base MySQL
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("❌ Erreur MySQL : " . $conn->connect_error);
}

// 🔍 Sélection des images locales non uploadées
$sql = "SELECT id, image FROM images_produit WHERE image NOT LIKE 'https://%'";
$res = $conn->query($sql);

if ($res->num_rows === 0) {
    echo "✅ Toutes les images sont déjà hébergées sur Cloudinary.";
    exit;
}

while ($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $imageFilename = $row['image'];
    $localPath = __DIR__ . '/../tchadshop/' . $imageFilename;

    if (!file_exists($localPath)) {
        echo "⚠️ Image introuvable : $localPath<br>";
        continue;
    }

    try {
        // 📤 Upload vers Cloudinary
        $uploadResult = $cloudinary->uploadApi()->upload($localPath, [
            'folder' => 'tchadshop'
        ]);

        $cloudUrl = $uploadResult['secure_url'];

        // 🔄 Mettre à jour la base avec le lien Cloudinary
        $stmt = $conn->prepare("UPDATE images_produit SET image = ? WHERE id = ?");
        $stmt->bind_param("si", $cloudUrl, $id);
        $stmt->execute();

        echo "✅ Image ID $id uploadée avec succès : <a href='$cloudUrl' target='_blank'>$cloudUrl</a><br>";

    } catch (Exception $e) {
        echo "❌ Échec upload ID $id : " . $e->getMessage() . "<br>";
    }
}

echo "<hr><strong>✅ Upload terminé avec succès.</strong>";
