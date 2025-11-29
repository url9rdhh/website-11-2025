<?php
session_start();

if (!isset($_SESSION['doctor_id'])) {
    header("Location: auth.html");
    exit();
}

include '../config.php'; 

date_default_timezone_set('Africa/Algiers');

setlocale(LC_TIME, 'fr_FR.UTF-8', 'fra', 'French_France.1252');

$doctor_id = (int) $_SESSION['doctor_id'];
$is_first_login = ($_SESSION['profile_complete'] ?? 0) == 0;

try {
    $stmt = $conn->prepare("
        SELECT firstname, lastname, speciality, gender, email, 
               cpc, phone, hospital, date_naissance, pays, wilaya, commune,
               universite_diplome, annee_diplome, certifications, adresse_cabinet, 
               telephone_cabinet, email_cabinet, jours_consultation, horaires_reception
        FROM doctors
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        die("Erreur: Médecin introuvable.");
    }

} catch (PDOException $e) {
    die("Erreur base de données: " . $e->getMessage());
}

$firstname_safe       = htmlspecialchars($doctor['firstname'] ?? '');
$lastname_safe        = htmlspecialchars($doctor['lastname'] ?? '');
$full_name            = htmlspecialchars($firstname_safe . ' ' . $lastname_safe);
$last_name_only = htmlspecialchars($doctor['lastname']);
$dr_email_safe        = htmlspecialchars($doctor['email'] ?? '');
$specialty_text       = htmlspecialchars($doctor['speciality'] ?? '');
$cpc                  = htmlspecialchars($doctor['cpc'] ?? '');
$speciality           = $specialty_text; 
$hospital             = htmlspecialchars($doctor['hospital'] ?? '');
$phone                = htmlspecialchars($doctor['phone'] ?? '');
$date_naissance       = htmlspecialchars($doctor['date_naissance'] ?? '');
$gender               = htmlspecialchars($doctor['gender'] ?? '');
$pays                 = htmlspecialchars($doctor['pays'] ?? '');
$wilaya_code_selected = htmlspecialchars($doctor['wilaya'] ?? '');
$commune_name_selected= htmlspecialchars($doctor['commune'] ?? '');
$universite_diplome   = htmlspecialchars($doctor['universite_diplome'] ?? '');
$annee_diplome        = htmlspecialchars($doctor['annee_diplome'] ?? '');
$certifications       = htmlspecialchars($doctor['certifications'] ?? '');
$adresse_cabinet      = htmlspecialchars($doctor['adresse_cabinet'] ?? '');
$telephone_cabinet    = htmlspecialchars($doctor['telephone_cabinet'] ?? '');
$email_cabinet        = htmlspecialchars($doctor['email_cabinet'] ?? '');
$horaires_reception   = htmlspecialchars($doctor['horaires_reception'] ?? '');
$jours_selected       = !empty($doctor['jours_consultation']) ? json_decode($doctor['jours_consultation'], true) : [];

$days = ["Sunday"=>"Dimanche","Monday"=>"Lundi","Tuesday"=>"Mardi","Wednesday"=>"Mercredi","Thursday"=>"Jeudi","Friday"=>"Vendredi","Saturday"=>"Samedi"]; 
$months = ["January"=>"Janvier","February"=>"Février","March"=>"Mars","April"=>"Avril","May"=>"Mai","June"=>"Juin","July"=>"Juillet","August"=>"Août","September"=>"Septembre","October"=>"Octobre","November"=>"Novembre","December"=>"Décembre"];
$day = $days[date("l")];
$month = $months[date("F")];
$today_date = $day . " " . date("d") . " " . $month . " " . date("Y");
$wilayas_json = file_get_contents('Wilaya_Of_Algeria.json');
$communes_json = file_get_contents('Commune_Of_Algeria.json');

if ($wilayas_json === false || $communes_json === false) {
    die("Erreur: Impossible de charger les donnees des Wilayas/Communes");
}

$wilayas_data = json_decode($wilayas_json, true);
$communes_data = json_decode($communes_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Erreur: Le décodage des données JSON a échoué. " . json_last_error_msg());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accueil</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Montserrat', sans-serif;
    }
    
    body {
        display: flex;
        height: 100vh;
        background: #f4f6f9;
    }
    
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    
    .main-layout-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100vh;
        overflow: hidden;
    }
    
    #sidebar {
        height: 100vh;
        overflow-y: hidden;
    }
    
    .sidebar {
        height: 100vh;
        width: 260px;
        background: #1f2937;
        color: white;
        padding: 25px 20px;
        border-right: 1px solid #111;
        transition: 0.3s;
        overflow-y: hidden;
    }
    
    .sidebar.collapsed {
        width: 80px;
        padding: 25px 10px;
    }
    
    .profile-big {
        text-align: center;
        margin-bottom: 25px;
        transition: 0.3s;
    }
    
    .profile-big.collapsed h3,
    .profile-big.collapsed p {
        display: none;
    }
    
    .menu {
        margin-top: 20px;
    }
    
    .menu a {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px;
        margin-bottom: 8px;
        border-radius: 8px;
        color: white;
        text-decoration: none;
        font-size: 15px;
        transition: 0.3s;
    }
    
    .menu a i {
        font-size: 18px;
        width: 22px;
        text-align: center;
    }
    
    .sidebar.collapsed .menu a span {
        display: none;
    }
    
    .menu a:hover,
    .menu a.active {
        background: #3498db;
    }
    
    .topbar {
        width: 100%;
        padding: 15px 25px;
        background: white;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }
    
    #toggleMenu {
        font-size: 25px;
        cursor: pointer;
        color: #3498db;
        margin-right: 10px;
    }
    
    .topbar h2 {
        color: #1f2937;
    }
    
    .top-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #1f2937;
    }
    
    .main-area {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .content {
        padding: 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    #main-content-scrollable {
        overflow-y: auto;
        flex-grow: 1;
        padding: 25px;
    }
    
    .content h2 {
        color: #3498db;
        margin-bottom: 5px;
    }
    
    .content p {
        color: #1f2937;
    }
    
    .content h3 {
        color: #1f2937;
        margin-top: 20px;
        margin-bottom: 10px;
    }
    
    .profile-icon {
        border-radius: 50%;
        border: 4px solid #3498db;
        background-color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        transition: 0.3s;
    }
    
    .profile-big .profile-icon {
        width: 100px;
        height: 100px;
    }
    
    .top-profile .profile-icon {
        width: 45px;
        height: 45px;
        border-width: 2px;
    }
    
    .sidebar.collapsed .profile-big .profile-icon {
        width: 50px;
        height: 50px;
        border-width: 2px;
    }
    
    .profile-icon i {
        font-size: 50px;
        color: #3498db;
        line-height: 1;
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
    }
    
    .top-profile .profile-icon i {
        font-size: 24px;
    }
    
    .icon-male i::before {
        content: "\f0f0";
    }
    
    .first-login-box {
        background: white;
        padding: 25px;
        border-radius: 10px;
        width: 100%;
        max-width: 900px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
        margin-top: 20px;
        border-left: 5px solid #3498db;
        display: flex;
        flex-direction: column;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }
    
    button {
        width: 100%;
        padding: 12px;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.2s;
        margin-top: 20px;
    }
    
    button:hover {
        background: #2980b9;
    }
    
    .input-group {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
    }
    
    .form {
        --width-of-input: 100%;
        --border-height: 1px;
        --border-before-color: rgba(31, 41, 55, 0.3);
        --border-after-color: #3498db;
        --input-hovered-color: #3498db1a;
        position: relative;
        width: var(--width-of-input);
    }
    
    .input {
        color: #1f2937;
        font-size: 0.9rem;
        background-color: transparent;
        width: 100%;
        box-sizing: border-box;
        padding-inline: 0.5em;
        padding-block: 0.7em;
        border: none;
        border-bottom: var(--border-height) solid var(--border-before-color);
        padding-left: 30px;
    }
    
    .input, select, textarea {
        color: #1f2937;
        font-size: 0.9rem;
        background-color: transparent;
        width: 100%;
        box-sizing: border-box;
        padding-inline: 0.5em;
        padding-block: 0.7em;
        border: none;
        border-bottom: var(--border-height) solid var(--border-before-color);
        padding-left: 30px;
    }
    
    textarea {
        padding-left: 5px;
        resize: none;
    }
    
    .input-group label {
        font-weight: bold;
        margin-bottom: 5px;
        display: block;
        color: #1f2937;
    }
    
    .input-border {
        position: absolute;
        background: var(--border-after-color);
        width: 0%;
        height: 2px;
        bottom: 0;
        left: 0;
        transition: 0.3s;
    }
    
    .input-icon {
        position: absolute;
        left: 5px;
        bottom: 10px;
        color: var(--border-before-color);
        font-size: 16px;
        pointer-events: none;
    }
    
    .input:focus ~ .input-border {
        width: 100%;
    }
    
    .input:hover,
    .input:focus {
        background: var(--input-hovered-color);
        border-radius: 5px 5px 0 0;
        outline: none;
    }
    
    .input-group.highlighted textarea {
        background-color: #f0f8ff !important;
        border: 1px solid #3498db50;
        padding-left: 10px !important;
        border-radius: 5px;
        transition: background-color 0.3s;
    }
    
    .input-group.highlighted textarea:focus {
        background-color: #e6f7ff !important;
    }
    
    .consult-days-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 10px;
        border: 1px solid var(--border-before-color);
        border-radius: 5px;
        background-color: white;
    }
    
    .consult-days-container label {
        display: block;
        padding: 0;
        margin-bottom: 0;
        cursor: pointer;
        border: none;
        background: transparent;
    }
    
    .consult-days-container label span {
        display: block;
        padding: 8px 15px;
        border-radius: 6px;
        border: 1px solid #ccc;
        background-color: #f9f9f9;
        color: #1f2937;
        transition: background-color 0.2s, border-color 0.2s, color 0.2s;
    }
    
    .consult-days-container input[type="checkbox"]:checked + span {
        background-color: #3498db;
        color: white;
        border-color: #2980b9;
        font-weight: bold;
        box-shadow: 0 2px 5px rgba(52, 152, 219, 0.3);
    }
    
    .consult-days-container input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }
    
    .input.error,
    textarea.error,
    select.error {
        border-bottom: 2px solid red !important;
    }
    
    .input-group .input-icon.error {
        color: red !important;
    }
    
    .error-message {
        color: red;
        font-size: 13px;
        margin-top: 4px;
        display: block;
    }
    
    #msgBox {
        display: none;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-size: 15px;
        font-weight: 600;
        margin-top: 10px;
        text-align: center;
        transition: 0.3s ease;
    }
    
    .hero {
        width: 100%;
        max-width: 1000px;
        margin: 0 auto 18px;
        background: linear-gradient(90deg, #3498db 8%, #2980b9 100%);
        color: #fff;
        padding: 25px 30px;
        border-radius: 12px;
        box-sizing: border-box;
    }
    
    .hero .date {
        opacity: 0.9;
        font-size: 14px;
        margin-bottom: 8px;
    }
    
    .hero .title {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 10px 0;
    }

</style>
</head>

<body>

    <div class="sidebar" id="sidebar">
        <div class="profile-big" id="sidebarProfile">
            <div class="profile-icon">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
            <h3>Dr. <?= $full_name ?></h3>
            <p><?= $specialty_text ?></p>
        </div>

        <div class="menu">
            <a href="accueil.php" class="active"><i class="fa-solid fa-house"></i><span>Accueil</span></a>
            <a href="patients.php"><i class="fa-solid fa-user-injured"></i><span>Mes Patients</span></a>
            <a href="#"><i class="fa-solid fa-calendar-check"></i><span>Rendez-vous</span></a>
            <a href="profile.php"><i class="fa-solid fa-id-card"></i><span>Mon Profil</span></a>
            <a href="settings.php"><i class="fa-solid fa-gear"></i><span>Paramètres</span></a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Déconnexion</span></a>
        </div>
    </div>

    <div class="main-area">

        <div class="topbar">
            <i id="toggleMenu" class="fa-solid fa-bars"></i>
            <h2>Accueil</h2>
            <div class="top-profile">
                <div class="profile-icon">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <span>Dr. <?= $last_name_only ?></span>
            </div>
        </div>

        <div class="content">

            <?php if ($is_first_login): ?>

                <h2>Bienvenue, Dr. <?= $last_name_only ?></h2>
                <p id="cpm">Veuillez compléter votre profil professionnel pour finaliser l'inscription au portail :</p>
                <div class="first-login-box" id="updateContainer" >
                    <form method="POST" id="profileForm" action="update_profile.php" onsubmit="return validateForm()">
                        <div class="form-grid">

                            <div class="input-group" data-input-name="firstname">
                                <label>Prénom</label>
                                <div class="form">
                                    <i class="fa-solid fa-user input-icon"></i>
                                    <input class="input" name="firstname" value="<?= $firstname_safe ?>"   type="text">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="firstname"></span>
                            </div>

                            <div class="input-group" data-input-name="lastname">
                                <label>Nom</label>
                                <div class="form">
                                    <i class="fa-solid fa-user input-icon"></i>
                                    <input class="input" name="lastname" value="<?= $lastname_safe ?>"   type="text">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="lastname"></span>
                            </div>

                            <div class="input-group" data-input-name="cpc">
                                <label>Numéro CPC </label>
                                <div class="form">
                                    <i class="fa-solid fa-id-badge input-icon"></i>
                                    <input class="input" name="cpc" value="<?= htmlspecialchars($cpc ?? '') ?>"   type="text">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="cpc"></span>
                            </div>

                            <div class="input-group" data-input-name="date_naissance">
                                <label>Date de Naissance</label>
                                <div class="form">
                                    <i class="fa-solid fa-cake-candles input-icon"></i>
                                    <input class="input" name="date_naissance" value="<?= htmlspecialchars($date_naissance ?? '') ?>"   type="date">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="date_naissance"></span>
                            </div>

                            <div class="input-group" data-input-name="gender">
                                <label>Genre</label>
                                <div class="form">
                                    <i class="fa-solid fa-venus-mars input-icon"></i>
                                    <select class="input" name="gender"   >
                                        <option value="" <?= empty($gender) ? 'selected' : '' ?> disabled>Sélectionner le genre</option>
                                        <option value="male" <?= ($gender === 'male' ? 'selected' : '') ?>>Homme</option>
                                        <option value="female" <?= ($gender === 'female' ? 'selected' : '') ?>>Femme</option>
                                    </select>
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="gender"></span>
                            </div>

                            <div class="input-group" data-input-name="pays">
                                <label>Pays</label>
                                <div class="form">
                                    <i class="fa-solid fa-globe input-icon"></i>
                                    <input class="input" name="pays" value="<?= htmlspecialchars($pays ?? '') ?>"   type="text">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="pays"></span>
                            </div>

                            <div class="input-group" data-input-name="wilaya_code">
                                <label>Wilaya</label>
                                <div class="form">
                                    <i class="fa-solid fa-map-location-dot input-icon"></i>
                                    <select class="input" name="wilaya_code" id="wilayaSelect"   onchange="populateCommunes(false)">
                                        <option value="" disabled <?= empty($wilaya_code_selected) ? 'selected' : '' ?>>Sélectionner la Wilaya</option>
                                        <?php foreach ($wilayas_data as $wilaya_item): ?>
                                            <option value="<?= htmlspecialchars($wilaya_item['code']) ?>"
                                                <?= ($wilaya_item['code'] == $wilaya_code_selected) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($wilaya_item['code']) ?> - <?= htmlspecialchars($wilaya_item['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="wilaya_code"></span>
                            </div>

                            <div class="input-group" data-input-name="commune_name">
                                <label>Commune</label>
                                <div class="form">
                                    <i class="fa-solid fa-city input-icon"></i>
                                    <select class="input" name="commune_name" id="communeSelect"   >
                                        <option value="" disabled selected>Sélectionner la Wilaya d'abord</option>
                                        </select>
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="commune_name"></span>
                            </div>

                            <div class="input-group" data-input-name="speciality">
                                <label>Spécialité</label>
                                <div class="form">
                                    <i class="fa-solid fa-stethoscope input-icon"></i>
                                    <input class="input" name="speciality" value="<?= htmlspecialchars($speciality ?? '') ?>"   type="text">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="speciality"></span>
                            </div>
                            
                            <div class="input-group" data-input-name="hospital">
                                <label>Hôpital / Clinique d'Affiliation</label>
                                <div class="form">
                                    <i class="fa-solid fa-hospital input-icon"></i>
                                    <input class="input" name="hospital" value="<?= htmlspecialchars($hospital ?? '') ?>"   type="text">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="hospital"></span>
                            </div>
                            
                            <div class="input-group" data-input-name="universite_diplome">
                                <label>Université de Diplôme</label>
                                <div class="form">
                                    <i class="fa-solid fa-graduation-cap input-icon"></i>
                                    <input class="input" name="universite_diplome" value="<?= htmlspecialchars($universite_diplome ?? '') ?>"   type="text">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="universite_diplome"></span>
                            </div>
            
                            <div class="input-group" data-input-name="annee_diplome">
                                <label>Année de Diplôme</label>
                                <div class="form">
                                    <i class="fa-solid fa-calendar-alt input-icon"></i>
                                    <input class="input" name="annee_diplome" id="annee_diplome"  value="<?= htmlspecialchars($annee_diplome ?? '') ?> "  
                                           type="text" placeholder="YYYY" inputmode="numeric"  maxlength="4">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="annee_diplome"></span>
                            </div>
            
                            <div class="input-group highlighted" data-input-name="certifications" style="grid-column: 1 / -1;">
                                <label>Autres Certifications / Diplômes</label>
                                <div class="form">
                                     <textarea name="certifications" rows="3" class="input"><?= htmlspecialchars($certifications ?? '') ?></textarea>
                                    <span class="input-border" style="width: 100%;"></span> 
                                </div>
                                <span class="error-message" data-input-name="certifications"></span>
                            </div>

                            <div class="input-group" data-input-name="phone">
                                <label>Téléphone Personnel</label>
                                <div class="form">
                                    <i class="fa-solid fa-mobile-alt input-icon"></i>
                                    <input class="input" name="phone" id="phone" value="<?= htmlspecialchars($phone ?? '') ?>"  
                                           type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="10">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="phone"></span>
                            </div>
            
                            <div class="input-group" data-input-name="telephone_cabinet">
                                <label>Téléphone Cabinet</label>
                                <div class="form">
                                    <i class="fa-solid fa-phone input-icon"></i>
                                    <input class="input" name="telephone_cabinet" id="telephone_cabinet" value="<?= htmlspecialchars($telephone_cabinet ?? '') ?>"  
                                           type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="10">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="telephone_cabinet"></span>
                            </div>
            
                            <div class="input-group">
                                <label>Email Personnel </label>
                                <div class="form">
                                    <i class="fa-solid fa-at input-icon"></i>
                                    <input class="input" value="<?= $dr_email_safe ?>" type="email">
                                    <span class="input-border"></span>
                                </div>
                            </div>
                            
                            <div class="input-group" data-input-name="email_cabinet">
                                <label>Email du Cabinet</label>
                                <div class="form">
                                    <i class="fa-solid fa-envelope input-icon"></i>
                                    <input class="input" name="email_cabinet" id="email_cabinet" value="<?= htmlspecialchars($email_cabinet ?? '') ?>"   type="email">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="email_cabinet"></span>
                            </div>
                            
                            </div> <div class="input-group" data-input-name="adresse_cabinet">
                                <label>Adresse Complète du Cabinet</label>
                                <div class="form">
                                    <i class="fa-solid fa-map-pin input-icon" style="left: 5px; top: 12px;"></i>
                                    <input class="input" name="adresse_cabinet" value="<?= htmlspecialchars($adresse_cabinet ?? '') ?>"   type="text">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="adresse_cabinet"></span>
                            </div>

                            <div class="input-group" data-input-name="jours_consultation">
                                <label>Jours de Consultation</label>
                                <div class="consult-days-container" id="jours_consultation_container">
                                    <?php
                                    $jours_semaine = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
                                    foreach ($jours_semaine as $jour) {
                                        $checked = in_array($jour, $jours_selected) ? 'checked' : '';
                                        echo '<label>';
                                        echo '<input type="checkbox" name="jours_consultation[]" value="' . $jour . '" ' . $checked . '>';
                                        echo '<span>' . $jour . '</span>';
                                        echo '</label>';
                                    }
                                    ?>
                                </div>
                                <span class="error-message" data-input-name="jours_consultation"></span>
                            </div>
                
                            <div class="input-group" data-input-name="horaires_reception_text">
                                <label>Horaires de Réception</label>
                                <p style="font-size: 0.85rem; margin-top: -02px; margin-bottom: 5px; color: #555;">Exemple: Lun-Ven 9h-12h et 14h-18h ; Sam 9h-12h.</p>
                                <div class="form">
                                    <i class="fa-solid fa-clock input-icon" style="left: 5px; top: 12px;"></i>
                                    <input class="input" name="horaires_reception" value="<?= htmlspecialchars($horaires_reception ?? '') ?>"   type="text">
                                    <span class="input-border"></span>
                                </div>
                                <span class="error-message" data-input-name="horaires_reception_text"></span>
                            </div>


                            <button id="updateBtn" type="submit">Enregistrer les Informations</button>
                        </div>
                    </form>
                    
                    <div id="msgBox" style=" display:none;position:fixed;top:20px;right:20px;background:#10b981;color:white;padding:14px 20px;border-radius:8px;box-shadow:0 3px 10px rgba(0,0,0,0.2);font-size:16px;align-items:center;z-index:9999;"></div>
                </div>

            <?php else: ?>

                <div class="hero">
                    <div class="date"><?= htmlspecialchars($today_date) ?></div>
                    <div class="title">Bienvenue Dr <?= $lastname_safe ?> !</div>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById("toggleMenu");
        const sidebar = document.getElementById("sidebar");
        const profileBox = document.getElementById("sidebarProfile");
        
        toggleBtn.onclick = () => {
            sidebar.classList.toggle("collapsed");
            profileBox.classList.toggle("collapsed");
            toggleBtn.classList.toggle("fa-bars");
            toggleBtn.classList.toggle("fa-chevron-left");
        };

        const isFirstLogin = <?= json_encode($is_first_login) ?>;
        
        if (isFirstLogin) {
        
            const allCommunes = <?= $communes_json ?>;
            
            const initialSelectedWilayaCode = '<?= htmlspecialchars($wilaya_code_selected ?? '') ?>';
            const initialSelectedCommuneName = '<?= htmlspecialchars($commune_name_selected ?? '') ?>';
    
            const wilayaSelect = document.getElementById('wilayaSelect');
            const communeSelect = document.getElementById('communeSelect');
    
            function populateCommunes(initialLoad = false) {
                const selectedWilayaCode = wilayaSelect.value;
                
                communeSelect.innerHTML = '<option value="" disabled selected>Sélectionner la Commune</option>';
                
                if (!selectedWilayaCode) {
                    communeSelect.innerHTML = '<option value="" disabled selected>Sélectionner la Wilaya d\'abord</option>';
                    return;
                }
    
                const filteredCommunes = allCommunes.filter(commune => commune.wilaya_id == selectedWilayaCode); // Use == for comparison as wilaya_id in JSON might be string/int
    
                let foundInitialSelection = false;
    
                filteredCommunes.forEach(commune => {
                    const option = document.createElement('option');
                    option.value = commune.name;
                    option.textContent = commune.name;
                    
                    if (initialLoad && commune.name === initialSelectedCommuneName) {
                        option.selected = true;
                        foundInitialSelection = true;
                    }
    
                    communeSelect.appendChild(option);
                });
    
                if (!initialLoad) {
                    communeSelect.value = ""; 
                    communeSelect.querySelector('option[disabled]').selected = true;
                }
                
                if (filteredCommunes.length === 0) {
                    communeSelect.innerHTML = '<option value="" disabled selected>Aucune Commune trouvée</option>';
                }
            }
    
            window.addEventListener('load', () => {

                if (initialSelectedWilayaCode) {

                    if (wilayaSelect.value !== initialSelectedWilayaCode) {
                        wilayaSelect.value = initialSelectedWilayaCode;
                    }
                    populateCommunes(true); 
                }
            });
        }

        function displayError(inputName, message) {

            const group = document.querySelector(`.input-group[data-input-name="${inputName}"]`);
            const errorSpan = document.querySelector(`.error-message[data-input-name="${inputName}"]`);
            if (group && errorSpan) {
                group.classList.add('has-error');
                errorSpan.textContent = message;
                errorSpan.classList.add('show');
            }
        }
    
        function clearError(inputName) {

            const group = document.querySelector(`.input-group[data-input-name="${inputName}"]`);
            const errorSpan = document.querySelector(`.error-message[data-input-name="${inputName}"]`);
            if (group && errorSpan) {
                group.classList.remove('has-error');
                errorSpan.textContent = '';
                errorSpan.classList.remove('show');
            }
        }

        function handleNumericInput(event, maxLength) {
            let input = event.target;
            let value = input.value;
            
            let numericValue = value.replace(/[^0-9]/g, '');
            
            if (numericValue.length > maxLength) {
                numericValue = numericValue.substring(0, maxLength);
            }
            
            input.value = numericValue;
    
            if (numericValue.length === maxLength) {
                clearError(input.name);
            }
        }

        document.getElementById('annee_diplome')?.addEventListener('input', (e) => {
            handleNumericInput(e, 4);
        });
    
        document.getElementById('phone')?.addEventListener('input', (e) => {
            handleNumericInput(e, 10);
        });
    
        document.getElementById('telephone_cabinet')?.addEventListener('input', (e) => {
            handleNumericInput(e, 10);
        });

        function validateForm() {
            let valid = true;
        
            const fields = [
                "firstname",
                "lastname",
                "cpc",
                "date_naissance",
                "gender",
                "pays",
                "wilaya_code",
                "commune_name",
                "speciality",
                "hospital",
                "universite_diplome",
                "annee_diplome",
                "certifications",
                "phone",
                "telephone_cabinet",
                "email_cabinet",
                "adresse_cabinet",
                "horaires_reception_text",
                "jours_consultation"
            ];
        
            document.querySelectorAll(".input-group").forEach(g => g.classList.remove("has-error"));
            document.querySelectorAll(".error-message").forEach(e => e.textContent = "");
            document.querySelectorAll(".input").forEach(i => i.classList.remove("error"));
            document.querySelectorAll(".input-icon").forEach(ic => ic.classList.remove("error"));
        
            fields.forEach(name => {
                const group = document.querySelector(`.input-group[data-input-name="${name}"]`);
                if (!group) return;
        
                const errorMsg = group.querySelector(".error-message");
        
                if (name === "jours_consultation") {
                    const checked = group.querySelectorAll("input[type='checkbox']:checked");
                    if (checked.length === 0) {
                        errorMsg.textContent = "Veuillez sélectionner au moins un jour.";
                        group.classList.add("has-error");
                        valid = false;
                    }
                    return;
                }
        
                const input = group.querySelector(".input");
                const icon = group.querySelector(".input-icon");
        
                if (!input || input.value.trim() === "") {
                    errorMsg.textContent = "Veuillez remplir ce champ.";
                    group.classList.add("has-error");
                    if (input) input.classList.add("error");
                    if (icon) icon.classList.add("error");
                    valid = false;
                    return;
                }
        
                if (name === "phone" || name === "telephone_cabinet") {
                    const digits = input.value.replace(/\D/g, "");
                    if (digits.length !== 10) {
                        errorMsg.textContent = "Le numéro doit contenir 10 chiffres.";
                        input.classList.add("error");
                        if (icon) icon.classList.add("error");
                        group.classList.add("has-error");
                        valid = false;
                    }
                }
        
                if (name === "email_cabinet") {
                    const email = input.value.trim();
                    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!regex.test(email)) {
                        errorMsg.textContent = "Veuillez entrer un email valide.";
                        input.classList.add("error");
                        if (icon) icon.classList.add("error");
                        group.classList.add("has-error");
                        valid = false;
                    }
                }
        
                if (name === "annee_diplome") {
                    if (!/^\d{4}$/.test(input.value)) {
                        errorMsg.textContent = "L'année doit être au format YYYY.";
                        input.classList.add("error");
                        if (icon) icon.classList.add("error");
                        group.classList.add("has-error");
                        valid = false;
                    }
                }
                });

                if (!valid) {
                    const first = document.querySelector(".input-group.has-error");
                    if (first) {
                        first.scrollIntoView({ behavior: "smooth", block: "center" });
                    }
                    return false; 
                }
            
                return true; 
        }
        document.getElementById("updateBtn").addEventListener("click", function (e) {
            e.preventDefault();
        
            if (!validateForm()) return;
        
            let form = document.getElementById("profileForm");
            let data = new FormData(form);
        
            fetch("update_profile.php", {
                method: "POST",
                body: data
            })
            .then(res => res.text()) 
            .then(txt => {
                console.log("PHP RESPONSE:", txt);
        
                let data;
                try {
                    data = JSON.parse(txt);
                } catch (e) {
                    alert("PHP returned invalid JSON:\n\n" + txt);
                    return;
                }
        
                if (data.status === "success") {
                showMsg("Votre profil a été mis à jour avec succès", "success");
        
                setTimeout(() => {
                    location.reload();
                }, 2000); }
            });
        });

        function showMsg(text, type = "success") {
            const box = document.getElementById("msgBox");
            box.style.background = type === "success" ? "#10b981" : "#dc2626";
        
            const successIconSVG = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                style="margin-right: 10px;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>`;
        
            box.innerHTML = type === "success" ? successIconSVG + text : text;
            box.style.display = "flex";
        
            setTimeout(() => {
                box.style.display = "none";
            }, 3000);
        }


        document.querySelectorAll(".input").forEach(input => {
        
            input.addEventListener("input", () => {
                clearError(input);
            });
        
            input.addEventListener("change", () => {
                clearError(input);
            });
        
        });
        
        function clearError(input) {

            input.classList.remove("error");
        
            const icon = input.parentElement.querySelector(".input-icon");
            if (icon) icon.classList.remove("error");
        
            const errorMsg = input.closest(".input-group").querySelector(".error-message");
            if (errorMsg) errorMsg.textContent = "";
        }
    </script>
</body>
</html>