<?php
// Démarrer la session
session_start();

// Configuration de la base de données
require_once('connexion.php');

// Vérifier la connexion (la vérification est gérée dans connexion.php avec PDO)

// Si l'utilisateur est déjà connecté, le rediriger
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// ----- GESTION CONNEXION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['login-email']);
    $password = $_POST['login-password'];

    if (empty($email) || empty($password)) {
        $login_error = "Veuillez remplir tous les champs";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $email;

                    if (isset($_POST['remember-me'])) {
                        setcookie("user_email", $email, time() + (86400 * 30), "/");
                    }

                    header("Location: index.php");
                    exit;
                } else {
                    $login_error = "Mot de passe incorrect";
                }
            } else {
                $login_error = "Email non trouvé";
            }
        } catch (PDOException $e) {
            $login_error = "Erreur lors de la connexion: " . $e->getMessage();
        }
    }
}

// ----- GESTION INSCRIPTION -----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $fullname = isset($_POST['signup-name']) ? trim($_POST['signup-name']) : '';
    $email = isset($_POST['signup-email']) ? trim($_POST['signup-email']) : '';
    $password = isset($_POST['signup-password']) ? trim($_POST['signup-password']) : '';
    $confirm_password = isset($_POST['signup-confirm-password']) ? trim($_POST['signup-confirm-password']) : '';

    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $signup_error = "Veuillez remplir tous les champs";
    } elseif ($password !== $confirm_password) {
        $signup_error = "Les mots de passe ne correspondent pas";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {
                $signup_error = "Cet email est déjà utilisé";
            } else {
                // Hasher le mot de passe et insérer
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
                
                if ($insert->execute([$fullname, $email, $hashed_password])) {
                    $signup_success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                    $fullname = $email = "";
                } else {
                    $signup_error = "Erreur lors de l'inscription. Veuillez réessayer.";
                }
            }
        } catch (PDOException $e) {
            $signup_error = "Erreur lors de l'inscription: " . $e->getMessage();
        }
    }
}

// ----- Récupérer email si "se souvenir de moi" -----
$saved_email = isset($_COOKIE['user_email']) ? $_COOKIE['user_email'] : "";

// Pas besoin de fermer la connexion avec PDO
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PillCare - Authentification</title>
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
                    <div class="tab <?php echo !isset($_GET['signup']) ? 'active' : ''; ?>" id="login-tab">Connexion</div>
                    <div class="tab <?php echo isset($_GET['signup']) ? 'active' : ''; ?>" id="signup-tab">Inscription</div>
                </div>

                <!-- Connexion -->
                <div class="form-container <?php echo isset($_GET['signup']) ? 'hidden' : ''; ?>" id="login-form">
                    <?php if (!empty($login_error)): ?>
                        <div class="error-message"><?php echo $login_error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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

                <!-- Inscription -->
                <div class="form-container <?php echo !isset($_GET['signup']) ? 'hidden' : ''; ?>" id="signup-form">
                    <?php if (!empty($signup_error)): ?>
                        <div class="error-message"><?php echo $signup_error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($signup_success)): ?>
                        <div class="success-message"><?php echo $signup_success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?signup">
                        <div class="form-group">
                            <label for="signup-name">Nom complet</label>
                            <input type="text" id="signup-name" name="signup-name" 
                                placeholder="Votre nom" value="<?php echo isset($fullname) ? $fullname : ''; ?>" />
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
                        <button type="submit" name="signup" class="btn" id="signup-btn">S'inscrire</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        console.log("Script inline chargé");
        
        // Onglet connexion
        const loginTab = document.getElementById("login-tab");
        if (loginTab) {
            console.log("Login tab trouvé");
            loginTab.addEventListener("click", function() {
                console.log("Login tab cliqué");
                const loginForm = document.getElementById("login-form");
                const signupForm = document.getElementById("signup-form");
                const signupTab = document.getElementById("signup-tab");
                
                if (loginForm && signupForm && signupTab) {
                    history.pushState(null, "", "index.php");
                    loginForm.classList.remove("hidden");
                    signupForm.classList.add("hidden");
                    this.classList.add("active");
                    signupTab.classList.remove("active");
                } else {
                    console.error("Éléments manquants pour l'action du login tab");
                }
            });
        } else {
            console.error("Login tab non trouvé");
        }
        
        // Onglet inscription
        const signupTab = document.getElementById("signup-tab");
        if (signupTab) {
            console.log("Signup tab trouvé");
            signupTab.addEventListener("click", function() {
                console.log("Signup tab cliqué");
                const loginForm = document.getElementById("login-form");
                const signupForm = document.getElementById("signup-form");
                const loginTab = document.getElementById("login-tab");
                
                if (loginForm && signupForm && loginTab) {
                    history.pushState(null, "", "index.php?signup");
                    signupForm.classList.remove("hidden");
                    loginForm.classList.add("hidden");
                    this.classList.add("active");
                    loginTab.classList.remove("active");
                } else {
                    console.error("Éléments manquants pour l'action du signup tab");
                }
            });
        } else {
            console.error("Signup tab non trouvé");
        }
        
        // Validation côté client pour le formulaire de connexion
        const loginForm = document.querySelector('form[name="login"]');
        if (loginForm) {
            loginForm.addEventListener("submit", function(e) {
                const emailInput = document.getElementById("login-email");
                const passwordInput = document.getElementById("login-password");
                
                if (!emailInput.value.trim()) {
                    e.preventDefault();
                    alert("Veuillez saisir votre adresse email");
                    emailInput.focus();
                    return false;
                }
                
                if (!passwordInput.value) {
                    e.preventDefault();
                    alert("Veuillez saisir votre mot de passe");
                    passwordInput.focus();
                    return false;
                }
            });
        }
        
        // Validation côté client pour le formulaire d'inscription
        const signupForm = document.querySelector('form[name="signup"]');
        if (signupForm) {
            signupForm.addEventListener("submit", function(e) {
                const nameInput = document.getElementById("signup-name");
                const emailInput = document.getElementById("signup-email");
                const passwordInput = document.getElementById("signup-password");
                const confirmPasswordInput = document.getElementById("signup-confirm-password");
                
                if (!nameInput.value.trim()) {
                    e.preventDefault();
                    alert("Veuillez saisir votre nom complet");
                    nameInput.focus();
                    return false;
                }
                
                if (!emailInput.value.trim()) {
                    e.preventDefault();
                    alert("Veuillez saisir votre adresse email");
                    emailInput.focus();
                    return false;
                }
                
                if (!passwordInput.value) {
                    e.preventDefault();
                    alert("Veuillez créer un mot de passe");
                    passwordInput.focus();
                    return false;
                }
                
                if (passwordInput.value !== confirmPasswordInput.value) {
                    e.preventDefault();
                    alert("Les mots de passe ne correspondent pas");
                    confirmPasswordInput.focus();
                    return false;
                }
            });
        }
        
        // Boutons de connexion sociale
        const socialButtons = document.querySelectorAll(".social-btn");
        socialButtons.forEach(function(button) {
            button.addEventListener("click", function() {
                alert(`Connexion avec ${this.textContent.trim()} - Fonctionnalité à implémenter`);
            });
        });
        
        console.log("Initialisation terminée");
    });
    </script>
</body>
</html>