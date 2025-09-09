<?php
session_start();
$searchContext = 'contact';
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Traitement de la mise à jour du statut "vu"
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $contact_id = intval($_GET['mark_read']);
    $conn->query("UPDATE contacts SET vu = 1 WHERE id = $contact_id");
    
    // Construire une nouvelle URL sans le paramètre mark_read
    $queryParams = $_GET;
    unset($queryParams['mark_read']);
    $newQueryString = http_build_query($queryParams);
    
    header("Location: contact_visiteur.php?" . $newQueryString);
    exit();
}

// Marquer tous les messages comme lus
if (isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE contacts SET vu = 1 WHERE vu = 0");
    
    // Construire une nouvelle URL sans le paramètre mark_all_read
    $queryParams = $_GET;
    unset($queryParams['mark_all_read']);
    $newQueryString = http_build_query($queryParams);
    
    header("Location: contact_visiteur.php?" . $newQueryString);
    exit();
}

$search = $_GET['search'] ?? '';
$filtre = $_GET['filtre'] ?? 'tous'; // tous | non_vus
$tri = $_GET['tri'] ?? 'recent'; // récent | ancien

// 🔎 Requête principale
$sql = "SELECT c.*, cl.nom AS client_nom, cl.email AS client_email, cl.invite
        FROM contacts c
        LEFT JOIN clients cl ON c.client_id = cl.id";

$where = [];
$params = [];
$types = "";

// 🔍 Recherche
if (!empty($search)) {
    $where[] = "(c.nom LIKE ? OR c.email LIKE ? OR c.sujet LIKE ? OR c.telephone LIKE ? OR c.message LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
    $types .= "sssss";
}

// 🎯 Filtres
if ($filtre === 'non_vus') {
    $where[] = "c.vu = 0";
}

// 🔗 Ajout WHERE si besoin
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// 🔄 Tri
if ($tri === 'ancien') {
    $sql .= " ORDER BY c.created_at ASC";
} else {
    $sql .= " ORDER BY c.created_at DESC";
}

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Compter les messages non lus
$sqlNonVus = "SELECT COUNT(*) AS count FROM contacts WHERE vu = 0";
$resultNonVus = $conn->query($sqlNonVus);
$nonVusCount = $resultNonVus->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📩 Contacts reçus - Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #00796b;
            --primary-light: #48a999;
            --primary-dark: #004c40;
            --accent-color: #ff9800;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: -50px auto;
            padding: 20px;

        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 30px;
        }
        
        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-title h1 {
            font-size: 2.2rem;
            color: var(--primary-dark);
            margin: 0;
        }
        
        .page-title .icon {
            font-size: 2.5rem;
            color: var(--primary-color);
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 15px 20px;
            text-align: center;
            min-width: 180px;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 10px rgba(0,0,0,0.08);
        }
        
        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stats-card .label {
            font-size: 0.9rem;
            color: var(--dark-gray);
            margin-top: 5px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 1.4rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            background: var(--light-gray);
            border-bottom: 1px solid #e0e0e0;
        }
        
        .filters {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box1 {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 0 15px;
            height: 40px;
            width: 300px;
            transition: box-shadow 0.3s ease;
        }
        
        .search-box1:focus-within {
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }
        
        .search-box1 input {
            border: none;
            outline: none;
            padding: 5px 10px;
            width: 100%;
            font-size: 0.95rem;
        }
        
        .search-box1 button {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--primary-color);
            font-size: 1.1rem;
            transition: transform 0.2s ease;
        }
        
        .search-box1 button:hover {
            transform: scale(1.1);
        }
        
        .btn1 {
            padding: 8px 20px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        
        .btn1-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn1-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn1-outline {
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn1-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn1-warning {
            background: var(--warning-color);
            color: #000;
        }
        
        .btn1-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        
        .btn1-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn1-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn1-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn1-danger:hover {
            background: #bd2130;
            transform: translateY(-2px);
        }
        
        .btn1-sm {
            padding: 5px 12px;
            font-size: 0.85rem;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background-color: #f1f1f1;
            position: sticky;
            top: 0;
        }
        
        th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-gray);
            border-bottom: 2px solid #e0e0e0;
            white-space: nowrap;
        }
        
        td {
            padding: 15px 12px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: top;
            transition: background-color 0.2s ease;
        }
        
        tr:hover td {
            background-color: #f8f9fa;
        }
        
        .contact-info {
            display: flex;
            flex-direction: column;
        }
        
        .contact-name {
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .contact-email, .contact-phone {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        
        .contact-subject {
            font-weight: 600;
            color: #333;
        }
        
        .contact-excerpt {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
            max-width: 300px;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .badge-vu {
            background-color: var(--success-color);
            color: white;
        }
        
        .badge-nonvu {
            background-color: var(--warning-color);
            color: #000;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .badge-client {
            background-color: var(--primary-light);
            color: white;
            margin-top: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .action-btn1 {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .action-view {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-view:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .action-reply {
            background-color: #4a6cf7;
            color: white;
        }
        
        .action-reply:hover {
            background-color: #3a56d0;
            transform: translateY(-2px);
        }
        
        .action-phone {
            background-color: #28a745;
            color: white;
        }
        
        .action-phone:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        
        .action-whatsapp {
            background-color: #25D366;
            color: white;
        }
        
        .action-whatsapp:hover {
            background-color: #128C7E;
            transform: translateY(-2px);
        }
        
        .action-sms {
            background-color: #6610f2;
            color: white;
        }
        
        .action-sms:hover {
            background-color: #5300d8;
            transform: translateY(-2px);
        }
        
        .action-delete {
            background-color: var(--danger-color);
            color: white;
        }
        
        .action-delete:hover {
            background-color: #bd2130;
            transform: translateY(-2px);
        }
        
        .date-cell {
            white-space: nowrap;
            font-size: 0.9rem;
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ccc;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            padding: 20px;
            gap: 10px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            background: #f1f1f1;
            color: #333;
            transition: all 0.2s ease;
        }
        
        .pagination a:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .pagination .current {
            background: var(--primary-color);
            color: white;
        }
        
        .audio-player {
            margin-top: 10px;
            width: 100%;
            max-width: 250px;
        }
        
        .audio-player audio {
            width: 100%;
        }
        
        .communication-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .communication-label {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
            font-weight: 600;
            width: 100%;
        }
        
        .mark-read-btn1 {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .mark-read-btn1:hover {
            background: #218838;
        }
        
        .mark-all-btn1 {
            background: var(--primary-dark);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .mark-all-btn1:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .toolbar {
                flex-direction: column;
                gap: 15px;
            }
            
            .filters {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .search-box1 {
                width: 100%;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .communication-options {
                flex-direction: column;
            }
            
            .stats-card {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    <div class="home-content">
    <div class="admin-container">
        <div class="header">
            <div class="page-title">
                <div class="icon">📩</div>
                <h1>Gestion des Contacts</h1>
            </div>
            <div class="stats-card">
                <div class="number"><?php echo $nonVusCount; ?></div>
                <div class="label">Messages non lus</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Liste des messages</h2>
            </div>
            
            <div class="toolbar">
                <div class="filters">
                    <a href="?filtre=tous" class="btn1 <?php echo $filtre === 'tous' ? 'btn1-primary' : 'btn1-outline'; ?>">📄 Tous les messages</a>
                    <a href="?filtre=non_vus" class="btn1 <?php echo $filtre === 'non_vus' ? 'btn1-primary' : 'btn1-outline'; ?>">📩 Non lus</a>
                    <a href="?tri=<?php echo $tri === 'recent' ? 'ancien' : 'recent'; ?>" class="btn1 btn1-outline">
                        <?php echo $tri === 'recent' ? 'Du plus ancien' : 'Du plus récent'; ?>
                    </a>
                    
                    <!-- Lien corrigé pour marquer tout comme lu -->
                    <a href="?mark_all_read=1" 
                       class="btn1 btn1-success"
                       onclick="return confirm('Marquer tous les messages comme lus ?')">
                        <i class="fas fa-check-double"></i> Marquer tout comme lu
                    </a>
                </div>
                
                <div class="search-box1">
                    <input type="text" id="searchInput" placeholder="Rechercher un contact..." value="<?php echo htmlspecialchars($search); ?>">
                    <button id="searchbtn1"><i class="fas fa-search"></i></button>
                </div>
            </div>
            
            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Contact</th>
                            <th>Sujet & Message</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): 
                            // Nettoyer et formater le numéro de téléphone
                            $phone = $row['telephone'] ?? '';
                            $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
                            $whatsapp_link = "https://wa.me/$clean_phone";
                            $sms_link = "sms:$clean_phone";
                            $call_link = "tel:$clean_phone";
                        ?>
                        <tr>
                            <td>
                                <div class="contact-info">
                                    <div class="contact-name"><?= htmlspecialchars($row['nom'] ?? '') ?></div>
                                    <div class="contact-email"><?= htmlspecialchars($row['email'] ?? '') ?></div>
                                    <div class="contact-phone"><?= htmlspecialchars($phone) ?></div>
                                    <?php if (!empty($row['client_id'])): ?>
                                        <div class="badge badge-client">Client enregistré</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="contact-subject"><?= htmlspecialchars($row['sujet'] ?? '') ?></div>
                                <div class="contact-excerpt"><?= nl2br(htmlspecialchars(mb_strimwidth($row['message'], 0, 100, "..."))) ?></div>
                                
                                <?php if (!empty($row['audio_path'])): ?>
                                    <div class="audio-player">
                                        <div class="communication-label">Message vocal:</div>
                                        <audio controls>
                                            <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/mpeg">
                                            Votre navigateur ne supporte pas l'élément audio.
                                        </audio>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="date-cell"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td>
                                <?php if ($row['vu']): ?>
                                    <span class="badge badge-vu">Lu</span>
                                <?php else: ?>
                                    <span class="badge badge-nonvu">Non lu</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <!-- Bouton "Voir" avec paramètre pour marquer comme lu -->
                                    <a href="lire_message.php?id=<?= $row['id'] ?>&mark_read=1" class="action-btn1 action-view">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    
                                    <?php if (!empty($row['client_id']) && isset($row['invite']) && $row['invite'] == 0): ?>
                                        <!-- Bouton "Messagerie" avec marquage comme lu -->
                                        <a href="message_client.php?client_id=<?= $row['client_id'] ?>&contact_id=<?= $row['id'] ?>" 
                                           class="action-btn1 action-reply" 
                                           onclick="markAsRead(<?= $row['id'] ?>)">
                                            <i class="fas fa-comment"></i> Messagerie
                                        </a>
                                    <?php else: ?>
                                        <a href="mailto:<?= htmlspecialchars($row['email']) ?>?subject=Réponse à votre contact" class="action-btn1 action-reply" onclick="markAsRead(<?= $row['id'] ?>)">
                                            <i class="fas fa-envelope"></i> Email
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="supprimer_contact.php?id=<?= $row['id'] ?>" class="action-btn1 action-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                                
                                <?php if (!empty($phone)): ?>
                                <div class="communication-label">Répondre par téléphone:</div>
                                <div class="communication-options">
                                    <a href="<?= $call_link ?>" class="action-btn1 action-phone" onclick="markAsRead(<?= $row['id'] ?>)">
                                        <i class="fas fa-phone"></i> Appel
                                    </a>
                                    
                                    <a href="<?= $whatsapp_link ?>" target="_blank" class="action-btn1 action-whatsapp" onclick="markAsRead(<?= $row['id'] ?>)">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                    
                                    <a href="<?= $sms_link ?>" class="action-btn1 action-sms" onclick="markAsRead(<?= $row['id'] ?>)">
                                        <i class="fas fa-comment-alt"></i> SMS
                                    </a>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Aucun message trouvé</h3>
                    <p>Votre boîte de réception est vide pour le moment.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="pagination">
                <a href="#"><i class="fas fa-chevron-left"></i> Précédent</a>
                <span class="current">1</span>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#">Suivant <i class="fas fa-chevron-right"></i></a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Recherche
            const searchbtn1 = document.getElementById('searchbtn1');
            const searchInput = document.getElementById('searchInput');
            
            searchbtn1.addEventListener('click', function() {
                const searchValue = searchInput.value.trim();
                const params = new URLSearchParams(window.location.search);
                
                if (searchValue) {
                    params.set('search', searchValue);
                } else {
                    params.delete('search');
                }
                
                window.location.search = params.toString();
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchbtn1.click();
                }
            });
            
            // Animation pour les badges non lus
            const nonVuBadges = document.querySelectorAll('.badge-nonvu');
            nonVuBadges.forEach(badge => {
                badge.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                });
                
                badge.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
        
        // Fonction pour marquer un message comme lu
        function markAsRead(contactId) {
            fetch(`contact_visiteur.php?mark_read=${contactId}`)
                .then(response => {
                    // Mettre à jour l'interface
                    const badge = document.querySelector(`tr:has(td:nth-child(4) .badge-nonvu`);
                    if (badge) {
                        badge.classList.remove('badge-nonvu');
                        badge.classList.add('badge-vu');
                        badge.textContent = 'Lu';
                        
                        // Supprimer le bouton "Marquer comme lu"
                        const markbtn1 = document.querySelector(`tr:has(td:nth-child(4) .mark-read-btn1`);
                        if (markbtn1) markbtn1.remove();
                        
                        // Mettre à jour le compteur de messages non lus
                        const nonVusCount = document.querySelector('.stats-card .number');
                        if (nonVusCount) {
                            const currentCount = parseInt(nonVusCount.textContent);
                            if (currentCount > 0) {
                                nonVusCount.textContent = currentCount - 1;
                            }
                        }
                    }
                })
                .catch(error => console.error('Erreur:', error));
        }
    </script>
</body>
</html>