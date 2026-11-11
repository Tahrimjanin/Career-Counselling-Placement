import axios from "axios";
const origin = (import.meta.env.VITE_API_URL || "http://localhost:8000")
  .replace(/\/api\/?$/, "")
  .replace(/\/$/, "");
const TOKEN_KEY = "ccp_access_token";
export const getToken = () => sessionStorage.getItem(TOKEN_KEY);
export const setToken = (token) =>
  token
    ? sessionStorage.setItem(TOKEN_KEY, token)
    : sessionStorage.removeItem(TOKEN_KEY);
const api = axios.create({
  baseURL: `${origin}/api`,
  withCredentials: false,
  headers: { Accept: "application/json" },
});
api.interceptors.request.use((request) => {
  const token = getToken();
  if (token) request.headers.Authorization = `Bearer ${token}`;
  return request;
});
api.interceptors.response.use(
  (r) => r,
  (e) => {
    if (e.response?.status === 401) {
      setToken(null);
      window.dispatchEvent(new Event("auth-expired"));
    }
    return Promise.reject(e);
  },
);
export const err = (e) =>
  Object.values(e.response?.data?.errors || {})[0]?.[0] ||
  e.response?.data?.message ||
  "Unable to connect. Please try again.";
export const verificationPath = (value) => {
  const url = new URL(value);
  if (
    url.origin !== new URL(origin).origin ||
    !/^\/api\/email\/verify\/\d+\/[a-f0-9]+$/.test(url.pathname)
  )
    throw new Error("Invalid verification link.");
  return url.pathname.replace(/^\/api/, "") + url.search;
};
export default api;
