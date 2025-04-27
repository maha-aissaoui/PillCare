// Attendre que le DOM soit complètement chargé
document.addEventListener("DOMContentLoaded", function () {
  // Sélectionner les éléments du formulaire
  const loginForm = document.getElementById("login-form");
  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("password");
  const emailError = document.getElementById("email-error");
  const passwordError = document.getElementById("password-error");
  const togglePasswordBtn = document.querySelector(".toggle-password");

  // Fonction pour valider l'email
  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Fonction pour valider le mot de passe (minimum 6 caractères)
  function validatePassword(password) {
    return password.length >= 6;
  }

  // Fonction pour afficher les erreurs
  function showError(input, errorElement, message) {
    input.classList.add("invalid");
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = "block";
    }
    return false;
  }

  // Fonction pour masquer les erreurs
  function hideError(input, errorElement) {
    input.classList.remove("invalid");
    if (errorElement) {
      errorElement.style.display = "none";
    }
    return true;
  }

  // Validation de l'email en temps réel
  emailInput.addEventListener("input", function () {
    if (validateEmail(emailInput.value)) {
      hideError(emailInput, emailError);
    } else {
      showError(
        emailInput,
        emailError,
        "Veuillez entrer une adresse email valide"
      );
    }
  });

  // Validation du mot de passe en temps réel
  passwordInput.addEventListener("input", function () {
    if (validatePassword(passwordInput.value)) {
      hideError(passwordInput, passwordError);
    } else {
      showError(
        passwordInput,
        passwordError,
        "Le mot de passe doit contenir au moins 6 caractères"
      );
    }
  });

  // Afficher/masquer le mot de passe
  togglePasswordBtn.addEventListener("click", function () {
    const type =
      passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);

    // Modifier l'icône
    const icon = this.querySelector("i");
    if (type === "password") {
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
    } else {
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
    }
  });

  // Validation du formulaire lors de la soumission
  loginForm.addEventListener("submit", function (event) {
    let isValid = true;

    // Valider l'email
    if (!validateEmail(emailInput.value)) {
      isValid = showError(
        emailInput,
        emailError,
        "Veuillez entrer une adresse email valide"
      );
    } else {
      hideError(emailInput, emailError);
    }

    // Valider le mot de passe
    if (!validatePassword(passwordInput.value)) {
      isValid = showError(
        passwordInput,
        passwordError,
        "Le mot de passe doit contenir au moins 6 caractères"
      );
    } else {
      hideError(passwordInput, passwordError);
    }

    // Si le formulaire n'est pas valide, empêcher la soumission
    if (!isValid) {
      event.preventDefault();
    }
  });

  // Gestion de l'authentification avec Google
  const googleBtn = document.querySelector(".btn-google");
  if (googleBtn) {
    googleBtn.addEventListener("click", function () {
      // Ici, vous pouvez rediriger vers une page PHP qui gère l'authentification Google
      window.location.href = "google_auth.php";
    });
  }

  // Gestion de l'authentification avec Facebook
  const facebookBtn = document.querySelector(".btn-facebook");
  if (facebookBtn) {
    facebookBtn.addEventListener("click", function () {
      // Ici, vous pouvez rediriger vers une page PHP qui gère l'authentification Facebook
      window.location.href = "facebook_auth.php";
    });
  }
});
