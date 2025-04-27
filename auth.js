document.addEventListener("DOMContentLoaded", function () {
  console.log("Script auth.js chargé");

  // Fonction qui vérifie si un élément existe avant d'ajouter un événement
  function addEventIfExists(elementId, eventType, callback) {
    const element = document.getElementById(elementId);
    if (element) {
      element.addEventListener(eventType, callback);
      console.log(`✓ Événement ajouté à ${elementId}`);
    } else {
      console.error(`✗ Élément ${elementId} non trouvé`);
    }
  }

  // Gestion des onglets
  addEventIfExists("login-tab", "click", function () {
    const loginTab = document.getElementById("login-tab");
    const signupTab = document.getElementById("signup-tab");
    const loginForm = document.getElementById("login-form");
    const signupForm = document.getElementById("signup-form");

    if (loginTab && signupTab && loginForm && signupForm) {
      history.pushState(null, "", "index.php");
      loginTab.classList.add("active");
      signupTab.classList.remove("active");
      loginForm.classList.remove("hidden");
      signupForm.classList.add("hidden");
    }
  });

  addEventIfExists("signup-tab", "click", function () {
    const loginTab = document.getElementById("login-tab");
    const signupTab = document.getElementById("signup-tab");
    const loginForm = document.getElementById("login-form");
    const signupForm = document.getElementById("signup-form");

    if (loginTab && signupTab && loginForm && signupForm) {
      history.pushState(null, "", "index.php?signup");
      signupTab.classList.add("active");
      loginTab.classList.remove("active");
      signupForm.classList.remove("hidden");
      loginForm.classList.add("hidden");
    }
  });

  // Validation formulaire de connexion
  addEventIfExists("login-btn", "click", function (e) {
    const form = this.closest("form");
    if (form) {
      const emailInput = document.getElementById("login-email");
      const passwordInput = document.getElementById("login-password");

      if (!emailInput || !emailInput.value.trim()) {
        e.preventDefault();
        alert("Veuillez saisir votre adresse email");
        if (emailInput) emailInput.focus();
        return false;
      }

      if (!passwordInput || !passwordInput.value) {
        e.preventDefault();
        alert("Veuillez saisir votre mot de passe");
        if (passwordInput) passwordInput.focus();
        return false;
      }
    }
  });

  // Validation formulaire d'inscription
  addEventIfExists("signup-btn", "click", function (e) {
    const form = this.closest("form");
    if (form) {
      const nameInput = document.getElementById("signup-name");
      const emailInput = document.getElementById("signup-email");
      const passwordInput = document.getElementById("signup-password");
      const confirmPasswordInput = document.getElementById(
        "signup-confirm-password"
      );

      if (!nameInput || !nameInput.value.trim()) {
        e.preventDefault();
        alert("Veuillez saisir votre nom complet");
        if (nameInput) nameInput.focus();
        return false;
      }

      if (!emailInput || !emailInput.value.trim()) {
        e.preventDefault();
        alert("Veuillez saisir votre adresse email");
        if (emailInput) emailInput.focus();
        return false;
      }

      if (!passwordInput || !passwordInput.value) {
        e.preventDefault();
        alert("Veuillez créer un mot de passe");
        if (passwordInput) passwordInput.focus();
        return false;
      }

      if (
        !confirmPasswordInput ||
        passwordInput.value !== confirmPasswordInput.value
      ) {
        e.preventDefault();
        alert("Les mots de passe ne correspondent pas");
        if (confirmPasswordInput) confirmPasswordInput.focus();
        return false;
      }
    }
  });

  // Boutons réseaux sociaux
  document.querySelectorAll(".social-btn").forEach(function (button) {
    if (button) {
      button.addEventListener("click", function () {
        alert(
          `Connexion avec ${this.textContent.trim()} - Fonctionnalité à implémenter`
        );
      });
    }
  });

  console.log("Initialisation auth.js terminée");
});
