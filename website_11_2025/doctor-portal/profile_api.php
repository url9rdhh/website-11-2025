<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../config.php';

$doctor_id = (int) $_SESSION['doctor_id'];
$action = $_POST['action'] ?? '';

if ($action === 'update') {
    $fields = [
        'firstname','lastname','cpc','speciality','date_naissance','gender','email','phone',
        'hospital','universite_diplome','annee_diplome','certifications','adresse_cabinet',
        'telephone_cabinet','email_cabinet','jours_consultation','horaires_reception','lat','lng'
    ];

    $set = [];
    $values = [];

    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $set[] = "$f = :$f";
            if (in_array($f, ['jours_consultation','horaires_reception']) && is_array($_POST[$f])) {
                $values[":$f"] = json_encode($_POST[$f], JSON_UNESCAPED_UNICODE);
            } else {
                $values[":$f"] = $_POST[$f];
            }
        }
    }

    if (empty($set)) {
        echo json_encode(['error' => 'No data provided']);
        exit();
    }

    $sql = "UPDATE doctors SET " . implode(', ', $set) . " WHERE id = :id";
    $values[':id'] = $doctor_id;

    try {
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute($values);
        echo json_encode($ok ? ['success' => true] : ['error' => 'Update failed']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }

    exit();
}

echo json_encode(['error' => 'Invalid action']);
