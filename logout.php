<?php
// Démarrer la session
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Supprimer le cookie "Se souvenir de moi"
if (isset($_COOKIE['user_email'])) {
    setcookie("user_email", "", time() - 3600, "/");
}

// Rediriger vers la page de connexion
header("Location: index.php");
exit;
?>