import { useState } from "react";
import { useAuth } from "../AuthContext";
import api, { err } from "../api";
import {
  useData,
  Load,
  rows,
  Form,
  Action,
  Status,
  Pages,
} from "../WorkflowUI";
export default function CvLifecycle() {
  const { user } = useAuth(),
    [page, setPage] = useState(1),
    state = useData("/v1/cvs?page=" + page),
    [preview, setPreview] = useState(null),
    [draft, setDraft] = useState(null),
    [file, setFile] = useState(null),
    [message, setMessage] = useState("");
  const student = user.role === "student";
  return (
    <>
      <h1>{student ? "CV Builder & versions" : "CV review"}</h1>
      <p>
        Each save creates a new draft version. Submitted and approved content
        cannot be edited.
      </p>
      {student && (
        <>
          <details className="card" open={!!draft}>
            <summary>Build a new CV version</summary>
            <Form
              key={draft?.id || "new"}
              fields={[
                ["title", "Version title"],
                ["name", "Full name"],
                ["email", "Contact email", "email"],
                ["summary", "Professional summary", "textarea"],
                ["education", "Education", "textarea"],
                ["skills", "Skills", "textarea"],
                ["experience", "Experience", "textarea", null, true],
              ]}
              initial={
                draft
                  ? { ...draft.content, title: draft.title }
                  : { name: user.name, email: user.email }
              }
              submit={(v) =>
                api.post("/v1/cvs", {
                  title: v.title,
                  source: "builder",
                  content: v,
                })
              }
              onValues={(v) => setPreview({ content: v })}
              button="Save new draft"
              done={(r) => {
                setPreview(r.data.data);
                state.reload();
              }}
            />
          </details>
          <section className="card">
            <h2>Upload PDF or DOCX</h2>
            <p>Maximum 5 MB. Files are private.</p>
            <input
              aria-label="CV file"
              type="file"
              accept=".pdf,.docx"
              onChange={(e) => setFile(e.target.files[0])}
            />
            <Action
              disabled={!file}
              run={() => {
                const d = new FormData();
                d.append("file", file);
                d.append("source", "uploaded");
                d.append("title", file.name);
                return api.post("/v1/cvs", d);
              }}
              done={state.reload}
            >
              Save uploaded draft
            </Action>
          </section>
        </>
      )}
      <Load state={state}>
        {!rows(state.data).length && <p>No CV versions yet.</p>}
        {rows(state.data).map((cv) => (
          <section className="card" key={cv.id}>
            <h2>
              {cv.student_name} · v{cv.version} · {cv.title}
            </h2>
            <Status value={cv.status} />
            {cv.reviewed_at && (
              <p>
                Reviewed {new Date(cv.reviewed_at).toLocaleDateString()} ·
                Advisor #{cv.reviewed_by}
              </p>
            )}
            <div className="toolbar">
              {cv.source === "builder" ? (
                <button onClick={() => setPreview(cv)}>Preview / PDF</button>
              ) : (
                <Action
                  run={async () => {
                    const r = await api.get(`/v1/cvs/${cv.id}/download`, {
                      responseType: "blob",
                    });
                    const url = URL.createObjectURL(r.data);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = cv.title || "cv";
                    a.click();
                    setTimeout(() => URL.revokeObjectURL(url), 1000);
                  }}
                >
                  Download CV
                </Action>
              )}
              {student && cv.source === "builder" && (
                <button onClick={() => setDraft(cv)}>
                  Copy to new version
                </button>
              )}
              {student && cv.status === "draft" && (
                <Action
                  run={() => api.post(`/v1/cvs/${cv.id}/submit`)}
                  done={state.reload}
                >
                  Submit for review
                </Action>
              )}
            </div>
            {cv.reviews.map((r) => (
              <div key={r.id}>
                <p>
                  <strong>Strengths:</strong> {r.strengths}
                </p>
                <p>
                  <strong>Improvements:</strong> {r.improvements}
                </p>
                <p>
                  Placement recommendation:{" "}
                  {r.placement_recommendation === "ready"
                    ? "Ready for placement"
                    : "Not ready yet"}
                </p>
              </div>
            ))}
            {!student && ["submitted", "pending"].includes(cv.status) && (
              <Form
                fields={[
                  [
                    "decision",
                    "Decision",
                    "select",
                    [
                      ["approved", "Approve CV version"],
                      ["changes_requested", "Request changes"],
                    ],
                  ],
                  ["strengths", "Strengths", "textarea"],
                  ["improvements", "Improvements", "textarea"],
                  [
                    "placement_recommendation",
                    "Placement recommendation",
                    "select",
                    [
                      ["ready", "Ready for placement"],
                      ["not_ready", "Not ready yet"],
                    ],
                  ],
                ]}
                submit={(v) => api.post(`/v1/cvs/${cv.id}/review`, v)}
                button="Save review"
                done={state.reload}
              />
            )}
          </section>
        ))}
        <Pages state={state} page={page} setPage={setPage} />
      </Load>
      {preview?.content && (
        <section className="card cv-preview">
          <div className="toolbar no-print">
            <button onClick={() => window.print()}>Print / Save as PDF</button>
            <button onClick={() => setPreview(null)}>Close preview</button>
          </div>
          <h1>{preview.content.name}</h1>
          <p>{preview.content.email}</p>
          {["summary", "education", "skills", "experience"].map((k) => (
            <section key={k}>
              <h2>{k[0].toUpperCase() + k.slice(1)}</h2>
              <p>{preview.content[k]}</p>
            </section>
          ))}
        </section>
      )}
      <p role="alert">{message}</p>
    </>
  );
}
