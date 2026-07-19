import { useEffect, useState } from "react";
import apiClient from "../../../shared/api/apiClient";

export default function EncadrementForm({ onEncadrementCreated }) {
  const [etudiants, setEtudiants] = useState([]);
  const [enseignants, setEnseignants] = useState([]);
  const [etudiantId, setEtudiantId] = useState("");
  const [enseignantId, setEnseignantId] = useState("");
  const [type, setType] = useState("stage");
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    apiClient.get("/annuaire/etudiants").then(({ data }) => setEtudiants(data));
    apiClient.get("/annuaire/enseignants").then(({ data }) => setEnseignants(data));
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const { data } = await apiClient.post("/encadrements", {
        etudiant_id: etudiantId,
        enseignant_id: enseignantId,
        type,
      });

      setEtudiantId("");
      setEnseignantId("");
      setType("stage");

      onEncadrementCreated?.(data);
    } catch (err) {
      const messages = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(" ")
        : "Une erreur est survenue.";
      setError(messages);
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} style={{ border: "1px solid #ddd", borderRadius: 8, padding: 16, marginBottom: 20 }}>
      <h2 style={{ marginTop: 0 }}>Créer un encadrement</h2>

      <div style={{ marginBottom: 12 }}>
        <label>Étudiant</label>
        <select value={etudiantId} onChange={(e) => setEtudiantId(e.target.value)} required style={{ width: "100%", padding: 8 }}>
          <option value="">-- Choisir --</option>
          {etudiants.map((e) => (
            <option key={e.id} value={e.id}>{e.nom} ({e.matricule})</option>
          ))}
        </select>
      </div>

      <div style={{ marginBottom: 12 }}>
        <label>Enseignant</label>
        <select value={enseignantId} onChange={(e) => setEnseignantId(e.target.value)} required style={{ width: "100%", padding: 8 }}>
          <option value="">-- Choisir --</option>
          {enseignants.map((e) => (
            <option key={e.id} value={e.id}>{e.nom} ({e.specialite})</option>
          ))}
        </select>
      </div>

      <div style={{ marginBottom: 12 }}>
        <label>Type</label>
        <select value={type} onChange={(e) => setType(e.target.value)} style={{ width: "100%", padding: 8 }}>
          <option value="stage">Stage</option>
          <option value="memoire">Mémoire</option>
        </select>
      </div>

      {error && <p style={{ color: "red" }}>{error}</p>}

      <button type="submit" disabled={loading}>
        {loading ? "Envoi..." : "Créer l'encadrement"}
      </button>
    </form>
  );
}