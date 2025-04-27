<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PillCare - Connexion</title>
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
                    <div class="tab active">Connexion</div>
                    <div class="tab" onclick="window.location.href='index.php?action=register'">Inscription</div>
                </div>

                <!-- Connexion -->
                <div class="form-container" id="login-form">
                    <?php if (!empty($login_error)): ?>
                        <div class="error-message"><?php echo $login_error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="index.php">
                        <div class="form-group">
                            <label for="login-email">Email</label>
                            <input type="email" id="login-email" name="login-email" 
                                placeholder="exemple@email.com" value="<?php echo $saved_email; ?>" />
                        </div>
                        <div class="form-group">
                            <label for="login-password">Mot de passe</label>
                            <input type="password" id="login-password" name="login-password" 
                                placeholder="Votre mot de passe" />
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="remember-me" name="remember-me" />
                            <label for="remember-me">Se souvenir de moi</label>
                        </div>
                        <div class="forgot-password">
                            <a href="reset-password.php">Mot de passe oublié?</a>
                        </div>
                        <button type="submit" name="login" class="btn" id="login-btn">Se connecter</button>
                    </form>

                    <div class="separator">ou continuer avec</div>

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