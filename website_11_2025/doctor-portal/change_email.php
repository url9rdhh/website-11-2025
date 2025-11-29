<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include '../config.php';

$raw = json_decode(file_get_contents('php://input'), true);
$newEmail = trim($raw['newEmail'] ?? '');
$password = $raw['password'] ?? '';

if (!$newEmail || !$password) {
    echo json_encode(['error' => 'Champs manquants']);
    exit;
}

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Email invalide']);
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

    if (!password_verify($password, $hash)) {
        echo json_encode(['error' => 'Mot de passe incorrect']);
        exit;
    }

    $check = $conn->prepare("SELECT id FROM doctors WHERE email = :email AND id != :id");
    $check->execute([':email' => $newEmail, ':id' => $doctor_id]);
    if ($check->fetch()) {
        echo json_encode(['error' => 'Cet email est déjà utilisé par un autre compte']);
        exit;
    }

    $update = $conn->prepare("UPDATE doctors SET email = :email WHERE id = :id");
    if ($update->execute([':email' => $newEmail, ':id' => $doctor_id])) {
        echo json_encode(['success' => true, 'message' => 'Email mis à jour avec succès']);
    } else {
        echo json_encode(['error' => 'Impossible de mettre à jour l\'email']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
