document.addEventListener("DOMContentLoaded", function () {
  // Récupérer les éléments DOM
  const uploadPhotoBtn = document.getElementById("uploadPhotoBtn");
  const photoInput = document.getElementById("photoInput");
  const profilePhotoDisplay = document.getElementById("profilePhotoDisplay");
  const initialsDisplay = document.getElementById("initials");
  const fullNameInput = document.getElementById("fullName");
  const birthdateInput = document.getElementById("birthdate");
  const emailInput = document.getElementById("email");
  const phoneInput = document.getElementById("phone");
  const profileNameDisplay = document.getElementById("profileNameDisplay");
  const profileEmailDisplay = document.getElementById("profileEmailDisplay");
  const profilePhoneDisplay = document.getElementById("profilePhoneDisplay");
  const profileAgeDisplay = document.getElementById("profileAgeDisplay");
  const saveProfileBtn = document.getElementById("saveProfileBtn");
  const successMessage = document.getElementById("successMessage");
  const addContactBtn = document.getElementById("addContactBtn");
  const contactModal = document.getElementById("contactModal");
  const closeContactModal = document.getElementById("closeContactModal");
  const contactForm = document.getElementById("contactForm");
  const contactsList = document.getElementById("contactsList");

  // Charger les données du profil depuis le localStorage
  loadProfileData();

  // Événement pour le changement de photo
  uploadPhotoBtn.addEventListener("click", function () {
    photoInput.click();
  });

  photoInput.addEventListener("change", function (e) {
    if (e.target.files && e.target.files[0]) {
      const reader = new FileReader();

      reader.onload = function (event) {
        // Créer une image et la définir comme fond du profilePhotoDisplay
        profilePhotoDisplay.style.backgroundImage = `url(${event.target.result})`;
        profilePhotoDisplay.style.backgroundSize = "cover";
        profilePhotoDisplay.style.backgroundPosition = "center";

        // Cacher les initiales
        initialsDisplay.style.display = "none";

        // Sauvegarder l'image dans localStorage (sous forme de base64)
        localStorage.setItem("profilePhoto", event.target.result);
      };

      reader.readAsDataURL(e.target.files[0]);
    }
  });

  // Mettre à jour l'affichage du nom lorsqu'il change
  fullNameInput.addEventListener("input", function () {
    profileNameDisplay.textContent = fullNameInput.value;
    updateInitials(fullNameInput.value);
  });

  // Mettre à jour l'affichage de l'email lorsqu'il change
  emailInput.addEventListener("input", function () {
    profileEmailDisplay.textContent = emailInput.value;
  });

  // Mettre à jour l'affichage du téléphone lorsqu'il change
  phoneInput.addEventListener("input", function () {
    profilePhoneDisplay.textContent = phoneInput.value;
  });

  // Mettre à jour l'âge lorsque la date de naissance change
  birthdateInput.addEventListener("input", function () {
    updateAge();
  });

  // Enregistrer les modifications du profil
  saveProfileBtn.addEventListener("click", function () {
    saveProfileData();

    // Afficher le message de succès
    successMessage.style.display = "block";

    // Cacher le message après 3 secondes
    setTimeout(function () {
      successMessage.style.display = "none";
    }, 3000);
  });

  // Ouvrir la modal pour ajouter un contact
  addContactBtn.addEventListener("click", function () {
    contactModal.style.display = "flex";
  });

  // Fermer la modal de contact
  closeContactModal.addEventListener("click", function () {
    contactModal.style.display = "none";
  });

  // Soumettre le formulaire de contact
  contactForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const contactName = document.getElementById("contactName").value;
    const contactRelation = document.getElementById("contactRelation").value;
    const contactPhone = document.getElementById("contactPhone").value;
    const contactEmail = document.getElementById("contactEmail").value;
    const contactNotify = document.getElementById("contactNotify").checked;

    addContact(
      contactName,
      contactRelation,
      contactPhone,
      contactEmail,
      contactNotify
    );

    // Réinitialiser le formulaire
    contactForm.reset();

    // Fermer la modal
    contactModal.style.display = "none";
  });

  // Fermer la modal si on clique en dehors
  window.addEventListener("click", function (e) {
    if (e.target === contactModal) {
      contactModal.style.display = "none";
    }
  });

  // Gérer la suppression des contacts
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("remove-contact")) {
      const contactCard = e.target.closest(".contact-card");
      if (contactCard) {
        contactCard.remove();
        saveContacts(); // Sauvegarder après suppression
      }
    }
  });

  // Fonction pour calculer l'âge
  function updateAge() {
    const birthdate = new Date(birthdateInput.value);
    const today = new Date();

    let age = today.getFullYear() - birthdate.getFullYear();
    const monthDiff = today.getMonth() - birthdate.getMonth();

    if (
      monthDiff < 0 ||
      (monthDiff === 0 && today.getDate() < birthdate.getDate())
    ) {
      age--;
    }

    profileAgeDisplay.textContent = age + " ans";
  }

  // Fonction pour mettre à jour les initiales
  function updateInitials(fullName) {
    const names = fullName.split(" ");
    let initials = "";

    if (names.length >= 2) {
      initials = names[0].charAt(0) + names[1].charAt(0);
    } else if (names.length === 1) {
      initials = names[0].charAt(0);
    }

    initialsDisplay.textContent = initials.toUpperCase();
  }

  // Fonction pour charger les données du profil
  function loadProfileData() {
    // Charger la photo
    const savedPhoto = localStorage.getItem("profilePhoto");
    if (savedPhoto) {
      profilePhotoDisplay.style.backgroundImage = `url(${savedPhoto})`;
      profilePhotoDisplay.style.backgroundSize = "cover";
      profilePhotoDisplay.style.backgroundPosition = "center";
      initialsDisplay.style.display = "none";
    }

    // Charger les informations de base
    const profileData = JSON.parse(localStorage.getItem("profileData")) || {
      fullName: "Jean Dupont",
      birthdate: "1957-05-15",
      email: "jean.dupont@email.com",
      phone: "06 12 34 56 78",
      emailNotifs: true,
      smsNotifs: true,
      reminderTiming: "60",
    };

    fullNameInput.value = profileData.fullName;
    birthdateInput.value = profileData.birthdate;
    emailInput.value = profileData.email;
    phoneInput.value = profileData.phone;
    document.getElementById("emailNotifs").checked = profileData.emailNotifs;
    document.getElementById("smsNotifs").checked = profileData.smsNotifs;
    document.getElementById("reminderTiming").value =
      profileData.reminderTiming;

    // Mettre à jour l'affichage
    profileNameDisplay.textContent = profileData.fullName;
    profileEmailDisplay.textContent = profileData.email;
    profilePhoneDisplay.textContent = profileData.phone;
    updateInitials(profileData.fullName);
    updateAge();

    // Charger les contacts
    loadContacts();

    // Synchroniser avec les informations du calendrier
    localStorage.setItem("userEmail", profileData.email);
    localStorage.setItem("userPhone", profileData.phone);
  }

  // Fonction pour sauvegarder les données du profil
  function saveProfileData() {
    const profileData = {
      fullName: fullNameInput.value,
      birthdate: birthdateInput.value,
      email: emailInput.value,
      phone: phoneInput.value,
      emailNotifs: document.getElementById("emailNotifs").checked,
      smsNotifs: document.getElementById("smsNotifs").checked,
      reminderTiming: document.getElementById("reminderTiming").value,
    };

    localStorage.setItem("profileData", JSON.stringify(profileData));

    // Synchroniser avec les informations du calendrier
    localStorage.setItem("userEmail", profileData.email);
    localStorage.setItem("userPhone", profileData.phone);

    // Sauvegarder les contacts
    saveContacts();
  }

  // Fonction pour ajouter un contact
  function addContact(name, relation, phone, email, notify) {
    const contactCard = document.createElement("div");
    contactCard.className = "contact-card";
    contactCard.innerHTML = `
                <button class="remove-contact">&times;</button>
                <div class="contact-name">${name}</div>
                <div class="contact-relation">${relation}</div>
                <div class="contact-info">${phone}</div>
                <div class="contact-info">${email}</div>
                <input type="hidden" class="contact-notify" value="${notify}">
            `;

    contactsList.appendChild(contactCard);

    // Sauvegarder les contacts
    saveContacts();
  }

  // Fonction pour sauvegarder les contacts
  function saveContacts() {
    const contacts = [];
    const contactCards = document.querySelectorAll(".contact-card");

    contactCards.forEach((card) => {
      const name = card.querySelector(".contact-name").textContent;
      const relation = card.querySelector(".contact-relation").textContent;
      const phoneEl = card.querySelector(".contact-info:nth-of-type(1)");
      const emailEl = card.querySelector(".contact-info:nth-of-type(2)");
      const notifyEl = card.querySelector(".contact-notify");

      contacts.push({
        name: name,
        relation: relation,
        phone: phoneEl ? phoneEl.textContent : "",
        email: emailEl ? emailEl.textContent : "",
        notify: notifyEl ? notifyEl.value === "true" : true,
      });
    });

    localStorage.setItem("emergencyContacts", JSON.stringify(contacts));
  }

  // Fonction pour charger les contacts
  function loadContacts() {
    const contacts =
      JSON.parse(localStorage.getItem("emergencyContacts")) || [];

    contactsList.innerHTML = "";

    contacts.forEach((contact) => {
      addContact(
        contact.name,
        contact.relation,
        contact.phone,
        contact.email,
        contact.notify
      );
    });

    // Si aucun contact n'est chargé et que c'est la première fois, ajouter des exemples
    if (contacts.length === 0 && !localStorage.getItem("contactsInitialized")) {
      addContact(
        "Marie Dupont",
        "Épouse",
        "07 23 45 67 89",
        "marie.dupont@email.com",
        true
      );
      addContact(
        "Pierre Dupont",
        "Fils",
        "06 34 56 78 90",
        "pierre.dupont@email.com",
        true
      );
      localStorage.setItem("contactsInitialized", "true");
    }
  }
});
