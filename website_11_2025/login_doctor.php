<?php
session_start();

include "config.php"; 

$cpc      = trim($_POST['cpc'] ?? '');
$password = $_POST['password'] ?? '';

if ($cpc === '' || $password === '') {
    echo "error";
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id, password, profile_complete FROM doctors WHERE cpc = ?");
    $stmt->execute([$cpc]);

    if ($stmt->rowCount() === 0) {
        echo "no_cpc";
        exit();
    }

    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        echo "error";
        exit();
    }

    if (password_verify($password, $doctor['password'])) {
        $_SESSION['doctor_id'] = $doctor['id'];
        $_SESSION['profile_complete'] = $doctor['profile_complete'];
        echo "success";
    } else {
        echo "wrong_password";
    }

} catch (Exception $e) {
    echo "error";
}
?>
