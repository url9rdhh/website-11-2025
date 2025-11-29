<?php
session_start();

if (!isset($_SESSION['doctor_id'])) {
    header("Location: auth.html");
    exit();
}

include '../config.php';

$doctor_id = (int) $_SESSION['doctor_id'];

try {
    $stmt = $conn->prepare("
        SELECT id, cpc, firstname, lastname, speciality, date_naissance, age, hospital, email, phone, gender, 
               pays, wilaya, commune, universite_diplome, annee_diplome, certifications, adresse_cabinet, 
               telephone_cabinet, email_cabinet, jours_consultation, horaires_reception, lat, lng
        FROM doctors 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        die("Doctor not found");
    }

    $full_name = htmlspecialchars(trim($doctor['firstname'] . ' ' . $doctor['lastname']));
    $specialty_text = htmlspecialchars($doctor['speciality'] ?? '');
    $last_name_only = htmlspecialchars($doctor['lastname'] ?? '');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profil du Docteur</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>

    :root {
        --primary: #1A73E8;
        --muted: #6b7280;
        --danger: #EA4335;
        --soft: #F4F8FF;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Montserrat', sans-serif;
    }
    
    body {
        display: flex;
        min-height: 100vh;
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
        gap: 25px;
        width: 100%;
        max-width: 100%;
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
        width: 60px;
        height: 60px;
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
    
    .profile-card {
        width: calc(50% - 20px);
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,.1);
        margin-bottom: 10px;
        transition: all .3s;
    }
    
    @media(max-width: 768px) {
        .profile-card {
            width: 100%;
        }
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #eee;
        margin-bottom: 12px;
        padding-bottom: 6px;
    }
    
    .card-header h3 {
        color: #3498db;
    }
    
    .edit-btn {
        background: var(--primary);
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        font-weight: 600;
        transition: background .2s, transform .2s;
    }
    
    .edit-btn:hover {
        background: #0f5cc8;
        transform: scale(1.05);
    }
    
    .edit-btn svg {
        width: 16px;
        height: 16px;
        fill: white;
    }
    
    .card-body p {
        margin: 8px 0;
        font-size: 14px;
    }
    
    .profile-card p {
        color: #1f2937;
        margin-bottom: 6px;
    }
    
    .btn {
        padding: 8px 15px;
        border-radius: 8px;
        cursor: pointer;
        border: none;
        font-weight: 600;
    }
    
    .btn.primary {
        background: var(--primary);
        color: white;
    }
    
    .profile-section {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: flex-start;
    }
    
    input {
        padding-left: 30px !important;
    }
    
    select.input {
        padding-left: 30px !important;
    }
    
    .input-group {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
    }
    
    .form {
        --width-of-input: 100%;
        --border-height: 1px;
        --border-before-color: rgba(31,41,55,0.3);
        --border-after-color: #3498db;
        --input-hovered-color: #3498db1a;
        position: relative;
        width: var(--width-of-input);
    }
    
    .input,
    .input:focus,
    .input:hover {
        color: #1f2937;
        font-size: 0.9rem;
        background-color: transparent;
        width: 100%;
        box-sizing: border-box;
        padding-inline: 0.5em;
        padding-block: 0.7em;
        border: none;
        border-bottom: var(--border-height) solid var(--border-before-color);
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
    
    .input-icon {
        position: absolute;
        left: 5px;
        bottom: 10px;
        color: var(--border-before-color);
        font-size: 16px;
        pointer-events: none;
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
    
    textarea.input {
        padding-left: 5px !important;
        resize: none;
    }
    
    .input-group label {
        font-weight: bold;
        margin-bottom: 5px;
        display: block;
        color: #1f2937;
    }
    
    .input-group.highlighted textarea {
        background-color: #f0f8ff !important;
        border: 1px solid #3498db50 !important;
        padding-left: 10px !important;
        border-radius: 5px !important;
        border-bottom: 1px solid #3498db50 !important;
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
        box-shadow: 0 2px 5px rgba(52,152,219,0.3);
    }
    
    .consult-days-container input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }
    
    .error-message {
        color: red;
        font-size: 13px;
        margin-top: 4px;
        display: block;
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
                <a href="accueil.php" ><i class="fa-solid fa-house"></i><span>Accueil</span></a>
                <a href="patients.php"><i class="fa-solid fa-user-injured"></i><span>Mes Patients</span></a>
                <a href="#"><i class="fa-solid fa-calendar-check"></i><span>Rendez-vous</span></a>
                <a href="profile.php" class="active"><i class="fa-solid fa-id-card"></i><span>Mon Profil</span></a>
                <a href="settings.php"><i class="fa-solid fa-gear"></i><span>Paramètres</span></a>
                <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Déconnexion</span></a>
            </div>
        </div>
    
        <div class="main-area">
            <div class="topbar">
                <i id="toggleMenu" class="fa-solid fa-bars"></i>
                <h2>Mon Profile</h2>
                <div class="top-profile">
                    <div class="profile-icon">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <span>Dr. <?= $last_name_only ?></span>
                </div>
            </div>
            <div class="content">
                <div class="profile-section">
    
                <div class="profile-card">
                    <div class="card-header">
                        <h3>Informations Personnelles</h3>
                        <button class="edit-btn" onclick="enableEdit('personal')">
                            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM21.41 6.34a1.25 1.25 0 0 0 0-1.77l-2.98-2.98a1.25 1.25 0 0 0-1.77 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            Modifier
                        </button>
                    </div>
                    <div class="card-body" id="personal">
                        <p><strong>Prénom:</strong> <span><?= htmlspecialchars($doctor['firstname']) ?></span></p>
                        <p><strong>Nom:</strong> <span><?= htmlspecialchars($doctor['lastname']) ?></span></p>
                        <p><strong>CPC:</strong> <span><?= htmlspecialchars($doctor['cpc']) ?></span></p>
                        <p><strong>Spécialité:</strong> <span><?= htmlspecialchars($doctor['speciality']) ?></span></p>
                        <p><strong>Date de naissance:</strong> <span><?= htmlspecialchars($doctor['date_naissance']) ?></span></p>
                        <p><strong>Âge:</strong> <span><?= htmlspecialchars($doctor['age']) ?></span></p>
                        <p><strong>Genre:</strong> <span><?= htmlspecialchars($doctor['gender']) ?></span></p>
                        <p><strong>Email:</strong> <span><?= htmlspecialchars($doctor['email']) ?></span></p>
                        <p><strong>Téléphone:</strong> <span><?= htmlspecialchars($doctor['phone']) ?></span></p>
                        <p><strong>Hôpital:</strong> <span><?= htmlspecialchars($doctor['hospital']) ?></span></p>
                    </div>
                </div>
    
                <div class="profile-card">
                    <div class="card-header">
                        <h3>Diplômes & Certifications</h3>
                        <button class="edit-btn" onclick="enableEdit('education')">
                            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM21.41 6.34a1.25 1.25 0 0 0 0-1.77l-2.98-2.98a1.25 1.25 0 0 0-1.77 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                            </svg>
                            Modifier
                        </button>
                    </div>
                    <div class="card-body" id="education">
                        <p><strong>Université:</strong> <span><?= htmlspecialchars($doctor['universite_diplome']) ?></span></p>
                        <p><strong>Année diplôme:</strong> <span><?= htmlspecialchars($doctor['annee_diplome']) ?></span></p>
                        <p><strong>Certifications:</strong> <span><?= htmlspecialchars($doctor['certifications']) ?></span></p>
                    </div>
                </div>
    
                <div class="profile-card">
                    <div class="card-header">
                    <h3>Adresse </h3>
                    <button class="edit-btn" onclick="enableEdit('address')">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM21.41 6.34a1.25 1.25 0 0 0 0-1.77l-2.98-2.98a1.25 1.25 0 0 0-1.77 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                        </svg>
                        Modifier
                    </button>
                </div>
                <div class="card-body" id="address">
                    <p><strong>Pays:</strong> <span><?= htmlspecialchars($doctor['pays']) ?></span></p>
                    <p><strong>Wilaya:</strong> <span><?= htmlspecialchars($doctor['wilaya']) ?></span></p>
                    <p><strong>Commune:</strong> <span><?= htmlspecialchars($doctor['commune']) ?></span></p>
                    <p><strong>Adresse Cabinet:</strong> <span><?= htmlspecialchars($doctor['adresse_cabinet']) ?></span></p>
                </div>
            </div>
    
            <div class="profile-card">
                <div class="card-header">
                    <h3>Consultation</h3>
                    <button class="edit-btn" onclick="enableEdit('consultation')">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM21.41 6.34a1.25 1.25 0 0 0 0-1.77l-2.98-2.98a1.25 1.25 0 0 0-1.77 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                        </svg>
                        Modifier
                    </button>
                </div>
                <div class="card-body" id="consultation">
                    <p><strong>Téléphone Cabinet:</strong> <span><?= htmlspecialchars($doctor['telephone_cabinet']) ?></span></p>
                    <p><strong>Email Cabinet:</strong> <span><?= htmlspecialchars($doctor['email_cabinet']) ?></span></p>
                    <p><strong>Jours Consultation:</strong>
                        <span >
                         <?php 
                             $jours = json_decode($doctor['jours_consultation'] ?? '[]', true);
                             echo implode(', ', $jours); 
                         ?>
                        </span>
                    </p>    
                    <p><strong>Horaires Réception:</strong> 
                        <span>
                          <?= htmlspecialchars(
                              is_array(json_decode($doctor['horaires_reception'], true)) 
                                  ? implode(', ', json_decode($doctor['horaires_reception'], true)) 
                                  : $doctor['horaires_reception']
                          ) ?>
                        </span>
                    </p>
                </div>
            </div></div>
        </div>
    <div id="msgBox" style="display: none; position: fixed; top: 20px; right: 20px; padding: 15px; border-radius: 8px;color: white; align-items: center; font-weight: bold; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);z-index: 9999;"></div>

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
        
        
        
        function showMsg(text, type = "success") {
            const box = document.getElementById("msgBox");
            if (!box) {
                alert(text);
                return;
            }
            
            box.style.background = type === "success" ? "#10b981" : "#dc2626";
            box.style.color = "white"; 
            box.style.padding = "15px"; 
            box.style.borderRadius = "8px"; 
            box.style.boxShadow = "0 4px 12px rgba(0, 0, 0, 0.2)";
            box.style.zIndex = "9999"; 
        
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
        
        const fieldIcons = {
            "Prénom": "fa-user", "Nom": "fa-user", "CPC": "fa-id-badge", "Date de naissance": "fa-cake-candles", 
            "Genre": "fa-venus-mars", "Pays": "fa-globe", "Wilaya": "fa-map-location-dot", "Commune": "fa-city", 
            "Spécialité": "fa-stethoscope", "Hôpital": "fa-hospital", "Université": "fa-graduation-cap", 
            "Année diplôme": "fa-calendar-alt", "Certifications": null, "Téléphone": "fa-mobile-alt", 
            "Téléphone Cabinet": "fa-phone", "Email Cabinet": "fa-envelope", "Adresse Cabinet": "fa-map-pin", 
            "Horaires Réception": "fa-clock", "Jours Consultation": null,
        };
        
        const inputRestrictions = {
            "Année diplôme": { type: "text", name: "annee_diplome", inputmode: "numeric", maxlength: "4", pattern: "[0-9]*", placeholder: "YYYY" },
            "Téléphone": { type: "tel", name: "phone", inputmode: "numeric", pattern: "[0-9]{10}", maxlength: "10" },
            "Téléphone Cabinet": { type: "tel", name: "telephone_cabinet", inputmode: "numeric", pattern: "[0-9]{10}", maxlength: "10" },
            "Date de naissance": { type: "date", name: "date_naissance" },
            "Email Cabinet": { type: "email", name: "email_cabinet" },
        };
        
        function safeParse(value) {
            if (typeof value !== 'string') return value;
            
            let parsed = value;
            try {

                for(let i = 0; i < 5; i++){
                    if(typeof parsed === 'string'){
                        let trimmed = parsed.trim();
                        if ((trimmed.startsWith('[') && trimmed.endsWith(']')) || (trimmed.startsWith('"') && trimmed.endsWith('"'))) {
                            parsed = JSON.parse(trimmed);
                        } else {
                            break; 
                        }
                    } else {
                        break;
                    }
                }
            } catch (e) {
                return value; 
            }
            return parsed;
        }

        function enableEdit(sectionId) {
            const section = document.getElementById(sectionId);
            const card = section.closest(".profile-card");
        
            section.dataset.originalHtml = section.innerHTML;
        
            const editBtn = card.querySelector(".edit-btn");
            editBtn.style.display = "none";
        
            let actionDiv = card.querySelector(".card-header .action-buttons");
            if (!actionDiv) {
                actionDiv = document.createElement("div");
                actionDiv.className = "action-buttons";
                actionDiv.style.marginTop = "15px";
                actionDiv.innerHTML = `
                    <button class="btn" style="background:#1A9E55;color:white;margin-right:10px;" onclick="saveEdit('${sectionId}')">Enregistrer</button>
                    <button class="btn" style="background:#b0b0b0;color:white;" onclick="cancelEdit('${sectionId}')">Annuler</button>
                `;
                card.querySelector(".card-header").appendChild(actionDiv);
            }
            actionDiv.style.display = "block";
        
            section.innerHTML = ''; 
        
            const doctorData = <?= json_encode($doctor) ?>;
        
            const fieldsToEdit = {
                'personal': [
                    { label: "Prénom", dbField: "firstname" }, { label: "Nom", dbField: "lastname" },
                    { label: "CPC", dbField: "cpc" }, { label: "Spécialité", dbField: "speciality" },
                    { label: "Date de naissance", dbField: "date_naissance" },
                    { label: "Genre", dbField: "gender", isSelect: true, options: { 'male': 'Homme', 'female': 'Femme' } }, 
                    { label: "Téléphone", dbField: "phone" }, { label: "Hôpital", dbField: "hospital" },
                ],
                'education': [
                    { label: "Université", dbField: "universite_diplome" },
                    { label: "Année diplôme", dbField: "annee_diplome" },
                    { label: "Certifications", dbField: "certifications", isTextarea: true },
                ],
                'address': [
                    { label: "Pays", dbField: "pays" }, { label: "Wilaya", dbField: "wilaya" },
                    { label: "Commune", dbField: "commune" }, { label: "Adresse Cabinet", dbField: "adresse_cabinet" },
                ],
                'consultation': [
                    { label: "Téléphone Cabinet", dbField: "telephone_cabinet" },
                    { label: "Email Cabinet", dbField: "email_cabinet" },
                    { label: "Jours Consultation", dbField: "jours_consultation", isCheckboxGroup: true },
                    { label: "Horaires Réception", dbField: "horaires_reception" },
                ]
            };

            const currentFields = fieldsToEdit[sectionId];
            if (!currentFields) return;

            currentFields.forEach(field => {
            let value = doctorData[field.dbField] || "";
            const iconClass = fieldIcons[field.label];
            const restrictions = inputRestrictions[field.label] || {};
            
            let inputHtml = '';
            let inputTagName = 'input';
            let customClass = '';
            let isSelectElement = false;
    
            let parsedValue = safeParse(value);
    
            if (field.isTextarea) {
                inputTagName = 'textarea';
                customClass = 'highlighted';
                inputHtml = `<textarea name="${field.dbField}" rows="3" class="input">${value}</textarea>`;
            } else if (field.isSelect) {
                isSelectElement = true;
                inputTagName = 'select';
                let optionsHtml = '<option value="" disabled>Sélectionner...</option>';
                for (const optValue in field.options) {
                    const selected = (optValue === value) ? 'selected' : '';
                    optionsHtml += `<option value="${optValue}" ${selected}>${field.options[optValue]}</option>`;
                }
                inputHtml = `<select name="${field.dbField}" class="input">${optionsHtml}</select>`;
            } else if (field.isCheckboxGroup) {
                const jours_semaine = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
                const jours_selected = Array.isArray(parsedValue) ? parsedValue : [];
                
                let checkboxHtml = '<div class="consult-days-container">';
                jours_semaine.forEach(jour => {
                    const checked = jours_selected.includes(jour) ? 'checked' : '';
                    checkboxHtml += `
                        <label>
                            <input type="checkbox" name="jours_consultation[]" value="${jour}" ${checked}>
                            <span>${jour}</span>
                        </label>
                    `;
                });
                checkboxHtml += '</div>';
                inputHtml = checkboxHtml;
            } else {
                let inputValue = (field.dbField === 'horaires_reception' && Array.isArray(parsedValue)) ? parsedValue.join(', ') : value;
                
                let attributes = Object.keys(restrictions).map(key => `${key}="${restrictions[key]}"`).join(' ');
                attributes += ` name="${field.dbField}"`;
                const readonlyAttr = field.readonly ? 'readonly' : '';
               
    
                inputHtml = `
                    <div class="form">
                        ${iconClass ? `<i class="fa-solid ${iconClass} input-icon"></i>` : ''}
                        <input class="input" value="${inputValue}" ${attributes} ${readonlyAttr}>
                        <span class="input-border" ${field.dbField === 'adresse_cabinet' ? 'style="width: 100%;"' : ''}></span>
                    </div>
                `;
            }

            const inputGroupStyle = (field.dbField === 'certifications' || field.dbField === 'adresse_cabinet' || field.isCheckboxGroup) ? 'grid-column: 1 / -1;' : '';
            const newGroup = document.createElement("div");
            newGroup.className = `input-group ${customClass}`;
            newGroup.setAttribute('data-input-name', field.dbField);
            newGroup.style = inputGroupStyle;
    
            let finalInputDisplay = inputHtml;
    
            if (isSelectElement || field.isTextarea) {
                finalInputDisplay = `
                    <div class="form">
                        ${iconClass && isSelectElement ? `<i class="fa-solid ${iconClass} input-icon"></i>` : ''}
                        ${inputHtml}
                        <span class="input-border" ${field.isTextarea ? 'style="width: 100%;"' : ''}></span>
                    </div>
                `;
            }
            
            let displayValue = value;
            if (field.dbField === 'jours_consultation' || field.dbField === 'horaires_reception') {
                const parsedArray = Array.isArray(parsedValue) ? parsedValue : [];
                displayValue = parsedArray.join(', ');
            }
    
    
            newGroup.innerHTML = `
                <label>${field.label}</label>
                ${finalInputDisplay}
                ${field.dbField === 'jours_consultation' ? '' : `<span class="error-message" data-input-name="${field.dbField}"></span>`}
            `;
            
            section.appendChild(newGroup);
    
            if (field.isTextarea || field.isSelect) {
                 newGroup.querySelector(inputTagName).value = value;
            }
            });
        }

        function cancelEdit(sectionId){
            const section = document.getElementById(sectionId);
            section.innerHTML = section.dataset.originalHtml;
        
            const card = section.closest(".profile-card");
            const editBtn = card.querySelector(".edit-btn");
            if(editBtn) editBtn.style.display = "inline-flex";
        
            const actionDiv = card.querySelector(".card-header .action-buttons");
            if(actionDiv) actionDiv.style.display = "none";
        }

        async function saveEdit(sectionId){
            const section = document.getElementById(sectionId);
            const inputs = section.querySelectorAll("input, select, textarea");
        
            const fieldMap = {
                "Prénom": "firstname", "Nom": "lastname", "CPC": "cpc", "Date de naissance": "date_naissance", "Genre": "gender",
                "Pays": "pays", "Wilaya": "wilaya", "Commune": "commune", "Spécialité": "speciality", "Hôpital": "hospital",
                "Université": "universite_diplome", "Année diplôme": "annee_diplome", "Certifications": "certifications",
                "Téléphone": "phone", "Téléphone Cabinet": "telephone_cabinet", "Email Cabinet": "email_cabinet",
                "Adresse Cabinet": "adresse_cabinet", "Horaires Réception": "horaires_reception", "Jours Consultation": "jours_consultation"
            };
        
            const formData = new FormData();
            formData.append("action", "update");
            formData.append("section", sectionId);
        
            let valid = true;
        
            inputs.forEach(input => {
                const inputGroup = input.closest(".input-group");
                if (!inputGroup) return; 
        
                const label = inputGroup.querySelector("label")?.innerText.trim();
                const dbField = fieldMap[label];
                if (!dbField) return; 
        
                let value;
                if(input.type === "checkbox" && dbField === "jours_consultation"){
                    return;
                } else {
                    value = input.value.trim();
                }
        
                let errorMessage = "";
                
                if(value === "" && input.name !== "certifications"){ 
                    errorMessage = "Ce champ est obligatoire";
                }
        
                if((dbField === "phone" || dbField === "telephone_cabinet") && value !== ""){
                    if (!/^\d{10}$/.test(value)) {
                        errorMessage = "Numéro non valide (doit contenir exactement 10 chiffres)";
                    }
                }
        
                if(input.type === "email" && value !== "" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)){
                    errorMessage = "Email non valide";
                }
        
                if(dbField === "annee_diplome" && value !== ""){
                    if (!/^\d{4}$/.test(value)){
                        errorMessage = "Année invalide (format YYYY, 4 chiffres requis)";
                    } else if (parseInt(value) > new Date().getFullYear()){
                            errorMessage = "L'année ne peut pas être dans le futur";
                    }
                }
        
                const errorSpan = inputGroup.querySelector(".error-message");
                if(errorMessage !== ""){
                    if(errorSpan) errorSpan.innerText = errorMessage;
                    valid = false;
                } else {
                    if(errorSpan) errorSpan.innerText = "";
                }
                
                
                if (dbField === 'horaires_reception' && value !== "") {
                    const horairesArray = value.split(',').map(h => h.trim()).filter(h => h.length > 0);
                    formData.append(dbField, JSON.stringify(horairesArray));
                } else if (dbField) {
                     formData.append(dbField, value);
                }
            });
            
            const joursGroup = section.querySelector(".consult-days-container");
            if(joursGroup){
                const checkedBoxes = joursGroup.querySelectorAll("input[type='checkbox']:checked");
                const jours_value = Array.from(checkedBoxes).map(cb => cb.value);
                
                formData.append("jours_consultation", JSON.stringify(jours_value));
            }
        
        
            if(!valid) {
                showMsg("Veuillez corriger les erreurs dans le formulaire.", 'error'); 
                return;
            }
        
            try {
                const res = await fetch("profile_api.php", {
                    method: "POST",
                    body: formData
                });
        
                const data = await res.json();
        
                if(data.success){
                    showMsg("Changements enregistrés avec succès !", 'success'); 
                    
                    setTimeout(() => {
                        location.reload(); 
                    }, 1000); 
                } else {
                    showMsg("Erreur lors de l'enregistrement : " + (data.error || "inconnu"), 'error'); 
                }
        
            } catch(err){
                console.error(err);
                showMsg("Erreur de connexion au serveur.", 'error'); 
            }
        }
    </script>
</body>
</html>
