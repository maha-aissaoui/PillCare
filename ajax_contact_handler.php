<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Connexion à la base de données
require_once 'connexion.php';

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Valider et sanitiser les entrées
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Vérifier si un champ est vide
function is_empty($field, $name) {
    if (empty($field)) {
        echo json_encode(['success' => false, 'message' => "Le champ $name est requis"]);
        exit();
    }
    return false;
}

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Ajouter un contact
    if ($action === 'add_contact') {
        // Valider les champs requis
        is_empty($_POST['name'], 'nom');
        is_empty($_POST['relation'], 'relation');
        
        // Sanitiser les entrées
        $name = sanitize_input($_POST['name']);
        $relation = sanitize_input($_POST['relation']);
        $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
        $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
        $notify = isset($_POST['notify']) ? (int)$_POST['notify'] : 0;
        
        // Insertion dans la base de données
        $stmt = $conn->prepare("INSERT INTO emergency_contacts (user_id, name, relation, phone, email, notify) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $user_id, $name, $relation, $phone, $email, $notify);
        
        if ($stmt->execute()) {
            $contact_id = $conn->insert_id;
            
            // Retourner les données du contact avec son ID
            $contact = [
                'id' => $contact_id,
                'name' => $name,
                'relation' => $relation,
                'phone' => $phone,
                'email' => $email,
                'notify' => $notify
            ];
            
            echo json_encode(['success' => true, 'contact' => $contact]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du contact: ' . $conn->error]);
        }
    }
    
    // Mettre à jour un contact
    else if ($action === 'update_contact') {
        // Valider les champs requis
        is_empty($_POST['contact_id'], 'ID du contact');
        is_empty($_POST['name'], 'nom');
        is_empty($_POST['relation'], 'relation');
        
        // Sanitiser les entrées
        $contact_id = (int)$_POST['contact_id'];
        $name = sanitize_input($_POST['name']);
        $relation = sanitize_input($_POST['relation']);
        $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
        $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
        $notify = isset($_POST['notify']) ? (int)$_POST['notify'] : 0;
        
        // Vérifier que le contact appartient bien à l'utilisateur
        $check_stmt = $conn->prepare("SELECT id FROM emergency_contacts WHERE id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $contact_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Contact non trouvé ou non autorisé']);
            exit();
        }
        
        // Mise à jour dans la base de données
        $update_stmt = $conn->prepare("UPDATE emergency_contacts SET name = ?, relation = ?, phone = ?, email = ?, notify = ? WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("ssssiii", $name, $relation, $phone, $email, $notify, $contact_id, $user_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du contact: ' . $conn->error]);
        }
    }
    
    // Supprimer un contact
    else if ($action === 'delete_contact') {
        // Valider les champs requis
        is_empty($_POST['contact_id'], 'ID du contact');
        
        // Sanitiser les entrées
        $contact_id = (int)$_POST['contact_id'];
        
        // Vérifier que le contact appartient bien à l'utilisateur
        $check_stmt = $conn->prepare("SELECT id FROM emergency_contacts WHERE id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $contact_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Contact non trouvé ou non autorisé']);
            exit();
        }
        
        // Suppression dans la base de données
        $delete_stmt = $conn->prepare("DELETE FROM emergency_contacts WHERE id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $contact_id, $user_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du contact: ' . $conn->error]);
        }
    }
    
    else {
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
}

// Fermer la connexion à la base de données
$conn->close();
?>