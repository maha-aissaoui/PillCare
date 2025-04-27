<?php
// Inclusion du fichier de connexion à la base de données
require_once 'connexion.php';

// Vérification de la session utilisateur
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirection vers la page de connexion si l'utilisateur n'est pas connecté
    header('Location: login.php');
    exit;
}

// Fonction pour récupérer tous les médicaments de l'utilisateur connecté
function getMedicaments($conn, $user_id, $forme = null, $recherche = null) {
    try {
        $sql = "SELECT * FROM medicaments WHERE user_id = :user_id";
        $params = [':user_id' => $user_id];

        // Ajouter le filtre de forme si nécessaire
        if ($forme !== null && $forme !== 'all') {
            $sql .= " AND forme = :forme";
            $params[':forme'] = $forme;
        }

        // Ajouter le filtre de recherche si nécessaire
        if ($recherche !== null && $recherche !== '') {
            $sql .= " AND (nom LIKE :recherche OR dosage LIKE :recherche)";
            $params[':recherche'] = "%$recherche%";
        }

        $sql .= " ORDER BY nom ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        $medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculer les jours restants pour chaque médicament
        foreach ($medicaments as &$medicament) {
            $medicament['jours_restants'] = getJoursRestants($medicament['start_date'], $medicament['end_date']);
        }
        
        return $medicaments;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des médicaments: " . $e->getMessage());
        return [];
    }
}

// Fonction pour ajouter un médicament
function ajouterMedicament($conn, $data) {
    try {
        $stmt = $conn->prepare("INSERT INTO medicaments (nom, dosage, forme, quantite, horaires, couleur, user_id, start_date, end_date) 
                                VALUES (:nom, :dosage, :forme, :quantite, :horaires, :couleur, :user_id, :date_debut, :date_fin)");
        
        $dateDebut = new DateTime();
        $dateFin = clone $dateDebut;
        $dateFin->modify("+{$data['quantite']} days");

        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':dosage', $data['dosage']);
        $stmt->bindParam(':forme', $data['forme']);
        $stmt->bindParam(':quantite', $data['quantite']);
        $stmt->bindParam(':horaires', $data['horaires']);
        $stmt->bindParam(':couleur', $data['couleur']);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindValue(':date_debut', $dateDebut->format('Y-m-d H:i:s'));
        $stmt->bindValue(':date_fin', $dateFin->format('Y-m-d H:i:s'));

        $stmt->execute();
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erreur lors de l'ajout du médicament: " . $e->getMessage());
        return false;
    }
}

// Fonction pour obtenir les jours restants pour un médicament
function getJoursRestants($dateDebut, $dateFin) {
    try {
        if (empty($dateDebut) || empty($dateFin)) return 0;
        
        $aujourdhui = new DateTime();
        $finTraitement = new DateTime($dateFin);
        
        if ($aujourdhui > $finTraitement) {
            return 0;
        }
        
        $interval = $aujourdhui->diff($finTraitement);
        return $interval->days;
    } catch (Exception $e) {
        error_log("Erreur calcul jours restants: " . $e->getMessage());
        return 0;
    }
}

// Fonction pour mettre à jour un médicament
function updateMedicament($conn, $data) {
    try {
        // Vérification que l'utilisateur est bien propriétaire du médicament
        $stmtVerify = $conn->prepare("SELECT COUNT(*) FROM medicaments WHERE id = :id AND user_id = :user_id");
        $stmtVerify->bindParam(':id', $data['id']);
        $stmtVerify->bindParam(':user_id', $data['user_id']);
        $stmtVerify->execute();
        
        if ($stmtVerify->fetchColumn() == 0) {
            // L'utilisateur n'est pas autorisé à modifier ce médicament
            return false;
        }
        
        // Récupérer la date de début existante
        $stmtSelect = $conn->prepare("SELECT date_debut FROM medicaments WHERE id = :id AND user_id = :user_id");
        $stmtSelect->bindParam(':id', $data['id']);
        $stmtSelect->bindParam(':user_id', $data['user_id']);
        $stmtSelect->execute();
        $dateDebutStr = $stmtSelect->fetchColumn();

        if (!$dateDebutStr) {
            throw new Exception("Date de début introuvable pour le médicament.");
        }

        $dateDebut = new DateTime($dateDebutStr);
        $dateFin = clone $dateDebut;
        $dateFin->modify("+{$data['quantite']} days");

        $stmt = $conn->prepare("UPDATE medicaments SET 
                                nom = :nom, 
                                dosage = :dosage, 
                                forme = :forme, 
                                quantite = :quantite, 
                                horaires = :horaires, 
                                couleur = :couleur,
                                date_fin = :date_fin
                                WHERE id = :id AND user_id = :user_id");
        
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':dosage', $data['dosage']);
        $stmt->bindParam(':forme', $data['forme']);
        $stmt->bindParam(':quantite', $data['quantite']);
        $stmt->bindParam(':horaires', $data['horaires']);
        $stmt->bindParam(':couleur', $data['couleur']);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindValue(':date_fin', $dateFin->format('Y-m-d H:i:s'));

        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour du médicament: " . $e->getMessage());
        return false;
    }
}

// Fonction pour supprimer un médicament
function supprimerMedicament($conn, $id, $user_id) {
    try {
        // Vérification que l'utilisateur est bien propriétaire du médicament
        $stmtVerify = $conn->prepare("SELECT COUNT(*) FROM medicaments WHERE id = :id AND user_id = :user_id");
        $stmtVerify->bindParam(':id', $id);
        $stmtVerify->bindParam(':user_id', $user_id);
        $stmtVerify->execute();
        
        if ($stmtVerify->fetchColumn() == 0) {
            // L'utilisateur n'est pas autorisé à supprimer ce médicament
            return false;
        }
        
        $stmt = $conn->prepare("DELETE FROM medicaments WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression du médicament: " . $e->getMessage());
        return false;
    }
}

// Gestion des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Vérifier que l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get':
            $forme = isset($_POST['forme']) ? $_POST['forme'] : null;
            $recherche = isset($_POST['recherche']) ? $_POST['recherche'] : null;
            $medicaments = getMedicaments($conn, $user_id, $forme, $recherche);
            echo json_encode($medicaments);
            break;
            
        case 'add':
            if (!isset($_POST['nom']) || !isset($_POST['dosage']) || !isset($_POST['forme']) || 
                !isset($_POST['quantite']) || !isset($_POST['horaires']) || !isset($_POST['couleur'])) {
                echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
                break;
            }
            
            $data = [
                'nom' => $_POST['nom'],
                'dosage' => $_POST['dosage'],
                'forme' => $_POST['forme'],
                'quantite' => $_POST['quantite'],
                'horaires' => $_POST['horaires'],
                'couleur' => $_POST['couleur'],
                'user_id' => $user_id
            ];
            
            $id = ajouterMedicament($conn, $data);
            if ($id) {
                $data['id'] = $id;
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du médicament']);
            }
            break;
            
            case 'update':
              // Ajout de logs pour le débogage
              error_log('Traitement action update - données reçues: ' . json_encode($_POST));
              
              if (!isset($_POST['id']) || !isset($_POST['nom']) || !isset($_POST['dosage']) || !isset($_POST['forme']) || 
                  !isset($_POST['quantite']) || !isset($_POST['horaires']) || !isset($_POST['couleur'])) {
                  
                  // Identifier quelles données sont manquantes pour un débogage plus précis
                  $missing = [];
                  foreach (['id', 'nom', 'dosage', 'forme', 'quantite', 'horaires', 'couleur'] as $field) {
                      if (!isset($_POST[$field])) $missing[] = $field;
                  }
                  
                  echo json_encode([
                      'success' => false, 
                      'message' => 'Données incomplètes', 
                      'missing' => $missing
                  ]);
                  break;
              }
            
            $data = [
                'id' => $_POST['id'],
                'nom' => $_POST['nom'],
                'dosage' => $_POST['dosage'],
                'forme' => $_POST['forme'],
                'quantite' => $_POST['quantite'],
                'horaires' => $_POST['horaires'],
                'couleur' => $_POST['couleur'],
                'user_id' => $user_id
            ];
            
            $success = updateMedicament($conn, $data);
            echo json_encode(['success' => $success]);
            break;
            
        case 'delete':
            if (!isset($_POST['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID du médicament manquant']);
                break;
            }
            
            $success = supprimerMedicament($conn, $_POST['id'], $user_id);
            echo json_encode(['success' => $success]);
            break;
            
        case 'filter':
            $forme = isset($_POST['forme']) ? $_POST['forme'] : 'all';
            $medicaments = getMedicaments($conn, $user_id, $forme);
            echo json_encode($medicaments);
            break;
            
        case 'search':
            $recherche = isset($_POST['recherche']) ? $_POST['recherche'] : '';
            $medicaments = getMedicaments($conn, $user_id, null, $recherche);
            echo json_encode($medicaments);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mes Médicaments - PillCare</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <!-- Import des fichiers CSS -->
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="header.css" />
    <link rel="stylesheet" href="footer.css" />
    <link rel="stylesheet" href="medicament.css" />
    <style>
      /* Styles pour les jours restants */
      .medication-remaining-days {
        margin-top: 5px;
        font-weight: 500;
        padding: 3px 8px;
        border-radius: 4px;
        display: inline-block;
      }
      
      .jours-critiques {
        background-color: rgba(231, 74, 59, 0.2);
        color: #e74a3b;
      }
      
      .jours-attention {
        background-color: rgba(246, 194, 62, 0.2);
        color: #f6c23e;
      }
      
      .jours-ok {
        background-color: rgba(28, 200, 138, 0.2);
        color: #1cc88a;
      }
    </style>
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
            href="medicament.php"
            class="nav-item active"
            data-page="Medicament"
            >Medicaments</a
          >
          <a href="calendrier.php" class="nav-item" data-page="calendar"
            >Calendrier</a
          >
          <a href="ordonance.php" class="nav-item" data-page="ordonance"
            >Ordonnance</a
          >
          <a href="profile.php" class="nav-item" data-page="profile"
            >Profil</a
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

    <!-- Main Content -->
    <main>
      <div class="container">
        <div class="page-header">
          <h1 class="page-title">Mes Médicaments</h1>
          <button class="btn btn-primary" id="add-medication-btn">
            <i class="fas fa-plus"></i> Ajouter un médicament
          </button>
        </div>

        <div class="search-filter">
          <div class="search-container">
            <i class="fas fa-search"></i>
            <input
              type="text"
              placeholder="Rechercher un médicament..."
              id="search-medication"
            />
          </div>

          <div class="filter-container">
            <button class="filter-btn">
              <i class="fas fa-filter"></i>
              <span>Filtrer</span>
            </button>
            <div class="filter-dropdown" id="filter-dropdown">
              <div class="filter-options">
                <label>Forme du médicament :</label>
                <select id="form-filter">
                  <option value="all">Tous</option>
                  <option value="tablet">Comprimés</option>
                  <option value="capsule">Gélules</option>
                  <option value="liquid">Liquides</option>
                  <option value="injection">Injections</option>
                  <option value="other">Autres</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Les médicaments seront chargés dynamiquement ici -->
        <div class="medications-grid">
          <?php
            // Récupérer les médicaments de l'utilisateur
            $medicaments = getMedicaments($conn, $_SESSION['user_id']);
            
            // Afficher les médicaments sous forme de cartes
            if (!empty($medicaments)) {
                foreach ($medicaments as $medicament) {
                    // Convertir les horaires stockés en format JSON pour l'affichage
                    $horaires = json_decode($medicament['horaires'], true);
                    $horaireTexte = '';
                    
                    if (!empty($horaires)) {
                        $moments = ['Matin', 'Midi', 'Après-midi', 'Soir', 'Coucher'];
                        $heures = [];
                        
                        foreach ($moments as $index => $moment) {
                            if (isset($horaires[$index]) && $horaires[$index]) {
                                $heures[] = $moment;
                            }
                        }
                        
                        $horaireTexte = implode(', ', $heures);
                    }
                    
                    // Déterminer l'icône en fonction de la forme du médicament
                    $icone = 'pill';
                    switch ($medicament['forme']) {
                        case 'tablet': $icone = 'tablet'; break;
                        case 'capsule': $icone = 'capsule'; break;
                        case 'liquid': $icone = 'flask'; break;
                        case 'injection': $icone = 'syringe'; break;
                        default: $icone = 'pills'; break;
                    }
                    
                    // Obtenir le nombre de jours restants
                    $joursRestants = $medicament['jours_restants'];
                    $joursRestantsClass = $joursRestants <= 3 ? 'jours-critiques' : ($joursRestants <= 7 ? 'jours-attention' : 'jours-ok');
          ?>
                    <div class="medication-card" data-id="<?php echo $medicament['id']; ?>">
                        <div class="medication-header" style="background-color: <?php echo $medicament['couleur']; ?>">
                            <div class="medication-icon">
                                <i class="fas fa-<?php echo $icone; ?>"></i>
                            </div>
                            <div class="medication-actions">
                                <button class="edit-btn"><i class="fas fa-edit"></i></button>
                                <button class="delete-btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="medication-body">
                            <h3 class="medication-name"><?php echo htmlspecialchars($medicament['nom']); ?></h3>
                            <p class="medication-dosage"><?php echo htmlspecialchars($medicament['dosage']); ?></p>
                            <p class="medication-quantity">Quantité: <span><?php echo $medicament['quantite']; ?></span></p>
                            <p class="medication-time"><?php echo $horaireTexte; ?></p>
                            <p class="medication-remaining-days <?php echo $joursRestantsClass; ?>">
                                Jours restants: <span><?php echo $joursRestants; ?></span>
                            </p>
                        </div>
                    </div>
          <?php
                }
            } else {
          ?>
                <div class="no-medications">
                    <p>Aucun médicament trouvé. Commencez par ajouter votre premier médicament !</p>
                </div>
          <?php
            }
          ?>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <div id="footer-placeholder"></div>

    <!-- Add Medication Modal -->
    <div class="modal" id="add-medication-modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">Ajouter un médicament</h2>
          <button class="close-modal" id="close-modal">&times;</button>
        </div>

        <form class="modal-form" id="add-medication-form">
          <input type="hidden" name="action" value="add">
          <div class="form-group">
            <label for="medication-name">Nom du médicament</label>
            <input type="text" id="medication-name" name="nom" required />
          </div>

          <div class="form-group">
            <label for="medication-dosage">Dosage</label>
            <input
              type="text"
              id="medication-dosage"
              name="dosage"
              placeholder="ex: 500mg"
              required
            />
          </div>

          <div class="form-group">
            <label for="medication-form">Forme</label>
            <select id="medication-form" name="forme" required>
              <option value="tablet">Comprimé</option>
              <option value="capsule">Gélule</option>
              <option value="liquid">Liquide</option>
              <option value="injection">Injection</option>
              <option value="other">Autre</option>
            </select>
          </div>

          <div class="form-group">
            <label for="medication-qty">Quantité totale</label>
            <input type="number" id="medication-qty" name="quantite" min="1" required />
          </div>

          <div class="form-group">
            <label>Horaires</label>
            <div class="time-slots">
              <span class="time-slot" data-value="0">Matin</span>
              <span class="time-slot" data-value="1">Midi</span>
              <span class="time-slot" data-value="2">Après-midi</span>
              <span class="time-slot" data-value="3">Soir</span>
              <span class="time-slot" data-value="4">Coucher</span>
            </div>
            <input type="hidden" id="medication-schedule" name="horaires" value="[]">
          </div>

          <div class="form-group">
            <label for="medication-color">Couleur</label>
            <select id="medication-color" name="couleur" required>
              <option value="#4e73df">Bleu</option>
              <option value="#1cc88a">Vert</option>
              <option value="#e74a3b">Rouge</option>
              <option value="#f6c23e">Jaune</option>
              <option value="#36b9cc">Cyan</option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-cancel" id="cancel-add">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary">Ajouter</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Medication Modal -->
    <div class="modal" id="edit-medication-modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">Modifier un médicament</h2>
          <button class="close-modal" id="close-edit-modal">&times;</button>
        </div>

        <form class="modal-form" id="edit-medication-form">
          <input type="hidden" name="action" value="update">
          <input type="hidden" id="edit-medication-id" name="id">
          <div class="form-group">
            <label for="edit-medication-name">Nom du médicament</label>
            <input type="text" id="edit-medication-name" name="nom" required />
          </div>

          <div class="form-group">
            <label for="edit-medication-dosage">Dosage</label>
            <input
              type="text"
              id="edit-medication-dosage"
              name="dosage"
              placeholder="ex: 500mg"
              required
            />
          </div>

          <div class="form-group">
            <label for="edit-medication-form">Forme</label>
            <select id="edit-medication-form" name="forme" required>
              <option value="tablet">Comprimé</option>
              <option value="capsule">Gélule</option>
              <option value="liquid">Liquide</option>
              <option value="injection">Injection</option>
              <option value="other">Autre</option>
            </select>
          </div>

          <div class="form-group">
            <label for="edit-medication-qty">Quantité totale</label>
            <input type="number" id="edit-medication-qty" name="quantite" min="1" required />
          </div>

          <div class="form-group">
            <label>Horaires</label>
            <div class="time-slots">
              <span class="edit-time-slot" data-value="0">Matin</span>
              <span class="edit-time-slot" data-value="1">Midi</span>
              <span class="edit-time-slot" data-value="2">Après-midi</span>
              <span class="edit-time-slot" data-value="3">Soir</span>
              <span class="edit-time-slot" data-value="4">Coucher</span>
            </div>
            <input type="hidden" id="edit-medication-schedule" name="horaires" value="[]">
          </div>

          <div class="form-group">
            <label for="edit-medication-color">Couleur</label>
            <select id="edit-medication-color" name="couleur" required>
              <option value="#4e73df">Bleu</option>
              <option value="#1cc88a">Vert</option>
              <option value="#e74a3b">Rouge</option>
              <option value="#f6c23e">Jaune</option>
              <option value="#36b9cc">Cyan</option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-cancel" id="cancel-edit">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
          </div>
        </form>
      </div>
    </div>

    
    <script src="medicament2.js"></script>
    
  </body>
</html>