import { useState } from "react";
import apiClient from "../../../shared/api/apiClient";

export default function StageForm({ onStageCreated }) {
  const [titre, setTitre] = useState("");
  const [description, setDescription] = useState("");
  const [dateDebut, setDateDebut] = useState("");
  const [dateFin, setDateFin] = useState("");
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const { data } = await apiClient.post("/stages", {
        titre,
        description,
        date_debut: dateDebut,
        date_fin: dateFin,
      });

      setTitre("");
      setDescription("");
      setDateDebut("");
      setDateFin("");

      onStageCreated?.(data);
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
      <h2 style={{ marginTop: 0 }}>Demander un stage</h2>

      <div style={{ marginBottom: 12 }}>
        <label>Titre</label>
        <input
          type="text"
          value={titre}
          onChange={(e) => setTitre(e.target.value)}
          required
          style={{ width: "100%", padding: 8 }}
        />
      </div>

      <div style={{ marginBottom: 12 }}>
        <label>Description</label>
        <textarea
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          style={{ width: "100%", padding: 8 }}
        />
      </div>

      <div style={{ display: "flex", gap: 12, marginBottom: 12 }}>
        <div style={{ flex: 1 }}>
          <label>Date de début</label>
          <input
            type="date"
            value={dateDebut}
            onChange={(e) => setDateDebut(e.target.value)}
            required
            style={{ width: "100%", padding: 8 }}
          />
        </div>
        <div style={{ flex: 1 }}>
          <label>Date de fin</label>
          <input
            type="date"
            value={dateFin}
            onChange={(e) => setDateFin(e.target.value)}
            required
            style={{ width: "100%", padding: 8 }}
          />
        </div>
      </div>

      {error && <p style={{ color: "red" }}>{error}</p>}

      <button type="submit" disabled={loading}>
        {loading ? "Envoi..." : "Envoyer la demande"}
      </button>
    </form>
  );
}