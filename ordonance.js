// Script principal pour la gestion des ordonnances médicales
(function() {
  // Déclaration des variables globales nécessaires aux fonctions exportées
  let alertMessageElement;
  
  // Fonction principale exécutée au chargement du document
  document.addEventListener("DOMContentLoaded", function() {
    // Récupération des éléments DOM
    const userBtn = document.getElementById("user-btn");
    const dropdownMenu = document.querySelector(".dropdown-menu");
    const mobileMenuBtn = document.querySelector(".mobile-menu-btn");
    const topNav = document.querySelector(".top-nav");
    const fichierInput = document.getElementById("fichier");
    const filePreview = document.getElementById("filePreview");
    const ordonnanceForm = document.getElementById("ordonnanceForm");
    alertMessageElement = document.getElementById("alertMessage");
    
    // INITIALISATION DES MENUS
    initMenus(userBtn, dropdownMenu, mobileMenuBtn, topNav);
    
    // GESTION DE LA PRÉVISUALISATION DES FICHIERS
    initFilePreview(fichierInput, filePreview);
    
    // INITIALISATION DU FORMULAIRE
    initOrdonnanceForm(ordonnanceForm, filePreview);
    
    // INITIALISATION DU THÈME
    initTheme();
    
    // CHARGEMENT INITIAL DES ORDONNANCES
    chargerOrdonnances();
  });
  
  // ====== FONCTIONS D'INITIALISATION ======
  
  // Initialisation des menus utilisateur et mobile
  function initMenus(userBtn, dropdownMenu, mobileMenuBtn, topNav) {
    if (userBtn) {
      userBtn.addEventListener("click", function() {
        dropdownMenu.classList.toggle("active");
      });
      
      // Fermer le menu si on clique ailleurs
      document.addEventListener("click", function(event) {
        if (!event.target.closest(".user-menu")) {
          dropdownMenu.classList.remove("active");
        }
      });
    }
    
    if (mobileMenuBtn) {
      mobileMenuBtn.addEventListener("click", function() {
        topNav.classList.toggle("mobile-active");
        mobileMenuBtn.classList.toggle("active");
      });
    }
  }
  
  // Initialisation de la prévisualisation des fichiers
  function initFilePreview(fichierInput, filePreview) {
    if (fichierInput) {
      fichierInput.addEventListener("change", function() {
        filePreview.innerHTML = "";
        
        if (this.files && this.files[0]) {
          const file = this.files[0];
          const fileType = file.type;
          
          if (fileType.startsWith("image/")) {
            const img = document.createElement("img");
            img.src = URL.createObjectURL(file);
            img.alt = "Aperçu de l'image";
            filePreview.appendChild(img);
          } else if (fileType === "application/pdf") {
            const pdfIcon = document.createElement("div");
            pdfIcon.className = "pdf-preview";
            pdfIcon.innerHTML = '<i class="fas fa-file-pdf"></i><span>' + file.name + "</span>";
            filePreview.appendChild(pdfIcon);
          }
        }
      });
    }
  }
  
  // Initialisation du formulaire d'ajout d'ordonnance
  function initOrdonnanceForm(ordonnanceForm, filePreview) {
    if (ordonnanceForm) {
      ordonnanceForm.addEventListener("submit", function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch("ajouter_ordonnance.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              afficherMessage(true, data.message);
              ordonnanceForm.reset();
              filePreview.innerHTML = "";
              
              // Recharger la liste des ordonnances après l'ajout
              chargerOrdonnances();
            } else {
              afficherMessage(false, data.message);
            }
          })
          .catch((error) => {
            afficherMessage(false, "Erreur de connexion au serveur");
            console.error("Erreur:", error);
          });
      });
    }
  }
  
  // Initialisation du thème
  function initTheme() {
    // Appliquer le thème sauvegardé lors du chargement
    const savedTheme = localStorage.getItem("darkTheme");
    if (savedTheme === "true") {
      document.body.classList.add("dark-theme");
      const themeToggle = document.querySelector(".theme-toggle i");
      if (themeToggle) {
        themeToggle.className = "fas fa-sun";
      }
    }
  }
  
  // ====== FONCTIONS UTILITAIRES ======
  
  // Affichage des messages d'alerte
  function afficherMessage(estSucces, message) {
    if (!alertMessageElement) return;
    
    const type = estSucces ? "success" : "error";
    alertMessageElement.innerHTML = `<div class="alert ${type}">${message}</div>`;
    
    // Faire défiler vers le haut pour voir le message
    alertMessageElement.scrollIntoView({ behavior: "smooth" });
    
    // Masquer le message après 3 secondes
    setTimeout(() => {
      alertMessageElement.innerHTML = "";
    }, 3000);
  }
  
  // ====== FONCTIONS EXPOSÉES GLOBALEMENT ======
  
  // Fonction pour charger les ordonnances
  function chargerOrdonnances() {
    const ordonnancesList = document.getElementById("ordonnancesList");
    if (!ordonnancesList) return;
    
    fetch("charger_ordonnances.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.ordonnances.length > 0) {
          ordonnancesList.innerHTML = "";
          
          data.ordonnances.forEach((ordonnance) => {
            const ordonnanceCard = document.createElement("div");
            ordonnanceCard.className = "ordonnance-card";
            
            // Formater la date pour un affichage plus convivial
            const date = new Date(ordonnance.date);
            const dateFormatee = date.toLocaleDateString("fr-FR", {
              day: "2-digit",
              month: "2-digit",
              year: "numeric",
            });
            
            // Déterminer l'icône en fonction du type de fichier
            let fileIcon = '<i class="fas fa-file"></i>';
            if (ordonnance.fichier_type === "application/pdf") {
              fileIcon = '<i class="fas fa-file-pdf"></i>';
            } else if (ordonnance.fichier_type.startsWith("image/")) {
              fileIcon = '<i class="fas fa-file-image"></i>';
            }
            
            ordonnanceCard.innerHTML = `
              <div class="ordonnance-header">
                <h3>${ordonnance.titre}</h3>
                <span class="ordonnance-date">${dateFormatee}</span>
              </div>
              <div class="ordonnance-content">
                <p><strong>Médecin:</strong> ${ordonnance.medecin}</p>
                ${
                  ordonnance.description
                    ? `<p><strong>Description:</strong> ${ordonnance.description}</p>`
                    : ""
                }
              </div>
              <div class="ordonnance-actions">
                <button class="btn btn-view" onclick="afficherDetails(${
                  ordonnance.id
                })">
                  <i class="fas fa-eye"></i> Voir détails
                </button>
                <button class="btn btn-file" onclick="ouvrirFichier(${
                  ordonnance.id
                })">
                  ${fileIcon} Fichier
                </button>
                <button class="btn btn-delete" onclick="supprimerOrdonnance(${
                  ordonnance.id
                })">
                  <i class="fas fa-trash"></i> Supprimer
                </button>
              </div>
            `;
            
            ordonnancesList.appendChild(ordonnanceCard);
          });
        } else {
          ordonnancesList.innerHTML =
            '<div class="no-ordonnances">Aucune ordonnance disponible</div>';
        }
      })
      .catch((error) => {
        console.error("Erreur lors du chargement des ordonnances:", error);
        ordonnancesList.innerHTML =
          '<div class="error-message">Erreur lors du chargement des ordonnances</div>';
      });
  }
  
  // Exposer les fonctions nécessaires au niveau global
  window.afficherDetails = function(id) {
    fetch(`afficher_ordonnance.php?id=${id}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const ordonnance = data.ordonnance;
          const date = new Date(ordonnance.date);
          const dateFormatee = date.toLocaleDateString("fr-FR", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
          });
          
          // Créer une modal pour afficher les détails
          const modal = document.createElement("div");
          modal.className = "modal";
          modal.innerHTML = `
            <div class="modal-content">
              <span class="close-btn">&times;</span>
              <h2>${ordonnance.titre}</h2>
              <div class="ordonnance-details">
                <p><strong>Date:</strong> ${dateFormatee}</p>
                <p><strong>Médecin:</strong> ${ordonnance.medecin}</p>
                ${
                  ordonnance.description
                    ? `<p><strong>Description:</strong> ${ordonnance.description}</p>`
                    : ""
                }
                <p><strong>Ajoutée le:</strong> ${new Date(
                  ordonnance.created_at
                ).toLocaleString("fr-FR")}</p>
                <div class="modal-actions">
                  <button class="btn btn-file" onclick="ouvrirFichier(${
                    ordonnance.id
                  })">
                    <i class="fas fa-file"></i> Ouvrir le fichier
                  </button>
                </div>
              </div>
            </div>
          `;
          
          document.body.appendChild(modal);
          
          // Afficher la modal
          setTimeout(() => {
            modal.style.display = "block";
          }, 100);
          
          // Fermer la modal en cliquant sur le X
          const closeBtn = modal.querySelector(".close-btn");
          closeBtn.addEventListener("click", function() {
            modal.style.display = "none";
            setTimeout(() => {
              document.body.removeChild(modal);
            }, 300);
          });
          
          // Fermer la modal en cliquant en dehors
          window.addEventListener("click", function(event) {
            if (event.target === modal) {
              modal.style.display = "none";
              setTimeout(() => {
                document.body.removeChild(modal);
              }, 300);
            }
          });
        } else {
          alert("Erreur: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Erreur lors du chargement des détails:", error);
        alert("Erreur de connexion au serveur");
      });
  };
  
  window.ouvrirFichier = function(id) {
    window.open(`ouvrir_fichier.php?id=${id}`, "_blank");
  };
  
  window.supprimerOrdonnance = function(id) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cette ordonnance?")) {
      const formData = new FormData();
      formData.append("id", id);
      
      fetch("supprimer_ordonnance.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          afficherMessage(data.success, data.message);
          
          if (data.success) {
            chargerOrdonnances();
          }
        })
        .catch((error) => {
          afficherMessage(false, "Erreur de connexion au serveur");
          console.error("Erreur:", error);
        });
    }
  };
  
  window.toggleTheme = function() {
    document.body.classList.toggle("dark-theme");
    
    // Sauvegarder la préférence de thème dans le stockage local
    const isDarkTheme = document.body.classList.contains("dark-theme");
    localStorage.setItem("darkTheme", isDarkTheme);
    
    // Mettre à jour l'icône du bouton de thème
    const themeToggle = document.querySelector(".theme-toggle i");
    if (themeToggle) {
      if (isDarkTheme) {
        themeToggle.className = "fas fa-sun";
      } else {
        themeToggle.className = "fas fa-moon";
      }
    }
  };
  
  // Exposer également chargerOrdonnances pour une utilisation externe si nécessaire
  window.chargerOrdonnances = chargerOrdonnances;
})();