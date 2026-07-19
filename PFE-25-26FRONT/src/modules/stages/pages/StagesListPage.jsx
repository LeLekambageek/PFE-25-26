import { useEffect, useState } from "react";
import apiClient from "../../../shared/api/apiClient";
import { useAuth } from "../../../shared/auth/AuthContext";
import StageForm from "../components/StageForm";

export default function StagesListPage() {
  const { user } = useAuth();
  const [stages, setStages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [openJournalId, setOpenJournalId] = useState(null);
  const [journalEntries, setJournalEntries] = useState([]);
  const [journalLoading, setJournalLoading] = useState(false);
  const [nouvelleEntree, setNouvelleEntree] = useState("");

  const chargerStages = () => {
    setLoading(true);
    apiClient
      .get("/stages")
      .then(({ data }) => setStages(data.data))
      .catch(() => setError("Impossible de charger les stages."))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    chargerStages();
  }, []);

  const handleValider = async (stageId) => {
    try {
      await apiClient.post(`/stages/${stageId}/valider`);
      setStages((prev) =>
        prev.map((s) => (s.id === stageId ? { ...s, statut: "valide" } : s))
      );
    } catch (err) {
      alert("Validation impossible : " + (err.response?.data?.message || "erreur inconnue"));
    }
  };

  const handleStageCreated = (nouveauStage) => {
    setStages((prev) => [nouveauStage, ...prev]);
  };

  const toggleJournal = async (stageId) => {
    if (openJournalId === stageId) {
      setOpenJournalId(null);
      return;
    }
    setOpenJournalId(stageId);
    setJournalLoading(true);
    try {
      const { data } = await apiClient.get(`/stages/${stageId}/journal`);
      setJournalEntries(data);
    } catch (err) {
      setJournalEntries([]);
    } finally {
      setJournalLoading(false);
    }
  };

  const handleAjouterEntree = async (stageId) => {
    if (!nouvelleEntree.trim()) return;
    try {
      const { data } = await apiClient.post(`/stages/${stageId}/journal`, {
        contenu: nouvelleEntree,
      });
      setJournalEntries((prev) => [data, ...prev]);
      setNouvelleEntree("");
    } catch (err) {
      alert("Ajout impossible : " + (err.response?.data?.message || "erreur inconnue"));
    }
  };

  const peutValider = user?.roles?.some(
    (r) => r.name === "enseignant_encadreur" || r.name === "responsable_formation"
  );
  const estEtudiant = user?.roles?.some((r) => r.name === "etudiant");

  if (loading) return <p>Chargement des stages...</p>;
  if (error) return <p style={{ color: "red" }}>{error}</p>;

  return (
    <div style={{ maxWidth: 700, margin: "40px auto" }}>
      <h1>Mes stages</h1>

      {estEtudiant && <StageForm onStageCreated={handleStageCreated} />}

      {stages.length === 0 && <p>Aucun stage pour le moment.</p>}
      <ul style={{ listStyle: "none", padding: 0 }}>
        {stages.map((stage) => (
          <li
            key={stage.id}
            style={{
              border: "1px solid #ddd",
              borderRadius: 8,
              padding: 16,
              marginBottom: 12,
            }}
          >
            <strong>{stage.titre}</strong>
            <p style={{ margin: "4px 0", color: "#666" }}>
              Statut : <span style={{ fontWeight: "bold" }}>{stage.statut}</span>
            </p>

            <div style={{ display: "flex", gap: 8, marginBottom: 8 }}>
              {peutValider && stage.statut === "en_attente" && (
                <button onClick={() => handleValider(stage.id)}>Valider</button>
              )}
              <button onClick={() => toggleJournal(stage.id)}>
                {openJournalId === stage.id ? "Fermer le journal" : "Voir le journal de bord"}
              </button>
            </div>

            {openJournalId === stage.id && (
              <div style={{ background: "#f9f9f9", borderRadius: 6, padding: 12, marginTop: 8 }}>
                <div style={{ display: "flex", gap: 8, marginBottom: 10 }}>
                  <input
                    type="text"
                    placeholder="Nouvelle entrée..."
                    value={nouvelleEntree}
                    onChange={(e) => setNouvelleEntree(e.target.value)}
                    style={{ flex: 1, padding: 6 }}
                  />
                  <button onClick={() => handleAjouterEntree(stage.id)}>Ajouter</button>
                </div>

                {journalLoading && <p>Chargement du journal...</p>}
                {!journalLoading && journalEntries.length === 0 && (
                  <p style={{ color: "#999" }}>Aucune entrée pour le moment.</p>
                )}
                {!journalLoading && journalEntries.length > 0 && (
                  <ul style={{ listStyle: "none", padding: 0, margin: 0 }}>
                    {journalEntries.map((entry) => (
                      <li
                        key={entry.id}
                        style={{ borderBottom: "1px solid #e0e0e0", padding: "6px 0" }}
                      >
                        <p style={{ margin: 0 }}>{entry.contenu}</p>
                        <span style={{ fontSize: 12, color: "#999" }}>
                          {entry.auteur?.name} — {new Date(entry.created_at).toLocaleDateString()}
                        </span>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            )}
          </li>
        ))}
      </ul>
    </div>
  );
}