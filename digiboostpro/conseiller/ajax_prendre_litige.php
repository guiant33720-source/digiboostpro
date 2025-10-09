<?php
/**
 * AJAX - Prise en charge d'un litige par un conseiller
 */
require_once '../config.php';
requireLogin('conseiller');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$litige_id = $data['litige_id'] ?? null;

if (!$litige_id) {
    echo json_encode(['success' => false, 'message' => 'ID litige manquant']);
    exit;
}

try {
    // Récupération de l'ID du conseiller
    $stmt = $pdo->prepare("SELECT id FROM conseillers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $conseiller = $stmt->fetch();
    
    if (!$conseiller) {
        echo json_encode(['success' => false, 'message' => 'Conseiller non trouvé']);
exit;
}
// Mise à jour du litige
$stmt = $pdo->prepare("
    UPDATE litiges 
    SET conseiller_id = ?, statut = 'en_cours', updated_at = NOW() 
    WHERE id = ? AND (conseiller_id IS NULL OR conseiller_id = ?)
");
$stmt->execute([$conseiller['id'], $litige_id, $conseiller['id']]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Litige pris en charge']);
} else {
    echo json_encode(['success' => false, 'message' => 'Litige déjà attribué']);
}
} catch (PDOException $e) {
echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>