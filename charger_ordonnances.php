<?php
// Démarrer la session
session_start();

// Inclure le fichier de connexion à la base de données
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour accéder à vos ordonnances']);
    exit();
}

// Tableau pour la réponse
$response = ['success' => false, 'ordonnances' => [], 'message' => ''];

try {
    // Préparation de la requête SQL pour récupérer les ordonnances de l'utilisateur
    $sql = "SELECT id, titre, date, medecin, description, fichier_path, fichier_type, created_at, updated_at 
            FROM ordonnances 
            WHERE user_id = :user_id 
            ORDER BY date DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Liaison du paramètre user_id
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    
    // Exécution de la requête
    $stmt->execute();
    
    // Récupération des résultats
    $ordonnances = $stmt->fetchAll();
    
    if (count($ordonnances) > 0) {
        $response['success'] = true;
        $response['ordonnances'] = $ordonnances;
    } else {
        $response['success'] = true;
        $response['message'] = 'Aucune ordonnance trouvée';
    }
} catch (PDOException $e) {
    $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
}

// Renvoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response);
?>