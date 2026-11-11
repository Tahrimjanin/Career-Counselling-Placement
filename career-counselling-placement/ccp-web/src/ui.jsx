import { useCallback, useEffect, useState } from "react";
import api, { err } from "./api";

const TONE = {
  Applied: "neutral",
  Shortlisted: "pending",
  Interview: "pending",
  Offer: "success",
  Rejected: "attention",
  approved: "success",
  pending: "pending",
  changes_requested: "attention",
  rejected: "attention",
};
export const Badge = ({ status }) => (
  <span className={`badge ${TONE[status] || "neutral"}`}>
    {String(status).replace("_", " ")}
  </span>
);
export const Card = ({ title, children }) => (
  <section className="card">
    {title && <h3>{title}</h3>}
    {children}
  </section>
);
export const fmt = (d) => new Date(d).toLocaleString();
export const Msg = ({ text }) =>
  text ? (
    <p className="msg" role="status">
      {text}
    </p>
  ) : null;

export function useFetch(url) {
  const [data, setData] = useState(null);
  const [error, setError] = useState("");
  const reload = useCallback(() => {
    if (!url) {
      setData(null);
      return;
    }
    api
      .get(url)
      .then((r) => {
        setData(r.data);
        setError("");
      })
      .catch((e) => setError(err(e)));
  }, [url]);
  useEffect(() => {
    reload();
  }, [reload]);
  return { data, error, reload };
}

export async function openCv(id, setMsg) {
  try {
    const r = await api.get(`/cvs/${id}/download`, { responseType: "blob" });
    window.open(URL.createObjectURL(r.data));
  } catch {
    setMsg?.("File not available (the seeded demo CV has no real file).");
  }
}
