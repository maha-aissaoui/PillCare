<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PillCare - Inscription</title>
    <link rel="stylesheet" href="login.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
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
                <div class="tabs">
                    <div class="tab" onclick="window.location.href='login.php'">Connexion</div>
                    <div class="tab active">Inscription</div>
                </div>

                <!-- Inscription -->
                <div class="form-container" id="signup-form">
                    <?php if (!empty($signup_error)): ?>
                        <div class="error-message"><?php echo $signup_error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($signup_success)): ?>
                        <div class="success-message"><?php echo $signup_success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="index.php?action=register">
                        <div class="form-group">
                            <label for="signup-name">Nom complet</label>
                            <input type="text" id="signup-name" name="signup-name" 
                                placeholder="Votre nom complet" value="<?php echo isset($fullname) ? $fullname : ''; ?>" />
                        </div>
                        
                        <div class="form-group">
                            <label for="signup-email">Email</label>
                            <input type="email" id="signup-email" name="signup-email" 
                                placeholder="exemple@email.com" value="<?php echo isset($email) ? $email : ''; ?>" />
                        </div>
                        
                        <div class="form-group">
                            <label for="signup-password">Mot de passe</label>
                            <input type="password" id="signup-password" name="signup-password" 
                                placeholder="Créer un mot de passe" />
                        </div>
                        
                        <div class="form-group">
                            <label for="signup-confirm-password">Confirmer le mot de passe</label>
                            <input type="password" id="signup-confirm-password" name="signup-confirm-password" 
                                placeholder="Confirmer votre mot de passe" />
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="signup-terms" name="signup-terms" required />
                            <label for="signup-terms">J'accepte les <a href="#">conditions d'utilisation</a> et la <a href="#">politique de confidentialité</a></label>
                        </div>
                        
                        <button type="submit" name="signup" class="btn" id="signup-btn">S'inscrire</button>
                    </form>
                    
                    <div class="separator">ou s'inscrire avec</div>

                    <div class="social-login">
                        <button class="social-btn google">
                            <i class="fab fa-google"></i> Google
                        </button>
                        <button class="social-btn facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </button>
                        <button class="social-btn linkedin">
                            <i class="fab fa-linkedin-in"></i> LinkedIn
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="auth.js"></script>
</body>
</html>