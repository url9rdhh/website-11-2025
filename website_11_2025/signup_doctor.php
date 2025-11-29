<?php
include "config.php";

ini_set('display_errors', 0);
error_reporting(E_ALL);

function send_response($status) {
    echo $status;
    exit();
}

$required = [
    'cpc','lastname','firstname','speciality','hospital','email','phone','password'
];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        send_response("error");
    }
}

$cpc        = trim($_POST['cpc']);
$lastname   = trim($_POST['lastname']);
$firstname  = trim($_POST['firstname']);
$speciality = trim($_POST['speciality']);
$hospital   = trim($_POST['hospital']);
$email      = trim($_POST['email']);
$phone      = trim($_POST['phone']);
$password   = password_hash($_POST['password'], PASSWORD_DEFAULT);

try {

    $stmt = $conn->prepare("SELECT id FROM doctors WHERE cpc = ?");
    $stmt->execute([$cpc]);

    if ($stmt->rowCount() > 0) {
        send_response("cpc_exists");
    }

    $stmt = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        send_response("email_exists");
    }

    $stmt = $conn->prepare("
        INSERT INTO doctors (cpc, lastname, firstname, speciality, hospital, email, phone, password)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $cpc, $lastname, $firstname, $speciality, $hospital, $email, $phone, $password
    ]);

    send_response("success");

} catch (Exception $e) {
    send_response("error");
}
?>
