<?php
/**
 * Messagerie interne (Conseiller)
 */
require_once '../config.php';

requireRole(['conseiller', 'admin']);

$pageTitle = 'Messagerie';
$userId = $_SESSION['user_id'];
$message = '';

// Envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = '<div class="alert alert-error">Erreur de sécurité.</div>';
    } else {
        try {
            $receiverId = $_POST['receiver_id'];
            $subject = trim($_POST['subject']);
            $messageText = trim($_POST['message']);
            
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $receiverId, $subject, $messageText]);
            
            $message = '<div class="alert alert-success">Message envoyé avec succès.</div>';
        } catch (PDOException $e) {
            error_log("Erreur envoi message: " . $e->getMessage());
            $message = '<div class="alert alert-error">Erreur lors de l\'envoi.</div>';
        }
    }
}

// Marquer comme lu
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE id = ? AND receiver_id = ?");
        $stmt->execute([$_GET['mark_read'], $userId]);
    } catch (PDOException $e) {
        error_log("Erreur mark read: " . $e->getMessage());
    }
}

// Récupération des messages
try {
    $tab = $_GET['tab'] ?? 'received';
    
    if ($tab === 'received') {
        $stmt = $pdo->prepare("
            SELECT m.*, u.name as sender_name 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = ?
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT m.*, u.name as receiver_name 
            FROM messages m
            JOIN users u ON m.receiver_id = u.id
            WHERE m.sender_id = ?
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId]);
    }
    
    $messages = $stmt->fetchAll();
    
    // Destinataires possibles
    $stmtUsers = $pdo->prepare("SELECT id, name FROM users WHERE id != ? AND status = 'actif'");
    $stmtUsers->execute([$userId]);
    $users = $stmtUsers->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur messages: " . $e->getMessage());
    $messages = [];
    $users = [];
}

include '../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Messagerie</h2>
        
        <?php echo $message; ?>
        
        <div class="dashboard-actions">
            <button class="btn btn-primary" onclick="showComposeModal()">✉️ Nouveau message</button>
        </div>
        
        <div class="message-tabs">
            <a href="?tab=received" class="tab-link <?php echo $tab === 'received' ? 'active' : ''; ?>">
                📥 Messages reçus
            </a>
            <a href="?tab=sent" class="tab-link <?php echo $tab === 'sent' ? 'active' : ''; ?>">
                📤 Messages envoyés
            </a>
        </div>
        
        <?php if (count($messages) > 0): ?>
            <div class="messages-list">
                <?php foreach ($messages as $msg): ?>
                    <div class="message-item <?php echo !$msg['is_read'] && $tab === 'received' ? 'unread' : ''; ?>">
                        <div class="message-header">
                            <strong>
                                <?php 
                                if ($tab === 'received') {
                                    echo 'De : ' . clean($msg['sender_name']);
                                } else {
                                    echo 'À : ' . clean($msg['receiver_name']);
                                }
                                ?>
                            </strong>
                            <span class="message-date"><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></span>
                        </div>
                        <div class="message-subject">
                            <?php echo clean($msg['subject']); ?>
                            <?php if (!$msg['is_read'] && $tab === 'received'): ?>
                                <span class="badge" style="background:#f59e0b;">Nouveau</span>
                            <?php endif; ?>
                        </div>
                        <div class="message-body">
                            <?php echo nl2br(clean($msg['message'])); ?>
                        </div>
                        <?php if (!$msg['is_read'] && $tab === 'received'): ?>
                            <a href="?tab=received&mark_read=<?php echo $msg['id']; ?>" class="btn btn-sm">Marquer comme lu</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-data">Aucun message.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Modal Composer un message -->
<div id="composeModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
    <div style="background:white; max-width:600px; margin:50px auto; padding:30px; border-radius:12px;">
        <h3>Nouveau Message</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="send">
            
            <div class="form-group">
                <label>Destinataire</label>
                <select name="receiver_id" class="form-control" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo clean($user['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Objet</label>
                <input type="text" name="subject" class="form-control" required maxlength="200">
            </div>
            
            <div class="form-group">
                <label>Message</label>
                <textarea name="message" class="form-control" rows="6" required></textarea>
            </div>
            
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-primary">📨 Envoyer</button>
                <button type="button" class="btn btn-secondary" onclick="closeComposeModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<style>
.message-tabs {
    display: flex;
    gap: 10px;
    margin: 20px 0;
    border-bottom: 2px solid var(--border-color);
}

.tab-link {
    padding: 10px 20px;
    text-decoration: none;
    color: var(--gray-text);
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.tab-link.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
    font-weight: 600;
}

.messages-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.message-item {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow);
    border-left: 4px solid transparent;
}

.message-item.unread {
    border-left-color: var(--primary-color);
    background: #f0f9ff;
}

.message-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.message-date {
    color: var(--gray-text);
    font-size: 0.9rem;
}

.message-subject {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: var(--primary-color);
}

.message-body {
    color: var(--dark-text);
    line-height: 1.6;
    margin-bottom: 15px;
}
</style>

<script>
function showComposeModal() {
    document.getElementById('composeModal').style.display = 'block';
}

function closeComposeModal() {
    document.getElementById('composeModal').style.display = 'none';
}
</script>

<?php include '../footer.php'; ?>