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
    $current_email = htmlspecialchars($doctor['email'] ?? '');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Paramètres </title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root {
      --primary:#3498db;
      --danger:#dc2626;
      --muted:#6b7280;
      --bg:#f4f6f9;
  }
  
  * {
      margin:0;
      padding:0;
      box-sizing:border-box;
      font-family:'Montserrat', sans-serif;
  }
  
  body {
      display:flex;
      height:100vh;
      background:#f4f6f9;
  }
  
  html, body {
      height:100%;
      margin:0;
      padding:0;
  }
  
  .main-layout-wrapper {
      flex:1;
      display:flex;
      flex-direction:column;
      height:100vh;
      overflow:hidden;
  }
  
  #sidebar {
      height:100vh;
      overflow-y:hidden;
  }
  
  .sidebar {
      height:100vh;
      width:260px;
      background:#1f2937;
      color:white;
      padding:25px 20px;
      border-right:1px solid #111;
      transition:0.3s;
      overflow-y:hidden;
  }
  
  .sidebar.collapsed {
      width:80px;
      padding:25px 10px;
  }
  
  .profile-big {
      text-align:center;
      margin-bottom:25px;
      transition:0.3s;
  }
  
  .profile-big.collapsed h3,
  .profile-big.collapsed p {
      display:none;
  }
  
  .menu {
      margin-top:20px;
  }
  
  .menu a {
      display:flex;
      align-items:center;
      gap:15px;
      padding:12px;
      margin-bottom:8px;
      border-radius:8px;
      color:white;
      text-decoration:none;
      font-size:15px;
      transition:0.3s;
  }
  
  .menu a i {
      font-size:18px;
      width:22px;
      text-align:center;
  }
  
  .sidebar.collapsed .menu a span {
      display:none;
  }
  
  .menu a:hover,
  .menu a.active {
      background:#3498db;
  }
  
  .topbar {
      width:100%;
      padding:15px 25px;
      background:white;
      border-bottom:1px solid #ddd;
      display:flex;
      justify-content:space-between;
      align-items:center;
      position:relative;
  }
  
  #toggleMenu {
      font-size:25px;
      cursor:pointer;
      color:#3498db;
      margin-right:10px;
  }
  
  .topbar h2 {
      color:#1f2937;
  }
  
  .top-profile {
      display:flex;
      align-items:center;
      gap:10px;
      color:#1f2937;
  }
  
  .main-area {
      flex:1;
      display:flex;
      flex-direction:column;
  }
  
  .content {
      padding:25px;
      flex-grow:1;
      display:flex;
      flex-direction:column;
      align-items:center;
  }
  
  #main-content-scrollable {
      overflow-y:auto;
      flex-grow:1;
      padding:25px;
  }
  
  .content h2 {
      color:#3498db;
      margin-bottom:5px;
  }
  
  .content p {
      color:#1f2937;
  }
  
  .content h3 {
      color:#1f2937;
      margin-top:20px;
      margin-bottom:10px;
  }
  
  .profile-icon {
      border-radius:50%;
      border:4px solid #3498db;
      background-color:white;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      overflow:hidden;
      transition:0.3s;
  }
  
  .profile-big .profile-icon {
      width:100px;
      height:100px;
  }
  
  .top-profile .profile-icon {
      width:45px;
      height:45px;
      border-width:2px;
  }
  
  .sidebar.collapsed .profile-big .profile-icon {
      width:50px;
      height:50px;
      border-width:2px;
  }
  
  .profile-icon i {
      font-size:50px;
      color:#3498db;
      line-height:1;
      font-family:'Font Awesome 6 Free';
      font-weight:900;
  }
  
  .top-profile .profile-icon i {
      font-size:24px;
  }
  
  .icon-male i::before {
      content:"\f0f0";
  }
  
  .card {
      background:#fff;
      border-radius:10px;
      padding:18px;
      margin-bottom:16px;
      box-shadow:0 6px 18px rgba(0,0,0,0.06);
  }
  
  .card h3 {
      margin:0 0 12px 0;
      color:var(--primary);
  }
  
  .field {
      flex:1;
      min-width:220px;
      margin-bottom:10px;
  }
  
  small.muted {
      display:block;
      color:var(--muted);
      margin-top:6px;
  }
  
  .btn {
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 14px;
      border-radius:8px;
      border:0;
      background:var(--primary);
      color:#fff;
      cursor:pointer;
  }
  
  .danger {
      background:var(--danger);
      color:#fff;
  }
  
  .row-actions {
      display:flex;
      gap:10px;
      align-items:center;
  }
  
  .msg {
      padding:10px;
      border-radius:8px;
      margin-bottom:10px;
      display:none;
  }
  
  .msg.success {
      background:#e6ffef;
      color:#0b9b57;
  }
  
  .msg.error {
      background:#ffe8e8;
      color:#b00020;
  }
  
  .note {
      font-size:13px;
      color:var(--muted);
  }
  
  .confirm-delete {
      display:flex;
      gap:10px;
      align-items:center;
  }
  
  .first-login-box {
      background:white;
      padding:25px;
      border-radius:10px;
      width:100%;
      max-width:900px;
      box-shadow:0 3px 10px rgba(0,0,0,0.15);
      margin-top:20px;
      border-left:5px solid #3498db;
      display:flex;
      flex-direction:column;
  }
  
  .form-grid {
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
      gap:20px;
      margin-top:15px;
  }
  
  .input-group {
      margin-bottom:15px;
      display:flex;
      flex-direction:column;
  }
  
  .form {
      --width-of-input:100%;
      --border-height:1px;
      --border-before-color:rgba(31,41,55,0.3);
      --border-after-color:#3498db;
      --input-hovered-color:#3498db1a;
      position:relative;
      width:var(--width-of-input);
  }
  
  .input {
      color:#1f2937;
      font-size:0.9rem;
      background-color:transparent;
      width:100%;
      box-sizing:border-box;
      padding-inline:0.5em;
      padding-block:0.7em;
      border:none;
      border-bottom:var(--border-height) solid var(--border-before-color);
      padding-left:30px;
  }
  
  .input,
  select,
  textarea {
      color:#1f2937;
      font-size:0.9rem;
      background-color:transparent;
      width:100%;
      box-sizing:border-box;
      padding-inline:0.5em;
      padding-block:0.7em;
      border:none;
      border-bottom:var(--border-height) solid var(--border-before-color);
      padding-left:30px;
  }
  
  textarea {
      padding-left:5px;
      resize:none;
  }
  
  .input-group label {
      font-weight:bold;
      margin-bottom:5px;
      margin-top:5px;
      display:block;
      color:#1f2937;
  }
  
  .input-border {
      position:absolute;
      background:var(--border-after-color);
      width:0%;
      height:2px;
      bottom:0;
      left:0;
      transition:0.3s;
  }
  
  .input-icon {
      position:absolute;
      left:5px;
      bottom:10px;
      color:var(--border-before-color);
      font-size:16px;
      pointer-events:none;
  }
  
  .input:focus ~ .input-border {
      width:100%;
  }
  
  .input:hover,
  .input:focus {
      background:var(--input-hovered-color);
      border-radius:5px 5px 0 0;
      outline:none;
  }
  
  .input-group.highlighted textarea {
      background-color:#f0f8ff !important;
      border:1px solid #3498db50;
      padding-left:10px !important;
      border-radius:5px;
      transition:background-color 0.3s;
  }
  
  .input-group.highlighted textarea:focus {
      background-color:#e6f7ff !important;
  }
  
  .modal {
      display:none;
      position:fixed;
      top:0;
      left:0;
      width:100%;
      height:100%;
      background:rgba(0,0,0,0.5);
      justify-content:center;
      align-items:center;
      z-index:9999;
  }
  
  .modal-content {
      background:#fff;
      padding:25px;
      border-radius:10px;
      max-width:400px;
      width:90%;
      text-align:center;
      box-shadow:0 5px 20px rgba(0,0,0,0.3);
  }
  
  .modal-content h4 {
      margin-bottom:15px;
      color:var(--danger);
  }
  
  .modal-content p {
      margin-bottom:20px;
      color:#333;
      font-size:0.95rem;
  }
  
  .modal-actions {
      display:flex;
      justify-content:center;
      gap:15px;
  }
  
  .password-toggle {
      position:absolute;
      right:10px;
      bottom:10px;
      cursor:pointer;
      color:var(--muted);
      font-size:16px;
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
        <a href="profile.php"><i class="fa-solid fa-id-card"></i><span>Mon Profil</span></a>
        <a href="settings.php" class="active"><i class="fa-solid fa-gear"></i><span>Paramètres</span></a>
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Déconnexion</span></a>
    </div>
  </div>

  <div class="main-area">
    <div class="topbar">
      <i id="toggleMenu" class="fa-solid fa-bars"></i>
      <h2>Paramètres</h2>
      <div class="top-profile">
        <div class="profile-icon">
          <i class="fa-solid fa-user-doctor"></i>
        </div>
        <span>Dr. <?= $last_name_only ?></span>
      </div>
    </div>

    <div class="content">
      <div class="container">
  
        <div class="first-login-box card" id="passwordCard">
          <h3>Changer le mot de passe</h3>
        <div id="pwMsg" class="msg"></div>
        <div class="form-grid">

          <div class="input-group" data-input-name="oldPassword">
            <label>Mot de passe actuel</label>
            <div class="form">
              <i class="fa-solid fa-lock input-icon"></i>
              <input id="oldPassword" class="input" type="password" autocomplete="current-password">
              <i class="fa-solid fa-eye password-toggle" data-target="oldPassword" title="Afficher / Masquer"></i>
              <span class="input-border"></span>
            </div>
            <span class="error-message" data-input-name="oldPassword"></span>
          </div>

          <div class="input-group" data-input-name="newPassword">
            <label>Nouveau mot de passe</label>
            <div class="form">
              <i class="fa-solid fa-lock input-icon"></i>
              <input id="newPassword" class="input" type="password" autocomplete="new-password">
              <i class="fa-solid fa-eye password-toggle" data-target="newPassword" title="Afficher / Masquer"></i>
              <span class="input-border"></span>
            </div>
            <small class="muted">Minimum 8 caractères, inclure lettre et chiffre de préférence.</small>
            <span class="error-message" data-input-name="newPassword"></span>
          </div>

          <div class="input-group" data-input-name="confirmPassword">
            <label>Confirmer le mot de passe</label>
            <div class="form">
              <i class="fa-solid fa-lock input-icon"></i>
              <input id="confirmPassword" class="input" type="password" autocomplete="new-password">
              <i class="fa-solid fa-eye password-toggle" data-target="confirmPassword" title="Afficher / Masquer"></i>
              <span class="input-border"></span>
            </div>
            <span class="error-message" data-input-name="confirmPassword"></span>
          </div>
        </div>

        <div style="margin-top:12px">
          <button id="changePwBtn" class="btn">Enregistrer le mot de passe</button>
        </div></div>


        <div class="first-login-box card" id="emailCard">
          <h3>Changer l'email</h3>
          <div id="emailMsg" class="msg"></div>
          <div class="form-grid">

            <div class="input-group" data-input-name="currentEmail">
              <label>Email actuel</label>
              <div class="form">
                <i class="fa-solid fa-envelope input-icon"></i>
                <input id="currentEmail" class="input" value="<?= $current_email ?>" readonly>
                <span class="input-border"></span>
              </div>
            </div>

            <div class="input-group" data-input-name="newEmail">
              <label>Nouvel email</label>
              <div class="form">
                <i class="fa-solid fa-envelope input-icon"></i>
                <input id="newEmail" class="input">
                <span class="input-border"></span>
              </div>
              <small class="muted">L'email sera modifié uniquement si aucune autre compte n'utilise cet email.</small>
            </div>

            <div class="input-group" data-input-name="emailPassword">
              <label>Mot de passe (confirmation)</label>
              <div class="form">
                <i class="fa-solid fa-lock input-icon"></i>
                <input id="emailPassword" class="input" type="password" placeholder="Tapez votre mot de passe">
                <i class="fa-solid fa-eye password-toggle" data-target="emailPassword" title="Afficher / Masquer"></i>
                <span class="input-border"></span>
              </div>
            </div>
          </div>

          <div style="margin-top:12px">
            <button id="changeEmailBtn" class="btn">Mettre à jour l'email</button>
          </div>
        </div>


        <div class="first-login-box card" id="deleteCard">
          <h3>Supprimer le compte</h3>
          <div id="delMsg" class="msg"></div>
          <p class="note">La suppression est irréversible. Toutes les données associées à ce compte seront supprimées.</p>

          <div class="input-group" data-input-name="delPassword">
            <label>Confirmer avec le mot de passe</label>
            <div class="form">
              <i class="fa-solid fa-lock input-icon"></i>
              <input id="delPassword" class="input" type="password" placeholder="Mot de passe">
              <i class="fa-solid fa-eye password-toggle" data-target="delPassword" title="Afficher / Masquer"></i>
              <span class="input-border"></span>
            </div>
            <span class="error-message" data-input-name="delPassword"></span>
          </div>

          <div style="margin-top:12px" class="row-actions">
            <button id="deleteBtn" class="btn danger">Supprimer définitivement le compte</button>
          </div>
        </div>

       <div id="deleteModal" class="modal">
        <div class="modal-content">
          <h4>Confirmer la suppression</h4>
          <p>Confirmez-vous la suppression définitive de votre compte ? Cette action est irréversible.</p>
          <div class="modal-actions">
            <button id="confirmDeleteBtn" class="btn danger">Supprimer</button>
            <button id="cancelDeleteBtn" class="btn secondary">Annuler</button>
          </div>
        </div>
      </div>
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

    document.querySelectorAll('.password-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        if (!input) return;
    
        if (input.type === 'password') {
          input.type = 'text';
          btn.classList.remove('fa-eye');
          btn.classList.add('fa-eye-slash');
        } else {
          input.type = 'password';
          btn.classList.remove('fa-eye-slash');
          btn.classList.add('fa-eye'); 
        }
      });
    });

    function showMsg(elId, text, type='success'){
      const el = document.getElementById(elId);
      el.textContent = text;
      el.className = 'msg ' + (type === 'success' ? 'success' : 'error');
      el.style.display = 'block';
      setTimeout(()=> el.style.display = 'none', 5000);
    }

    function postJson(url, data){
      return fetch(url, {
        method:'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
      }).then(r => r.json());
    }
    
    document.getElementById('changePwBtn').addEventListener('click', async ()=>{
      const oldP = document.getElementById('oldPassword').value.trim();
      const newP = document.getElementById('newPassword').value.trim();
      const conf = document.getElementById('confirmPassword').value.trim();
    
      if(!oldP || !newP || !conf) { showMsg('pwMsg','Tous les champs sont requis','error'); return; }
      if(newP.length < 8){ showMsg('pwMsg','Le mot de passe doit contenir au moins 8 caractères','error'); return; }
      if(newP !== conf){ showMsg('pwMsg','Les mots de passe ne correspondent pas','error'); return; }
    
      document.getElementById('changePwBtn').disabled = true;
      try {
        const res = await postJson('change_password.php', { oldPassword: oldP, newPassword: newP });
        if(res.success) {
          showMsg('pwMsg', res.message || 'Mot de passe mis à jour', 'success');
          document.getElementById('oldPassword').value = '';
          document.getElementById('newPassword').value = '';
          document.getElementById('confirmPassword').value = '';
        } else {
          showMsg('pwMsg', res.error || 'Erreur', 'error');
        }
      } catch(e){
        showMsg('pwMsg','Erreur de connexion','error');
      } finally { document.getElementById('changePwBtn').disabled = false; }
    });
    
    document.getElementById('changeEmailBtn').addEventListener('click', async ()=>{
      const newEmail = document.getElementById('newEmail').value.trim();
      const pwd = document.getElementById('emailPassword').value.trim();
      if(!newEmail || !pwd) { showMsg('emailMsg','Remplissez email et mot de passe','error'); return; }
      if(!/^\S+@\S+\.\S+$/.test(newEmail)){ showMsg('emailMsg','Email non valide','error'); return; }
    
      document.getElementById('changeEmailBtn').disabled = true;
      try {
        const res = await postJson('change_email.php', { newEmail, password: pwd });
        if(res.success){
          showMsg('emailMsg', res.message || 'Email mis à jour', 'success');
          document.getElementById('currentEmail').value = newEmail;
          document.getElementById('newEmail').value = '';
          document.getElementById('emailPassword').value = '';
        } else {
          showMsg('emailMsg', res.error || 'Erreur', 'error');
        }
      } catch(e){
        showMsg('emailMsg','Erreur de connexion','error');
      } finally { document.getElementById('changeEmailBtn').disabled = false; }
    });
    
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    
    deleteBtn.addEventListener('click', ()=>{
      const pwd = document.getElementById('delPassword').value.trim();
      if(!pwd){
        showMsg('delMsg','Tapez votre mot de passe pour confirmer','error');
        return;
      }
      deleteModal.style.display = 'flex';
    });
    
    cancelDeleteBtn.addEventListener('click', ()=>{
      deleteModal.style.display = 'none';
    });
    
    confirmDeleteBtn.addEventListener('click', async ()=>{
      const pwd = document.getElementById('delPassword').value.trim();
      if(!pwd) return; 
    
      confirmDeleteBtn.disabled = true;
      try {
        const res = await postJson('delete_account.php', { password: pwd });
        if(res.success){
          showMsg('delMsg', res.message || 'Compte supprimé', 'success');
          setTimeout(()=> { window.location.href = '../index.html'; }, 1200);
        } else {
          showMsg('delMsg', res.error || 'Erreur', 'error');
        }
      } catch(e){
        showMsg('delMsg','Erreur de connexion','error');
      } finally {
        confirmDeleteBtn.disabled = false;
        deleteModal.style.display = 'none';
      }
    });
    
    window.addEventListener('click', e=>{
      if(e.target === deleteModal){
        deleteModal.style.display = 'none';
      }
    });

  </script>
</body>
</html>
