<?php
session_start();
$searchContext = "messagel";
$conn = new mysqli("localhost", "root", "", "tchadshop_db");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Gestion des suppressions
if (isset($_GET['delete'])) {
    if ($_GET['delete'] === 'all' && isset($_GET['livreur_id'])) {
        $livreur_id = intval($_GET['livreur_id']);
        $conn->query("DELETE FROM messages_livreurs WHERE livreur_id = $livreur_id");
        header("Location: message_livreur.php?livreur_id=$livreur_id");
        exit();
    } elseif (isset($_GET['message_id'])) {
        $message_id = intval($_GET['message_id']);
        $conn->query("DELETE FROM messages_livreurs WHERE id = $message_id");
        
        if (isset($_GET['livreur_id'])) {
            header("Location: message_livreur.php?livreur_id=" . intval($_GET['livreur_id']));
        } else {
            header("Location: message_livreur.php");
        }
        exit();
    }
}

// Récupérer l'ID du livreur sélectionné
$livreur_id = $_GET['livreur_id'] ?? null;
$current_livreur = null;
$messages = [];

// 🔎 Récupérer la liste des livreurs avec conversations
$livreurs_sql = "SELECT 
            l.id AS livreur_id,
            l.nom,
            l.email,
            MAX(ml.created_at) AS dernier_message,
            COUNT(ml.id) AS total_messages,
            SUM(CASE WHEN ml.lu = 0 THEN 1 ELSE 0 END) AS non_lus
        FROM livreurs l
        LEFT JOIN messages_livreurs ml ON l.id = ml.livreur_id
        GROUP BY l.id, l.nom, l.email
        ORDER BY dernier_message DESC";

$livreurs_result = $conn->query($livreurs_sql);

// 🔎 Récupérer les messages du livreur sélectionné
if ($livreur_id && is_numeric($livreur_id)) {
    // Marquer tous les messages comme lus
    $update_stmt = $conn->prepare("UPDATE messages_livreurs SET lu = 1 WHERE livreur_id = ?");
    $update_stmt->bind_param("i", $livreur_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Récupérer les infos du livreur
    $livreur_stmt = $conn->prepare("SELECT * FROM livreurs WHERE id = ?");
    $livreur_stmt->bind_param("i", $livreur_id);
    $livreur_stmt->execute();
    $current_livreur = $livreur_stmt->get_result()->fetch_assoc();
    $livreur_stmt->close();
    
    // Récupérer les messages
    $messages_sql = "SELECT * FROM messages_livreurs 
                    WHERE livreur_id = ? 
                    ORDER BY created_at ASC";
    $stmt = $conn->prepare($messages_sql);
    $stmt->bind_param("i", $livreur_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ✉ Envoyer une réponse (texte ou audio)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $livreur_id) {
    $reponse = trim($_POST['reponse'] ?? '');
    $audio_path = null;
    
    // Gestion de l'audio
    if (!empty($_FILES['audio_file']['tmp_name'])) {
        $file_extension = pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['mp3', 'wav', 'ogg', 'webm'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $filename = "audio_admin_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_extension;
            $target_dir = __DIR__ . "/uploads/audio_support/";
            
            // Créer le dossier s'il n'existe pas
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $target_file)) {
                $audio_path = "uploads/audio_support/" . $filename;
            } else {
                error_log("Échec d'upload audio: " . $target_file);
            }
        } else {
            error_log("Format audio non supporté: " . $file_extension);
        }
    }
    
    if (!empty($reponse) || $audio_path) {
        $stmt = $conn->prepare("INSERT INTO messages_livreurs (livreur_id, sujet, message, audio_path, repondu) 
                               VALUES (?, 'Réponse admin', ?, ?, 1)");
        $stmt->bind_param("iss", $livreur_id, $reponse, $audio_path);
        if ($stmt->execute()) {
            // Recharger la page pour afficher le nouveau message
            header("Location: message_livreur.php?livreur_id=$livreur_id");
            exit();
        } else {
            error_log("Erreur insertion message: " . $stmt->error);
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📥 Conversations Livreurs - TchadShop Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c6e49;
            --primary-light: #4c956c;
            --secondary: #e3f2fd;
            --accent: #ff6b6b;
            --dark: #0d47a1;
            --light: #f8f9fa;
            --gray: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --radius: 16px;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-hover: 0 6px 25px rgba(0,0,0,0.12);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa, #e4e7f1);
            color: #333;
            min-height: 100vh;
            line-height: 1.6;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 25px;
            height: calc(100vh - 60px);
        }
        
        .conversations-panel {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            margin-top: 70px;
            transition: var(--transition);
        }
        
        .conversations-panel:hover {
            box-shadow: var(--shadow-hover);
        }
        
        .panel-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .panel-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .search-box1 {
            position: relative;
        }
        
        .search-box1 input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .search-box1 input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.15);
        }
        
        .search-box1 i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        .conversation-item {
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 10px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            gap: 15px;
            position: relative;
            text-decoration: none;
            color: inherit;
        }
        
        .conversation-item:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        
        .conversation-item.active {
            background: rgba(30, 136, 229, 0.1);
            border-left: 4px solid var(--primary);
        }
        
        .livreur-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .livreur-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .last-message {
            color: var(--gray);
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
        }
        
        .message-date {
            color: var(--gray);
            font-size: 0.8rem;
            white-space: nowrap;
        }
        
        .unread-count {
            background: var(--accent);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .chat-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            margin-top: 70px;
            transition: var(--transition);
        }
        
        .chat-container:hover {
            box-shadow: var(--shadow-hover);
        }
        
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .chat-actions {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .action-btn {
            background: transparent;
            border: none;
            color: var(--gray);
            font-size: 1.2rem;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            background: #f0f2f5;
            color: var(--primary);
            transform: scale(1.1);
        }
        
        .delete-all-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }
        
        .delete-all-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .chat-info {
            flex: 1;
        }
        
        .chat-livreur-name {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--dark);
            margin-bottom: 3px;
        }
        
        .chat-livreur-email {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #f8f9fa;
        }
        
        .message {
            max-width: 75%;
            padding: 15px 20px;
            border-radius: 18px;
            position: relative;
            animation: fadeIn 0.4s ease;
            box-shadow: var(--shadow);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .livreur-message {
            background: white;
            border: 1px solid #e0e0e0;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }
        
        .admin-message {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }
        
        .message-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            margin-top: 10px;
            opacity: 0.8;
        }
        
        .livreur-message .message-meta {
            color: var(--gray);
        }
        
        .admin-message .message-meta {
            color: rgba(255,255,255,0.8);
        }
        
        .message-content {
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .audio-container {
            margin-top: 10px;
            background: rgba(0,0,0,0.05);
            border-radius: 20px;
            padding: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-message .audio-container {
            background: rgba(255,255,255,0.1);
        }
        
        .audio-icon {
            font-size: 1.2rem;
            color: var(--primary);
        }
        
        .admin-message .audio-icon {
            color: white;
        }
        
        audio {
            flex: 1;
            height: 40px;
        }
        
        .chat-input-container {
            padding: 15px;
            border-top: 1px solid #eee;
            background: white;
        }
        
        .chat-input-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .input-container {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        textarea {
            flex: 1;
            min-height: 60px;
            max-height: 150px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-size: 1rem;
            resize: none;
            transition: var(--transition);
        }
        
        textarea:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.15);
        }
        
        .btn-send {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-send:hover {
            background: var(--primary-light);
            transform: scale(1.05);
        }
        
        .no-conversation {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 30px;
            color: var(--gray);
        }
        
        .no-conversation i {
            font-size: 5rem;
            margin-bottom: 20px;
            color: #e0e0e0;
        }
        
        .no-conversation h3 {
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .empty-list {
            text-align: center;
            padding: 50px 20px;
            color: var(--gray);
        }
        
        .empty-list i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #e0e0e0;
        }
        
        .recorder-container {
            background: #f8f9fa;
            border-radius: 30px;
            padding: 10px 15px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            display: none;
        }
        
        .recorder-container.active {
            display: flex;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .recorder-visualizer {
            flex: 1;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 0 10px;
            gap: 2px;
        }
        
        .visualizer-bar {
            width: 4px;
            height: 5px;
            background: var(--primary);
            border-radius: 2px;
            transition: height 0.2s;
        }
        
        .recorder-time {
            font-weight: 600;
            color: var(--dark);
            min-width: 60px;
            text-align: center;
        }
        
        .recorder-btn {
            background: transparent;
            border: none;
            color: var(--accent);
            font-size: 1.5rem;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .recorder-btn:hover {
            background: rgba(255, 107, 107, 0.1);
        }
        
        .recorder-btn.recording {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(255, 107, 107, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0); }
        }
        
        .voice-btn {
            background: transparent;
            border: none;
            color: var(--primary);
            font-size: 1.5rem;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .voice-btn:hover {
            background: rgba(30, 136, 229, 0.1);
        }
        
        .btn-container {
            display: flex;
            gap: 5px;
        }
        
        .message-status {
            font-size: 0.7rem;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .delete-message {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.9rem;
        }
        
        .admin-message .delete-message {
            color: rgba(255,255,255,0.7);
        }
        
        .livreur-message .delete-message {
            color: rgba(0,0,0,0.4);
        }
        
        .message:hover .delete-message {
            opacity: 1;
        }
        
        .delete-message:hover {
            color: var(--danger);
        }
        
        .confirmation-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .confirmation-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 90%;
            transform: translateY(20px);
            transition: transform 0.3s;
        }
        
        .confirmation-modal.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .modal-text {
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            border: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-cancel {
            background: #f0f2f5;
            color: var(--gray);
        }
        
        .btn-cancel:hover {
            background: #e4e6e9;
        }
        
        .btn-confirm {
            background: var(--danger);
            color: white;
        }
        
        .btn-confirm:hover {
            background: #c82333;
        }
        
        @media (max-width: 992px) {
            .admin-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .conversations-panel {
                height: 400px;
            }
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding: 15px;
            }
            
            .livreur-avatar, .chat-avatar {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .chat-header {
                padding: 15px;
            }
            
            .chat-messages {
                padding: 15px;
            }
            
            .message {
                max-width: 85%;
            }
            
            .delete-all-btn span {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .conversation-item {
                padding: 10px;
            }
            
            .livreur-name {
                font-size: 0.9rem;
            }
            
            .last-message {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
<?php include("header.php"); ?>

<div class="admin-container">
    <div class="conversations-panel">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fas fa-motorcycle"></i>
                <span>Livreurs</span>
            </div>
            <div class="search-box1">
                <i class="fas fa-search"></i>
                <input type="text" id="searchLivreur" placeholder="Rechercher un livreur...">
            </div>
        </div>
        
        <div class="conversations-list">
            <?php if ($livreurs_result->num_rows > 0): ?>
                <?php while ($row = $livreurs_result->fetch_assoc()): 
                    $is_active = $livreur_id == $row['livreur_id'];
                    ?>
                    <a href="?livreur_id=<?= $row['livreur_id'] ?>" 
                       class="conversation-item <?= $is_active ? 'active' : '' ?>">
                        <div class="livreur-avatar">
                            <?= strtoupper(substr($row['nom'], 0, 1)) ?>
                        </div>
                        
                        <div class="conversation-info">
                            <div class="livreur-name">
                                <?= htmlspecialchars($row['nom']) ?>
                            </div>
                            <div class="last-message">
                                <?= $row['total_messages'] > 0 ? 
                                    "{$row['total_messages']} message".($row['total_messages'] > 1 ? 's' : '') : 
                                    "Aucun message" ?>
                            </div>
                        </div>
                        
                        <div class="conversation-meta">
                            <div class="message-date">
                                <?= $row['dernier_message'] ? date('d/m', strtotime($row['dernier_message'])) : '-' ?>
                            </div>
                            <?php if ($row['non_lus'] > 0): ?>
                                <div class="unread-count"><?= $row['non_lus'] ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-list">
                    <i class="fas fa-comment-slash"></i>
                    <h3>Aucune conversation</h3>
                    <p>Aucun livreur n'a encore envoyé de message</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="chat-container">
        <?php if ($current_livreur): ?>
            <div class="chat-header">
                <div class="chat-avatar">
                    <?= strtoupper(substr($current_livreur['nom'], 0, 1)) ?>
                </div>
                <div class="chat-info">
                    <div class="chat-livreur-name">
                        <?= htmlspecialchars($current_livreur['nom']) ?>
                    </div>
                    <div class="chat-livreur-email">
                        <?= htmlspecialchars($current_livreur['email'] ?? '') ?>
                    </div>
                </div>
                
                <div class="chat-actions">
                    <button class="action-btn" title="Marquer comme important">
                        <i class="fas fa-star"></i>
                    </button>
                    <button class="delete-all-btn" id="deleteAllBtn" title="Supprimer la conversation">
                        <i class="fas fa-trash"></i>
                        <span>Supprimer tout</span>
                    </button>
                </div>
            </div>
            
            <div class="chat-messages">
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $message): 
                        $is_admin = $message['sujet'] === 'Réponse admin';
                        ?>
                        <div class="message <?= $is_admin ? 'admin-message' : 'livreur-message' ?>">
                            <button class="delete-message" 
                                    data-message-id="<?= $message['id'] ?>"
                                    title="Supprimer ce message">
                                <i class="fas fa-trash"></i>
                            </button>
                            
                            <div class="message-content">
                                <?php if (!empty($message['message'])): ?>
                                    <?= nl2br(htmlspecialchars($message['message'])) ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($message['audio_path'])): ?>
                                    <div class="audio-container">
                                        <i class="fas fa-volume-up audio-icon"></i>
                                        <audio controls>
                                            <source src="<?= htmlspecialchars($message['audio_path']) ?>" type="audio/mpeg">
                                            <source src="<?= htmlspecialchars($message['audio_path']) ?>" type="audio/webm">
                                            <source src="<?= htmlspecialchars($message['audio_path']) ?>" type="audio/wav">
                                            <source src="<?= htmlspecialchars($message['audio_path']) ?>" type="audio/ogg">
                                            Votre navigateur ne supporte pas l'élément audio.
                                        </audio>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="message-meta">
                                <span><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></span>
                                <?php if ($is_admin): ?>
                                    <span class="message-status">Envoyé</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-conversation">
                        <i class="fas fa-comments"></i>
                        <h3>Pas de messages</h3>
                        <p>Ce livreur n'a pas encore envoyé de message</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="chat-input-container">
                <form method="post" class="chat-input-form" id="messageForm" enctype="multipart/form-data">
                    <input type="file" name="audio_file" id="audioFileInput" style="display: none;">
                    
                    <div class="input-container">
                        <textarea name="reponse" id="messageInput" placeholder="Écrivez votre réponse..."></textarea>
                        <div class="btn-container">
                            <button type="button" class="voice-btn" id="voiceButton" title="Enregistrement vocal">
                                <i class="fas fa-microphone"></i>
                            </button>
                            <button type="submit" class="btn-send" title="Envoyer">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="recorder-container" id="recorderContainer">
                        <div class="recorder-visualizer" id="visualizer"></div>
                        <div class="recorder-time" id="recordingTime">00:00</div>
                        <button type="button" class="recorder-btn" id="stopRecord" title="Arrêter l'enregistrement">
                            <i class="fas fa-stop"></i>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="no-conversation">
                <i class="fas fa-comments"></i>
                <h3>Pas de conversation sélectionnée</h3>
                <p>Sélectionnez un livreur dans la liste pour afficher la conversation</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="confirmation-modal" id="confirmationModal">
    <div class="modal-content">
        <h3 class="modal-title">Confirmer la suppression</h3>
        <p class="modal-text" id="modalText">Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.</p>
        <div class="modal-actions">
            <button class="modal-btn btn-cancel" id="cancelDelete">Annuler</button>
            <button class="modal-btn btn-confirm" id="confirmDelete">Supprimer</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Barre de recherche des livreurs
    const searchInput = document.getElementById('searchLivreur');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const conversationItems = document.querySelectorAll('.conversation-item');
            
            conversationItems.forEach(item => {
                const livreurName = item.querySelector('.livreur-name').textContent.toLowerCase();
                if (livreurName.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Enregistrement vocal
    const voiceButton = document.getElementById('voiceButton');
    const stopRecordBtn = document.getElementById('stopRecord');
    const recorderContainer = document.getElementById('recorderContainer');
    const visualizer = document.getElementById('visualizer');
    const recordingTime = document.getElementById('recordingTime');
    const audioFileInput = document.getElementById('audioFileInput');
    const messageForm = document.getElementById('messageForm');
    
    let mediaRecorder;
    let audioChunks = [];
    let audioStream;
    let recordingInterval;
    let seconds = 0;
    let isRecording = false;
    
    // Créer les barres du visualiseur
    if (visualizer) {
        for (let i = 0; i < 20; i++) {
            const bar = document.createElement('div');
            bar.className = 'visualizer-bar';
            visualizer.appendChild(bar);
        }
    }
    
    const bars = document.querySelectorAll('.visualizer-bar');
    
    // Mettre à jour le visualiseur audio
    function updateVisualizer() {
        bars.forEach(bar => {
            const height = Math.floor(Math.random() * 20) + 5;
            bar.style.height = `${height}px`;
        });
    }
    
    // Formater le temps
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
        const secs = (seconds % 60).toString().padStart(2, '0');
        return `${mins}:${secs}`;
    }
    
    // Obtenir les formats supportés
    function getSupportedMimeType() {
        const types = [
            'audio/webm;codecs=opus',
            'audio/mp3',
            'audio/wav',
            'audio/mpeg',
            'audio/ogg;codecs=opus'
        ];
        
        for (let type of types) {
            if (MediaRecorder.isTypeSupported(type)) {
                return type;
            }
        }
        
        return 'audio/webm';
    }
    
    // Démarrer l'enregistrement
    async function startRecording() {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error("L'enregistrement audio n'est pas supporté par votre navigateur");
            }
            
            audioStream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    sampleRate: 44100
                }
            });
            
            const options = { 
                audioBitsPerSecond: 128000,
                mimeType: getSupportedMimeType()
            };
            
            mediaRecorder = new MediaRecorder(audioStream, options);
            audioChunks = [];
            isRecording = true;
            
            mediaRecorder.ondataavailable = event => {
                if (event.data.size > 0) {
                    audioChunks.push(event.data);
                }
            };
            
            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunks, { 
                    type: mediaRecorder.mimeType 
                });
                
                // Créer un fichier pour l'upload
                const file = new File([audioBlob], 'enregistrement.webm', {
                    type: mediaRecorder.mimeType,
                    lastModified: Date.now()
                });
                
                // Créer un DataTransfer pour simuler l'upload
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                audioFileInput.files = dataTransfer.files;
            };
            
            mediaRecorder.start();
            recorderContainer.classList.add('active');
            voiceButton.classList.add('recording');
            
            // Démarrer le minuteur
            seconds = 0;
            recordingInterval = setInterval(() => {
                seconds++;
                recordingTime.textContent = formatTime(seconds);
            }, 1000);
            
            // Démarrer le visualiseur
            visualizerInterval = setInterval(updateVisualizer, 100);
            
        } catch (error) {
            console.error('Erreur d\'accès au microphone:', error);
            alert('Erreur: ' + error.message + '. Veuillez autoriser l\'accès au microphone.');
        }
    }
    
    // Arrêter l'enregistrement
    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
            isRecording = false;
            
            if (audioStream) {
                audioStream.getTracks().forEach(track => track.stop());
            }
            
            clearInterval(recordingInterval);
            clearInterval(visualizerInterval);
            voiceButton.classList.remove('recording');
            recorderContainer.classList.remove('active');
        }
    }
    
    // Événements
    if (voiceButton) {
        voiceButton.addEventListener('click', function() {
            if (isRecording) {
                stopRecording();
            } else {
                startRecording();
            }
        });
    }
    
    if (stopRecordBtn) {
        stopRecordBtn.addEventListener('click', stopRecording);
    }
    
    // Auto-resize du textarea
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
    // Faire défiler vers le bas automatiquement
    const chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Arrêter l'enregistrement si le formulaire est soumis
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            if (isRecording) {
                e.preventDefault();
                stopRecording();
                
                // Attendre que l'enregistrement soit converti
                setTimeout(() => {
                    messageForm.submit();
                }, 500);
            }
        });
    }
    
    // Gestion de la suppression des messages
    const deleteButtons = document.querySelectorAll('.delete-message');
    const modal = document.getElementById('confirmationModal');
    const modalText = document.getElementById('modalText');
    const cancelDelete = document.getElementById('cancelDelete');
    const confirmDelete = document.getElementById('confirmDelete');
    const deleteAllBtn = document.getElementById('deleteAllBtn');
    
    let currentMessageToDelete = null;
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentMessageToDelete = this.getAttribute('data-message-id');
            modalText.textContent = "Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.";
            modal.classList.add('active');
        });
    });
    
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', function() {
            currentMessageToDelete = null;
            modalText.textContent = "Êtes-vous sûr de vouloir supprimer toute la conversation ? Tous les messages seront définitivement effacés.";
            modal.classList.add('active');
        });
    }
    
    cancelDelete.addEventListener('click', function() {
        modal.classList.remove('active');
        currentMessageToDelete = null;
    });
    
    confirmDelete.addEventListener('click', function() {
        modal.classList.remove('active');
        
        if (currentMessageToDelete) {
            // Supprimer un message spécifique
            window.location.href = `?delete=message&message_id=${currentMessageToDelete}&livreur_id=<?= $livreur_id ?>`;
        } else {
            // Supprimer toute la conversation
            window.location.href = `?delete=all&livreur_id=<?= $livreur_id ?>`;
        }
    });
    
    // Fermer la modal en cliquant à l'extérieur
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
            currentMessageToDelete = null;
        }
    });
});
</script>
</body>
</html>