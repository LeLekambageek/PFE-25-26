import { useEffect, useState } from "react";
import apiClient from "../../../shared/api/apiClient";
import { useAuth } from "../../../shared/auth/AuthContext";
import EncadrementForm from "../components/EncadrementForm";

export default function EncadrementsListPage() {
  const { user } = useAuth();
  const [encadrements, setEncadrements] = useState([]);
  const [enseignants, setEnseignants] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [editingId, setEditingId] = useState(null);
  const [selectedEnseignant, setSelectedEnseignant] = useState("");

  const chargerEncadrements = () => {
    setLoading(true);
    apiClient
      .get("/encadrements")
      .then(({ data }) => setEncadrements(data.data))
      .catch(() => setError("Impossible de charger les encadrements."))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    chargerEncadrements();
    apiClient.get("/annuaire/enseignants").then(({ data }) => setEnseignants(data)).catch(() => {});
  }, []);

  const handleEncadrementCreated = (nouvel) => {
    setEncadrements((prev) => [nouvel, ...prev]);
  };

  const handleAjouterEntree = async (encadrementId) => {
    const contenu = prompt("Contenu de l'entrée :");
    if (!contenu) return;

    try {
      await apiClient.post(`/encadrements/${encadrementId}/entree`, { contenu });
      alert("Entrée ajoutée.");
    } catch (err) {
      alert("Action impossible : " + (err.response?.data?.message || "erreur inconnue"));
    }
  };

  const handlePlanifierRdv = async (encadrementId) => {
    const datePrevue = prompt("Date et heure du rendez-vous (format: 2026-08-15 14:00:00) :");
    if (!datePrevue) return;
    const sujet = prompt("Sujet du rendez-vous (optionnel) :") || null;

    try {
      await apiClient.post(`/encadrements/${encadrementId}/rendez-vous`, {
        date_prevue: datePrevue,
        sujet,
      });
      alert("Rendez-vous planifié.");
    } catch (err) {
      alert("Action impossible : " + (err.response?.data?.message || "erreur inconnue"));
    }
  };

  const ouvrirEdition = (enc) => {
    setEditingId(enc.id);
    setSelectedEnseignant(enc.enseignant_id ?? "");
  };

  const annulerEdition = () => {
    setEditingId(null);
    setSelectedEnseignant("");
  };

  const confirmerModification = async (encadrementId) => {
    if (!selectedEnseignant) return;

    try {
      const { data } = await apiClient.put(`/encadrements/${encadrementId}`, {
        enseignant_id: selectedEnseignant,
      });
      setEncadrements((prev) => prev.map((e) => (e.id === encadrementId ? data : e)));
      annulerEdition();
    } catch (err) {
      alert("Modification impossible : " + (err.response?.data?.message || "erreur inconnue"));
    }
  };

  const handleCloturer = async (encadrementId) => {
    try {
      await apiClient.post(`/encadrements/${encadrementId}/cloturer`);
      chargerEncadrements();
    } catch (err) {
      alert("Clôture impossible : " + (err.response?.data?.message || "erreur inconnue"));
    }
  };

  const peutCreer = user?.roles?.some((r) => r.name === "responsable_formation");
  const peutModifier = user?.roles?.some((r) => r.name === "responsable_formation");
  const peutAjouterEntree = user?.roles?.some(
    (r) => r.name === "etudiant" || r.name === "enseignant_encadreur"
  );
  const peutGererEnseignant = user?.roles?.some((r) => r.name === "enseignant_encadreur");

  if (loading) return <p>Chargement des encadrements...</p>;
  if (error) return <p style={{ color: "red" }}>{error}</p>;

  return (
    <div style={{ maxWidth: 700, margin: "40px auto" }}>
      <h1>Encadrements</h1>

      {peutCreer && <EncadrementForm onEncadrementCreated={handleEncadrementCreated} />}

      {encadrements.length === 0 && <p>Aucun encadrement pour le moment.</p>}
      <ul style={{ listStyle: "none", padding: 0 }}>
        {encadrements.map((enc) => (
          <li
            key={enc.id}
            style={{ border: "1px solid #ddd", borderRadius: 8, padding: 16, marginBottom: 12 }}
          >
            <strong>Encadrement #{enc.id} — {enc.type}</strong>
            <p style={{ margin: "4px 0", color: "#666" }}>
              Statut : <span style={{ fontWeight: "bold" }}>{enc.statut}</span>
            </p>

            {editingId === enc.id ? (
              <div style={{ display: "flex", gap: 8, alignItems: "center", marginTop: 8 }}>
                <select
                  value={selectedEnseignant}
                  onChange={(e) => setSelectedEnseignant(e.target.value)}
                  style={{ padding: 6 }}
                >
                  <option value="">-- Choisir un enseignant --</option>
                  {enseignants.map((ens) => (
                    <option key={ens.id} value={ens.id}>
                      {ens.nom} ({ens.specialite})
                    </option>
                  ))}
                </select>
                <button onClick={() => confirmerModification(enc.id)}>Confirmer</button>
                <button onClick={annulerEdition}>Annuler</button>
              </div>
            ) : (
              <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
                {peutAjouterEntree && (
                  <button onClick={() => handleAjouterEntree(enc.id)}>Ajouter une entrée</button>
                )}
                {peutGererEnseignant && (
                  <button onClick={() => handlePlanifierRdv(enc.id)}>Planifier un rendez-vous</button>
                )}
                {peutModifier && (
                  <button onClick={() => ouvrirEdition(enc)}>Modifier l'enseignant</button>
                )}
                {peutGererEnseignant && enc.statut === "actif" && (
                  <button onClick={() => handleCloturer(enc.id)}>Clôturer</button>
                )}
              </div>
            )}
          </li>
        ))}
      </ul>
    </div>
  );
}