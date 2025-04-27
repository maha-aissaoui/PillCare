<?php
// Démarrer la session
session_start();

// Inclure le fichier de connexion à la base de données
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour accéder à cette ordonnance']);
    exit();
}

// Tableau pour la réponse
$response = ['success' => false, 'ordonnance' => null, 'message' => ''];

// Vérifier si l'ID de l'ordonnance est fourni
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $ordonnance_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    try {
        // Préparation de la requête SQL pour récupérer l'ordonnance spécifique
        $sql = "SELECT id, titre, date, medecin, description, fichier_path, fichier_type, created_at, updated_at 
                FROM ordonnances 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $conn->prepare($sql);
        
        // Liaison des paramètres
        $stmt->bindParam(':id', $ordonnance_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        // Exécution de la requête
        $stmt->execute();
        
        // Récupération du résultat
        $ordonnance = $stmt->fetch();
        
        if ($ordonnance) {
            $response['success'] = true;
            $response['ordonnance'] = $ordonnance;
        } else {
            $response['message'] = 'Ordonnance non trouvée ou vous n\'êtes pas autorisé à y accéder';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'ID d\'ordonnance invalide';
}

// Renvoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response);
?>