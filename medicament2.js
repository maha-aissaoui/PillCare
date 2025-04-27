document.addEventListener("DOMContentLoaded", function () {
  // Éléments du DOM
  const addMedicationBtn = document.getElementById("add-medication-btn");
  const addMedicationModal = document.getElementById("add-medication-modal");
  const editMedicationModal = document.getElementById("edit-medication-modal");
  const closeModalBtn = document.getElementById("close-modal");
  const closeEditModalBtn = document.getElementById("close-edit-modal");
  const cancelAddBtn = document.getElementById("cancel-add");
  const cancelEditBtn = document.getElementById("cancel-edit");
  const addMedicationForm = document.getElementById("add-medication-form");
  const editMedicationForm = document.getElementById("edit-medication-form");
  const searchInput = document.getElementById("search-medication");
  const formFilter = document.getElementById("form-filter");
  const filterBtn = document.querySelector(".filter-btn");
  const filterDropdown = document.getElementById("filter-dropdown");
  const medicationsGrid = document.querySelector(".medications-grid");

  // Icônes pour les moments de prise
  const scheduleIcons = {
    Matin: "sun",
    Midi: "sun",
    Après_midi: "sun",
    Soir: "moon",
    Coucher: "bed",
  };

  // Variables globales
  let selectedTimeSlots = [false, false, false, false, false];
  let editSelectedTimeSlots = [false, false, false, false, false];
  const timeSlotLabels = ["Matin", "Midi", "Après-midi", "Soir", "Coucher"];

  // Initialisation
  function init() {
    attachEventListeners();
    initModals();
    loadMedications();
    checkTheme();
  }

  // Fonctions d'initialisation
  function attachEventListeners() {
    // Modales
    addMedicationBtn.addEventListener("click", openAddModal);
    closeModalBtn.addEventListener("click", closeAddModal);
    closeEditModalBtn.addEventListener("click", closeEditModal);
    cancelAddBtn.addEventListener("click", closeAddModal);
    cancelEditBtn.addEventListener("click", closeEditModal);

    // Formulaires
    addMedicationForm.addEventListener("submit", handleAddMedicationSubmit);
    editMedicationForm.addEventListener("submit", handleEditMedicationSubmit);

    // Recherche et filtres
    searchInput.addEventListener("input", handleSearch);
    formFilter.addEventListener("change", handleFormFilter);
    filterBtn.addEventListener("click", toggleFilterDropdown);

    // Cartes de médicaments
    medicationsGrid.addEventListener("click", handleMedicationCardClick);

    // Time slots
    document.querySelectorAll(".time-slot").forEach((slot) => {
      slot.addEventListener("click", toggleTimeSlot);
    });

    document.querySelectorAll(".edit-time-slot").forEach((slot) => {
      slot.addEventListener("click", toggleEditTimeSlot);
    });

    // Menu utilisateur
    document.getElementById("user-btn").addEventListener("click", function () {
      document.querySelector(".dropdown-menu").classList.toggle("active");
    });

    // Menu mobile
    document
      .querySelector(".mobile-menu-btn")
      .addEventListener("click", function () {
        document.querySelector(".top-nav").classList.toggle("active");
      });

    // Fermeture des dropdowns au clic externe
    document.addEventListener("click", function (event) {
      if (
        !event.target.closest(".filter-container") &&
        filterDropdown.classList.contains("active")
      ) {
        filterDropdown.classList.remove("active");
      }
    });
  }

  function initModals() {
    window.addEventListener("click", function (event) {
      if (event.target === addMedicationModal) {
        closeAddModal();
      }
      if (event.target === editMedicationModal) {
        closeEditModal();
      }
    });
  }

  // Gestion des modales
  function openAddModal() {
    resetAddForm();
    addMedicationModal.style.display = "flex";
  }

  function closeAddModal() {
    addMedicationModal.style.display = "none";
  }

  function openEditModal(medicationData) {
    populateEditForm(medicationData);
    editMedicationModal.style.display = "flex";
  }

  function closeEditModal() {
    editMedicationModal.style.display = "none";
  }

  function resetAddForm() {
    addMedicationForm.reset();
    selectedTimeSlots = [false, false, false, false, false];
    document.querySelectorAll(".time-slot").forEach((slot) => {
      slot.classList.remove("active");
    });
    document.getElementById("medication-schedule").value =
      JSON.stringify(selectedTimeSlots);
  }

  // Gestion des time slots
  function toggleTimeSlot(event) {
    const slotIndex = parseInt(event.target.dataset.value);
    selectedTimeSlots[slotIndex] = !selectedTimeSlots[slotIndex];
    event.target.classList.toggle("active");
    document.getElementById("medication-schedule").value =
      JSON.stringify(selectedTimeSlots);
  }

  function toggleEditTimeSlot(event) {
    const slotIndex = parseInt(event.target.dataset.value);
    editSelectedTimeSlots[slotIndex] = !editSelectedTimeSlots[slotIndex];
    event.target.classList.toggle("active");
    document.getElementById("edit-medication-schedule").value = JSON.stringify(
      editSelectedTimeSlots
    );
  }

  // Gestion des formulaires
  function handleAddMedicationSubmit(event) {
    event.preventDefault();
    submitForm("add", addMedicationForm)
      .then(() => {
        closeAddModal();
        loadMedications();
        showNotification("Médicament ajouté avec succès", "success");
      })
      .catch((error) => {
        showNotification("Erreur: " + error.message, "error");
      });
  }

  function handleEditMedicationSubmit(event) {
    event.preventDefault();
    submitForm("edit", editMedicationForm)
      .then(() => {
        closeEditModal();
        loadMedications();
        showNotification("Médicament mis à jour avec succès", "success");
      })
      .catch((error) => {
        showNotification("Erreur: " + error.message, "error");
      });
  }

  function submitForm(action, form) {
    const formData = new FormData(form);
    formData.append("action", action);

    return fetch("medicament.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Erreur réseau");
        }
        return response.json();
      })
      .then((data) => {
        if (!data.success) {
          throw new Error(data.message || "Erreur inconnue");
        }
        return data;
      });
  }

  // Gestion des médicaments
  function populateEditForm(medicationData) {
    document.getElementById("edit-medication-id").value = medicationData.id;
    document.getElementById("edit-medication-name").value = medicationData.nom;
    document.getElementById("edit-medication-dosage").value =
      medicationData.dosage;
    document.getElementById("edit-medication-form").value =
      medicationData.forme;
    document.getElementById("edit-medication-qty").value =
      medicationData.quantite;
    document.getElementById("edit-medication-color").value =
      medicationData.couleur;

    editSelectedTimeSlots = parseTimeSlots(medicationData.horaires);

    document.querySelectorAll(".edit-time-slot").forEach((slot, index) => {
      slot.classList.toggle("active", editSelectedTimeSlots[index]);
    });

    document.getElementById("edit-medication-schedule").value = JSON.stringify(
      editSelectedTimeSlots
    );
  }

  function parseTimeSlots(timeSlots) {
    try {
      let slots =
        typeof timeSlots === "string" ? JSON.parse(timeSlots) : timeSlots;
      if (!Array.isArray(slots) || slots.length !== 5) {
        return [false, false, false, false, false];
      }
      return slots;
    } catch (e) {
      return [false, false, false, false, false];
    }
  }

  function fetchMedicationData(medicationId) {
    const card = document.querySelector(
      `.medication-card[data-id="${medicationId}"]`
    );
    if (!card) return;

    const name = card.querySelector(".med-name").textContent;
    const dosage = card.querySelector(".med-dosage").textContent;
    const quantity = card.querySelector(".med-quantity span").textContent;

    const iconElement = card.querySelector(".medication-icon i");
    let forme = "other";
    if (iconElement.classList.contains("fa-tablet")) forme = "tablet";
    else if (iconElement.classList.contains("fa-capsule")) forme = "capsule";
    else if (iconElement.classList.contains("fa-flask")) forme = "liquid";
    else if (iconElement.classList.contains("fa-syringe")) forme = "injection";

    // Récupérer les horaires depuis les éléments schedule-time
    const scheduleItems = Array.from(card.querySelectorAll(".schedule-time"));
    const timeSlots = timeSlotLabels.map((label) =>
      scheduleItems.some((item) => item.textContent.trim().includes(label))
    );

    const headerColor =
      card.querySelector(".medication-header").style.backgroundColor;

    openEditModal({
      id: medicationId,
      nom: name,
      dosage: dosage,
      forme: forme,
      quantite: quantity,
      horaires: JSON.stringify(timeSlots),
      couleur: headerColor,
    });
  }

  function handleMedicationCardClick(event) {
    const medicationCard = event.target.closest(".medication-card");
    if (!medicationCard) return;

    const medicationId = medicationCard.dataset.id;

    if (event.target.closest(".edit-btn")) {
      fetchMedicationData(medicationId);
    } else if (event.target.closest(".delete-btn")) {
      confirmDeleteMedication(medicationId);
    }
  }

  function confirmDeleteMedication(medicationId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce médicament ?")) {
      deleteMedication(medicationId);
    }
  }

  function deleteMedication(medicationId) {
    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("id", medicationId);

    fetch("medicament.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          loadMedications();
          showNotification("Médicament supprimé avec succès", "success");
        } else {
          throw new Error(data.message || "Erreur lors de la suppression");
        }
      })
      .catch((error) => {
        showNotification("Erreur: " + error.message, "error");
      });
  }

  // Recherche et filtrage
  function handleSearch() {
    const searchTerm = searchInput.value.trim();
    fetchData("search", { recherche: searchTerm })
      .then((data) => updateMedicationsGrid(data))
      .catch((error) => console.error("Erreur recherche:", error));
  }

  function handleFormFilter() {
    const selectedForm = formFilter.value;
    fetchData("filter", { forme: selectedForm })
      .then((data) => updateMedicationsGrid(data))
      .catch((error) => console.error("Erreur filtrage:", error));
  }

  function toggleFilterDropdown() {
    filterDropdown.classList.toggle("active");
  }

  function fetchData(action, params) {
    const formData = new FormData();
    formData.append("action", action);
    Object.entries(params).forEach(([key, value]) =>
      formData.append(key, value)
    );

    return fetch("medicament.php", {
      method: "POST",
      body: formData,
    }).then((response) => response.json());
  }

  // Affichage des médicaments
  function loadMedications() {
    fetchData("get", {})
      .then((data) => updateMedicationsGrid(data))
      .catch((error) => console.error("Erreur chargement:", error));
  }

  function updateMedicationsGrid(medications) {
    medicationsGrid.innerHTML = "";

    if (!medications || medications.length === 0) {
      medicationsGrid.innerHTML = `
              <div class="no-medications">
                  <p>Aucun médicament trouvé. Commencez par ajouter votre premier médicament !</p>
              </div>
          `;
      return;
    }

    medications.forEach((med) => {
      const iconMap = {
        tablet: "tablet",
        capsule: "capsule",
        liquid: "flask",
        injection: "syringe",
        default: "pills",
      };

      const icone = iconMap[med.forme] || iconMap.default;
      const horaires = parseTimeSlots(med.horaires);

      // Créer les éléments de planning avec les icônes appropriées
      const scheduleItems = [];
      horaires.forEach((active, index) => {
        if (active) {
          const timeLabel = timeSlotLabels[index];
          const icon = scheduleIcons[timeLabel] || "clock";
          scheduleItems.push(`
            <span class="schedule-time">
              <i class="fas fa-${icon}"></i>
              ${timeLabel}
            </span>
          `);
        }
      });

      const joursRestants = med.jours_restants || 0;
      const joursClass =
        joursRestants <= 3
          ? "jours-critiques"
          : joursRestants <= 7
          ? "jours-attention"
          : "jours-ok";

      medicationsGrid.innerHTML += `
              <div class="medication-card" data-id="${med.id}">
                  <div class="medication-header" style="background-color: ${
                    med.couleur || "#4a89dc"
                  }">
                      <div class="medication-icon">
                          <i class="fas fa-${icone}"></i>
                      </div>
                      <div class="medication-actions">
                          <button class="edit-btn"><i class="fas fa-edit"></i></button>
                          <button class="delete-btn"><i class="fas fa-trash"></i></button>
                      </div>
                  </div>
                  <div class="medication-body">
                      <h3 class="med-name">${med.nom}</h3>
                      <p class="med-dosage">${med.dosage}</p>
                      <div class="med-quantity">
                          <i class="fas fa-pills"></i>
                          Quantité: <span>${med.quantite}</span>
                      </div>
                      <div class="med-schedule">
                          ${scheduleItems.join("")}
                      </div>
                      <p class="medication-remaining-days ${joursClass}">
                          Jours restants: <span>${joursRestants}</span>
                      </p>
                  </div>
              </div>
          `;
    });
  }

  // Notifications
  function showNotification(message, type = "info") {
    const notification =
      document.querySelector(".notification") || createNotification();
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.display = "block";

    setTimeout(() => {
      notification.style.opacity = "0";
      setTimeout(() => {
        notification.style.display = "none";
        notification.style.opacity = "1";
      }, 500);
    }, 3000);
  }

  function createNotification() {
    const notification = document.createElement("div");
    notification.classList.add("notification");
    document.body.appendChild(notification);
    return notification;
  }

  // Thème
  function checkTheme() {
    if (localStorage.getItem("darkTheme") === "true") {
      document.body.classList.add("dark-theme");
    }
  }

  window.toggleTheme = function () {
    document.body.classList.toggle("dark-theme");
    localStorage.setItem(
      "darkTheme",
      document.body.classList.contains("dark-theme")
    );
  };

  // Démarrer l'application
  init();
});
