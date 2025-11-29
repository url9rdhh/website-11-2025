<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include '../config.php';

$raw = json_decode(file_get_contents('php://input'), true);
$old = $raw['oldPassword'] ?? '';
$new = $raw['newPassword'] ?? '';

if (!$old || !$new) {
    echo json_encode(['error' => 'Champs manquants']);
    exit;
}

$doctor_id = (int) $_SESSION['doctor_id'];

try {
    
    $stmt = $conn->prepare("SELECT password FROM doctors WHERE id = :id");
    $stmt->execute([':id' => $doctor_id]);
    $hash = $stmt->fetchColumn();

    if (!$hash) {
        echo json_encode(['error' => 'Utilisateur introuvable']);
        exit;
    }

    
    if (!password_verify($old, $hash)) {
        echo json_encode(['error' => 'Mot de passe actuel incorrect']);
        exit;
    }

    
    if (strlen($new) < 8) {
        echo json_encode(['error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères']);
        exit;
    }

    
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE doctors SET password = :password WHERE id = :id");
    if ($update->execute([':password' => $newHash, ':id' => $doctor_id])) {
        echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour avec succès']);
    } else {
        echo json_encode(['error' => 'Impossible de mettre à jour le mot de passe']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
