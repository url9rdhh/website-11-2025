<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

include "../config.php";

$doctor_id = $_SESSION['doctor_id'] ?? null;
if (!$doctor_id) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit();
}

$data = $_POST;

$firstname = $data['firstname'] ?? null;
$lastname = $data['lastname'] ?? null;
$cpc = $data['cpc'] ?? null;
$date_naissance = $data['date_naissance'] ?? null;
$gender = $data['gender'] ?? null;
$pays = $data['pays'] ?? null;
$wilaya_code = (int)($data['wilaya_code'] ?? 0);
$commune_name = $data['commune_name'] ?? null;
$speciality = $data['speciality'] ?? null;
$hospital = $data['hospital'] ?? null;
$universite_diplome = $data['universite_diplome'] ?? null;
$annee_diplome = $data['annee_diplome'] ?? null;
$certifications = $data['certifications'] ?? null;
$phone = $data['phone'] ?? null;
$telephone_cabinet = $data['telephone_cabinet'] ?? null;
$email_cabinet = $data['email_cabinet'] ?? null;
$adresse_cabinet = $data['adresse_cabinet'] ?? null;

$wilayas = [
    1 => "Adrar", 2 => "Chlef", 3 => "Laghouat", 4 => "Oum El Bouaghi",
    5 => "Batna", 6 => "Béjaïa", 7 => "Biskra", 8 => "Béchar",
    9 => "Blida", 10 => "Bouira", 11 => "Tamanrasset", 12 => "Tébessa",
    13 => "Tlemcen", 14 => "Tiaret", 15 => "Tizi Ouzou", 16 => "Alger",
    17 => "Djelfa", 18 => "Jijel", 19 => "Sétif", 20 => "Saïda",
    21 => "Skikda", 22 => "Sidi Bel Abbès", 23 => "Annaba", 24 => "Guelma",
    25 => "Constantine", 26 => "Médéa", 27 => "Mostaganem", 28 => "M'Sila",
    29 => "Mascara", 30 => "Ouargla", 31 => "Oran", 32 => "El Bayadh",
    33 => "Illizi", 34 => "Bordj Bou Arreridj", 35 => "Boumerdès", 36 => "El Tarf",
    37 => "Tindouf", 38 => "Tissemsilt", 39 => "El Oued", 40 => "Khenchela",
    41 => "Souk Ahras", 42 => "Tipaza", 43 => "Mila", 44 => "Aïn Defla",
    45 => "Naâma", 46 => "Aïn Témouchent", 47 => "Ghardaïa", 48 => "Relizane",
    49 => "Timimoun", 50 => "Bordj Badji Mokhtar", 51 => "Ouled Djellal",
    52 => "Béni Abbès", 53 => "In Salah", 54 => "In Guezzam", 55 => "Touggourt",
    56 => "Djanet", 57 => "El M'Ghair", 58 => "El Meniaa"
];
$wilaya_name = $wilayas[$wilaya_code] ?? "Unknown";

$age_val = null;
if (!empty($date_naissance)) {
    try {
        $birthDate = new DateTime($date_naissance);
        $today = new DateTime();
        $age_val = $today->diff($birthDate)->y;
    } catch (Exception $e) {
        $age_val = null;
    }
}

$horaires_reception = $data['horaires_reception'] ?? [];
if (!is_array($horaires_reception)) {
    $horaires_reception = [$horaires_reception];
}
$horaires_reception_str = json_encode($horaires_reception, JSON_UNESCAPED_UNICODE);

$jours_consultation = $data['jours_consultation'] ?? [];
if (!is_array($jours_consultation)) {
    $jours_consultation = [$jours_consultation];
}
$jours_consultation_str = json_encode($jours_consultation, JSON_UNESCAPED_UNICODE);

try {
    $sql = "UPDATE doctors SET 
        firstname=:firstname,
        lastname=:lastname,
        cpc=:cpc,
        date_naissance=:date_naissance,
        gender=:gender,
        pays=:pays,
        wilaya=:wilaya,
        commune=:commune,
        speciality=:speciality,
        hospital=:hospital,
        universite_diplome=:universite_diplome,
        annee_diplome=:annee_diplome,
        certifications=:certifications,
        phone=:phone,
        telephone_cabinet=:telephone_cabinet,
        email_cabinet=:email_cabinet,
        adresse_cabinet=:adresse_cabinet,
        horaires_reception=:horaires_reception,
        jours_consultation=:jours_consultation,
        age=:age,
        profile_complete=1
        WHERE id=:id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':firstname' => $firstname,
        ':lastname' => $lastname,
        ':cpc' => $cpc,
        ':date_naissance' => $date_naissance,
        ':gender' => $gender,
        ':pays' => $pays,
        ':wilaya' => $wilaya_name,
        ':commune' => $commune_name,
        ':speciality' => $speciality,
        ':hospital' => $hospital,
        ':universite_diplome' => $universite_diplome,
        ':annee_diplome' => $annee_diplome,
        ':certifications' => $certifications,
        ':phone' => $phone,
        ':telephone_cabinet' => $telephone_cabinet,
        ':email_cabinet' => $email_cabinet,
        ':adresse_cabinet' => $adresse_cabinet,
        ':horaires_reception' => $horaires_reception_str,
        ':jours_consultation' => $jours_consultation_str,
        ':age' => $age_val,
        ':id' => $doctor_id
    ]);

    $_SESSION['profile_complete'] = 1;

    echo json_encode(["status" => "success", "message" => "Profile updated"]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
