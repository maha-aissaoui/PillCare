<?php
// Démarrer la session
session_start();

// Inclure le fichier de connexion à la base de données
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour supprimer une ordonnance']);
    exit();
}

// Tableau pour la réponse
$response = ['success' => false, 'message' => ''];

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si l'ID de l'ordonnance est fourni
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $ordonnance_id = intval($_POST['id']);
        $user_id = $_SESSION['user_id'];
        
        try {
            // D'abord, récupérer le chemin du fichier à supprimer
            $sql_select = "SELECT fichier_path FROM ordonnances WHERE id = :id AND user_id = :user_id";
            $stmt_select = $conn->prepare($sql_select);
            $stmt_select->bindParam(':id', $ordonnance_id, PDO::PARAM_INT);
            $stmt_select->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_select->execute();
            
            $ordonnance = $stmt_select->fetch();
            
            if ($ordonnance) {
                // Supprimer l'enregistrement de la base de données
                $sql_delete = "DELETE FROM ordonnances WHERE id = :id AND user_id = :user_id";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bindParam(':id', $ordonnance_id, PDO::PARAM_INT);
                $stmt_delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                
                if ($stmt_delete->execute()) {
                    // Supprimer le fichier du serveur si possible
                    if (file_exists($ordonnance['fichier_path']) && is_file($ordonnance['fichier_path'])) {
                        unlink($ordonnance['fichier_path']);
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Ordonnance supprimée avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la suppression de l\'ordonnance';
                }
            } else {
                $response['message'] = 'Ordonnance non trouvée ou vous n\'êtes pas autorisé à la supprimer';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'ID d\'ordonnance invalide';
    }
} else {
    $response['message'] = 'Méthode non autorisée';
}

// Renvoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response);
?>