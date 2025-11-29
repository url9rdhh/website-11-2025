<?php
header("Content-Type: text/plain");

include "config.php";

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT password FROM patients WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if (!$row) {
        echo "no_email";
        exit();
    }

    if (password_verify($password, $row['password'])) {
        echo "success";
    } else {
        echo "wrong_password";
    }

} catch (Exception $e) {
    echo "error";
}
?>
