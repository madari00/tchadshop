<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Connexion à la base de données
function getDBConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "tchadshop_db";
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Augmenter la limite de GROUP_CONCAT si nécessaire
        $conn->exec("SET SESSION group_concat_max_len = 1000000");
        
        return $conn;
    } catch(PDOException $e) {
        error_log("Erreur de connexion: " . $e->getMessage());
        return null;
    }
}

// Récupérer les paramètres
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Calculer l'offset
$offset = ($page - 1) * $limit;

try {
    $conn = getDBConnection();
    
    if ($conn) {
        // Construction de la requête de base avec jointure
        $sql = "SELECT p.id, p.nom, p.stock, p.description, p.prix, 
                       p.promotion, p.prix_promotion, p.date_debut_promo, p.date_fin_promo,
                       GROUP_CONCAT(i.image SEPARATOR '|') AS images
                FROM produits p
                LEFT JOIN images_produit i ON p.id = i.produit_id
                WHERE p.statut = 'disponible' AND p.stock > 0";
        
        $params = [];
        
        // Ajouter la recherche si nécessaire
        if (!empty($search)) {
            $sql .= " AND (p.nom LIKE :search OR p.description LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // Grouper par produit et ajouter la pagination
        $sql .= " GROUP BY p.id LIMIT :limit OFFSET :offset";
        
        // Préparation et exécution de la requête
        $stmt = $conn->prepare($sql);
        
        if (!empty($search)) {
            $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convertir la chaîne d'images séparées par '|' en tableau
        foreach ($produits as &$produit) {
            if (!empty($produit['images'])) {
                $produit['images'] = explode('|', $produit['images']);
            } else {
                $produit['images'] = [];
            }
            
            // Calculer le prix final (prix promotionnel ou normal)
            $produit['prixFinal'] = (!empty($produit['promotion']) && $produit['promotion'] > 0) 
                ? $produit['prix_promotion'] 
                : $produit['prix'];
        }
        
        // Compter le nombre total de produits
        $countSql = "SELECT COUNT(DISTINCT p.id) as total 
                     FROM produits p
                     LEFT JOIN images_produit i ON p.id = i.produit_id
                     WHERE p.statut = 'disponible' AND p.stock > 0";
        
        if (!empty($search)) {
            $countSql .= " AND (p.nom LIKE :search OR p.description LIKE :search)";
        }
        
        $countStmt = $conn->prepare($countSql);
        if (!empty($search)) {
            $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $pages = ceil($total / $limit);
    } else {
        // Fallback: données simulées si la connexion échoue
        $produits = [
            [
                'id' => 1,
                'nom' => 'T-shirt Homme',
                'prix' => 5000,
                'prixFinal' => 5000,
                'images' => ['tshirt.jpg'],
                'description' => 'T-shirt coton confortable'
            ],
            [
                'id' => 2,
                'nom' => 'Jean Slim',
                'prix' => 15000,
                'prixFinal' => 15000,
                'images' => ['jean.jpg'],
                'description' => 'Jean slim délavé'
            ],
            [
                'id' => 3,
                'nom' => 'Chaussures Sport',
                'prix' => 25000,
                'prixFinal' => 25000,
                'images' => ['chaussures.jpg'],
                'description' => 'Chaussures de sport légères'
            ]
        ];
        
        $total = 3;
        $pages = 1;
    }
    
    // Préparer la réponse
    $response = [
        'produits' => $produits,
        'pagination' => [
            'page' => $page,
            'pages' => $pages,
            'total' => $total
        ]
    ];
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    // En cas d'erreur, retourner un message d'erreur
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}
?>