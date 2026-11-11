import AdvisorSettings from "./AdvisorSettings";
import { useEffect, useState } from "react";
import { useAuth } from "../AuthContext";
import api, { err } from "../api";
const STEPS = [
  "Personal & contact",
  "Academic background",
  "Technical skills",
  "Soft skills",
  "Experience",
  "Certificates",
  "Career objective & interests",
  "Review",
];
const emptyEntry = () => ({
  title: "",
  org: "",
  start_date: "",
  end_date: "",
  description: "",
});
export default function Profile() {
  const { user, refresh } = useAuth();
  const student = user.role === "student";
  const [data, setData] = useState(null),
    [f, setF] = useState({}),
    [step, setStep] = useState(1),
    [busy, setBusy] = useState(false),
    [message, setMessage] = useState(""),
    [errors, setErrors] = useState({}),
    [loadError, setLoadError] = useState("");
  function hydrate(d) {
    setData(d);
    setF({
      ...d.profile,
      ...Object.fromEntries(
        Object.entries(d.company || {}).map(([k, v]) => ["company_" + k, v]),
      ),
      name: d.name,
      phone: d.phone || "",
      technical_skills: (d.technical_skills || []).join(", "),
      soft_skills: (d.soft_skills || []).join(", "),
      interests: (d.profile.interests || []).join(", "),
      experiences:
        d.experiences?.length > 0
          ? d.experiences
          : d.profile.has_experience
            ? [emptyEntry()]
            : [],
      certificates:
        d.certificates?.length > 0
          ? d.certificates
          : d.profile.has_certificates
            ? [emptyEntry()]
            : [],
    });
  }
  async function load() {
    setLoadError("");
    try {
      const r = await api.get("/v1/profile");
      hydrate(r.data.data);
      setStep(r.data.data.profile.wizard_step || 1);
    } catch (e) {
      setLoadError(err(e));
    }
  }
  useEffect(() => {
    load();
  }, []);
  const set = (key, value) => setF((old) => ({ ...old, [key]: value }));
  function input(key, label, type = "text", max) {
    return (
      <div key={key}>
        <label htmlFor={key}>{label}</label>
        <input
          id={key}
          type={type}
          value={f[key] ?? ""}
          min={type === "number" ? 0 : undefined}
          max={max}
          step={type === "number" ? "any" : undefined}
          onChange={(e) => set(key, e.target.value)}
          aria-invalid={!!errors[key]}
        />
        {errors[key] && <small className="error">{errors[key][0]}</small>}
      </div>
    );
  }
  const academic = [
    "student_no",
    "department",
    "batch",
    "cgpa",
    "ssc_institution",
    "ssc_board",
    "ssc_year",
    "ssc_gpa",
    "hsc_institution",
    "hsc_board",
    "hsc_year",
    "hsc_gpa",
  ];
  const words = (s) =>
    s
      .split("_")
      .map((x) =>
        ["ssc", "hsc", "cgpa", "gpa"].includes(x)
          ? x.toUpperCase()
          : x[0].toUpperCase() + x.slice(1),
      )
      .join(" ");
  const list = (s) =>
    String(s || "")
      .split(",")
      .map((x) => x.trim())
      .filter(Boolean);
  async function save(e, advance = false) {
    e.preventDefault();
    setBusy(true);
    setErrors({});
    setMessage("");
    let body = {};
    if (step === 1) body = { name: f.name, phone: f.phone };
    if (step === 1 && user.role === "recruiter")
      body.company = Object.fromEntries(
        ["name", "industry", "website", "description"].map((k) => [
          k,
          f["company_" + k] || null,
        ]),
      );
    if (step === 2)
      body = Object.fromEntries(academic.map((k) => [k, f[k] ?? null]));
    if (step === 3 || step === 4)
      body = {
        skills: list(f[step === 3 ? "technical_skills" : "soft_skills"]),
      };
    if (step === 5)
      body = { has_experience: f.has_experience, entries: f.experiences };
    if (step === 6)
      body = { has_certificates: f.has_certificates, entries: f.certificates };
    if (step === 7)
      body = {
        career_objective: f.career_objective,
        interests: list(f.interests),
      };
    try {
      const r = await api.patch(`/v1/profile/steps/${step}`, {
        ...body,
        advance,
      });
      hydrate(r.data.data);
      setMessage("Progress saved.");
      if (advance) setStep(Math.min(8, step + 1));
      if (step === 1) await refresh();
    } catch (x) {
      setMessage(err(x));
      setErrors(x.response?.data?.errors || {});
    } finally {
      setBusy(false);
    }
  }
  function entries(kind, flag) {
    return (
      <>
        <label htmlFor={flag}>Do you have {kind}?</label>
        <select
          id={flag}
          value={
            f[flag] === null || f[flag] === undefined ? "" : String(f[flag])
          }
          onChange={(e) => {
            const hasEntries = e.target.value === "true";
            set(flag, e.target.value === "" ? null : hasEntries);
            if (hasEntries && f[kind].length === 0) {
              set(kind, [emptyEntry()]);
            }
            if (!hasEntries) {
              set(kind, []);
            }
          }}
        >
          <option value="">Choose an option</option>
          <option value="true">Yes</option>
          <option value="false">Not applicable</option>
        </select>
        {f[flag] === true && (
          <>
            {f[kind].map((item, i) => (
              <fieldset key={i}>
                <legend>
                  {words(kind)} {i + 1}
                </legend>
                {["title", "org", "start_date", "end_date", "description"].map(
                  (key) => (
                    <div key={key}>
                      <label htmlFor={`${kind}-${i}-${key}`}>
                        {words(key)}
                      </label>
                      <input
                        id={`${kind}-${i}-${key}`}
                        type={key.includes("date") ? "date" : "text"}
                        value={item[key] || ""}
                        onChange={(e) =>
                          set(
                            kind,
                            f[kind].map((x, j) =>
                              i === j ? { ...x, [key]: e.target.value } : x,
                            ),
                          )
                        }
                      />
                      {errors[`entries.${i}.${key}`] && (
                        <small className="error">
                          {errors[`entries.${i}.${key}`][0]}
                        </small>
                      )}
                    </div>
                  ),
                )}
                <button
                  type="button"
                  onClick={() =>
                    set(
                      kind,
                      f[kind].filter((_, j) => i !== j),
                    )
                  }
                >
                  Remove entry
                </button>
              </fieldset>
            ))}
            <button
              type="button"
              onClick={() =>
                set(kind, [...f[kind], emptyEntry()])
              }
            >
              Add entry
            </button>
          </>
        )}
      </>
    );
  }
  if (loadError)
    return (
      <div role="alert">
        {loadError}
        <button onClick={load}>Retry</button>
      </div>
    );
  if (!data) return <p>Loading profile…</p>;
  return (
    <>
      <h1>My profile</h1>
      {user.role === "advisor" && <AdvisorSettings />}
      {student && (
        <>
          <div className="profile-summary">
            <section className="card">
              <h2>Profile completion · {data.completion.percent}%</h2>
              <progress max="100" value={data.completion.percent} />
              <p>University verification: {data.profile.verification_status}</p>
              {data.profile.verification_reason && (
                <p>{data.profile.verification_reason}</p>
              )}
            </section>
            <section className="card">
              <h2>Career readiness</h2>
              <strong>{data.readiness.state}</strong>
              <p>
                Approved CV: {data.readiness.approved_cv ? "Yes" : "No"} · Plan:{" "}
                {data.readiness.milestones_completed}/
                {data.readiness.milestones_total} · Recommendation:{" "}
                {data.readiness.advisor_recommends ? "Ready" : "Not ready"}
              </p>
            </section>
          </div>
          <nav aria-label="Profile steps" className="wizard-steps">
            {STEPS.map((label, i) => (
              <button
                type="button"
                key={label}
                aria-current={step === i + 1 ? "step" : undefined}
                disabled={busy}
                onClick={() => {
                  setStep(i + 1);
                  setMessage("");
                  setErrors({});
                }}
              >
                {i + 1}. {label}
              </button>
            ))}
          </nav>
        </>
      )}
      <form className="card profile-form" onSubmit={(e) => save(e, false)}>
        <h2>
          {student ? `${step}. ${STEPS[step - 1]}` : "Personal & contact"}
        </h2>
        <p>
          Save incomplete fields now and return later. Save before leaving this
          page.
        </p>
        <fieldset disabled={busy} className="profile-fields">
          {step === 1 && (
            <>
              {input("name", "Full name")}
              {input("phone", "Phone", "tel")}
              <p>Email: {data.email} (verified account address)</p>
              {user.role === "recruiter" && (
                <>
                  <h3>Company profile</h3>
                  {["name", "industry", "website", "description"].map((k) =>
                    input(
                      "company_" + k,
                      "Company " + k,
                      k === "website" ? "url" : "text",
                    ),
                  )}
                </>
              )}
            </>
          )}
          {step === 2 &&
            academic.map((k) =>
              input(
                k,
                words(k),
                /cgpa|gpa|year/.test(k) ? "number" : "text",
                k === "cgpa"
                  ? 4
                  : k.includes("gpa")
                    ? 5
                    : k.includes("year")
                      ? new Date().getFullYear()
                      : undefined,
              ),
            )}
          {step === 3 &&
            input("technical_skills", "Technical skills (comma separated)")}
          {step === 4 && input("soft_skills", "Soft skills (comma separated)")}
          {step === 5 && entries("experiences", "has_experience")}
          {step === 6 && entries("certificates", "has_certificates")}
          {step === 7 && (
            <>
              <label htmlFor="career_objective">Career objective</label>
              <textarea
                id="career_objective"
                value={f.career_objective || ""}
                onChange={(e) => set("career_objective", e.target.value)}
              />
              {input("interests", "Career interests (comma separated)")}
            </>
          )}
          {step === 8 && (
            <>
              <h3>Required fields checklist</h3>
              {data.completion.missing.length ? (
                <ul>
                  {data.completion.missing.map((k) => (
                    <li key={k}>{words(k)} is missing</li>
                  ))}
                </ul>
              ) : (
                <p>All required fields are complete.</p>
              )}
              <dl>
                {[
                  "name",
                  "phone",
                  ...academic,
                  "technical_skills",
                  "soft_skills",
                  "career_objective",
                  "interests",
                ].map((k) => (
                  <div key={k}>
                    <dt>{words(k)}</dt>
                    <dd>{f[k] || "Not provided"}</dd>
                  </div>
                ))}
              </dl>
            </>
          )}
        </fieldset>
        <p role="status">{message}</p>
        {Object.entries(errors)
          .filter(([k]) => !k.startsWith("entries."))
          .map(([k, v]) => (
            <p key={k} className="error">
              {words(k)}: {v[0]}
            </p>
          ))}
        <div className="wizard-actions">
          <button disabled={busy}>Save progress</button>
          {student && step < 8 && (
            <button
              type="button"
              disabled={busy}
              onClick={(e) => save(e, true)}
            >
              Save & continue →
            </button>
          )}
        </div>
      </form>
    </>
  );
}
