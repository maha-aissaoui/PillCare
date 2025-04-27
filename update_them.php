<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Non connecté']));
}

if (isset($_POST['theme']) && ($_POST['theme'] === 'dark' || $_POST['theme'] === 'light')) {
    $theme = $_POST['theme'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("UPDATE users SET theme = :theme WHERE id = :user_id");
        $stmt->bindParam(':theme', $theme);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Paramètre manquant ou invalide']);
}