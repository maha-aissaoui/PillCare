<?php
// Démarrer la session
session_start();

// Inclure le fichier de connexion à la base de données
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour ajouter une ordonnance']);
    exit();
}

// Tableau pour la réponse
$response = ['success' => false, 'message' => ''];

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $user_id = $_SESSION['user_id'];
    $titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $medecin = isset($_POST['medecin']) ? trim($_POST['medecin']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Validation des champs obligatoires
    if (empty($titre) || empty($date) || empty($medecin)) {
        $response['message'] = 'Veuillez remplir tous les champs obligatoires';
    } else {
        // Gestion du fichier uploadé
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            // Récupérer les informations sur le fichier
            $fichier_nom = $_FILES['fichier']['name'];
            $fichier_tmp = $_FILES['fichier']['tmp_name'];
            $fichier_type = $_FILES['fichier']['type'];
            $fichier_taille = $_FILES['fichier']['size'];
            
            // Vérifier le type de fichier (PDF, JPG, PNG)
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($fichier_type, $allowed_types)) {
                $response['message'] = 'Type de fichier non autorisé. Seuls les fichiers PDF, JPG et PNG sont acceptés.';
            } else if ($fichier_taille > 10485760) { // 10 Mo max
                $response['message'] = 'Le fichier est trop volumineux. Taille maximale: 10 Mo.';
            } else {
                // Créer un nom de fichier unique
                $fichier_extension = pathinfo($fichier_nom, PATHINFO_EXTENSION);
                $fichier_unique = uniqid() . '_' . time() . '.' . $fichier_extension;
                
                // Définir le chemin de destination
                $dossier_upload = 'uploads/ordonnances/';
                
                // Créer le dossier s'il n'existe pas
                if (!is_dir($dossier_upload)) {
                    mkdir($dossier_upload, 0755, true);
                }
                
                $chemin_destination = $dossier_upload . $fichier_unique;
                
                // Déplacer le fichier vers le dossier de destination
                if (move_uploaded_file($fichier_tmp, $chemin_destination)) {
                    try {
                        // Préparation de la requête SQL
                        $sql = "INSERT INTO ordonnances (user_id, titre, date, medecin, description, fichier_path, fichier_type) 
                                VALUES (:user_id, :titre, :date, :medecin, :description, :fichier_path, :fichier_type)";
                        
                        $stmt = $conn->prepare($sql);
                        
                        // Liaison des paramètres
                        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        $stmt->bindParam(':titre', $titre, PDO::PARAM_STR);
                        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
                        $stmt->bindParam(':medecin', $medecin, PDO::PARAM_STR);
                        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                        $stmt->bindParam(':fichier_path', $chemin_destination, PDO::PARAM_STR);
                        $stmt->bindParam(':fichier_type', $fichier_type, PDO::PARAM_STR);
                        
                        // Exécution de la requête
                        if ($stmt->execute()) {
                            $response['success'] = true;
                            $response['message'] = 'Ordonnance ajoutée avec succès';
                        } else {
                            $response['message'] = 'Erreur lors de l\'ajout de l\'ordonnance';
                        }
                    } catch (PDOException $e) {
                        $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Erreur lors du téléchargement du fichier';
                }
            }
        } else {
            $response['message'] = 'Veuillez sélectionner un fichier';
        }
    }
}

// Renvoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response);
?>