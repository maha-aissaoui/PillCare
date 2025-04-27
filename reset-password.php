<?php
// Démarrer la session
session_start();

$error = "";
$success = "";

// Traiter le formulaire de réinitialisation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Validation simple
    if (empty($email)) {
        $error = "Veuillez saisir votre adresse email";
    } else {
        // Ici, vous implémenteriez l'envoi réel d'un email de réinitialisation
        // Pour cet exemple, nous simulons juste un succès
        $success = "Un email de réinitialisation a été envoyé à $email si ce compte existe dans notre système.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PillCare - Réinitialisation du mot de passe</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h1>PillCare</h1>
            <p>
                Votre assistant médical personnel. Simplifiez vos consultations
                médicales et améliorez la communication avec vos professionnels de
                santé.
            </p>

            <div class="feature">
                <div class="feature-icon">✓</div>
                <div>Traduction médicale en temps réel</div>
            </div>
            <div class="feature">
                <div class="feature-icon">✓</div>
                <div>Résumés de consultations automatisés</div>
            </div>
            <div class="feature">
                <div class="feature-icon">✓</div>
                <div>Rappels de rendez-vous et de traitements</div>
            </div>
            <div class="feature">
                <div class="feature-icon">✓</div>
                <div>Sécurité et confidentialité garanties</div>
            </div>
        </div>

        <div class="main">
            <div class="auth-container">
                <h2>Réinitialisation du mot de passe</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php else: ?>
                    <p>Entrez votre adresse email pour recevoir un lien de réinitialisation du mot de passe.</p>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="exemple@email.com" required />
                        </div>
                        <button type="submit" class="btn">Envoyer le lien de réinitialisation</button>
                    </form>
                <?php endif; ?>
                
                <div class="back-link">
                    <a href="index.php">Retour à la page de connexion</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>