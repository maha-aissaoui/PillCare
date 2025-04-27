document.addEventListener("DOMContentLoaded", function () {
  // Sélectionner tous les options de thème
  const themeOptions = document.querySelectorAll(".theme-option");

  // Ajouter un écouteur d'événement à chaque option
  themeOptions.forEach((option) => {
    option.addEventListener("click", function () {
      // Détecter la classe de thème
      if (this.classList.contains("theme-blue")) {
        changeTheme("#4285f4", "#34a853", "#ea4335");
      } else if (this.classList.contains("theme-green")) {
        changeTheme("#34a853", "#4285f4", "#fbbc05");
      } else if (this.classList.contains("theme-purple")) {
        changeTheme("#7c4dff", "#4285f4", "#ea4335");
      } else if (this.classList.contains("theme-orange")) {
        changeTheme("#ff7043", "#34a853", "#fbbc05");
      }

      // Ajouter une animation pour montrer le changement
      animateThemeChange();
    });
  });

  // Fonction pour changer les couleurs du thème
  function changeTheme(primary, secondary, accent) {
    document.documentElement.style.setProperty("--primary-color", primary);
    document.documentElement.style.setProperty("--secondary-color", secondary);
    document.documentElement.style.setProperty("--accent-color", accent);

    // Sauvegarder le thème dans localStorage pour le conserver à travers les sessions
    localStorage.setItem(
      "pillcare-theme",
      JSON.stringify({
        primary: primary,
        secondary: secondary,
        accent: accent,
      })
    );
  }

  // Animation pour indiquer le changement de thème
  function animateThemeChange() {
    // Créer un élément d'animation
    const animation = document.createElement("div");
    animation.classList.add("theme-change-animation");
    animation.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.2);
        z-index: 9999;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
      `;

    document.body.appendChild(animation);

    // Déclencher l'animation
    setTimeout(() => {
      animation.style.opacity = "1";
      setTimeout(() => {
        animation.style.opacity = "0";
        setTimeout(() => {
          document.body.removeChild(animation);
        }, 300);
      }, 300);
    }, 0);
  }

  // Charger le thème sauvegardé s'il existe
  const savedTheme = localStorage.getItem("pillcare-theme");
  if (savedTheme) {
    const theme = JSON.parse(savedTheme);
    changeTheme(theme.primary, theme.secondary, theme.accent);
  }
});
