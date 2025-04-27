<?php
// Démarrage de la session pour gestion des utilisateurs connectés
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Redirection vers la page de connexion si non connecté
    header('Location: login.php');
    exit();
}

// Connexion à la base de données
$host = 'localhost';
$dbname = 'pillcare_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Récupération des informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement des actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'getEvents':
                // Récupérer les rendez-vous pour un mois spécifique
                $year = $_POST['year'];
                $month = $_POST['month'];
                $firstDayOfMonth = "$year-$month-01";
                $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));
                
                $stmt = $conn->prepare("
                    SELECT r.*, d.name as doctor_name 
                    FROM rendez_vous r
                    LEFT JOIN doctors d ON r.medecin = d.id
                    WHERE r.user_id = ? AND date_debut BETWEEN ? AND ?
                ");
                $stmt->execute([$user_id, $firstDayOfMonth, $lastDayOfMonth]);
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'events' => $events]);
                exit;
                
            case 'saveEvent':
                // Enregistrer ou mettre à jour un rendez-vous
                $id = isset($_POST['id']) && !empty($_POST['id']) ? $_POST['id'] : null;
                $titre = $_POST['titre'];
                $type = $_POST['type'];
                $date = $_POST['date'];
                $time = $_POST['time'];
                $description = $_POST['description'];
                $lieu = $_POST['lieu'] ?? null;
                $medecin = $_POST['medecin'] ?? null;
                $reminder_timing = $_POST['reminder_timing'] ?? 60;
                $couleur = $_POST['couleur'] ?? null;
                
                // Création de la date_debut et date_fin à partir de la date et l'heure
                $date_debut = $date . ' ' . ($time ?: '00:00:00');
                $date_fin = $date . ' ' . ($time ?: '23:59:59');
                
                if ($id) {
                    // Mise à jour d'un rendez-vous existant
                    $stmt = $conn->prepare("
                        UPDATE rendez_vous 
                        SET titre = ?, type = ?, date_debut = ?, date_fin = ?, 
                            description = ?, lieu = ?, medecin = ?, 
                            reminder_timing = ?, couleur = ?, updated_at = NOW()
                        WHERE id = ? AND user_id = ?
                    ");
                    $result = $stmt->execute([
                        $titre, $type, $date_debut, $date_fin, $description, 
                        $lieu, $medecin, $reminder_timing, $couleur, $id, $user_id
                    ]);
                } else {
                    // Création d'un nouveau rendez-vous
                    $stmt = $conn->prepare("
                        INSERT INTO rendez_vous 
                        (titre, type, date_debut, date_fin, description, lieu, 
                         medecin, reminder_timing, couleur, user_id, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $result = $stmt->execute([
                        $titre, $type, $date_debut, $date_fin, $description,
                        $lieu, $medecin, $reminder_timing, $couleur, $user_id
                    ]);
                    $id = $conn->lastInsertId();
                }
                
                // Création d'une notification
                if ($result) {
                    // Calculer la date d'envoi de la notification en fonction du reminder_timing
                    $send_at = date('Y-m-d H:i:s', strtotime($date_debut) - ($reminder_timing * 60));
                    
                    // Vérifier si une notification existe déjà pour ce rendez-vous
                    if ($id) {
                        $stmt = $conn->prepare("DELETE FROM notifications WHERE cible = 'rendez_vous' AND message LIKE ? AND user_id = ?");
                        $stmt->execute(['%Rappel : ' . $titre . '%', $user_id]);
                    }
                    
                    // Créer la nouvelle notification
                    $message = "Rappel : " . $titre . " - " . date('d/m/Y à H:i', strtotime($date_debut));
                    if ($lieu) {
                        $message .= " à " . $lieu;
                    }
                    
                    $stmt = $conn->prepare("
                        INSERT INTO notifications 
                        (message, type, cible, send_at, statut, user_id, created_at)
                        VALUES (?, ?, 'rendez_vous', ?, 'pending', ?, NOW())
                    ");
                    
                    // Si l'utilisateur a activé les notifications par email
                    if ($user['email_notifs']) {
                        $stmt->execute([$message, 'email', $send_at, $user_id]);
                    }
                    
                    // Si l'utilisateur a activé les notifications par SMS
                    if ($user['sms_notifs'] && $user['telephone']) {
                        $stmt->execute([$message, 'sms', $send_at, $user_id]);
                    }
                }
                
                echo json_encode(['success' => $result, 'id' => $id]);
                exit;
                
            case 'deleteEvent':
                // Supprimer un rendez-vous
                $id = $_POST['id'];
                
                $stmt = $conn->prepare("DELETE FROM rendez_vous WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$id, $user_id]);
                
                // Supprimer également les notifications associées
                if ($result) {
                    $stmt = $conn->prepare("DELETE FROM notifications WHERE cible = 'rendez_vous' AND message LIKE ? AND user_id = ?");
                    $stmt->execute(['%Rappel : ' . $_POST['titre'] . '%', $user_id]);
                }
                
                echo json_encode(['success' => $result]);
                exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    exit;
}

// Récupération de la liste des médecins pour le formulaire
$stmt = $conn->prepare("SELECT id, name, specialty FROM doctors ORDER BY name");
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste des lieux pour le formulaire
$stmt = $conn->prepare("SELECT id, name, address FROM locations ORDER BY name");
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Calendrier Mémo - Aide-mémoire pour vos rendez-vous</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="calendrier.css" />
    <link rel="stylesheet" href="header.css" />
    <link rel="stylesheet" href="footer.css">
</head>
<body>
  <!-- Header -->
  <header class="header">
      <div class="header-bg"></div>
      <div class="wave-container"></div>
      <div class="header-content">
        <div class="logo-container">
          <img src="logo.png" alt="PillCare Logo" class="logo-img" />
        </div>
        <nav class="top-nav">
          <a href="index.php" class="nav-item" data-page="home">Accueil</a>
          <a
            href="Medicament.php"
            class="nav-item "
            data-page="Medicament"
            >Medicaments</a
          >
          <a href="calendrier.php" class="nav-item active" data-page="calendar"
            >Calendrier</a
          >
          <a href="ordonance.php" class="nav-item" data-page="ordonance"
            >ordonance</a
          >
          <a href="profile.php" class="nav-item" data-page="profile"
            >profile</a
          >
        </nav>
        <div class="user-menu">
          <button id="user-btn"><i class="fas fa-user-circle"></i></button>
          <div class="dropdown-menu">
            <a href="settings.html"><i class="fas fa-cog"></i> Paramètres</a>
            <a href="#" class="theme-toggle" onclick="toggleTheme()"
              ><i class="fas fa-moon"></i> Thème</a
            >
            <a href="#" id="logout-btn"
              ><i class="fas fa-sign-out-alt"></i> Déconnexion</a
            >
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
        <div class="header1">
            <h1>Calendrier Mémo</h1>
            <p class="subtitle">Votre aide-mémoire pour ne rien oublier</p>
        </div>

        <div class="calendar-container">
            <div class="controls">
                <div class="month-selector">
                    <button id="prevMonth">&lt;</button>
                    <div class="current-month" id="currentMonth">Avril 2025</div>
                    <button id="nextMonth">&gt;</button>
                </div>
                <button class="add-event-btn" id="addEventBtn">+ Ajouter un rendez-vous</button>
            </div>

            <div class="calendar" id="calendar">
                <!-- Les jours de la semaine seront ajoutés dynamiquement -->
            </div>
        </div>
    </div>
    <div class="upcoming-appointments">
    <h2>Vos prochains rendez-vous</h2>
    
    <div class="appointment-tabs">
        <button class="tab-btn active" data-filter="all"><i class="fas fa-list"></i> Tous</button>
        <button class="tab-btn" data-filter="medical"><i class="fas fa-user-md"></i> Consultations</button>
        <button class="tab-btn" data-filter="analysis"><i class="fas fa-vial"></i> Analyses</button>
        <button class="tab-btn" data-filter="task"><i class="fas fa-tasks"></i> Tâches</button>
        <button class="tab-btn" data-filter="reminder"><i class="fas fa-bell"></i> Rappels</button>
    </div>

    <div class="appointment-cards" id="appointment-cards">
        <?php
        // Récupération des prochains rendez-vous
        $stmt = $conn->prepare("
            SELECT r.*, d.name as doctor_name, d.specialty, l.name as location_name, l.address 
            FROM rendez_vous r
            LEFT JOIN doctors d ON r.medecin = d.id
            LEFT JOIN locations l ON r.lieu = l.id
            WHERE r.user_id = ? AND date_debut >= NOW()
            ORDER BY date_debut ASC
            LIMIT 15
        ");
        $stmt->execute([$user_id]);
        $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($upcoming)) {
            echo '<div class="no-appointments"><i class="fas fa-calendar-times fa-2x"></i><p>Aucun rendez-vous à venir. Utilisez le calendrier pour en ajouter.</p></div>';
        }

        // Définition des icônes par type de rendez-vous
        $typeIcons = [
            'medical' => '<i class="fas fa-user-md"></i>',
            'analysis' => '<i class="fas fa-vial"></i>',
            'task' => '<i class="fas fa-tasks"></i>',
            'reminder' => '<i class="fas fa-bell"></i>'
        ];

        // Définition des libellés par type de rendez-vous
        $typeLabels = [
            'medical' => 'Consultation',
            'analysis' => 'Analyse',
            'task' => 'Tâche',
            'reminder' => 'Rappel'
        ];

        foreach ($upcoming as $appt) {
            $date = new DateTime($appt['date_debut']);
            $now = new DateTime();
            $interval = $now->diff($date);
            
            $formattedDate = $date->format('l j F Y - H:i');
            $frenchDay = [
                'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 
                'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'
            ];
            $frenchMonth = [
                'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars', 'April' => 'Avril',
                'May' => 'Mai', 'June' => 'Juin', 'July' => 'Juillet', 'August' => 'Août', 
                'September' => 'Septembre', 'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
            ];
            
            foreach ($frenchDay as $en => $fr) {
                $formattedDate = str_replace($en, $fr, $formattedDate);
            }
            
            foreach ($frenchMonth as $en => $fr) {
                $formattedDate = str_replace($en, $fr, $formattedDate);
            }
            
            // Obtenir le type de RDV, avec une valeur par défaut
            $apptType = isset($appt['type']) && array_key_exists($appt['type'], $typeLabels) ? $appt['type'] : 'medical';
            
            // Déterminer si le RDV approche (moins de 24h)
            $isApproaching = $interval->days === 0 && $interval->h < 24;
            
            // Classe supplémentaire si le RDV approche
            $approachingClass = $isApproaching ? 'approaching' : '';
        ?>
        <div class="appointment-card <?php echo $approachingClass; ?>" data-type="<?php echo $apptType; ?>">
            <div class="card-header">
                <div class="card-date"><?php echo $formattedDate; ?></div>
                <div class="card-badge <?php echo $apptType; ?>">
                    <?php echo $typeIcons[$apptType]; ?> <?php echo $typeLabels[$apptType]; ?>
                </div>
            </div>
            
            <h3 class="card-title">
                <?php 
                if ($apptType === 'medical' && !empty($appt['doctor_name'])) {
                    echo 'Dr. ' . htmlspecialchars($appt['doctor_name']);
                    if (!empty($appt['specialty'])) {
                        echo ' <span class="specialty">- ' . htmlspecialchars($appt['specialty']) . '</span>';
                    }
                } else {
                    echo htmlspecialchars($appt['titre']);
                }
                ?>
            </h3>
            
            <?php if (!empty($appt['location_name']) || !empty($appt['lieu'])): ?>
            <div class="card-info">
                <i class="fas fa-map-marker-alt"></i>
                <?php 
                if (!empty($appt['location_name'])) {
                    echo htmlspecialchars($appt['location_name']);
                    if (!empty($appt['address'])) {
                        echo ', ' . htmlspecialchars($appt['address']);
                    }
                } else {
                    echo htmlspecialchars($appt['lieu']);
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($appt['telephone'])): ?>
            <div class="card-info">
                <i class="fas fa-phone"></i>
                <?php echo htmlspecialchars($appt['telephone']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($appt['description'])): ?>
            <div class="card-notes">
                <i class="fas fa-comment-alt"></i>
                <?php echo htmlspecialchars($appt['description']); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($isApproaching): ?>
            <div class="approaching-notice">
                <i class="fas fa-clock"></i> Rendez-vous imminent
            </div>
            <?php endif; ?>
            
            <div class="card-actions">
                <button class="edit-btn" data-id="<?php echo $appt['id']; ?>" title="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="delete-btn" data-id="<?php echo $appt['id']; ?>" data-title="<?php echo htmlspecialchars($appt['titre']); ?>" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
    <div class="appointment-tabs">
        <button class="tab-btn active" data-filter="all"><i class="fas fa-list"></i> Tous</button>
        <button class="tab-btn" data-filter="medical"><i class="fas fa-user-md"></i> Consultations</button>
        <button class="tab-btn" data-filter="analysis"><i class="fas fa-vial"></i> Analyses</button>
    </div>

    <div class="appointment-cards" id="appointment-cards">
        <?php
        // Récupération des prochains rendez-vous
        $stmt = $conn->prepare("
            SELECT r.*, d.name as doctor_name, d.specialty, l.name as location_name, l.address 
            FROM rendez_vous r
            LEFT JOIN doctors d ON r.medecin = d.id
            LEFT JOIN locations l ON r.lieu = l.id
            WHERE r.user_id = ? AND date_debut >= NOW()
            ORDER BY date_debut ASC
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($upcoming)) {
            echo '<div class="no-appointments">Aucun rendez-vous à venir. Utilisez le calendrier pour en ajouter.</div>';
        }

        foreach ($upcoming as $appt) {
            $date = new DateTime($appt['date_debut']);
            $formattedDate = $date->format('l j F Y - H:i');
            $frenchDay = [
                'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 
                'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'
            ];
            $frenchMonth = [
                'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars', 'April' => 'Avril',
                'May' => 'Mai', 'June' => 'Juin', 'July' => 'Juillet', 'August' => 'Août', 
                'September' => 'Septembre', 'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
            ];
            
            foreach ($frenchDay as $en => $fr) {
                $formattedDate = str_replace($en, $fr, $formattedDate);
            }
            
            foreach ($frenchMonth as $en => $fr) {
                $formattedDate = str_replace($en, $fr, $formattedDate);
            }
            
            $cardType = ($appt['type'] === 'medical') ? 'consultation' : 'analyse';
            $badgeText = ($appt['type'] === 'medical') ? 'Consultation' : 'Analyse';
            $badgeClass = ($appt['type'] === 'medical') ? 'consultation' : 'analyse';
        ?>
        <div class="appointment-card" data-type="<?php echo $appt['type']; ?>">
            <div class="card-header">
                <div class="card-date"><?php echo $formattedDate; ?></div>
                <div class="card-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></div>
            </div>
            
            <h3 class="card-title">
                <?php 
                if ($appt['type'] === 'medical' && !empty($appt['doctor_name'])) {
                    echo 'Dr. ' . htmlspecialchars($appt['doctor_name']);
                    if (!empty($appt['specialty'])) {
                        echo ' - ' . htmlspecialchars($appt['specialty']);
                    }
                } else {
                    echo htmlspecialchars($appt['titre']);
                }
                ?>
            </h3>
            
            <?php if (!empty($appt['location_name']) || !empty($appt['lieu'])): ?>
            <div class="card-info">
                <i class="fas fa-map-marker-alt"></i>
                <?php 
                if (!empty($appt['location_name'])) {
                    echo htmlspecialchars($appt['location_name']);
                    if (!empty($appt['address'])) {
                        echo ', ' . htmlspecialchars($appt['address']);
                    }
                } else {
                    echo htmlspecialchars($appt['lieu']);
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($appt['telephone'])): ?>
            <div class="card-info">
                <i class="fas fa-phone"></i>
                <?php echo htmlspecialchars($appt['telephone']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($appt['description'])): ?>
            <div class="card-notes">
                <i class="fas fa-comment-alt"></i>
                <?php echo htmlspecialchars($appt['description']); ?>
            </div>
            <?php endif; ?>
            
            <div class="card-actions">
                <button class="edit-btn" data-id="<?php echo $appt['id']; ?>">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="delete-btn" data-id="<?php echo $appt['id']; ?>" data-title="<?php echo htmlspecialchars($appt['titre']); ?>">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

    <!-- Modal pour ajouter/modifier un événement -->
    <div class="modal" id="eventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Ajouter un rendez-vous</h3>
                <button class="close-modal" id="closeModal">&times;</button>
            </div>

            <form id="eventForm">
                <input type="hidden" id="eventId" name="id" />
                <input type="hidden" id="eventDate" name="date" />

                <div class="form-group">
                    <label for="eventType">Type:</label>
                    <select id="eventType" name="type">
                        <option value="medical">Rendez-vous médical</option>
                        <option value="task">Tâche à faire</option>
                        <option value="reminder">Rappel important</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="eventTitle">Titre:</label>
                    <input type="text" id="eventTitle" name="titre" placeholder="Ex: Consultation Dr Martin" required />
                </div>

                <div class="form-group">
                    <label for="eventTime">Heure:</label>
                    <input type="time" id="eventTime" name="time" />
                </div>

                <div class="form-group doctor-field">
                    <label for="eventDoctor">Médecin:</label>
                    <select id="eventDoctor" name="medecin">
                        <option value="">-- Sélectionner un médecin --</option>
                        <?php foreach ($doctors as $doctor): ?>
                        <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['name']) . ' - ' . htmlspecialchars($doctor['specialty']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group location-field">
                    <label for="eventLocation">Lieu:</label>
                    <select id="eventLocation" name="lieu">
                        <option value="">-- Sélectionner un lieu --</option>
                        <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>"><?php echo htmlspecialchars($location['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="eventDescription">Description:</label>
                    <textarea id="eventDescription" name="description" rows="2" placeholder="Détails supplémentaires"></textarea>
                </div>

                <div class="form-group">
                    <label for="eventColor">Couleur:</label>
                    <select id="eventColor" name="couleur">
                        <option value="#3788d8">Bleu</option>
                        <option value="#28a745">Vert</option>
                        <option value="#dc3545">Rouge</option>
                        <option value="#ffc107">Jaune</option>
                        <option value="#6f42c1">Violet</option>
                    </select>
                </div>

                <button type="button" class="toggle-notifications" id="toggleNotifications">+ Options de rappel</button>

                <div class="notification-settings notification-details" id="notificationDetails">
                    <h4 class="notification-title">Rappels</h4>

                    <div class="checkbox-group">
                        <input type="checkbox" id="emailNotification" <?php echo $user['email_notifs'] ? 'checked' : ''; ?> />
                        <label for="emailNotification">Email</label>
                    </div>

                    <div class="form-group">
                        <input type="email" id="emailAddress" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Adresse email" readonly />
                        <small>Pour modifier l'email, allez dans les paramètres du profil</small>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="smsNotification" <?php echo $user['sms_notifs'] && $user['telephone'] ? 'checked' : ''; ?> <?php echo !$user['telephone'] ? 'disabled' : ''; ?> />
                        <label for="smsNotification">SMS</label>
                    </div>

                    <div class="form-group">
                        <input type="tel" id="phoneNumber" value="<?php echo htmlspecialchars($user['telephone'] ?: ''); ?>" placeholder="Numéro de téléphone" readonly />
                        <small>Pour modifier le téléphone, allez dans les paramètres du profil</small>
                    </div>

                    <div class="form-group">
                        <label for="reminderTime">Rappeler:</label>
                        <select id="reminderTime" name="reminder_timing">
                            <option value="15">15 minutes avant</option>
                            <option value="30">30 minutes avant</option>
                            <option value="60" selected>1 heure avant</option>
                            <option value="120">2 heures avant</option>
                            <option value="1440">1 jour avant</option>
                        </select>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" class="delete-btn" id="deleteEventBtn">Supprimer</button>
                    <button type="submit" class="save-btn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!--footer-->
    <?php include 'footer.html'; ?>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Variables globales
        const calendar = document.getElementById("calendar");
        const currentMonthElem = document.getElementById("currentMonth");
        const prevMonthBtn = document.getElementById("prevMonth");
        const nextMonthBtn = document.getElementById("nextMonth");
        const addEventBtn = document.getElementById("addEventBtn");
        const eventModal = document.getElementById("eventModal");
        const closeModal = document.getElementById("closeModal");
        const eventForm = document.getElementById("eventForm");
        const modalTitle = document.getElementById("modalTitle");
        const deleteEventBtn = document.getElementById("deleteEventBtn");
        const toggleNotifications = document.getElementById("toggleNotifications");
        const notificationDetails = document.getElementById("notificationDetails");
        const eventType = document.getElementById("eventType");
        const doctorField = document.querySelector(".doctor-field");
        const locationField = document.querySelector(".location-field");

        let currentDate = new Date();
        let selectedDate = null;
        let events = [];

        // Noms des jours de la semaine et des mois
        const weekdays = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
        const months = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];

        // Afficher/masquer les champs en fonction du type d'événement
        eventType.addEventListener("change", function() {
            if (this.value === "medical") {
                doctorField.style.display = "block";
                locationField.style.display = "block";
            } else {
                doctorField.style.display = "none";
                locationField.style.display = "none";
            }
        });

        // Fonction pour basculer l'affichage des paramètres de notification
        toggleNotifications.addEventListener("click", function() {
            if (notificationDetails.style.display === "block") {
                notificationDetails.style.display = "none";
                toggleNotifications.textContent = "+ Options de rappel";
            } else {
                notificationDetails.style.display = "block";
                toggleNotifications.textContent = "- Masquer les options";
            }
        });

        // Fonction pour charger les événements du mois courant depuis la base de données
        function loadEvents() {
            const year = currentDate.getFullYear();
            const month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
            
            fetch('calendrier.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=getEvents&year=${year}&month=${month}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    events = data.events;
                    generateCalendar();
                }
            })
            .catch(error => console.error('Erreur:', error));
        }

        // Fonction pour générer le calendrier
        function generateCalendar() {
            calendar.innerHTML = "";

            // Ajouter les entêtes de jour de la semaine
            weekdays.forEach(day => {
                const weekdayElem = document.createElement("div");
                weekdayElem.className = "weekday";
                weekdayElem.textContent = day;
                calendar.appendChild(weekdayElem);
            });

            // Calculer le premier jour du mois
            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);

            // Ajouter les jours vides avant le premier jour du mois
            for (let i = 0; i < firstDay.getDay(); i++) {
                const emptyDay = document.createElement("div");
                emptyDay.className = "day empty";
                calendar.appendChild(emptyDay);
            }

            // Ajouter les jours du mois
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const dayElem = document.createElement("div");
                dayElem.className = "day";
                
                const dateStr = `${currentDate.getFullYear()}-${(currentDate.getMonth() + 1).toString().padStart(2, '0')}-${i.toString().padStart(2, '0')}`;
                dayElem.setAttribute("data-date", dateStr);
                dayElem.setAttribute("data-weekday", weekdays[new Date(currentDate.getFullYear(), currentDate.getMonth(), i).getDay()]);

                // Vérifier si c'est aujourd'hui
                const today = new Date();
                if (today.getDate() === i && today.getMonth() === currentDate.getMonth() && today.getFullYear() === currentDate.getFullYear()) {
                    dayElem.classList.add("today");
                }

                const dayNumber = document.createElement("span");
                dayNumber.className = "day-number";
                dayNumber.textContent = i;
                dayElem.appendChild(dayNumber);

                // Ajouter les événements pour ce jour
                const dayEvents = events.filter(event => {
                    const eventDate = new Date(event.date_debut);
                    return eventDate.getDate() === i && 
                           eventDate.getMonth() === currentDate.getMonth() && 
                           eventDate.getFullYear() === currentDate.getFullYear();
                });

                dayEvents.forEach(event => {
                    const eventElem = document.createElement("div");
                    eventElem.className = event.type === "medical" ? "event" : (event.type === "task" ? "task" : "reminder");
                    
                    if (event.couleur) {
                        eventElem.style.backgroundColor = event.couleur;
                    }

                    let displayText = event.titre;
                    const eventTime = new Date(event.date_debut).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                    
                    if (eventTime !== '00:00') {
                        displayText = `${eventTime} - ${event.titre}`;
                    }

                    eventElem.textContent = displayText;
                    eventElem.setAttribute("data-id", event.id);
                    eventElem.addEventListener("click", (e) => {
                        e.stopPropagation();
                        openEditEventModal(event);
                    });

                    dayElem.appendChild(eventElem);
                });

                // Ajouter l'événement click pour ajouter un nouvel événement
                dayElem.addEventListener("click", () => {
                    const dateStr = dayElem.getAttribute("data-date");
                    openAddEventModal(dateStr);
                });

                calendar.appendChild(dayElem);
            }

            // Mettre à jour l'affichage du mois courant
            currentMonthElem.textContent = `${months[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
        }

        // Fonction pour ouvrir la modal d'ajout d'événement
        function openAddEventModal(dateStr) {
            selectedDate = dateStr;
            modalTitle.textContent = "Ajouter un rendez-vous";
            
            document.getElementById("eventId").value = "";
            document.getElementById("eventDate").value = dateStr;
            document.getElementById("eventType").value = "medical";
            document.getElementById("eventTitle").value = "";
            document.getElementById("eventTime").value = "";
            document.getElementById("eventDescription").value = "";
            document.getElementById("eventDoctor").value = "";
            document.getElementById("eventLocation").value = "";
            document.getElementById("eventColor").value = "#3788d8";
            
            // Afficher les champs médecin et lieu par défaut
            doctorField.style.display = "block";
            locationField.style.display = "block";
            
            deleteEventBtn.style.display = "none";

            // Cacher les détails de notification par défaut
            notificationDetails.style.display = "none";
            toggleNotifications.textContent = "+ Options de rappel";

            eventModal.style.display = "flex";
        }

        // Fonction pour ouvrir la modal d'édition d'événement
        function openEditEventModal(event) {
            const eventDate = new Date(event.date_debut);
            const dateStr = `${eventDate.getFullYear()}-${(eventDate.getMonth() + 1).toString().padStart(2, '0')}-${eventDate.getDate().toString().padStart(2, '0')}`;
            const timeStr = eventDate.toTimeString().slice(0, 5);
            
            selectedDate = dateStr;
            modalTitle.textContent = "Modifier un rendez-vous";
            
            document.getElementById("eventId").value = event.id;
            document.getElementById("eventDate").value = dateStr;
            document.getElementById("eventType").value = event.type;
            document.getElementById("eventTitle").value = event.titre;
            document.getElementById("eventTime").value = timeStr !== '00:00' ? timeStr : '';
            document.getElementById("eventDescription").value = event.description || '';
            document.getElementById("eventDoctor").value = event.medecin || '';
            document.getElementById("eventLocation").value = event.lieu || '';
            document.getElementById("reminderTime").value = event.reminder_timing || '60';
            document.getElementById("eventColor").value = event.couleur || '#3788d8';
            
            // Afficher ou masquer les champs en fonction du type
            if (event.type === "medical") {
                doctorField.style.display = "block";
                locationField.style.display = "block";
            } else {
                doctorField.style.display = "none";
                locationField.style.display = "none";
            }

            deleteEventBtn.style.display = "block";

            // Cacher les détails de notification par défaut
            notificationDetails.style.display = "none";
            toggleNotifications.textContent = "+ Options de rappel";

            eventModal.style.display = "flex";
        }

        // Fonction pour sauvegarder un événement
        function saveEvent(e) {
            e.preventDefault();

            const formData = new FormData(eventForm);
            formData.append('action', 'saveEvent');
            formData.append('titre', document.getElementById("eventTitle").value);
            
            // Ajouter les informations de notification
            formData.append('email_notif', document.getElementById("emailNotification").checked ? '1' : '0');
            formData.append('sms_notif', document.getElementById("smsNotification").checked ? '1' : '0');
            
            fetch('calendrier.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    eventModal.style.display = "none";
                    loadEvents(); // Recharger les événements après la sauvegarde
                } else {
                    alert("Une erreur est survenue lors de l'enregistrement du rendez-vous.");
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert("Une erreur est survenue. Veuillez réessayer.");
            });
        }

        // Fonction pour supprimer un événement
        function deleteEvent() {
            const eventId = document.getElementById("eventId").value;
            const eventTitle = document.getElementById("eventTitle").value;
            
            if (confirm(`Êtes-vous sûr de vouloir supprimer le rendez-vous "${eventTitle}" ?`)) {
                const formData = new FormData();
                formData.append('action', 'deleteEvent');
                formData.append('id', eventId);
                formData.append('titre', eventTitle);
                
                fetch('calendrier.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        eventModal.style.display = "none";
                        loadEvents(); // Recharger les événements après la suppression
                    } else {
                        alert("Une erreur est survenue lors de la suppression du rendez-vous.");
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert("Une erreur est survenue. Veuillez réessayer.");
                });
            }
        }

        // Événements d'écoute
        prevMonthBtn.addEventListener("click", () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            loadEvents();
        });

        nextMonthBtn.addEventListener("click", () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            loadEvents();
        });

        addEventBtn.addEventListener("click", () => {
            const today = new Date();
            const dateStr = `${today.getFullYear()}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getDate().toString().padStart(2, '0')}`;
            openAddEventModal(dateStr);
        });

        closeModal.addEventListener("click", () => {
            eventModal.style.display = "none";
        });

        eventForm.addEventListener("submit", saveEvent);
        deleteEventBtn.addEventListener("click", deleteEvent);

        // Fermer la modal si on clique en dehors
        window.addEventListener("click", (e) => {
            if (e.target === eventModal) {
                eventModal.style.display = "none";
            }
        });

        // Initialiser le calendrier
        loadEvents();

        // Gestion des onglets de filtrage des rendez-vous
const tabButtons = document.querySelectorAll('.tab-btn');
const appointmentCards = document.querySelectorAll('.appointment-card');

tabButtons.forEach(button => {
    button.addEventListener('click', () => {
        // Activer le bouton cliqué
        tabButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        
        // Filtrer les cartes
        const filter = button.getAttribute('data-filter');
        appointmentCards.forEach(card => {
            if (filter === 'all' || card.getAttribute('data-type') === filter) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

// Gestion des boutons d'édition sur les cartes
document.querySelectorAll('.appointment-card .edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const eventId = btn.getAttribute('data-id');
        // Trouver l'événement correspondant dans les données chargées
        const event = events.find(e => e.id === eventId);
        if (event) {
            openEditEventModal(event);
        }
    });
});

// Gestion des boutons de suppression sur les cartes
document.querySelectorAll('.appointment-card .delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const eventId = btn.getAttribute('data-id');
        const eventTitle = btn.getAttribute('data-title');
        
        if (confirm(`Êtes-vous sûr de vouloir supprimer le rendez-vous "${eventTitle}" ?`)) {
            const formData = new FormData();
            formData.append('action', 'deleteEvent');
            formData.append('id', eventId);
            formData.append('titre', eventTitle);
            
            fetch('calendrier.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recharger la page pour actualiser la liste des rendez-vous
                    window.location.reload();
                } else {
                    alert("Une erreur est survenue lors de la suppression du rendez-vous.");
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert("Une erreur est survenue. Veuillez réessayer.");
            });
        }
    });
});
    });
    </script>
</body>
</html>