import { useState } from "react";
import apiClient from "../../../shared/api/apiClient";
import { useAuth } from "../../../shared/auth/AuthContext";

export default function MemoireForm({ onMemoireCreated }) {
  const { user } = useAuth();
  const [titre, setTitre] = useState("");
  const [description, setDescription] = useState("");
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const { data } = await apiClient.post("/memoires", {
        titre,
        description,
        etudiant_id: user?.id,
      });

      setTitre("");
      setDescription("");

      onMemoireCreated?.(data);
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
      <h2 style={{ marginTop: 0 }}>Proposer un sujet de memoire</h2>

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

      {error && <p style={{ color: "red" }}>{error}</p>}

      <button type="submit" disabled={loading}>
        {loading ? "Envoi..." : "Proposer le sujet"}
      </button>
    </form>
  );
}