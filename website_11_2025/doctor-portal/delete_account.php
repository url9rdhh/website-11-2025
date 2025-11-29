<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include '../config.php';

$raw = json_decode(file_get_contents('php://input'), true);
$password = $raw['password'] ?? '';

if (!$password) {
    echo json_encode(['error' => 'Mot de passe requis']);
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

    
    $del = $conn->prepare("DELETE FROM doctors WHERE id = :id");
    if ($del->execute([':id' => $doctor_id])) {
        
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Compte supprimé avec succès']);
    } else {
        echo json_encode(['error' => 'Impossible de supprimer le compte']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}