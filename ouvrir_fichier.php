<?php
// Démarrer la session
session_start();

// Inclure le fichier de connexion à la base de données
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die('Vous devez être connecté pour accéder à ce fichier');
}

// Vérifier si l'ID de l'ordonnance est fourni
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $ordonnance_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    try {
        // Préparation de la requête SQL pour récupérer le fichier
        $sql = "SELECT fichier_path, fichier_type FROM ordonnances WHERE id = :id AND user_id = :user_id";
        
        $stmt = $conn->prepare($sql);
        
        // Liaison des paramètres
        $stmt->bindParam(':id', $ordonnance_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        // Exécution de la requête
        $stmt->execute();
        
        // Récupération du résultat
        $ordonnance = $stmt->fetch();
        
        if ($ordonnance && file_exists($ordonnance['fichier_path'])) {
            // Définir les en-têtes appropriés en fonction du type de fichier
            header('Content-Type: ' . $ordonnance['fichier_type']);
            header('Content-Disposition: inline; filename="' . basename($ordonnance['fichier_path']) . '"');
            header('Content-Length: ' . filesize($ordonnance['fichier_path']));
            
            // Lire et afficher le fichier
            readfile($ordonnance['fichier_path']);
            exit;
        } else {
            die('Fichier non trouvé ou vous n\'êtes pas autorisé à y accéder');
        }
    } catch (PDOException $e) {
        die('Erreur de base de données: ' . $e->getMessage());
    }
} else {
    die('ID d\'ordonnance invalide');
}
?>