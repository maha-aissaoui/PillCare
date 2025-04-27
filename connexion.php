<?php
// Paramètres de connexion à la base de données
$host = "localhost";
$dbname = "pillcare_db";
$username = "root"; // à remplacer par votre nom d'utilisateur MySQL
$password = ""; // à remplacer par votre mot de passe MySQL

try {
    // Établir la connexion à la base de données avec PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configurer PDO pour lancer des exceptions en cas d'erreur
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configurer PDO pour retourner les résultats sous forme de tableau associatif
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Désactiver l'émulation des requêtes préparées pour plus de sécurité
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Afficher un message d'erreur si la connexion échoue
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}