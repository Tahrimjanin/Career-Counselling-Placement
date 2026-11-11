import { createContext, useContext, useEffect, useState } from "react";
import api, { getToken, setToken } from "./api";
const Ctx = createContext();
export const useAuth = () => useContext(Ctx);
export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const refresh = async () => {
    const r = await api.get("/me");
    setUser(r.data.data);
    return r.data.data;
  };
  useEffect(() => {
    // Remove credentials left by the old bearer-token implementation.
    localStorage.removeItem("token");
    const expired = () => {
      setUser(null);
      if (
        ![
          "/login",
          "/register",
          "/forgot-password",
          "/reset-password",
          "/verify-email",
        ].includes(location.pathname)
      )
        location.assign("/login");
    };
    window.addEventListener("auth-expired", expired);
    if (!getToken()) {
      setLoading(false);
      return () => window.removeEventListener("auth-expired", expired);
    }
    api
      .get("/me")
      .then((r) => setUser(r.data.data))
      .catch(() => setUser(null))
      .finally(() => setLoading(false));
    return () => window.removeEventListener("auth-expired", expired);
  }, []);
  const authenticate = async (path, data) => {
    const r = await api.post(path, data);
    setToken(r.data.token);
    setUser(r.data.data);
    return r.data.data;
  };
  const login = (email, password, role) =>
    authenticate("/login", { email, password, role });
  const register = (f) => authenticate("/register", f);
  const logout = async () => {
    await api.post("/logout");
    setToken(null);
    setUser(null);
  };
  return (
    <Ctx.Provider value={{ user, loading, login, register, logout, refresh }}>
      {children}
    </Ctx.Provider>
  );
}
