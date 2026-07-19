import { useEffect, useState } from "react";
import apiClient from "../../../shared/api/apiClient";
import { useAuth } from "../../../shared/auth/AuthContext";
import MemoireForm from "../components/MemoireForm";

export default function MemoiresListPage() {
  const { user } = useAuth();
  const [memoires, setMemoires] = useState([]);
  const [enseignants, setEnseignants] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [editingId, setEditingId] = useState(null);
  const [selectedEnseignant, setSelectedEnseignant] = useState("");

  const chargerMemoires = () => {
    setLoading(true);
    apiClient
      .get("/memoires")
      .then(({ data }) => setMemoires(data.data))
      .catch(() => setError("Impossible de charger les memoires."))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    chargerMemoires();
    apiClient.get("/annuaire/enseignants").then(({ data }) => setEnseignants(data)).catch(() => {});
  }, []);

  const handleMemoireCreated = (nouveau) => {
    setMemoires((prev) => [nouveau, ...prev]);
  };

  const handleValider = async (memoireId) => {
    try {
      await apiClient.post(`/memoires/${memoireId}/valider`);
      setMemoires((prev) =>
        prev.map((m) => (m.id === memoireId ? { ...m, statut: "valide" } : m))
      );
    } catch (err) {
      alert("Validation impossible : " + (err.response?.data?.message || "erreur inconnue"));
    }
  };

  const handleRejeter = async (memoireId) => {
    const commentaire = prompt("Motif du rejet :");
    if (!commentaire) return;
    try {
      await apiClient.post(`/memoires/${memoireId}/rejeter`, {
        commentaire_validation: commentaire,
      });
      setMemoires((prev) =>
        prev.map((m) => (m.id === memoireId ? { ...m, statut: "rejete" } : m))
      );
    } catch (err) {
      alert("Rejet impossible : " + (err.response?.data?.message || "erreur inconnue"));
    }
  };

  const ouvrirAffectation = (memoireId) => {
    setEditingId(memoireId);
    setSelectedEnseignant("");
  };

  const annulerAffectation = () => {
    setEditingId(null);
    setSelectedEnseignant("");
  };

  const confirmerAffectation = async (memoireId) => {
    if (!selectedEnseignant) return;
    try {
      const { data } = await apiClient.post(`/memoires/${memoireId}/affecter-encadreur`, {
        encadreur_id: selectedEnseignant,
      });
      setMemoires((prev) => prev.map((m) => (m.id === memoireId ? data : m)));
      annulerAffectation();
    } catch (err) {
      alert("Affectation impossible : " + (err.response?.data?.message || "erreur inconnue"));
    }
  };

  const peutCreer = user?.roles?.some((r) => r.name === "etudiant");
  const peutValiderOuAffecter = user?.roles?.some((r) => r.name === "responsable_formation");

  if (loading) return <p>Chargement des memoires...</p>;
  if (error) return <p style={{ color: "red" }}>{error}</p>;

  return (
    <div style={{ maxWidth: 700, margin: "40px auto" }}>
      <h1>Memoires</h1>

      {peutCreer && <MemoireForm onMemoireCreated={handleMemoireCreated} />}

      {memoires.length === 0 && <p>Aucun memoire pour le moment.</p>}
      <ul style={{ listStyle: "none", padding: 0 }}>
        {memoires.map((memoire) => (
          <li
            key={memoire.id}
            style={{ border: "1px solid #ddd", borderRadius: 8, padding: 16, marginBottom: 12 }}
          >
            <strong>{memoire.titre}</strong>
            <p style={{ margin: "4px 0", color: "#666" }}>
              Statut : <span style={{ fontWeight: "bold" }}>{memoire.statut}</span>
            </p>

            {editingId === memoire.id ? (
              <div style={{ display: "flex", gap: 8, alignItems: "center", marginTop: 8 }}>
                <select
                  value={selectedEnseignant}
                  onChange={(e) => setSelectedEnseignant(e.target.value)}
                  style={{ padding: 6 }}
                >
                  <option value="">-- Choisir un encadreur --</option>
                  {enseignants.map((ens) => (
                    <option key={ens.id} value={ens.id}>
                      {ens.nom} ({ens.specialite})
                    </option>
                  ))}
                </select>
                <button onClick={() => confirmerAffectation(memoire.id)}>Confirmer</button>
                <button onClick={annulerAffectation}>Annuler</button>
              </div>
            ) : (
              peutValiderOuAffecter && (
                <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
                  {memoire.statut === "propose" && (
                    <>
                      <button onClick={() => handleValider(memoire.id)}>Valider</button>
                      <button onClick={() => handleRejeter(memoire.id)}>Rejeter</button>
                    </>
                  )}
                  {memoire.statut === "valide" && (
                    <button onClick={() => ouvrirAffectation(memoire.id)}>Affecter un encadreur</button>
                  )}
                </div>
              )
            )}
          </li>
        ))}
      </ul>
    </div>
  );
}