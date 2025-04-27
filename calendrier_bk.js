// calendar.js - Script JavaScript pour gérer le calendrier côté client

document.addEventListener("DOMContentLoaded", function () {
  // Variables globales
  const calendar = document.getElementById("calendar");
  const currentMonthElem = document.getElementById("currentMonth");
  const prevMonthBtn = document.getElementById("prevMonth");
  const nextMonthBtn = document.getElementById("nextMonth");
  const addEventBtn = document.getElementById("addEventBtn");
  const eventModal = document.getElementById("eventModal");
  const closeModal = document.getElementById("closeModal");
  const eventForm = document.getElementById("eventForm");
  const modalTitle = document.getElementById("modalTitle");
  const deleteEventBtn = document.getElementById("deleteEventBtn");
  const toggleNotifications = document.getElementById("toggleNotifications");
  const notificationDetails = document.getElementById("notificationDetails");

  let currentDate = new Date();
  let selectedDate = null;
  let events = [];

  // Noms des jours de la semaine et des mois
  const weekdays = [
    "Dimanche",
    "Lundi",
    "Mardi",
    "Mercredi",
    "Jeudi",
    "Vendredi",
    "Samedi",
  ];
  const months = [
    "Janvier",
    "Février",
    "Mars",
    "Avril",
    "Mai",
    "Juin",
    "Juillet",
    "Août",
    "Septembre",
    "Octobre",
    "Novembre",
    "Décembre",
  ];

  // Fonction pour basculer l'affichage des paramètres de notification
  toggleNotifications.addEventListener("click", function () {
    if (notificationDetails.style.display === "block") {
      notificationDetails.style.display = "none";
      toggleNotifications.textContent = "+ Options de rappel";
    } else {
      notificationDetails.style.display = "block";
      toggleNotifications.textContent = "- Masquer les options";
    }
  });

  // Fonction pour charger les événements depuis le serveur
  async function loadEvents() {
    try {
      const year = currentDate.getFullYear();
      const month = currentDate.getMonth() + 1; // Les mois JavaScript commencent à 0

      const response = await fetch(
        `api-calendar.php?year=${year}&month=${month}`
      );
      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
      }

      events = await response.json();
      generateCalendar();
    } catch (error) {
      console.error("Erreur lors du chargement des événements:", error);
      // Fallback: utiliser les données locales si le serveur n'est pas disponible
      events = JSON.parse(localStorage.getItem("calendarEvents") || "[]");
      generateCalendar();
    }
  }

  // Fonction pour générer le calendrier
  function generateCalendar() {
    calendar.innerHTML = "";

    // Ajouter les entêtes de jour de la semaine
    weekdays.forEach((day) => {
      const weekdayElem = document.createElement("div");
      weekdayElem.className = "weekday";
      weekdayElem.textContent = day;
      calendar.appendChild(weekdayElem);
    });

    // Calculer le premier jour du mois
    const firstDay = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth(),
      1
    );
    const lastDay = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth() + 1,
      0
    );

    // Ajouter les jours vides avant le premier jour du mois
    for (let i = 0; i < firstDay.getDay(); i++) {
      const emptyDay = document.createElement("div");
      emptyDay.className = "day empty";
      calendar.appendChild(emptyDay);
    }

    // Ajouter les jours du mois
    for (let i = 1; i <= lastDay.getDate(); i++) {
      const dayElem = document.createElement("div");
      dayElem.className = "day";

      const dateStr = `${currentDate.getFullYear()}-${(
        currentDate.getMonth() + 1
      )
        .toString()
        .padStart(2, "0")}-${i.toString().padStart(2, "0")}`;

      dayElem.setAttribute("data-date", dateStr);
      dayElem.setAttribute(
        "data-weekday",
        weekdays[
          new Date(
            currentDate.getFullYear(),
            currentDate.getMonth(),
            i
          ).getDay()
        ]
      );

      // Vérifier si c'est aujourd'hui
      const today = new Date();
      if (
        today.getDate() === i &&
        today.getMonth() === currentDate.getMonth() &&
        today.getFullYear() === currentDate.getFullYear()
      ) {
        dayElem.classList.add("today");
      }

      const dayNumber = document.createElement("span");
      dayNumber.className = "day-number";
      dayNumber.textContent = i;
      dayElem.appendChild(dayNumber);

      // Ajouter les événements pour ce jour
      const dayEvents = events.filter((event) => {
        const eventDate = new Date(event.date);
        return (
          eventDate.getDate() === i &&
          eventDate.getMonth() === currentDate.getMonth() &&
          eventDate.getFullYear() === currentDate.getFullYear()
        );
      });

      dayEvents.forEach((event) => {
        const eventElem = document.createElement("div");
        eventElem.className = event.type === "event" ? "event" : "task";

        let displayText = event.title;
        if (event.time) {
          displayText = `${event.time} - ${event.title}`;
        }

        eventElem.textContent = displayText;
        eventElem.setAttribute("data-id", event.id);
        eventElem.addEventListener("click", (e) => {
          e.stopPropagation();
          openEditEventModal(event);
        });

        dayElem.appendChild(eventElem);
      });

      // Ajouter l'événement click pour ajouter un nouvel événement
      dayElem.addEventListener("click", () => {
        const dateStr = dayElem.getAttribute("data-date");
        openAddEventModal(dateStr);
      });

      calendar.appendChild(dayElem);
    }

    // Mettre à jour l'affichage du mois courant
    currentMonthElem.textContent = `${
      months[currentDate.getMonth()]
    } ${currentDate.getFullYear()}`;
  }

  // Fonction pour ouvrir la modal d'ajout d'événement
  function openAddEventModal(dateStr) {
    selectedDate = dateStr;
    modalTitle.textContent = "Ajouter un rendez-vous";
    document.getElementById("eventId").value = "";
    document.getElementById("eventDate").value = dateStr;
    document.getElementById("eventType").value = "event";
    document.getElementById("eventTitle").value = "";
    document.getElementById("eventTime").value = "";
    document.getElementById("eventDescription").value = "";

    // Remplir les champs avec les informations de l'utilisateur si disponibles
    document.getElementById("emailAddress").value =
      USER_EMAIL || localStorage.getItem("userEmail") || "";
    document.getElementById("phoneNumber").value =
      USER_PHONE || localStorage.getItem("userPhone") || "";

    deleteEventBtn.style.display = "none";

    // Cacher les détails de notification par défaut
    notificationDetails.style.display = "none";
    toggleNotifications.textContent = "+ Options de rappel";

    eventModal.style.display = "flex";
  }

  // Fonction pour ouvrir la modal d'édition d'événement
  function openEditEventModal(event) {
    selectedDate = event.date;
    modalTitle.textContent = "Modifier un rendez-vous";
    document.getElementById("eventId").value = event.id;
    document.getElementById("eventDate").value = event.date;
    document.getElementById("eventType").value = event.type;
    document.getElementById("eventTitle").value = event.title;
    document.getElementById("eventTime").value = event.time || "";
    document.getElementById("eventDescription").value = event.description || "";

    // Gérer les notifications
    const notifications = event.notifications || {};
    document.getElementById("emailNotification").checked =
      notifications.email || false;
    document.getElementById("smsNotification").checked =
      notifications.sms || false;
    document.getElementById("emailAddress").value =
      notifications.emailAddress || USER_EMAIL || "";
    document.getElementById("phoneNumber").value =
      notifications.phoneNumber || USER_PHONE || "";
    document.getElementById("reminderTime").value =
      notifications.reminderTime || "60";

    // Cacher les détails de notification par défaut
    notificationDetails.style.display = "none";
    toggleNotifications.textContent = "+ Options de rappel";

    deleteEventBtn.style.display = "block";

    eventModal.style.display = "flex";
  }

  // Fonction pour sauvegarder un événement
  async function saveEvent(e) {
    e.preventDefault();

    const eventId = document.getElementById("eventId").value || null;
    const eventDate = document.getElementById("eventDate").value;
    const eventType = document.getElementById("eventType").value;
    const eventTitle = document.getElementById("eventTitle").value;
    const eventTime = document.getElementById("eventTime").value;
    const eventDescription = document.getElementById("eventDescription").value;

    // Sauvegarde des informations de contact pour les futures utilisations
    const emailAddress = document.getElementById("emailAddress").value;
    const phoneNumber = document.getElementById("phoneNumber").value;
    localStorage.setItem("userEmail", emailAddress);
    localStorage.setItem("userPhone", phoneNumber);

    const newEvent = {
      id: eventId,
      date: eventDate,
      type: eventType,
      title: eventTitle,
      time: eventTime,
      description: eventDescription,
      notifications: {
        email: document.getElementById("emailNotification").checked,
        sms: document.getElementById("smsNotification").checked,
        emailAddress: emailAddress,
        phoneNumber: phoneNumber,
        reminderTime: document.getElementById("reminderTime").value,
      },
    };

    try {
      // Envoyer au serveur
      const response = await fetch("api-calendar.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(newEvent),
      });

      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
      }

      const result = await response.json();
      console.log("Événement enregistré:", result);

      // Mettre à jour l'ID si c'est un nouvel événement
      if (!eventId && result.id) {
        newEvent.id = result.id;
      }

      // Mise à jour locale des événements
      const existingEventIndex = events.findIndex(
        (event) => event.id === newEvent.id
      );
      if (existingEventIndex !== -1) {
        events[existingEventIndex] = newEvent;
      } else {
        events.push(newEvent);
      }

      // Sauvegarde locale de secours
      localStorage.setItem("calendarEvents", JSON.stringify(events));

      // Fermer la modal et rafraîchir le calendrier
      eventModal.style.display = "none";
      generateCalendar();
    } catch (error) {
      console.error("Erreur lors de l'enregistrement:", error);
      alert(
        "Impossible de communiquer avec le serveur. Votre événement a été enregistré localement."
      );

      // Fallback: sauvegarder localement en cas d'erreur
      if (!eventId) {
        newEvent.id = Date.now().toString();
      }

      const existingEventIndex = events.findIndex(
        (event) => event.id === newEvent.id
      );
      if (existingEventIndex !== -1) {
        // Mise à jour locale des événements (suite)
        events[existingEventIndex] = newEvent;
      } else {
        events.push(newEvent);
      }

      localStorage.setItem("calendarEvents", JSON.stringify(events));
      eventModal.style.display = "none";
      generateCalendar();
    }
  }

  // Fonction pour supprimer un événement
  async function deleteEvent() {
    const eventId = document.getElementById("eventId").value;
    if (!eventId) return;

    if (!confirm("Êtes-vous sûr de vouloir supprimer cet événement?")) {
      return;
    }

    try {
      // Supprimer sur le serveur
      const response = await fetch("api-calendar.php", {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ id: eventId }),
      });

      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
      }

      const result = await response.json();
      console.log("Événement supprimé:", result);

      // Supprimer localement
      events = events.filter((event) => event.id !== eventId);
      localStorage.setItem("calendarEvents", JSON.stringify(events));
    } catch (error) {
      console.error("Erreur lors de la suppression:", error);
      alert(
        "Impossible de communiquer avec le serveur. L'événement a été supprimé localement."
      );

      // Fallback: supprimer localement en cas d'erreur
      events = events.filter((event) => event.id !== eventId);
      localStorage.setItem("calendarEvents", JSON.stringify(events));
    }

    // Fermer la modal et rafraîchir le calendrier
    eventModal.style.display = "none";
    generateCalendar();
  }

  // Événements d'écoute
  prevMonthBtn.addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    loadEvents(); // Recharger les événements pour le nouveau mois
  });

  nextMonthBtn.addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    loadEvents(); // Recharger les événements pour le nouveau mois
  });

  addEventBtn.addEventListener("click", () => {
    const today = new Date();
    const dateStr = `${today.getFullYear()}-${(today.getMonth() + 1)
      .toString()
      .padStart(2, "0")}-${today.getDate().toString().padStart(2, "0")}`;
    openAddEventModal(dateStr);
  });

  closeModal.addEventListener("click", () => {
    eventModal.style.display = "none";
  });

  eventForm.addEventListener("submit", saveEvent);
  deleteEventBtn.addEventListener("click", deleteEvent);

  // Fermer la modal si on clique en dehors
  window.addEventListener("click", (e) => {
    if (e.target === eventModal) {
      eventModal.style.display = "none";
    }
  });

  // Initialiser le calendrier
  loadEvents();
});
