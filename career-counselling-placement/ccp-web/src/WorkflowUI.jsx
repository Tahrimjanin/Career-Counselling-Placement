import { useEffect, useState, useId } from "react";
import api, { err } from "./api";
import PasswordInput from "./PasswordInput";
export function useData(url) {
  const [data, setData] = useState(null),
    [error, setError] = useState(""),
    [tick, setTick] = useState(0);
  useEffect(() => {
    let active = true;
    setData(null);
    setError("");
    if (!url) return;
    api
      .get(url)
      .then((r) => {
        if (active) setData(r.data);
      })
      .catch((e) => {
        if (active) setError(err(e));
      });
    return () => {
      active = false;
    };
  }, [url, tick]);
  return { data, error, reload: () => setTick((n) => n + 1) };
}
export function Load({ state, children }) {
  if (state.error)
    return (
      <div className="card" role="alert">
        {state.error}
        <button onClick={state.reload}>Retry</button>
      </div>
    );
  if (!state.data) return <p role="status">Loading…</p>;
  return children;
}
export const rows = (d) =>
  Array.isArray(d?.data) ? d.data : d?.data?.data || [];
export const label = (s) =>
  ({
    submitted: "Submitted to Admin",
    forwarded: "Forwarded to company",
    corrections_requested: "Corrections requested",
    rejected_by_university: "Rejected by university",
    rejected_by_company: "Rejected by company",
  })[s] || String(s || "").replaceAll("_", " ");
export function Status({ value }) {
  const tone = [
    "active",
    "approved",
    "verified",
    "open",
    "offer_accepted",
    "completed",
    "forwarded",
  ].includes(value)
    ? "positive"
    : [
          "rejected",
          "suspended",
          "rejected_by_company",
          "rejected_by_university",
          "cancelled",
        ].includes(value)
      ? "negative"
      : ["pending", "submitted", "corrections_requested"].includes(value)
        ? "attention"
        : "neutral";
  return <span className={"badge " + tone}>{label(value)}</span>;
}
export function Action({
  children,
  run,
  done,
  disabled = false,
  className,
}) {
  const [busy, setBusy] = useState(false),
    [message, setMessage] = useState("");
  return (
    <span className={className}>
      <button
        disabled={busy || disabled}
        onClick={async () => {
          setBusy(true);
          setMessage("");
          try {
            await run();
            done?.();
          } catch (e) {
            setMessage(err(e));
          } finally {
            setBusy(false);
          }
        }}
      >
        {busy ? "Saving…" : children}
      </button>
      {message && (
        <p role="alert" className="error">
          {message}
        </p>
      )}
    </span>
  );
}
export function Form({
  fields,
  initial = {},
  submit,
  button = "Save",
  done,
  onValues,
}) {
  const formId = useId();
  const [values, setValues] = useState(initial),
    [busy, setBusy] = useState(false),
    [message, setMessage] = useState(""),
    [errors, setErrors] = useState({});
  return (
    <form
      className="stack"
      onSubmit={async (e) => {
        e.preventDefault();
        setBusy(true);
        setMessage("");
        setErrors({});
        try {
          const result = await submit(values);
          setMessage(result?.data?.message || "Saved successfully.");
          done?.(result);
        } catch (e) {
          setMessage(
            e.response?.data?.errors
              ? "Please check the highlighted fields."
              : err(e),
          );
          setErrors(e.response?.data?.errors || {});
        } finally {
          setBusy(false);
        }
      }}
    >
      {fields.map((field) => {
        const [key, title, type = "text", options, optional = false] = field;
        const props = {
          id: formId + "-" + key,
          name: key,
          value: values[key] ?? "",
          required: !optional,
          onChange: (e) => {
            const next = { ...values, [key]: e.target.value };
            setValues(next);
            onValues?.(next);
          },
          "aria-invalid": !!errors[key],
        };
        return (
          <div key={key}>
            <label htmlFor={formId + "-" + key}>{title}</label>
            {type === "textarea" ? (
              <textarea {...props} rows={4} />
            ) : type === "select" ? (
              <select {...props}>
                <option value="">Choose…</option>
                {options.map((o) => (
                  <option
                    key={Array.isArray(o) ? o[0] : o}
                    value={Array.isArray(o) ? o[0] : o}
                  >
                    {Array.isArray(o) ? o[1] : o}
                  </option>
                ))}
              </select>
            ) : (
              type === "password" ? (
                <PasswordInput {...props} />
              ) : (
                <input
                  {...props}
                  type={type}
                  step={type === "number" ? "any" : undefined}
                />
              )
            )}{" "}
            {errors[key] && <small className="error">{errors[key][0]}</small>}
          </div>
        );
      })}
      <button disabled={busy}>{busy ? "Saving…" : button}</button>
      <p role="status">{message}</p>
    </form>
  );
}
export function Pages({ state, page, setPage }) {
  const last = state.data?.meta?.last_page || state.data?.data?.last_page || 1;
  return (
    last > 1 && (
      <div className="toolbar">
        <button disabled={page <= 1} onClick={() => setPage(page - 1)}>
          Previous
        </button>
        <span>
          Page {page} of {last}
        </span>
        <button disabled={page >= last} onClick={() => setPage(page + 1)}>
          Next
        </button>
      </div>
    )
  );
}
export const date = (d) =>
  new Date(
    /[zZ]|[+-]\d{2}:\d{2}$/.test(d) ? d : String(d).replace(" ", "T") + "Z",
  ).toLocaleString("en-GB", { timeZone: "Asia/Dhaka" });
export const utc = (d) => new Date(d).toISOString();
