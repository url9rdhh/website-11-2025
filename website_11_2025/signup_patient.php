<?php
header("Content-Type: text/plain");

include "config.php";

$lastname  = trim($_POST['lastname'] ?? '');
$firstname = trim($_POST['firstname'] ?? '');
$birthdate = trim($_POST['birthdate'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

try {

    $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        echo "email_exists";
        exit();
    }

    $stmt = $pdo->prepare("
        INSERT INTO patients (lastname, firstname, birthdate, email, password)
        VALUES (?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([$lastname, $firstname, $birthdate, $email, $password])) {
        echo "success";
    } else {
        echo "error";
    }

} catch (Exception $e) {
    echo "error";
}
?>
