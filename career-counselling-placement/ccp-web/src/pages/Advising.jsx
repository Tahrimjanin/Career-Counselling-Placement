import { useState } from "react";
import { useAuth } from "../AuthContext";
import api from "../api";
import {
  useData,
  Load,
  rows,
  Status,
  Action,
  Form,
  date,
  utc,
  Pages,
} from "../WorkflowUI";
export function Advisors() {
  const state = useData("/v1/advisors"),
    { user } = useAuth();
  return (
    <>
      <h1>My advisor</h1>
      <p>Select two preferences. An administrator confirms your allocation.</p>
      <Load state={state}>
        <p>
          Status:{" "}
          {state.data?.assignment
            ? "Allocated"
            : state.data?.preferences?.[0]?.status || "Not yet submitted"}
        </p>
        <div className="workspace-grid">
          {rows(state.data).map((a) => (
            <section className="card" key={a.id}>
              <h2>{a.name}</h2>
              <p>{a.title}</p>
              <p>{a.bio}</p>
              <p>Expertise: {a.expertise.join(", ")}</p>
              {a.interests?.length > 0 && (
                <p>Interested in: {a.interests.join(", ")}</p>
              )}
              <strong>
                {a.is_accepting && a.places_left
                  ? `Available · ${a.places_left} places left`
                  : "At capacity / unavailable"}
              </strong>
            </section>
          ))}
        </div>
        {user.role === "student" && !state.data?.assignment && (
          <section className="card">
            <Form
              fields={["first", "second"].map((k, i) => [
                k,
                `Preference ${i + 1}`,
                "select",
                rows(state.data).map((a) => [a.id, a.name]),
              ])}
              submit={(v) => api.post("/v1/advisor-preferences", v)}
              button="Submit preferences"
              done={state.reload}
            />
          </section>
        )}
      </Load>
    </>
  );
}
export function Allocations() {
  const [page, setPage] = useState(1),
    state = useData("/v1/allocations?page=" + page);
  const advisors = useData("/v1/advisors");
  return (
    <>
      <h1>Advisor allocation</h1>
      <Load state={state}>
        {!rows(state.data).length && <p>No allocation requests yet.</p>}
        {Object.values(
          rows(state.data).reduce((acc, r) => {
            (acc[r.student_id] ??= []).push(r);
            return acc;
          }, {}),
        ).map((group) => (
          <section className="card" key={group[0].student_id}>
            <h2>{group[0].student_name}</h2>
            {group.map((r) => (
              <p key={r.id}>
                Preference {r.rank}: {r.advisor_name} ·{" "}
                <Status value={r.status} />
              </p>
            ))}
            <Form
              fields={[
                [
                  "advisor_id",
                  "Assign advisor",
                  "select",
                  rows(advisors.data).map((a) => [
                    a.id,
                    `${a.name} · ${a.places_left} places left`,
                  ]),
                ],
              ]}
              submit={(v) =>
                api.post(`/v1/allocations/${group[0].student_id}`, v)
              }
              button="Confirm allocation"
              done={() => {
                state.reload();
                advisors.reload();
              }}
            />
            <Action
              run={() => api.delete(`/v1/allocations/${group[0].student_id}`)}
              done={() => {
                state.reload();
                advisors.reload();
              }}
            >
              Reject preferences
            </Action>
          </section>
        ))}
        <Pages state={state} page={page} setPage={setPage} />
      </Load>
    </>
  );
}
export function Sessions() {
  const { user } = useAuth(),
    [page, setPage] = useState(1),
    state = useData("/v1/sessions?page=" + page),
    [edit, setEdit] = useState(null);
  return (
    <>
      <h1>Counselling sessions</h1>
      <p>
        Times are displayed in Asia/Dhaka. Enter appointments in your device’s
        local time.
      </p>
      {user.role === "student" && (
        <details className="card">
          <summary>Request a session</summary>
          <Form
            fields={[
              [
                "type",
                "Session type",
                "select",
                ["Career planning", "CV review", "Mock interview", "Skill gap"],
              ],
              ["scheduled_at", "Date and time", "datetime-local"],
              ["duration", "Duration (15–120 minutes)", "number"],
              ["mode", "Mode", "select", ["Online", "In person"]],
              [
                "location_or_link",
                "Location or meeting link",
                "text",
                null,
                true,
              ],
              ["student_note", "Note", "textarea", null, true],
            ]}
            initial={{ duration: 30 }}
            submit={(v) =>
              api.post("/v1/sessions", {
                ...v,
                scheduled_at: utc(v.scheduled_at),
              })
            }
            button="Request session"
            done={state.reload}
          />
        </details>
      )}
      <Load state={state}>
        {!rows(state.data).length && <p>No sessions yet.</p>}
        {rows(state.data).map((s) => (
          <section className="card" key={s.id}>
            <h2>{s.type}</h2>
            <p>
              {s.student_name} · {s.advisor_name}
            </p>
            <p>
              {date(s.scheduled_at)} — {date(s.ends_at)}
            </p>
            <Status value={s.status} />
            <p>
              {s.mode} · {s.location_or_link}
            </p>
            <p>{s.student_note}</p>
            {s.feedback && (
              <>
                <p>{s.feedback.body}</p>
                <strong>Next step: {s.feedback.next_step}</strong>
              </>
            )}
            {["requested", "confirmed"].includes(s.status) &&
              user.role !== "admin" && (
                <div className="toolbar">
                  {user.role === "advisor" && (
                    <>
                      <Action
                        run={() =>
                          api.patch(`/v1/sessions/${s.id}`, {
                            action: "confirm",
                          })
                        }
                        done={state.reload}
                      >
                        Confirm
                      </Action>
                      <button
                        onClick={() =>
                          setEdit({ id: s.id, action: "reschedule" })
                        }
                      >
                        Reschedule
                      </button>
                      <button
                        onClick={() => setEdit({ id: s.id, action: "decline" })}
                      >
                        Decline
                      </button>
                      <button
                        onClick={() =>
                          setEdit({ id: s.id, action: "complete" })
                        }
                      >
                        Record feedback
                      </button>
                    </>
                  )}
                  <button
                    onClick={() => setEdit({ id: s.id, action: "cancel" })}
                  >
                    Cancel
                  </button>
                </div>
              )}
            {edit?.id === s.id && (
              <Form
                key={edit.action}
                fields={
                  edit.action === "reschedule"
                    ? [
                        ["scheduled_at", "New time", "datetime-local"],
                        ["duration", "Minutes", "number"],
                      ]
                    : edit.action === "complete"
                      ? [
                          ["body", "Feedback", "textarea"],
                          ["next_step", "Next step", "textarea"],
                        ]
                      : [["cancel_reason", "Reason", "textarea"]]
                }
                submit={(v) =>
                  api.patch(`/v1/sessions/${s.id}`, {
                    ...v,
                    action: edit.action,
                    ...(v.scheduled_at
                      ? { scheduled_at: utc(v.scheduled_at) }
                      : {}),
                  })
                }
                button="Save session change"
                done={() => {
                  setEdit(null);
                  state.reload();
                }}
              />
            )}
          </section>
        ))}
        <Pages state={state} page={page} setPage={setPage} />
      </Load>
    </>
  );
}
export function Plan() {
  const { user } = useAuth(),
    students = useData(
      user.role === "student"
        ? null
        : user.role === "admin"
          ? "/users?role=student"
          : "/students",
    ),
    [selected, setSelected] = useState("");
  const id = user.role === "student" ? user.id : selected;
  return (
    <>
      <h1>Career plan</h1>
      {user.role !== "student" && (
        <>
          <label htmlFor="plan-student">Assigned student</label>
          <select
            id="plan-student"
            value={selected}
            onChange={(e) => setSelected(e.target.value)}
          >
            <option value="">Choose student…</option>
            {students.data?.map((s) => (
              <option key={s.id} value={s.id}>
                {s.name}
              </option>
            ))}
          </select>
        </>
      )}
      {id && <StudentPlan key={id} id={id} role={user.role} />}
    </>
  );
}
function StudentPlan({ id, role }) {
  const state = useData("/v1/plans/" + id),
    [editing, setEditing] = useState(false);
  const p = state.data?.data;
  return (
    <Load state={state}>
      {p ? (
        <section className="card">
          <h2>{p.target_role}</h2>
          <p>Strengths: {p.strengths}</p>
          <p>Skill gaps: {p.skill_gaps}</p>
          <p>{p.advisor_comment}</p>
          <strong>
            Plan progress:{" "}
            {
              p.milestones.filter((m) =>
                ["completed", "recommended"].includes(m.status),
              ).length
            }{" "}
            of {p.milestones.length} milestones
          </strong>
          {p.milestones.map((m) => (
            <div className="card" key={m.id}>
              <h3>{m.title}</h3>
              <p>
                Due: {m.due_date || "Not set"} · <Status value={m.status} />
              </p>
              <div className="toolbar">
                {(role === "admin"
                  ? []
                  : role === "student"
                    ? ["not_started", "in_progress"]
                    : ["not_started", "in_progress", "completed", "recommended"]
                ).map((status) => (
                  <Action
                    key={status}
                    run={() => api.patch("/v1/milestones/" + m.id, { status })}
                    done={state.reload}
                  >
                    {status.replaceAll("_", " ")}
                  </Action>
                ))}
              </div>
            </div>
          ))}
        </section>
      ) : (
        <p>Your advisor has not created a career plan yet.</p>
      )}
      {role === "advisor" && (
        <>
          <button onClick={() => setEditing(!editing)}>
            Create / edit plan
          </button>
          {editing && (
            <section className="card">
              <p>
                Existing milestone progress is kept when its title is unchanged.
                Removed milestones are deleted.
              </p>
              <Form
                fields={[
                  ["target_role", "Target role"],
                  ["strengths", "Strengths", "textarea"],
                  ["skill_gaps", "Skill gaps", "textarea"],
                  [
                    "advisor_comment",
                    "Advisor comment",
                    "textarea",
                    null,
                    true,
                  ],
                  [
                    "tasks",
                    "Milestones (one per line: title | YYYY-MM-DD)",
                    "textarea",
                  ],
                ]}
                initial={
                  p
                    ? {
                        ...p,
                        tasks: p.milestones
                          .map(
                            (m) =>
                              m.title + (m.due_date ? " | " + m.due_date : ""),
                          )
                          .join("\n"),
                      }
                    : {}
                }
                submit={(v) =>
                  api.put("/v1/plans/" + id, {
                    ...v,
                    milestones: v.tasks
                      .split("\n")
                      .filter((s) => s.trim())
                      .map((line) => {
                        const [title, due_date] = line
                          .split("|")
                          .map((s) => s.trim());
                        return { title, due_date: due_date || null };
                      }),
                  })
                }
                done={() => {
                  setEditing(false);
                  state.reload();
                }}
              />
            </section>
          )}
        </>
      )}
    </Load>
  );
}
