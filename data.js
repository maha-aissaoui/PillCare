// data.js - Simule une base de données de médicaments
let medications = [
  {
    id: 1,
    name: "Paracétamol",
    dosage: "500mg",
    form: "tablet",
    color: "#4e73df",
    schedule: ["8h00", "20h00"],
    adherence: 93,
    daysLeft: 15,
    quantity: 24,
  },
  {
    id: 2,
    name: "Amoxicilline",
    dosage: "250mg",
    form: "capsule",
    color: "#1cc88a",
    schedule: ["8h00", "14h00", "20h00"],
    adherence: 87,
    daysLeft: 5,
    quantity: 12,
  },
  {
    id: 3,
    name: "Aspirine",
    dosage: "100mg",
    form: "tablet",
    color: "#e74a3b",
    schedule: ["Matin"],
    adherence: 100,
    daysLeft: 30,
    quantity: 30,
  },
  {
    id: 4,
    name: "Vitamine D",
    dosage: "1000 UI",
    form: "capsule",
    color: "#f6c23e",
    schedule: ["Petit déjeuner"],
    adherence: 95,
    daysLeft: 45,
    quantity: 45,
  },
];

// API simulée pour accéder aux données
const medicationsAPI = {
  // Récupérer tous les médicaments
  getAllMedications: function () {
    return [...medications];
  },

  // Ajouter un nouveau médicament
  addMedication: function (medication) {
    const newId =
      medications.length > 0
        ? Math.max(...medications.map((med) => med.id)) + 1
        : 1;
    const newMedication = {
      id: newId,
      ...medication,
      adherence: Math.floor(Math.random() * 20) + 80, // Valeur aléatoire entre 80% et 100%
    };
    medications.push(newMedication);
    return newMedication;
  },

  // Mettre à jour un médicament existant
  updateMedication: function (id, updatedMedication) {
    const index = medications.findIndex((med) => med.id === id);
    if (index !== -1) {
      medications[index] = { ...medications[index], ...updatedMedication };
      return medications[index];
    }
    return null;
  },

  // Supprimer un médicament
  deleteMedication: function (id) {
    const index = medications.findIndex((med) => med.id === id);
    if (index !== -1) {
      const deletedMedication = medications[index];
      medications.splice(index, 1);
      return deletedMedication;
    }
    return null;
  },

  // Rechercher des médicaments par nom ou dosage
  searchMedications: function (searchTerm) {
    searchTerm = searchTerm.toLowerCase();
    return medications.filter(
      (med) =>
        med.name.toLowerCase().includes(searchTerm) ||
        med.dosage.toLowerCase().includes(searchTerm)
    );
  },

  // Filtrer les médicaments par forme
  filterMedicationsByForm: function (form) {
    if (!form || form === "all") return [...medications];
    return medications.filter((med) => med.form === form);
  },
};

// Exporter l'API pour utilisation dans d'autres fichiers
if (typeof module !== "undefined") {
  module.exports = medicationsAPI;
}
