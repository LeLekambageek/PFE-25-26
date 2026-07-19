import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { AuthProvider } from "./shared/auth/AuthContext";
import ProtectedRoute from "./shared/auth/ProtectedRoute";
import LoginPage from "./shared/auth/LoginPage";
import StagesListPage from "./modules/stages/pages/StagesListPage";
import EncadrementsListPage from "./modules/encadrements/pages/EncadrementsListPage";
import MemoiresListPage from "./modules/memoires/pages/MemoiresListPage";

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />

          <Route
            path="/stages"
            element={
              <ProtectedRoute>
                <StagesListPage />
              </ProtectedRoute>
            }
          />

          <Route
            path="/encadrements"
            element={
              <ProtectedRoute>
                <EncadrementsListPage />
              </ProtectedRoute>
            }
          />

          <Route
            path="/memoires"
            element={
              <ProtectedRoute>
                <MemoiresListPage />
              </ProtectedRoute>
           }
          />

          <Route path="/" element={<Navigate to="/login" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}