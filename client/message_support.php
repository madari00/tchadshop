<?php
require_once 'init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Vérifier si la connexion existe déjà (peut-être déjà établie par header.php)
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "tchadshop_db");
    if ($conn->connect_error) die("Erreur de connexion : " . $conn->connect_error);
}

if (!isset($_SESSION['client_id'])) {
    echo "<div class='login-required'>";
    echo "<p> " .$trans['login_to_send_message'] . "</p>";
    echo "<a href='login.php' class='btn'>🔑 " .$trans['login'] . "</a>";
    echo "</div>";
    exit;
}

$client_id = $_SESSION['client_id'];
$message_envoye = false;
$upload_dir = __DIR__ . "/uploads/audio_support/";

// Créer le dossier s'il n'existe pas avec vérification
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        die("<div class='error'>" .$trans['upload_dir_creation_failed'] . "</div>");
    }
}

// Vérifier les permissions d'écriture
if (!is_writable($upload_dir)) {
    die("<div class='error'>" .$trans['upload_dir_not_writable'] . "</div>");
}

// Gestion des suppressions
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $message_id = (int)$_GET['id'];
        $stmt = $conn->prepare("DELETE FROM messages_clients WHERE id = ? AND client_id = ?");
        $stmt->bind_param("ii", $message_id, $client_id);
        $stmt->execute();
        $stmt->close();
        header("Location: message_support.php");
        exit;
    }
    elseif ($_GET['action'] === 'delete_all') {
        $stmt = $conn->prepare("DELETE FROM messages_clients WHERE client_id = ?");
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $stmt->close();
        header("Location: message_support.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);
    $sujet = "Support client";
    $audio_path = null;

    // Gestion de l'enregistrement vocal
    if (!empty($_FILES['audio_file']['tmp_name'])) {
        $file_extension = pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['mp3', 'wav', 'ogg', 'webm'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $filename = "audio_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_extension;
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $target_file)) {
                $audio_path = "uploads/audio_support/" . $filename;
            } else {
                error_log($trans['upload_failed'] . " " . $target_file);
            }
        } else {
            error_log($trans['unsupported_audio_format'] . " " . $file_extension);
        }
    }

    if (!empty($message) || $audio_path) {
        $stmt = $conn->prepare("INSERT INTO messages_clients (client_id, sujet, message, audio_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $client_id, $sujet, $message, $audio_path);
        if ($stmt->execute()) {
            $message_envoye = true;
        } else {
            error_log($trans['db_error'] . " " . $stmt->error);
        }
        $stmt->close();
    }
}

// CORRECTION: Nouvelle requête pour identifier correctement les messages du support
$sql = "SELECT * FROM messages_clients 
        WHERE client_id = ? 
        AND (
            (sujet = 'Support client' AND (message IS NOT NULL OR audio_path IS NOT NULL))
            OR (sujet = 'Réponse admin' AND (message IS NOT NULL OR audio_path IS NOT NULL))
        )
        ORDER BY created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $trans['customer_support']; ?> - TchadShop</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2c6e49;
    --primary-light: #4c956c;
    --secondary: #fefee3;
    --accent: #ff6b6b;
    --dark: #1d3557;
    --light: #f8f9fa;
    --gray: #6c757d;
    --success: #28a745;
    --warning: #ffc107;
    --danger: #dc3545;
    --radius: 12px;
    --support-msg: #e3f2fd;
    --client-msg: #a695afff;
    --support-color: #2c6e49;
    --client-color: #6a1b9a;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    color: #333;
    min-height: 100vh;
    line-height: 1.6;
    padding-bottom: 80px;
}

.error {
    background: var(--danger);
    color: white;
    padding: 15px;
    text-align: center;
    margin: 10px;
    border-radius: var(--radius);
}

.container {
    max-width: 100%;
    padding: 0;
}

.header {
    background: linear-gradient(to right, var(--primary), var(--primary-light));
    color: white;
    padding: 15px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header h1 {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.header p {
    font-size: 0.9rem;
    opacity: 0.9;
}

.chat-container {
    display: flex;
    flex-direction: column;
    background: white;
    max-width: 800px;
    margin: 20px auto;
    border-radius: var(--radius);
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
    position: relative;
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.chat-title {
    font-size: 1.2rem;
    color: var(--support-color);
    display: flex;
    align-items: center;
    gap: 10px;
}

.chat-title i {
    color: var(--support-color);
}

.delete-all-btn {
    background: var(--danger);
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s;
}

.delete-all-btn:hover {
    background: #c62828;
    transform: translateY(-2px);
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px 15px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    max-height: calc(100vh - 250px);
    background: #fafafa;
}

.message {
    padding: 15px;
    border-radius: 18px;
    max-width: 85%;
    position: relative;
    animation: fadeIn 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Messages du client (à droite) */
.client {
    background: var(--client-msg);
    color: var(--dark);
    margin-left: auto;
    border-radius: 18px 18px 0 18px;
    border: 1px solid #c3e6cb;
}

/* Messages du support (à gauche) */
.support {
    background: var(--support-msg);
    color: var(--dark);
    margin-right: auto;
    border: 1px solid #bbdefb;
    border-radius: 18px 18px 18px 0;
    position: relative;
}

.message-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-weight: 600;
}

.support .message-header {
    color: var(--support-color);
}

.client .message-header {
    color: var(--client-color);
}

.message .meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    margin-top: 8px;
    opacity: 0.8;
}

.client .meta {
    color: var(--client-color);
}

.support .meta {
    color: var(--support-color);
}

.message audio {
    max-width: 100%;
    min-width: 250px;
    margin-top: 8px;
    background: rgba(0,0,0,0.05);
    border-radius: 20px;
    padding: 5px;
    outline: none;
}

.audio-warning {
    color: var(--danger);
    font-size: 0.9rem;
    margin-top: 5px;
}

.chat-input-container {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    padding: 10px 15px;
    border-top: 1px solid #eee;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    display: flex;
    gap: 10px;
    align-items: center;
    max-width: 800px;
    margin: 0 auto;
    width: 100%;
    z-index: 100;
}

.chat-input {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fa;
    border-radius: 24px;
    padding: 5px 15px;
}

.chat-input textarea {
    width: 100%;
    padding: 10px 0;
    border: none;
    background: transparent;
    resize: none;
    font-size: 1rem;
    min-height: 40px;
    max-height: 150px;
    outline: none;
}

.recorder-btn {
    background: transparent;
    border: none;
    color: var(--support-color);
    cursor: pointer;
    font-size: 1.4rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
}

.recorder-btn:hover {
    background: rgba(44, 110, 73, 0.1);
}

.recorder-btn.recording {
    color: var(--accent);
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7); }
    70% { box-shadow: 0 0 0 15px rgba(255, 107, 107, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0); }
}

.send-btn {
    background: linear-gradient(135deg, var(--support-color) 0%, var(--primary-light) 100%);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.3s;
}

.send-btn:hover {
    transform: scale(1.05);
}

.recorder {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 24px;
    display: none;
}

.recorder.active {
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

.whatsapp-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #25D366;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    text-decoration: none;
    font-size: 0.8rem;
    margin-top: 5px;
    transition: all 0.3s;
}

.whatsapp-btn:hover {
    background: #128C7E;
}

.recording-status {
    position: fixed;
    bottom: 80px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--accent);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    z-index: 1000;
    display: none;
}

.permission-guide {
    background: #e3f2fd;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
    display: none;
}

.permission-guide.active {
    display: block;
}

.permission-guide h4 {
    color: #1565c0;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.permission-steps {
    padding-left: 20px;
}

.permission-steps li {
    margin-bottom: 8px;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 10000;
    opacity: 0;
    transition: opacity 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.notification.success {
    background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
}

.notification.error {
    background: linear-gradient(135deg, #f44336 0%, #c62828 100%);
}

.delete-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.2);
    color: white;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.3s;
}

.client .delete-btn {
    background: rgba(255,255,255,0.3);
}

.support .delete-btn {
    background: rgba(0,0,0,0.1);
    color: #555;
}

.message:hover .delete-btn {
    opacity: 1;
}

@media (max-width: 768px) {
    .message {
        max-width: 90%;
    }
    
    .chat-input-container {
        padding: 10px;
    }
    
    .chat-input {
        padding: 5px 10px;
    }
    
    .chat-input textarea {
        font-size: 0.9rem;
    }
    
    .delete-btn {
        opacity: 1;
    }
}

@media (max-width: 480px) {
    .header h1 {
        font-size: 1.3rem;
    }
    
    .header p {
        font-size: 0.8rem;
    }
    
    .chat-messages {
        padding: 10px 10px;
    }
    
    .message {
        padding: 12px;
    }
    
    .recorder {
        padding: 8px 12px;
    }
    
    .recorder-btn {
        font-size: 1.2rem;  
    }
    
    .delete-all-btn span {
        display: none;
    }
    .chat-container {
        padding: 12px 16px;
        height: 850px;
    }
}
</style>
</head>
<body>
<div class="container">
    <?php include 'header.php'; ?>
    
    <div class="chat-container">
    <div class="chat-header">
        <div class="chat-title">
            <i class="fas fa-headset"></i>
            <span><?php echo $trans['customer_support']; ?></span>
        </div>
        <button class="delete-all-btn" id="deleteAllBtn">
            <i class="fas fa-trash-alt"></i> <span><?php echo $trans['delete_all']; ?></span>
        </button>
    </div>
    
    <div class="chat-messages" id="chatBox">
        <?php if ($messages->num_rows > 0): ?>
            <?php while ($row = $messages->fetch_assoc()): ?>
                <?php if ($row['sujet'] === 'Support client'): ?>
                    <!-- Message du client (à droite) -->
                    <div class="message client" data-id="<?= $row['id'] ?>">
                        <button class="delete-btn" >
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <div class="message-header">
                            <i class="fas fa-user"></i>
                            <span><?php echo $trans['you']; ?></span>
                        </div>
                        
                        <div class="message-content">
                            <?php if (!empty($row['message'])): ?>
                                <?= nl2br(htmlspecialchars($row['message'])) ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($row['audio_path'])): ?>
                                <audio controls>
                                    <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/mpeg">
                                    <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/webm">
                                    <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/wav">
                                    <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/ogg">
                                    <p class="audio-warning">
                                        <?php echo $trans['browser_no_audio']; ?> 
                                        <a href="<?= htmlspecialchars($row['audio_path']) ?>" download>
                                            <i class="fas fa-download"></i> <?php echo $trans['download_file']; ?>
                                        </a>
                                    </p>
                                </audio>
                            <?php endif; ?>
                            
                            <div class="meta">
                                <span><i class="far fa-clock"></i> <?= date('d/m H:i', strtotime($row['created_at'])) ?></span>
                                <?php
                                $numeroEntreprise = "23560000000";
                                $msgWhatsApp = urlencode($row['message']);
                                ?>
                                <a class="whatsapp-btn" target="_blank" href="https://wa.me/<?= $numeroEntreprise ?>?text=<?= $msgWhatsApp ?>">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                <?php elseif ($row['sujet'] === 'Réponse admin'): ?>
                    <!-- Réponse du support (à gauche) -->
                    <div class="message support">
                        <div class="message-header">
                            <i class="fas fa-headset"></i>
                            <span><?php echo $trans['tchadshop_support']; ?></span>
                        </div>
                        
                        <div class="message-content">
                            <?php if (!empty($row['message'])): ?>
                                <?= nl2br(htmlspecialchars($row['message'])) ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($row['audio_path'])): ?>
                                <audio controls>
                                    <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/mpeg">
                                    <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/webm">
                                    <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/wav">
                                    <source src="<?= htmlspecialchars($row['audio_path']) ?>" type="audio/ogg">
                                    <p class="audio-warning">
                                        <?php echo $trans['browser_no_audio']; ?> 
                                        <a href="<?= htmlspecialchars($row['audio_path']) ?>" download>
                                            <i class="fas fa-download"></i> <?php echo $trans['download_file']; ?>
                                        </a>
                                    </p>
                                </audio>
                            <?php endif; ?>
                            
                            <div class="meta">
                                <span>
                                    <i class="far fa-clock"></i> 
                                    <?php 
                                    if (!empty($row['updated_at'])) {
                                        echo date('d/m H:i', strtotime($row['updated_at']));
                                    } else {
                                        echo date('d/m H:i', strtotime($row['created_at']));
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-messages">
                <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                <p><?php echo $trans['no_messages']; ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="chat-input-container">
    <form method="post" id="messageForm" enctype="multipart/form-data">
        <div class="chat-input">
            <textarea name="message" id="messageInput" placeholder="<?php echo $trans['write_message']; ?>" rows="1"></textarea>
            <button type="button" class="recorder-btn" id="recordButton">
                <i class="fas fa-microphone"></i>
            </button>
            <button type="submit" class="send-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        
        <div class="recorder" id="recorder">
            <div class="recorder-visualizer" id="visualizer"></div>
            <div class="recorder-time" id="recordingTime">00:00</div>
            <button type="button" class="recorder-btn" id="stopRecord">
                <i class="fas fa-stop"></i>
            </button>
        </div>
        
        <input type="file" name="audio_file" id="audioFile" accept="audio/*" style="display: none;">
    </form>
</div>

<div class="recording-status" id="recordingStatus">
    <i class="fas fa-circle"></i> <?php echo $trans['recording_in_progress']; ?>
</div>

<div class="permission-guide" id="permissionGuide">
    <h4><i class="fas fa-info-circle"></i> <?php echo $trans['how_to_allow_microphone']; ?></h4>
    <ol class="permission-steps">
        <li><?php echo $trans['click_lock_icon']; ?></li>
        <li><?php echo $trans['select_site_settings']; ?></li>
        <li><?php echo $trans['in_microphone_section']; ?></li>
        <li><?php echo $trans['refresh_page']; ?></li>
    </ol>
</div>

<script>
// Définir les traductions JavaScript
const translations = {
    microphone_blocked: "<?php echo $trans['microphone_blocked']; ?>",
    no_microphone_detected: "<?php echo $trans['no_microphone_detected']; ?>",
    microphone_in_use: "<?php echo $trans['microphone_in_use']; ?>",
    please_write_message: "<?php echo $trans['please_write_message']; ?>",
    error_sending_message: "<?php echo $trans['error_sending_message']; ?>",
    confirm_delete_message: "<?php echo $trans['confirm_delete_message']; ?>",
    confirm_delete_all: "<?php echo $trans['confirm_delete_all']; ?>"
};

document.addEventListener('DOMContentLoaded', function() {
    const recordButton = document.getElementById('recordButton');
    const stopBtn = document.getElementById('stopRecord');
    const recorder = document.getElementById('recorder');
    const visualizer = document.getElementById('visualizer');
    const recordingTime = document.getElementById('recordingTime');
    const messageForm = document.getElementById('messageForm');
    const chatBox = document.getElementById('chatBox');
    const messageInput = document.getElementById('messageInput');
    const recordingStatus = document.getElementById('recordingStatus');
    const permissionGuide = document.getElementById('permissionGuide');
    const audioFile = document.getElementById('audioFile');
    const sendBtn = document.querySelector('.send-btn');
    const deleteAllBtn = document.getElementById('deleteAllBtn');
    
    let mediaRecorder;
    let audioChunks = [];
    let audioStream;
    let recordingInterval;
    let seconds = 0;
    let visualizerInterval;
    let isRecording = false;
    
    // Créer les barres du visualiseur
    for (let i = 0; i < 20; i++) {
        const bar = document.createElement('div');
        bar.className = 'visualizer-bar';
        visualizer.appendChild(bar);
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
            isRecording = true;
            audioStream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true
                }
            });
            
            const options = { 
                audioBitsPerSecond: 128000,
                mimeType: getSupportedMimeType()
            };
            
            mediaRecorder = new MediaRecorder(audioStream, options);
            audioChunks = [];
            
            mediaRecorder.ondataavailable = event => {
                if (event.data.size > 0) {
                    audioChunks.push(event.data);
                }
            };
            
            mediaRecorder.start();
            recorder.classList.add('active');
            recordButton.style.display = 'none';
            recordingStatus.style.display = 'block';
            permissionGuide.classList.remove('active');
            
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
            
            let errorMessage;
            switch(error.name) {
                case 'NotAllowedError':
                    errorMessage = translations.microphone_blocked;
                    permissionGuide.classList.add('active');
                    break;
                case 'NotFoundError':
                    errorMessage = translations.no_microphone_detected;
                    break;
                case 'NotReadableError':
                    errorMessage = translations.microphone_in_use;
                    break;
                default:
                    errorMessage = translations.microphone_error + error.message;
            }
            
            showNotification(errorMessage, 'error');
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
            recordingStatus.style.display = 'none';
            recordButton.style.display = 'block';
            recorder.classList.remove('active');
            
            // Convertir en fichier audio
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
                audioFile.files = dataTransfer.files;
            };
        }
    }
    
    // Événements
    recordButton.addEventListener('click', startRecording);
    stopBtn.addEventListener('click', stopRecording);
    
    // Auto-resize du textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Soumission du formulaire
    messageForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Si un enregistrement est en cours, on l'arrête d'abord
        if (isRecording) {
            stopRecording();
            
            // Attendre que l'enregistrement soit converti en fichier
            await new Promise(resolve => setTimeout(resolve, 500));
        }
        
        // Vérifier qu'il y a du contenu
        if (!messageInput.value.trim() && !audioFile.files.length) {
            showNotification(translations.please_write_message, 'error');
            return;
        }
        
        const formData = new FormData(this);
        
        // Afficher un indicateur de chargement
        const originalBtnContent = sendBtn.innerHTML;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        sendBtn.disabled = true;
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                // Réinitialiser le formulaire
                messageForm.reset();
                messageInput.style.height = 'auto';
                audioFile.value = '';
                
                // Recharger la page
                location.reload();
            } else {
                showNotification(translations.error_sending_message, 'error');
            }
        } catch (error) {
            showNotification(translations.network_error + error.message, 'error');
        } finally {
            sendBtn.innerHTML = originalBtnContent;
            sendBtn.disabled = false;
        }
    });
    
    // Gestion de la suppression
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const messageDiv = this.closest('.message');
            const messageId = messageDiv.dataset.id;
            
            if (confirm(translations.confirm_delete_message)) {
                window.location.href = `?action=delete&id=${messageId}`;
            }
        });
    });
    
    // Suppression de tous les messages
    deleteAllBtn.addEventListener('click', function() {
        if (confirm(translations.confirm_delete_all)) {
            window.location.href = '?action=delete_all';
        }
    });
    
    // Faire défiler vers le bas automatiquement
    chatBox.scrollTop = chatBox.scrollHeight;
    
    // Gestion de la touche Entrée
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            messageForm.dispatchEvent(new Event('submit'));
        }
    });
    
    // Fonction pour afficher les notifications
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 4000);
    }
});
</script>
</body>
</html>