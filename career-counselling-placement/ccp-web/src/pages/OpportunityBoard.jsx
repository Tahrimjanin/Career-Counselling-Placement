import { useState } from "react";
import { useAuth } from "../AuthContext";
import api from "../api";
import {
  useData,
  Load,
  rows,
  Form,
  Action,
  Status,
  Pages,
} from "../WorkflowUI";
export default function OpportunityBoard() {
  const { user } = useAuth(),
    [filter, setFilter] = useState({}),
    [page, setPage] = useState(1),
    [apply, setApply] = useState(null);
  const state = useData(
    "/v1/opportunities?" + new URLSearchParams({ ...filter, page }),
  );
  return (
    <>
      <h1>
        {user.role === "admin"
          ? "Opportunity approval"
          : user.role === "recruiter"
            ? "My opportunities"
            : "Find opportunities"}
      </h1>
      {user.role === "recruiter" && (
        <details className="card">
          <summary>Post an opportunity</summary>
          <Form
            fields={[
              ["title", "Title"],
              [
                "type",
                "Type",
                "select",
                ["Internship", "Job", "Training", "Part-time"],
              ],
              ["location", "Location"],
              [
                "work_mode",
                "Work mode",
                "select",
                ["On-site", "Hybrid", "Remote"],
              ],
              [
                "is_paid",
                "Paid?",
                "select",
                [
                  ["1", "Yes"],
                  ["0", "No"],
                ],
              ],
              ["stipend_min", "Minimum stipend (BDT)", "number", null, true],
              ["stipend_max", "Maximum stipend (BDT)", "number", null, true],
              ["stipend_label", "Stipend details", "text", null, true],
              ["description", "Description", "textarea"],
              ["responsibilities", "Responsibilities", "textarea"],
              ["requirements", "Requirements", "textarea"],
              ["skills", "Required skills (comma separated)"],
              ["min_cgpa", "Minimum CGPA (optional)", "number", null, true],
              ["deadline", "Deadline (Dhaka date)", "date"],
            ]}
            submit={(v) =>
              api.post("/v1/opportunities", {
                ...v,
                is_paid: v.is_paid === "1",
                skills: v.skills
                  .split(",")
                  .map((s) => s.trim())
                  .filter(Boolean),
                min_cgpa: v.min_cgpa || null,
                stipend_min: v.stipend_min || null,
                stipend_max: v.stipend_max || null,
              })
            }
            button="Submit for approval"
            done={state.reload}
          />
        </details>
      )}
      <details className="card">
        <summary>Search and filters</summary>
        <Form
          fields={[
            ["q", "Keyword", "text", null, true],
            [
              "type",
              "Type",
              "select",
              ["Internship", "Job", "Training", "Part-time"],
              true,
            ],
            [
              "work_mode",
              "Work mode",
              "select",
              ["On-site", "Hybrid", "Remote"],
              true,
            ],
            [
              "paid",
              "Paid",
              "select",
              [
                ["1", "Paid"],
                ["0", "Unpaid"],
              ],
              true,
            ],
            ["location", "Location", "text", null, true],
            ["skill", "Skill", "text", null, true],
            [
              "sort",
              "Sort",
              "select",
              [
                ["newest", "Newest"],
                ["deadline", "Deadline"],
                ["match", "Best skill match"],
              ],
              true,
            ],
            ["saved", "Saved only", "select", [["1", "Yes"]], true],
          ]}
          submit={async (v) => {
            setPage(1);
            setFilter(v);
          }}
          button="Apply filters"
        />
      </details>
      <Load state={state}>
        {!rows(state.data).length && (
          <p>No opportunities match your filters.</p>
        )}
        {rows(state.data).map((o) => (
          <section className="card" key={o.id}>
            <h2>{o.title}</h2>
            <p>
              {o.company?.name} · {o.location} · {o.work_mode} · {o.type}
            </p>
            <Status value={o.status} />
            <p>
              Deadline: {String(o.deadline).slice(0, 10)} ·{" "}
              {o.is_paid ? "Paid" : "Unpaid"} {o.stipend_label}
            </p>
            <p className="detail-list">{o.description}</p>
            <details>
              <summary>Responsibilities and requirements</summary>
              <p>{o.responsibilities}</p>
              <p>{o.requirements}</p>
              <p>Required skills: {o.skills?.join(", ")}</p>
              <p>Minimum CGPA: {o.min_cgpa || "None"}</p>
            </details>
            {user.role === "student" && (
              <>
                <p>Estimated skill match: {o.match_percent}%</p>
                <p>
                  Matched: {o.matched_skills.join(", ") || "None"} · Missing:{" "}
                  {o.missing_skills.join(", ") || "None"}
                </p>
                <div className="toolbar">
                  <Action
                    run={() =>
                      api.put(`/v1/opportunities/${o.id}/save`, {
                        saved: !o.saved,
                      })
                    }
                    done={state.reload}
                  >
                    {o.saved ? "Unsave" : "Save opportunity"}
                  </Action>
                  <button
                    onClick={() => setApply(apply === o.id ? null : o.id)}
                  >
                    Check eligibility / Apply
                  </button>
                </div>
                {apply === o.id && <Apply id={o.id} />}
              </>
            )}
            {user.role === "admin" && o.status === "pending" && (
              <Form
                fields={[
                  [
                    "decision",
                    "Decision",
                    "select",
                    [
                      ["approved", "Approve"],
                      ["rejected", "Reject"],
                    ],
                  ],
                  [
                    "reason",
                    "Reason (required for rejection)",
                    "textarea",
                    null,
                    true,
                  ],
                ]}
                submit={(v) => api.post(`/v1/opportunities/${o.id}/review`, v)}
                done={state.reload}
              />
            )}{" "}
            {o.rejection_reason && (
              <p>Rejection reason: {o.rejection_reason}</p>
            )}
          </section>
        ))}
        <Pages state={state} page={page} setPage={setPage} />
      </Load>
    </>
  );
}
function Apply({ id }) {
  const cvs = useData("/v1/cvs"),
    [cv, setCv] = useState(""),
    [note, setNote] = useState(""),
    state = useData(`/v1/opportunities/${id}/eligibility?cv_id=${cv}`),
    [sent, setSent] = useState(false);
  const checks = state.data?.data || {};
  return (
    <section className="card">
      <h3>Application eligibility</h3>
      <label htmlFor={"cv-" + id}>Approved CV version</label>
      <select
        id={"cv-" + id}
        value={cv}
        onChange={(e) => setCv(e.target.value)}
      >
        <option value="">Select approved CV…</option>
        {rows(cvs.data)
          .filter((c) => c.status === "approved")
          .map((c) => (
            <option key={c.id} value={c.id}>
              v{c.version} · {c.title}
            </option>
          ))}
      </select>
      <Load state={state}>
        <ul className="status-list">
          {Object.entries(checks).map(([k, v]) => (
            <li key={k}>
              {v ? "✓" : "✕"} {k}
            </li>
          ))}
        </ul>
      </Load>
      <label htmlFor={"note-" + id}>Cover note</label>
      <textarea
        id={"note-" + id}
        value={note}
        onChange={(e) => setNote(e.target.value)}
        maxLength={3000}
      />
      <Action
        disabled={
          sent ||
          !cv ||
          !Object.keys(checks).length ||
          Object.values(checks).some((v) => !v)
        }
        run={() =>
          api.post("/v1/applications", {
            opportunity_id: id,
            cv_id: cv,
            cover_note: note,
          })
        }
        done={() => {
          setSent(true);
          state.reload();
        }}
      >
        Submit to University
      </Action>
      {sent ? (
        <p role="status">
          Submitted to Admin. Your application has not been sent to the company
          yet.
        </p>
      ) : (
        <p>Every eligibility check must pass before submitting.</p>
      )}
    </section>
  );
}
