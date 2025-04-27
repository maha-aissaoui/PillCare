<?php
// Démarrer la session pour gérer les données utilisateur
session_start();

// Configuration de la base de données
require_once('connexion.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si non connecté
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion des Ordonnances</title>
    <link rel="stylesheet" href="ordonance.css" />
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="footer.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
    <header class="header">
        <div class="header-bg"></div>
        <div class="wave-container"></div>
        <div class="header-content">
            <div class="logo-container">
                <img src="logo.png" alt="PillCare Logo" class="logo-img" />
            </div>
            <nav class="top-nav">
                <a href="index.php" class="nav-item" data-page="home">Accueil</a>
                <a href="medicament.php" class="nav-item" data-page="Medicament">Medicaments</a>
                <a href="calendrier.php" class="nav-item" data-page="calendar">Calendrier</a>
                <a href="ordonance.php" class="nav-item active" data-page="ordonance">ordonance</a>
                <a href="profile.php" class="nav-item" data-page="profile">profile</a>
            </nav>
            <div class="user-menu">
                <button id="user-btn"><i class="fas fa-user-circle"></i></button>
                <div class="dropdown-menu">
                    <a href="settings.php"><i class="fas fa-cog"></i> Paramètres</a>
                    <a href="#" class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i> Thème</a>
                    <a href="logout.php" id="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
            <button class="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <div class="container">
        <div class="ordonnance-section">
            <h2>Ajouter une nouvelle ordonnance</h2>
            <div id="alertMessage"></div>

            <form id="ordonnanceForm" class="ordonnance-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titre">Titre de l'ordonnance</label>
                    <input type="text" id="titre" name="titre" required />
                </div>

                <div class="form-group">
                    <label for="date">Date de l'ordonnance</label>
                    <input type="date" id="date" name="date" required />
                </div>

                <div class="form-group">
                    <label for="medecin">Nom du médecin</label>
                    <input type="text" id="medecin" name="medecin" required />
                </div>

                <div class="form-group">
                    <label for="description">Description (optionnelle)</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="fichier">Fichier de l'ordonnance (PDF, JPG, PNG)</label>
                    <input type="file" id="fichier" name="fichier" accept=".pdf,.jpg,.jpeg,.png" required />
                    <div id="filePreview" class="file-preview"></div>
                </div>

                <button type="submit" class="btn">Enregistrer l'ordonnance</button>
            </form>
        </div>

        <div class="ordonnance-section">
            <h2>Mes ordonnances</h2>
            <div id="ordonnancesList" class="ordonnance-list">
                <!-- La liste des ordonnances sera chargée dynamiquement -->
            </div>
        </div>
    </div>

    <?php include 'footer.html'; ?>

    <script src="ordonance.js"></script>
</body>
</html>