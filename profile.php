<?php
// Démarrer la session pour accéder aux variables de session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si non connecté
    header('Location: login.php');
    exit();
}

// Inclure le fichier de connexion à la base de données
require_once 'connexion.php';

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Traitement de la mise à jour du profil
$updateSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveProfile'])) {
    try {
        // Récupérer les valeurs du formulaire
        $fullName = htmlspecialchars($_POST['fullName']);
        $birthdate = $_POST['birthdate'];
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars($_POST['phone']);
        $emailNotifs = isset($_POST['emailNotifs']) ? 1 : 0;
        $smsNotifs = isset($_POST['smsNotifs']) ? 1 : 0;
        $reminderTiming = (int)$_POST['reminderTiming'];
        
        // Préparer et exécuter la requête de mise à jour
        $stmt = $conn->prepare("UPDATE users SET 
            fullname = :fullname, 
            date_naissance = :birthdate, 
            email = :email, 
            telephone = :phone, 
            email_notifs = :emailNotifs, 
            sms_notifs = :smsNotifs, 
            reminder_timing = :reminderTiming,
            updated_at = NOW()
            WHERE id = :user_id");
            
        $stmt->bindParam(':fullname', $fullName);
        $stmt->bindParam(':birthdate', $birthdate);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':emailNotifs', $emailNotifs, PDO::PARAM_INT);
        $stmt->bindParam(':smsNotifs', $smsNotifs, PDO::PARAM_INT);
        $stmt->bindParam(':reminderTiming', $reminderTiming, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        $stmt->execute();
        $updateSuccess = true;
    } catch (PDOException $e) {
        $error = "Erreur lors de la mise à jour du profil: " . $e->getMessage();
    }
}

// Traitement de l'ajout d'un contact d'urgence
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addContact'])) {
    try {
        // Récupérer les valeurs du formulaire
        $contactName = htmlspecialchars($_POST['contactName']);
        $contactRelation = htmlspecialchars($_POST['contactRelation']);
        $contactPhone = htmlspecialchars($_POST['contactPhone']);
        $contactEmail = filter_var($_POST['contactEmail'], FILTER_SANITIZE_EMAIL);
        $contactNotify = isset($_POST['contactNotify']) ? 1 : 0;
        
        // Préparer et exécuter la requête d'insertion
        $stmt = $conn->prepare("INSERT INTO contacts_urgence (user_id, nom, relation, telephone, email, notify, created_at, updated_at) 
                               VALUES (:user_id, :nom, :relation, :telephone, :email, :notify, NOW(), NOW())");
                               
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':nom', $contactName);
        $stmt->bindParam(':relation', $contactRelation);
        $stmt->bindParam(':telephone', $contactPhone);
        $stmt->bindParam(':email', $contactEmail);
        $stmt->bindParam(':notify', $contactNotify, PDO::PARAM_INT);
        
        $stmt->execute();
        $contactSuccess = true;
    } catch (PDOException $e) {
        $contactError = "Erreur lors de l'ajout du contact: " . $e->getMessage();
    }
}

// Traitement de la suppression d'un contact
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteContact'])) {
    try {
        $contact_id = (int)$_POST['contact_id'];
        
        // Vérifier que le contact appartient bien à l'utilisateur
        $stmt = $conn->prepare("DELETE FROM contacts_urgence WHERE id = :contact_id AND user_id = :user_id");
        $stmt->bindParam(':contact_id', $contact_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        $stmt->execute();
        $deleteSuccess = true;
    } catch (PDOException $e) {
        $deleteError = "Erreur lors de la suppression du contact: " . $e->getMessage();
    }
}

// Traitement du téléchargement de la photo de profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profilePhoto'])) {
  try {
      $file = $_FILES['profilePhoto'];
      
      // Vérifier s'il y a des erreurs
      if ($file['error'] === 0) {
          // Vérifier le type de fichier
          $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
          if (in_array($file['type'], $allowedTypes)) {
              // Générer un nom de fichier unique
              $newFileName = uniqid('profile_') . '_' . $user_id . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
              $uploadDir = 'uploads/profiles/';
              
              // Créer le répertoire s'il n'existe pas
              if (!is_dir($uploadDir)) {
                  mkdir($uploadDir, 0755, true);
              }
              
              $destination = $uploadDir . $newFileName;
              
              // Déplacer le fichier téléchargé
              if (move_uploaded_file($file['tmp_name'], $destination)) {
                  // Mettre à jour le chemin de la photo dans la base de données
                  $stmt = $conn->prepare("UPDATE users SET profile_photo = :photo WHERE id = :user_id");
                  $stmt->bindParam(':photo', $destination);
                  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                  $stmt->execute();
                  
                  $photoSuccess = true;
              } else {
                  $photoError = "Erreur lors du téléchargement de l'image.";
              }
          } else {
              $photoError = "Type de fichier non autorisé. Veuillez télécharger une image (JPEG, PNG, GIF).";
          }
      } else {
          $photoError = "Erreur lors du téléchargement: code " . $file['error'];
      }
  } catch (PDOException $e) {
      $photoError = "Erreur lors de la mise à jour de la photo: " . $e->getMessage();
  }
}
// Récupérer les informations de l'utilisateur
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
    } else {
        // Utilisateur non trouvé dans la base de données
        session_destroy();
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des données utilisateur: " . $e->getMessage();
}

// Récupérer les contacts d'urgence
try {
    $stmt = $conn->prepare("SELECT * FROM contacts_urgence WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $contacts = $stmt->fetchAll();
} catch (PDOException $e) {
    $contactsError = "Erreur lors de la récupération des contacts: " . $e->getMessage();
}

// Fonction pour obtenir les initiales à partir du nom complet
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word[0])) {
            $initials .= strtoupper($word[0]);
        }
    }
    
    return $initials;
}

// Obtenir les initiales de l'utilisateur
$initials = getInitials($user['fullname']);
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil - Calendrier Mémo</title>
    <link rel="stylesheet" href="profile.css" />
    <link rel="stylesheet" href="footer.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
          <a href="index.php" class="nav-item" data-page="home">Accueil</a>
          <a href="Medicament.php" class="nav-item" data-page="Medicament"
            >Medicaments</a
          >
          <a href="calendrier.php" class="nav-item" data-page="calendar"
            >Calendrier</a
          >
          <a href="ordonance.php" class="nav-item" data-page="ordonance"
            >ordonance</a
          >
          <a href="profile.php" class="nav-item active" data-page="profile"
            >profile</a
          >
        </nav>
        <div class="user-menu">
          <button id="user-btn"><i class="fas fa-user-circle"></i></button>
          <div class="dropdown-menu">
            <a href="settings.php"><i class="fas fa-cog"></i> Paramètres</a>
            <a href="#" class="theme-toggle" onclick="toggleTheme()"
              ><i class="fas fa-moon"></i> Thème</a
            >
            <a href="logout.php" id="logout-btn"
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

    <main class="container">
      <section>
        <h1>Profil</h1>
        <p class="subtitle">Gérez vos informations personnelles</p>
        <?php if (isset($error)): ?>
          <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($updateSuccess): ?>
          <div class="success-message">Vos modifications ont été enregistrées avec succès !</div>
        <?php endif; ?>
      </section>

      <div class="profile-container">
        <div class="profile-card">
          <div class="profile-photo" id="profilePhotoDisplay">
            <?php if (!empty($user['profile_photo'])): ?>
              <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Photo de profil" />
            <?php else: ?>
              <span id="initials"><?php echo $initials; ?></span>
            <?php endif; ?>
          </div>
          <h2 class="profile-name" id="profileNameDisplay"><?php echo htmlspecialchars($user['fullname']); ?></h2>
          <?php
            // Calculer l'âge à partir de la date de naissance
            $birthdate = new DateTime($user['date_naissance']);
            $today = new DateTime();
            $age = $birthdate->diff($today)->y;
          ?>
          <p class="profile-info" id="profileAgeDisplay"><?php echo $age; ?> ans</p>
          <p class="profile-info" id="profileEmailDisplay"><?php echo htmlspecialchars($user['email']); ?></p>
          <p class="profile-info" id="profilePhoneDisplay"><?php echo htmlspecialchars($user['telephone']); ?></p>
          
          <form method="POST" enctype="multipart/form-data" id="photoForm">
            <button type="button" class="profile-action" id="uploadPhotoBtn">
              Changer la photo
            </button>
            <input type="file" name="profilePhoto" id="photoInput" accept="image/*" hidden onchange="this.form.submit()" />
          </form>
        </div>

        <div class="profile-details">
          <form method="POST" action="profile.php" id="profileForm">
            <h3 class="section-title">Informations personnelles</h3>
            <div class="form-group">
              <label for="fullName">Nom complet :</label>
              <input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($user['fullname']); ?>" required />
            </div>
            <div class="form-group">
              <label for="birthdate">Date de naissance :</label>
              <input type="date" id="birthdate" name="birthdate" value="<?php echo $user['date_naissance']; ?>" />
            </div>
            <div class="form-group">
              <label for="email">Adresse e-mail :</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />
              <p class="hint">Utilisée pour les rappels de rendez-vous</p>
            </div>
            <div class="form-group">
              <label for="phone">Numéro de téléphone :</label>
              <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['telephone']); ?>" />
              <p class="hint">Utilisé pour les rappels par SMS</p>
            </div>

            <h3 class="section-title">Préférences de notifications</h3>
            <div class="checkbox-group">
              <input type="checkbox" id="emailNotifs" name="emailNotifs" <?php echo $user['email_notifs'] ? 'checked' : ''; ?> />
              <label for="emailNotifs">Notifications par e-mail</label>
            </div>
            <div class="checkbox-group">
              <input type="checkbox" id="smsNotifs" name="smsNotifs" <?php echo $user['sms_notifs'] ? 'checked' : ''; ?> />
              <label for="smsNotifs">Notifications par SMS</label>
            </div>

            <div class="form-group">
              <label for="reminderTiming">Rappels par défaut :</label>
              <select id="reminderTiming" name="reminderTiming">
                <option value="15" <?php echo $user['reminder_timing'] == 15 ? 'selected' : ''; ?>>15 minutes avant</option>
                <option value="30" <?php echo $user['reminder_timing'] == 30 ? 'selected' : ''; ?>>30 minutes avant</option>
                <option value="60" <?php echo $user['reminder_timing'] == 60 ? 'selected' : ''; ?>>1 heure avant</option>
                <option value="120" <?php echo $user['reminder_timing'] == 120 ? 'selected' : ''; ?>>2 heures avant</option>
                <option value="1440" <?php echo $user['reminder_timing'] == 1440 ? 'selected' : ''; ?>>1 jour avant</option>
              </select>
            </div>

            <button type="submit" name="saveProfile" class="save-profile" id="saveProfileBtn">
              Enregistrer les modifications
            </button>
          </form>

          <h3 class="section-title">Contacts d'urgence</h3>
          <p>
            Ces personnes recevront également les rappels de vos rendez-vous
            importants.
          </p>

          <div class="contact-cards" id="contactsList">
            <?php if (!empty($contacts)): ?>
              <?php foreach ($contacts as $contact): ?>
                <div class="contact-card">
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                    <button type="submit" name="deleteContact" class="remove-contact">&times;</button>
                  </form>
                  <div class="contact-name"><?php echo htmlspecialchars($contact['nom']); ?></div>
                  <div class="contact-relation"><?php echo htmlspecialchars($contact['relation']); ?></div>
                  <div class="contact-info"><?php echo htmlspecialchars($contact['telephone']); ?></div>
                  <div class="contact-info"><?php echo htmlspecialchars($contact['email']); ?></div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p>Aucun contact d'urgence enregistré.</p>
            <?php endif; ?>
          </div>

          <button type="button" class="add-contact-btn" id="addContactBtn">
            + Ajouter un contact
          </button>
        </div>
      </div>
    </main>

    <div class="modal" id="contactModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title">Ajouter un contact</h3>
          <button type="button" class="close-modal" id="closeContactModal">&times;</button>
        </div>
        <form id="contactForm" method="POST" action="profile.php">
          <div class="form-group">
            <label for="contactName">Nom complet :</label>
            <input
              type="text"
              id="contactName"
              name="contactName"
              placeholder="Nom du contact"
              required
            />
          </div>
          <div class="form-group">
            <label for="contactRelation">Relation :</label>
            <input
              type="text"
              id="contactRelation"
              name="contactRelation"
              placeholder="Ex: Fils, Épouse, Infirmier, etc."
              required
            />
          </div>
          <div class="form-group">
            <label for="contactPhone">Téléphone :</label>
            <input
              type="tel"
              id="contactPhone"
              name="contactPhone"
              placeholder="Numéro de téléphone"
            />
          </div>
          <div class="form-group">
            <label for="contactEmail">Email :</label>
            <input 
              type="email" 
              id="contactEmail" 
              name="contactEmail" 
              placeholder="Adresse email" 
            />
          </div>
          <div class="checkbox-group">
            <input type="checkbox" id="contactNotify" name="contactNotify" checked />
            <label for="contactNotify">Envoyer les rappels à ce contact</label>
          </div>
          <div class="form-buttons">
            <button type="submit" name="addContact" class="save-profile">Ajouter</button>
          </div>
        </form>
      </div>
    </div>

    <!--footer -->
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
          <p>&copy; 2025 PillCare. Tous droits réservés.</p>
        </div>
      </div>
    </footer>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Gestion du bouton de téléchargement de photo
        const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
        const photoInput = document.getElementById('photoInput');
        
        if(uploadPhotoBtn && photoInput) {
          uploadPhotoBtn.addEventListener('click', function() {
            photoInput.click();
          });
        }
        
        // Gestion du modal d'ajout de contact
        const addContactBtn = document.getElementById('addContactBtn');
        const contactModal = document.getElementById('contactModal');
        const closeContactModal = document.getElementById('closeContactModal');
        
        if(addContactBtn && contactModal) {
          addContactBtn.addEventListener('click', function() {
            contactModal.style.display = 'flex';
          });
        }
        
        if(closeContactModal && contactModal) {
          closeContactModal.addEventListener('click', function() {
            contactModal.style.display = 'none';
          });
        }
        
        // Fermer le modal en cliquant en dehors
        window.addEventListener('click', function(event) {
          if (event.target === contactModal) {
            contactModal.style.display = 'none';
          }
        });
        
        // Gestion du menu utilisateur
        const userBtn = document.getElementById('user-btn');
        const dropdownMenu = document.querySelector('.dropdown-menu');
        
        if(userBtn && dropdownMenu) {
          userBtn.addEventListener('click', function() {
            dropdownMenu.classList.toggle('active');
          });
          
          // Fermer le menu lorsqu'on clique en dehors
          document.addEventListener('click', function(event) {
            if (!event.target.closest('.user-menu')) {
              dropdownMenu.classList.remove('active');
            }
          });
        }
        
        // Gestion du menu mobile
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const topNav = document.querySelector('.top-nav');
        
        if(mobileMenuBtn && topNav) {
          mobileMenuBtn.addEventListener('click', function() {
            topNav.classList.toggle('active');
            mobileMenuBtn.classList.toggle('active');
          });
        }
        
        // Masquer le message de succès après quelques secondes
        const successMessage = document.querySelector('.success-message');
        if(successMessage) {
          setTimeout(function() {
            successMessage.style.opacity = '0';
            setTimeout(function() {
              successMessage.style.display = 'none';
            }, 500);
          }, 3000);
        }
      });
      
      // Fonction pour basculer le thème sombre/clair
      function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        
        // Enregistrer la préférence dans le stockage local
        const isDarkMode = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDarkMode ? 'true' : 'false');
        
        // Mettre à jour l'icône du bouton
        const themeIcon = document.querySelector('.theme-toggle i');
        if(themeIcon) {
          themeIcon.className = isDarkMode ? 'fas fa-sun' : 'fas fa-moon';
        }
        
        // Envoi de la préférence au serveur (option)
        if(isDarkMode) {
          fetch('update_theme.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'theme=dark'
          });
        } else {
          fetch('update_theme.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'theme=light'
          });
        }
      }
      
      // Appliquer le thème sauvegardé au chargement
      document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('darkMode');
        if(savedTheme === 'true') {
          document.body.classList.add('dark-mode');
          const themeIcon = document.querySelector('.theme-toggle i');
          if(themeIcon) {
            themeIcon.className = 'fas fa-sun';
          }
        }
      });
    </script>
  </body>
</html>