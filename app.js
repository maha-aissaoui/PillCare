// app.js - Script JavaScript principal fusionné et optimisé

// Attendre que le DOM soit complètement chargé avant d'initialiser l'application
document.addEventListener("DOMContentLoaded", function () {
  // Fonctions d'initialisation principales
  loadHeader();
  loadFooter();
  initTheme();
  showSplashScreen();
  setupAuthUI();
  setupNavigation();
  setupCalendar();
  setupUserMenu();
  setupMobileMenu();
});

/**
 * Fonctions d'inclusion du header et footer
 */

// Charge le header depuis un fichier externe
function loadHeader() {
  fetch("header.html")
    .then((response) => response.text())
    .then((data) => {
      document.getElementById("header-placeholder").innerHTML = data;
      // Réattacher les événements après l'insertion du header
      setupHeaderEvents();
    })
    .catch((error) => {
      console.error("Erreur lors du chargement du header:", error);
    });
}

// Charge le footer depuis un fichier externe
function loadFooter() {
  fetch("footer.html")
    .then((response) => response.text())
    .then((data) => {
      document.getElementById("footer-placeholder").innerHTML = data;
    })
    .catch((error) => {
      console.error("Erreur lors du chargement du footer:", error);
    });
}

// Configurer les événements du header après son chargement
function setupHeaderEvents() {
  // Réattacher l'événement de toggle du thème
  const themeToggle = document.querySelector(".theme-toggle");
  if (themeToggle) {
    themeToggle.addEventListener("click", toggleTheme);
  }

  // Réattacher les événements du menu utilisateur
  setupUserMenu();
}

/**
 * Fonctions d'interface utilisateur
 */

// Configuration du menu utilisateur
function setupUserMenu() {
  const userBtn = document.getElementById("user-btn");
  const dropdownMenu = document.querySelector(".dropdown-menu");

  if (userBtn && dropdownMenu) {
    userBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      dropdownMenu.classList.toggle("active");
    });

    // Fermer le menu quand on clique ailleurs
    document.addEventListener("click", function () {
      dropdownMenu.classList.remove("active");
    });
  }
}

// Configuration du menu mobile
function setupMobileMenu() {
  const mobileMenuBtn = document.querySelector(".mobile-menu-btn");
  const topNav = document.querySelector(".top-nav");

  if (mobileMenuBtn && topNav) {
    mobileMenuBtn.addEventListener("click", function () {
      mobileMenuBtn.classList.toggle("active");
      topNav.classList.toggle("active");
    });
  }
}

// Affiche l'écran de démarrage avec animation de transition
function showSplashScreen() {
  const splashScreen = document.getElementById("splash-screen");
  if (splashScreen) {
    setTimeout(() => {
      splashScreen.style.opacity = "0";
      setTimeout(() => {
        splashScreen.style.display = "none";
        checkAuthState();
      }, 500);
    }, 2000);
  }
}

// Vérifie l'état d'authentification de l'utilisateur
function checkAuthState() {
  console.log("Vérification de l'état d'authentification...");
  // Cette fonction pourrait contenir votre logique d'authentification
  setupAuthUI();
}

// Gestion de l'authentification et affichage conditionnel
function setupAuthUI() {
  // Vérifie si l'utilisateur est connecté (à adapter selon votre logique d'authentification)
  const isLoggedIn = false; // Changez en true pour tester le tableau de bord

  // Éléments principaux
  const welcomeSection = document.getElementById("welcome-section");
  const dashboard = document.getElementById("dashboard");

  if (welcomeSection && dashboard) {
    // Affiche la section appropriée selon l'état de connexion
    if (isLoggedIn) {
      welcomeSection.classList.add("hidden");
      dashboard.classList.remove("hidden");
    } else {
      welcomeSection.classList.remove("hidden");
      dashboard.classList.add("hidden");
    }

    // Pour tester : ajoutez des événements aux boutons de connexion/inscription
    const loginBtn = document.querySelector(".btn-secondary");
    if (loginBtn) {
      loginBtn.addEventListener("click", function (e) {
        e.preventDefault(); // Empêche la navigation vers login.html
        welcomeSection.classList.add("hidden");
        dashboard.classList.remove("hidden");
      });
    }
  }
}

// Configuration de la navigation
function setupNavigation() {
  document.querySelectorAll(".nav-item").forEach((item) => {
    item.addEventListener("click", function (e) {
      e.preventDefault();

      // Désactiver tous les éléments de navigation
      document.querySelectorAll(".nav-item").forEach((nav) => {
        nav.classList.remove("active");
      });

      // Activer l'élément cliqué
      this.classList.add("active");

      // Masquer toutes les pages
      document.querySelectorAll(".page").forEach((page) => {
        page.classList.remove("active");
      });

      // Afficher la page correspondante
      const pageId = this.getAttribute("data-page");
      document.getElementById(pageId).classList.add("active");
    });
  });
}

// Configuration du calendrier
function setupCalendar() {
  var calendarEl = document.getElementById("calendar");

  // Vérification que l'élément calendrier existe
  if (calendarEl) {
    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: "dayGridMonth",
      events: [
        {
          title: "Événement important",
          start: "2025-04-05",
        },
        {
          title: "Réunion",
          start: "2025-04-10",
        },
      ],
    });
    calendar.render();
  }
}

/**
 * Fonctions de gestion du thème
 */

// Fonction pour basculer entre le thème clair et sombre
function toggleTheme() {
  const body = document.body;
  const isDarkMode = body.classList.contains("dark-mode");

  if (isDarkMode) {
    body.classList.remove("dark-mode");
    localStorage.setItem("theme", "light");

    // Mettre à jour toutes les icônes de thème
    document.querySelectorAll(".theme-toggle i").forEach((icon) => {
      icon.className = "fas fa-moon";
    });
  } else {
    body.classList.add("dark-mode");
    localStorage.setItem("theme", "dark");

    // Mettre à jour toutes les icônes de thème
    document.querySelectorAll(".theme-toggle i").forEach((icon) => {
      icon.className = "fas fa-sun";
    });
  }
}

// Fonction pour initialiser le thème au chargement de la page
function initTheme() {
  const savedTheme = localStorage.getItem("theme");

  if (savedTheme === "dark") {
    document.body.classList.add("dark-mode");

    document.querySelectorAll(".theme-toggle i").forEach((icon) => {
      icon.className = "fas fa-sun";
    });
  }
}

/**
 * Fonctions utilitaires
 */

// Fonction pour afficher des messages de notification
function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.innerHTML = `
    <div class="notification-content">
      ${message}
    </div>
    <button class="close-notification">&times;</button>
  `;

  document.body.appendChild(notification);

  // Faire apparaître la notification
  setTimeout(() => {
    notification.classList.add("show");
  }, 10);

  // Fermer automatiquement après 5 secondes
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => {
      notification.remove();
    }, 300);
  }, 5000);

  // Permettre la fermeture manuelle
  notification
    .querySelector(".close-notification")
    .addEventListener("click", () => {
      notification.classList.remove("show");
      setTimeout(() => {
        notification.remove();
      }, 300);
    });
}
