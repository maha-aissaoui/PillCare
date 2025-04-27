<?php
// Démarrer la session
session_start();

// Configuration de la base de données
require_once('connexion.php');

// Vérifier si l'utilisateur est connecté
$loggedIn = isset($_SESSION['user_id']);

// Récupérer les informations de l'utilisateur si connecté
$userData = [];
$todayMedications = [];
$upcomingAppointments = [];
$completedMedications = 0;
$pendingMedications = 0;
$nextAppointmentDays = 0;

if ($loggedIn) {
    // Récupérer les données de l'utilisateur
    $userId = $_SESSION['user_id'];
    $userQuery = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $userQuery->bindParam(':id', $userId, PDO::PARAM_INT);
    $userQuery->execute();
    $userData = $userQuery->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer la date actuelle
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');
    
// Récupérer les médicaments pour aujourd'hui
$medicationQuery = $conn->prepare("
    SELECT m.*, h.heure, 
    CASE WHEN h.heure <= :current_time THEN 1 ELSE 0 END as is_completed
    FROM medicaments m
    JOIN medicament_horaires h ON m.id = h.medicament_id
    WHERE m.user_id = :user_id 
    AND h.actif = 1
    AND :current_date BETWEEN m.start_date AND m.end_date
    ORDER BY h.heure ASC
");
$medicationQuery->bindParam(':current_time', $currentTime, PDO::PARAM_STR);
$medicationQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
$medicationQuery->bindParam(':current_date', $currentDate, PDO::PARAM_STR);
$medicationQuery->execute();
$todayMedications = $medicationQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter les médicaments complétés et en attente
    foreach ($todayMedications as $med) {
        if ($med['is_completed']) {
            $completedMedications++;
        } else {
            $pendingMedications++;
        }
    }
    
    // Récupérer les rendez-vous à venir
    $appointmentQuery = $conn->prepare("
        SELECT a.*, d.name as doctor_name, d.specialty, l.name as location_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.user_id = :user_id AND a.date >= :current_date
        ORDER BY a.date ASC, a.time ASC
        LIMIT 5
    ");
    $appointmentQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $appointmentQuery->bindParam(':current_date', $currentDate, PDO::PARAM_STR);
    $appointmentQuery->execute();
    $upcomingAppointments = $appointmentQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer les jours jusqu'au prochain rendez-vous
    if (count($upcomingAppointments) > 0) {
        $nextAppointmentDate = new DateTime($upcomingAppointments[0]['date']);
        $today = new DateTime($currentDate);
        $interval = $today->diff($nextAppointmentDate);
        $nextAppointmentDays = $interval->days;
    }

    // Récupérer les statistiques de suivi de la mémoire
    $memoryStatsQuery = $conn->prepare("
        SELECT 
            ROUND(AVG(score), 0) as current_score,
            ROUND((AVG(score) - (
                SELECT AVG(score) FROM memory_tracking 
                WHERE user_id = :user_id1 AND date BETWEEN DATE_SUB(:current_date1, INTERVAL 14 DAY) AND DATE_SUB(:current_date2, INTERVAL 7 DAY)
            )) / (
                SELECT AVG(score) FROM memory_tracking 
                WHERE user_id = :user_id2 AND date BETWEEN DATE_SUB(:current_date3, INTERVAL 14 DAY) AND DATE_SUB(:current_date4, INTERVAL 7 DAY)
            ) * 100, 0) as progress_percent
        FROM memory_tracking
        WHERE user_id = :user_id3 AND date BETWEEN DATE_SUB(:current_date5, INTERVAL 7 DAY) AND :current_date6
    ");
    $memoryStatsQuery->bindParam(':user_id1', $userId, PDO::PARAM_INT);
    $memoryStatsQuery->bindParam(':current_date1', $currentDate, PDO::PARAM_STR);
    $memoryStatsQuery->bindParam(':current_date2', $currentDate, PDO::PARAM_STR);
    $memoryStatsQuery->bindParam(':user_id2', $userId, PDO::PARAM_INT);
    $memoryStatsQuery->bindParam(':current_date3', $currentDate, PDO::PARAM_STR);
    $memoryStatsQuery->bindParam(':current_date4', $currentDate, PDO::PARAM_STR);
    $memoryStatsQuery->bindParam(':user_id3', $userId, PDO::PARAM_INT);
    $memoryStatsQuery->bindParam(':current_date5', $currentDate, PDO::PARAM_STR);
    $memoryStatsQuery->bindParam(':current_date6', $currentDate, PDO::PARAM_STR);
    $memoryStatsQuery->execute();
    $memoryStats = $memoryStatsQuery->fetch(PDO::FETCH_ASSOC);
}

// Déterminer quelle vue afficher en fonction du paramètre d'action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Si l'utilisateur n'est pas connecté et ne tente pas de s'inscrire/se connecter, rediriger vers la page de connexion
if (!$loggedIn && !in_array($action, ['login', 'register'])) {
    $action = 'login';
}

// Variables pour les formulaires de connexion et d'inscription
$login_error = "";
$signup_error = "";
$signup_success = "";
$saved_email = isset($_COOKIE['user_email']) ? $_COOKIE['user_email'] : "";
$name = "";
$email = "";

// Traiter le formulaire de connexion si soumis
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['login-email']);
    $password = $_POST['login-password'];
    
    // Validation simple
    if (empty($email) || empty($password)) {
        $login_error = "Veuillez remplir tous les champs";
    } else {
        // Vérification avec la base de données
        $loginQuery = $conn->prepare("SELECT id, email, password FROM users WHERE email = :email");
        $loginQuery->bindParam(':email', $email, PDO::PARAM_STR);
        $loginQuery->execute();
        
        if ($loginQuery->rowCount() == 1) {
            $user = $loginQuery->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                
                // Gérer "Se souvenir de moi"
                if (isset($_POST['remember-me'])) {
                    setcookie("user_email", $email, time() + (86400 * 30), "/");
                }
                
                // Rediriger vers la page d'accueil sans paramètre action
                header("Location: index.php");
                exit;
            } else {
                $login_error = "Email ou mot de passe incorrect";
            }
        } else {
            $login_error = "Email ou mot de passe incorrect";
        }
    }
}

// Traiter le formulaire d'inscription si soumis
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $name = trim($_POST['signup-name']);
    $email = trim($_POST['signup-email']);
    $password = $_POST['signup-password'];
    $confirm_password = $_POST['signup-confirm-password'];
    
    // Validation simple
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $signup_error = "Veuillez remplir tous les champs";
    } elseif ($password != $confirm_password) {
        $signup_error = "Les mots de passe ne correspondent pas";
    } else {
        // Vérifier si l'email existe déjà
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $checkEmail->bindParam(':email', $email, PDO::PARAM_STR);
        $checkEmail->execute();
        
        if ($checkEmail->rowCount() > 0) {
            $signup_error = "Cet email est déjà utilisé";
        } else {
            // Hacher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
           // Extraire prénom et nom
            $name_parts = explode(" ", $name, 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : "";

            // Combine into fullname
            $fullname = trim($name); // Using the original name or reconstructing it: trim($first_name . " " . $last_name)

            // Insérer le nouvel utilisateur
            $insertUser = $conn->prepare("INSERT INTO users (fullname, email, password, created_at) VALUES (:fullname, :email, :password, NOW())");
            $insertUser->bindParam(':fullname', $fullname, PDO::PARAM_STR);
            $insertUser->bindParam(':email', $email, PDO::PARAM_STR);
            $insertUser->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            
            if ($insertUser->execute()) {
                $signup_success = "Inscription réussie! Vous pouvez maintenant vous connecter.";
                // Rediriger vers la page de connexion après 2 secondes
                header("refresh:2;url=index.php?action=login");
                // Réinitialiser les champs du formulaire après inscription réussie
                $name = $email = "";
            } else {
                $signup_error = "Erreur lors de l'inscription: " . implode(", ", $insertUser->errorInfo());
            }
        }
    }
}

/* Récupérer les conseils et ressources
$resourcesQuery = $conn->query("SELECT * FROM resources ORDER BY RAND() LIMIT 4");
$resources = $resourcesQuery->fetchAll(PDO::FETCH_ASSOC);*/ 

// Obtenir la date en français
setlocale(LC_TIME, 'fr_FR.utf8', 'fra');
$frenchDate = utf8_encode(strftime('%A %d %B %Y', strtotime($currentDate ?? date('Y-m-d'))));

// Inclure les vues appropriées selon l'action
switch ($action) {
    case 'register':
        include('register_form.php');
        break;
    case 'login':
        include('login_form.php');
        break;
    default:
        // Afficher le tableau de bord pour les utilisateurs connectés
        if ($loggedIn) {
            // Nous incluons directement le HTML du tableau de bord ici
            // ou vous pouvez utiliser include('dashboard_view.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PillCare - Tableau de Bord</title>
    <link rel="stylesheet" href="home.css" />
    <link rel="stylesheet" href="header.css" />
    <link rel="stylesheet" href="footer.css" />
</head>
<body>
    <header class="header">
        <div class="header-bg"></div>
        <div class="wave-container"></div>
        <div class="header-content">
            <div class="logo-container">
                <img src="logo.png" alt="PillCare Logo" class="logo-img" />
            </div>
            <nav class="top-nav">
                <a href="index.php" class="nav-item active" data-page="home">Accueil</a>
                <a href="medicament.php" class="nav-item" data-page="Medicament">Medicaments</a>
                <a href="calendrier.php" class="nav-item" data-page="calendar">Calendrier</a>
                <a href="ordonnance.php" class="nav-item" data-page="ordonnance">Ordonnance</a>
                <a href="profile.php" class="nav-item" data-page="profile">Profil</a>
            </nav>
            <div class="user-menu">
                <button id="user-btn"><i class="fas fa-user-circle"></i></button>
                <div class="dropdown-menu">
                    <a href="settings.php"><i class="fas fa-cog"></i> Paramètres</a>
                    <a href="#" class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i> Thème</a>
                    <a href="logout.php" id="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
            <button class="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>
    
    <div class="container">
        <div class="welcome-message">
        <h1>Bonjour, <?php echo htmlspecialchars(explode(' ', $userData['fullname'])[0]); ?> !</h1>            <p>Nous sommes le <?php echo $frenchDate; ?>. Voici votre tableau de bord quotidien.</p>
            <div class="quick-actions">
                <button class="action-btn" onclick="window.location.href='medicament.php?action=add'">
                    <i class="fas fa-plus"></i> Ajouter un médicament
                </button>
                <button class="action-btn" onclick="window.location.href='calendrier.php?action=add'">
                    <i class="fas fa-calendar-plus"></i> Nouveau rendez-vous
                </button>
            </div>
        </div>

        <div class="quick-stats">
            <div class="stat-card">
                <h2><i class="fas fa-pills"></i> Médicaments aujourd'hui</h2>
                <div class="stat-value"><?php echo $completedMedications; ?>/<?php echo ($completedMedications + $pendingMedications); ?></div>
                <p><?php echo $pendingMedications; ?> médicament<?php echo $pendingMedications > 1 ? 's' : ''; ?> restant<?php echo $pendingMedications > 1 ? 's' : ''; ?> à prendre aujourd'hui</p>
            </div>
            <div class="stat-card">
                <h2><i class="fas fa-calendar"></i> Rendez-vous</h2>
                <div class="stat-value"><?php echo count($upcomingAppointments) > 0 ? '1' : '0'; ?></div>
                <p><?php echo count($upcomingAppointments) > 0 ? "Prochain rendez-vous dans $nextAppointmentDays jour" . ($nextAppointmentDays > 1 ? 's' : '') : "Aucun rendez-vous à venir"; ?></p>
            </div>
        </div>

        <div class="dashboard-card">
            <h2><i class="fas fa-calendar"></i> Rendez-vous à venir</h2>
            <div class="appointment-list">
                <?php if (count($upcomingAppointments) > 0): ?>
                    <?php foreach ($upcomingAppointments as $apt): ?>
                        <div class="appointment-item">
                            <div class="appointment-info">
                                <h3>Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?> - <?php echo htmlspecialchars($apt['specialty']); ?></h3>
                                <p><?php echo date('d F Y', strtotime($apt['date'])); ?>, <?php echo date('H:i', strtotime($apt['time'])); ?></p>
                                <p><?php echo htmlspecialchars($apt['location_name']); ?></p>
                            </div>
                            <div class="appointment-actions">
                                <button class="btn btn-primary" onclick="window.location.href='calendrier.php?action=view&id=<?php echo $apt['id']; ?>'">Voir</button>
                                <button class="btn btn-secondary" onclick="window.location.href='calendrier.php?action=edit&id=<?php echo $apt['id']; ?>'">Modifier</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-data">Aucun rendez-vous à venir</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-card">
            <h2><i class="fas fa-chart-line"></i> Suivi de la mémoire</h2>
            <div class="memory-stats">
                <div class="stat-chart">
                    <span class="stat-chart-value"><?php echo isset($memoryStats['current_score']) ? $memoryStats['current_score'] : '0'; ?>%</span>
                </div>
                <div class="stat-info">
                    <h3>Progrès hebdomadaire</h3>
                    <?php if (isset($memoryStats['progress_percent']) && $memoryStats['progress_percent'] != 0): ?>
                        <p><?php echo $memoryStats['progress_percent'] > 0 ? 'Amélioration' : 'Diminution'; ?> de <?php echo abs($memoryStats['progress_percent']); ?>% par rapport à la semaine dernière</p>
                    <?php else: ?>
                        <p>Pas de données comparatives disponibles</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cartes de conseils et ressources avec effet de retournement -->
<div class="dashboard-card">
    <h2><i class="fas fa-book-open"></i> Conseils et ressources</h2>
    <div class="resource-grid">
        <!-- Conseil 1 -->
        <div class="resource-card">
            <div class="resource-card-inner">
                <div class="resource-card-front">
                    <div class="resource-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Régularité</h3>
                    <p>Prenez vos médicaments à heures fixes</p>
                </div>
                <div class="resource-card-back">
                    <h3>Régularité</h3>
                    <p>Établir une routine quotidienne pour la prise de médicaments améliore l'efficacité du traitement et réduit le risque d'oubli. Utilisez des alarmes ou associez la prise à une activité quotidienne comme le petit-déjeuner.</p>
                    <button class="btn" onclick="window.location.href='conseils.php?type=regularite'">En savoir plus</button>
                </div>
            </div>
        </div>
        
        <!-- Conseil 2 -->
        <div class="resource-card">
            <div class="resource-card-inner">
                <div class="resource-card-front">
                    <div class="resource-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Alimentation</h3>
                    <p>Attention aux interactions avec la nourriture</p>
                </div>
                <div class="resource-card-back">
                    <h3>Alimentation</h3>
                    <p>Certains médicaments doivent être pris pendant les repas, d'autres à jeun. Le pamplemousse et l'alcool peuvent interagir avec de nombreux médicaments. Consultez toujours les notices ou votre pharmacien.</p>
                    <button class="btn" onclick="window.location.href='conseils.php?type=alimentation'">En savoir plus</button>
                </div>
            </div>
        </div>
        
        <!-- Conseil 3 -->
        <div class="resource-card">
            <div class="resource-card-inner">
                <div class="resource-card-front">
                    <div class="resource-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                    <h3>Stockage</h3>
                    <p>Conservez correctement vos médicaments</p>
                </div>
                <div class="resource-card-back">
                    <h3>Stockage</h3>
                    <p>Conservez vos médicaments dans un endroit sec, à l'abri de la lumière et de la chaleur. Certains médicaments nécessitent une conservation au réfrigérateur. Vérifiez toujours les conditions de stockage sur l'emballage.</p>
                    <button class="btn" onclick="window.location.href='conseils.php?type=stockage'">En savoir plus</button>
                </div>
            </div>
        </div>
        
        <!-- Conseil 4 -->
        <div class="resource-card">
            <div class="resource-card-inner">
                <div class="resource-card-front">
                    <div class="resource-icon">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <h3>Ordonnances</h3>
                    <p>Gardez une trace de vos prescriptions</p>
                </div>
                <div class="resource-card-back">
                    <h3>Ordonnances</h3>
                    <p>Conservez toutes vos ordonnances dans un même endroit et prenez-les lors de chaque visite médicale. Un historique complet aide votre médecin à adapter votre traitement et éviter les interactions médicamenteuses.</p>
                    <button class="btn" onclick="window.location.href='conseils.php?type=ordonnances'">En savoir plus</button>
                </div>
            </div>
        </div>
        
        <!-- Conseil 5 -->
        <div class="resource-card">
            <div class="resource-card-inner">
                <div class="resource-card-front">
                    <div class="resource-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <h3>Effets secondaires</h3>
                    <p>Surveillez les réactions inhabituelles</p>
                </div>
                <div class="resource-card-back">
                    <h3>Effets secondaires</h3>
                    <p>Notez tout symptôme inhabituel survenant après la prise d'un médicament. En cas d'effet indésirable grave (difficultés respiratoires, éruption cutanée, gonflement), contactez immédiatement un professionnel de santé.</p>
                    <button class="btn" onclick="window.location.href='conseils.php?type=effets'">En savoir plus</button>
                </div>
            </div>
        </div>
        
        <!-- Conseil 6 -->
        <div class="resource-card">
            <div class="resource-card-inner">
                <div class="resource-card-front">
                    <div class="resource-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Communication</h3>
                    <p>Échangez avec vos professionnels de santé</p>
                </div>
                <div class="resource-card-back">
                    <h3>Communication</h3>
                    <p>N'hésitez pas à poser des questions à votre médecin ou pharmacien concernant vos médicaments. Informez-les de tous les médicaments que vous prenez, y compris les compléments alimentaires et médicaments sans ordonnance.</p>
                    <button class="btn" onclick="window.location.href='conseils.php?type=communication'">En savoir plus</button>
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Partie personnalisation placée en bas -->
        <div class="dashboard-card">
            <h2><i class="fas fa-palette"></i> Personnalisation</h2>
            <div class="customization-section">
                <p>Choisissez un thème de couleur pour votre interface :</p>
                <div class="theme-switch">
                    <div class="theme-option theme-blue" title="Bleu" onclick="changeTheme('blue')"></div>
                    <div class="theme-option theme-green" title="Vert" onclick="changeTheme('green')"></div>
                    <div class="theme-option theme-purple" title="Violet" onclick="changeTheme('purple')"></div>
                    <div class="theme-option theme-orange" title="Orange" onclick="changeTheme('orange')"></div>
                </div>
                <div class="feedback-section">
                    <button class="btn btn-primary" onclick="window.location.href='feedback.php'">Partager votre avis</button>
                </div>
            </div>
        </div>

        <button class="support-button" onclick="window.location.href='support.php'">
            <i class="fas fa-headset"></i> Besoin d'aide ? Contacter le support
        </button>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="logo.png" alt="PillCare Logo" />
                </div>
                <div class="footer-links">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="about.php">À propos</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="privacy.php">Confidentialité</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact</h3>
                    <p><i class="fas fa-envelope"></i> contact@pillcare.com</p>
                    <p><i class="fas fa-phone"></i> +33 1 23 45 67 89</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> PillCare. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Font Awesome pour les icônes -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script src="home.js"></script>

    <script>
        // Gestion du menu utilisateur
        document.getElementById("user-btn").addEventListener("click", function () {
            document.querySelector(".dropdown-menu").classList.toggle("active");
        });

        // Fermeture du menu lorsqu'on clique ailleurs
        document.addEventListener("click", function (e) {
            if (!e.target.closest(".user-menu")) {
                document.querySelector(".dropdown-menu").classList.remove("active");
            }
        });

        // Gestion du menu mobile
        document.querySelector(".mobile-menu-btn").addEventListener("click", function () {
            document.querySelector(".top-nav").classList.toggle("show");
        });

        // Fonction pour changer le thème
        function changeTheme(theme) {
            // Créer une feuille de style pour les thèmes
            let style = document.querySelector("#theme-style");
            if (!style) {
                style = document.createElement("style");
                style.id = "theme-style";
                document.head.appendChild(style);
            }

            // Définir les couleurs selon le thème
            let primaryColor, secondaryColor, accentColor;

            switch (theme) {
                case "green":
                    primaryColor = "#66bb6a";
                    secondaryColor = "#81c784";
                    accentColor = "#388e3c";
                    break;
                case "purple":
                    primaryColor = "#9c27b0";
                    secondaryColor = "#ba68c8";
                    accentColor = "#7b1fa2";
                    break;
                case "orange":
                    primaryColor = "#ff9800";
                    secondaryColor = "#ffb74d";
                    accentColor = "#f57c00";
                    break;
                default: // Bleu (par défaut)
                    primaryColor = "#4a90e2";
                    secondaryColor = "#63a4ff";
                    accentColor = "#1a56a2";
            }

            // Appliquer les couleurs via CSS variables
            style.textContent = `
                :root {
                    --primary-color: ${primaryColor};
                    --secondary-color: ${secondaryColor};
                    --accent-color: ${accentColor};
                }
            `;

            // Sauvegarder la préférence
            localStorage.setItem("pillcare-theme", theme);
            
            // Enregistrer la préférence en base de données
            <?php if ($loggedIn): ?>
            fetch('save_preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'theme=' + theme
            });
            <?php endif; ?>
        }

        // Fonction pour basculer entre mode clair et sombre
        function toggleTheme() {
            const darkMode = document.body.classList.toggle('dark-mode');
            const themeIcon = document.querySelector('.theme-toggle i');
            
            if (darkMode) {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
            
            // Sauvegarder la préférence
            localStorage.setItem("pillcare-dark-mode", darkMode ? 'true' : 'false');
            
            <?php if ($loggedIn): ?>
            // Enregistrer la préférence en base de données
            fetch('save_preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'dark_mode=' + (darkMode ? '1' : '0')
            });
            <?php endif; ?>
        }

        // Charger les préférences au chargement de la page
        document.addEventListener("DOMContentLoaded", function () {
            const savedTheme = localStorage.getItem("pillcare-theme");
            if (savedTheme) {
                changeTheme(savedTheme);
            }
            
            const darkMode = localStorage.getItem("pillcare-dark-mode");
            if (darkMode === 'true') {
                document.body.classList.add('dark-mode');
                const themeIcon = document.querySelector('.theme-toggle i');
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }
            
            <?php if ($loggedIn && isset($_SESSION['user_theme'])): ?>
            // Appliquer le thème de l'utilisateur depuis la base de données
            changeTheme('<?php echo $_SESSION['user_theme']; ?>');
            <?php endif; ?>
            
            <?php if ($loggedIn && isset($_SESSION['user_dark_mode']) && $_SESSION['user_dark_mode']): ?>
            // Appliquer le mode sombre depuis la base de données
            document.body.classList.add('dark-mode');
            const themeIcon = document.querySelector('.theme-toggle i');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
            <?php endif; ?>
        });
        
        // Gérer la prise de médicaments
        function markMedicationTaken(medicationId) {
            fetch('update_medication.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'medication_id=' + medicationId + '&action=taken'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'interface utilisateur
                    location.reload();
                } else {
                    alert('Erreur lors de la mise à jour: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
<?php
        } else {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            header("Location: index.php?action=login");
            exit;
        }
        break;
}
?>