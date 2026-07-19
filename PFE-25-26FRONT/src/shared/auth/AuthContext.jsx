import { createContext, useContext, useState, useEffect, useCallback } from "react";
import apiClient from "../api/apiClient";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem("api_token");
    if (!token) {
      setLoading(false);
      return;
    }

    apiClient
      .get("/me")
      .then(({ data }) => setUser(data))
      .catch(() => {
        localStorage.removeItem("api_token");
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, []);

  const login = useCallback(async (email, password) => {
    setLoading(true);
    try {
      const { data } = await apiClient.post("/login", { email, password });
      localStorage.setItem("api_token", data.token);
      setUser(data.user);
      return data.user;
    } finally {
      setLoading(false);
    }
  }, []);

  const logout = useCallback(async () => {
    await apiClient.post("/logout");
    localStorage.removeItem("api_token");
    setUser(null);
  }, []);

  const hasRole = useCallback(
    (role) => user?.roles?.some((r) => r.name === role) ?? false,
    [user]
  );

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, hasRole }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth doit être utilisé dans un AuthProvider");
  return ctx;
}