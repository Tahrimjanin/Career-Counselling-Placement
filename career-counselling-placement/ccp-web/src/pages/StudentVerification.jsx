import { useEffect, useState } from "react";
import api, { err } from "../api";
export default function StudentVerification() {
  const [rows, setRows] = useState(null),
    [status, setStatus] = useState("pending"),
    [page, setPage] = useState(1),
    [last, setLast] = useState(1),
    [selected, setSelected] = useState(null),
    [reason, setReason] = useState(""),
    [busy, setBusy] = useState(false),
    [message, setMessage] = useState(""),
    [error, setError] = useState("");
  async function load() {
    setError("");
    try {
      const r = await api.get("/v1/student-verifications", {
        params: { status, page },
      });
      setRows(r.data.data);
      setLast(r.data.meta.last_page);
    } catch (e) {
      setError(err(e));
    }
  }
  useEffect(() => {
    setRows(null);
    load();
  }, [status, page]);
  async function decide(value) {
    setBusy(true);
    setMessage("");
    try {
      await api.patch(`/v1/student-verifications/${selected.id}`, {
        status: value,
        reason,
      });
      setSelected(null);
      setReason("");
      setMessage(`Student ${value}.`);
      await load();
    } catch (e) {
      setMessage(err(e));
    } finally {
      setBusy(false);
    }
  }
  return (
    <>
      <h1>Student verification</h1>
      <label htmlFor="verification-filter">Status</label>
      <select
        id="verification-filter"
        value={status}
        onChange={(e) => {
          setStatus(e.target.value);
          setPage(1);
          setSelected(null);
        }}
      >
        <option value="pending">Pending</option>
        <option value="verified">Verified</option>
        <option value="rejected">Rejected</option>
      </select>
      <p role="status">{message}</p>
      {error ? (
        <div role="alert">
          {error}
          <button onClick={load}>Retry</button>
        </div>
      ) : !rows ? (
        <p>Loading students…</p>
      ) : rows.length ? (
        <div className="verification-list">
          {rows.map((s) => (
            <section className="card" key={s.id}>
              <h2>{s.name}</h2>
              <p>
                {s.email} · {s.profile.department || "Department not provided"}
              </p>
              <p>Profile completion: {s.completion.percent}%</p>
              <button
                onClick={() => {
                  setSelected(s);
                  setReason("");
                }}
              >
                Review student
              </button>
            </section>
          ))}
        </div>
      ) : (
        <p>No students with this verification status.</p>
      )}
      {selected && (
        <section className="card" aria-label="Student verification detail">
          <h2>Review {selected.name}</h2>
          <dl>
            {Object.entries({
              email: selected.email,
              phone: selected.phone,
              ...selected.profile,
            })
              .filter(([k]) => !["interests"].includes(k))
              .map(([k, v]) => (
                <div key={k}>
                  <dt>{k.replaceAll("_", " ")}</dt>
                  <dd>{String(v ?? "Not provided")}</dd>
                </div>
              ))}
          </dl>
          <p>
            Missing fields: {selected.completion.missing.join(", ") || "None"}
          </p>
          <label htmlFor="reason">
            Decision reason (required for rejection)
          </label>
          <textarea
            id="reason"
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            maxLength={2000}
          />
          <div className="wizard-actions">
            <button disabled={busy} onClick={() => decide("verified")}>
              Approve student
            </button>
            <button
              disabled={busy || !reason.trim()}
              onClick={() => decide("rejected")}
            >
              Reject student
            </button>
            <button disabled={busy} onClick={() => setSelected(null)}>
              Close review
            </button>
          </div>
        </section>
      )}
      <div className="wizard-actions">
        <button disabled={page <= 1 || busy} onClick={() => setPage(page - 1)}>
          Previous
        </button>
        <span>
          Page {page} of {last}
        </span>
        <button
          disabled={page >= last || busy}
          onClick={() => setPage(page + 1)}
        >
          Next
        </button>
      </div>
    </>
  );
}
