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
  date,
  utc,
  Pages,
} from "../WorkflowUI";
import { useSearchParams } from "react-router-dom";
export default function PlacementWorkspace({ offersOnly = false }) {
  const { user } = useAuth(),
    [searchParams] = useSearchParams(),
    [page, setPage] = useState(1),
    [review, setReview] = useState(searchParams.get("review") || ""),
    state = useData(
      "/v1/applications?" +
        new URLSearchParams({
          page,
          review,
          status: searchParams.get("status") || "",
          ...(offersOnly ? { offers: "1" } : {}),
        }),
    ),
    [selected, setSelected] = useState(null);
  return (
    <>
      <h1>
        {offersOnly
          ? "Offers"
          : user.role === "admin"
            ? "Application review"
            : user.role === "recruiter"
              ? "Forwarded candidates"
              : "My applications"}
      </h1>
      {user.role === "admin" && (
        <div className="toolbar">
          <label htmlFor="queue-filter">Review queue</label>
          <select
            id="queue-filter"
            value={review}
            onChange={(e) => {
              setReview(e.target.value);
              setPage(1);
            }}
          >
            <option value="">All applications</option>
            <option value="pending">Pending review</option>
            <option value="corrections">Corrections requested</option>
            <option value="forwarded">Forwarded this week</option>
          </select>
        </div>
      )}
      <Load state={state}>
        {!rows(state.data).length && <p>No applications to display.</p>}
        {rows(state.data).map((a) => (
          <section className="card" key={a.id}>
            <h2>{a.opportunity?.title}</h2>
            <p>
              {a.user?.name} · {a.opportunity?.company?.name}
            </p>
            <Status value={a.workflow_status} />
            <p>
              Submitted CV · v{a.cv?.version} · reviewed by advisor #
              {a.cv?.reviewed_by}
            </p>
            <button
              onClick={() => setSelected(selected === a.id ? null : a.id)}
            >
              View application details
            </button>
            {selected === a.id && (
              <ApplicationDetail
                id={a.id}
                role={user.role}
                refresh={state.reload}
              />
            )}
          </section>
        ))}
        <Pages state={state} page={page} setPage={setPage} />
      </Load>
    </>
  );
}
function ApplicationDetail({ id, role, refresh }) {
  const state = useData("/v1/applications/" + id),
    [cv, setCv] = useState(null);
  const a = state.data?.data,
    offer = state.data?.offer;
  const done = () => {
    state.reload();
    refresh();
  };
  return (
    <Load state={state}>
      {a && (
        <>
          <p>
            Application #{a.id} · Set follow-up reminders from Notifications.
          </p>
          <p>Cover note: {a.cover_note || "None"}</p>
          <Action
            run={async () => {
              const r = await api.get("/v1/cvs/" + a.cv_id);
              if (r.data.data.source === "builder") setCv(r.data.data.content);
              else {
                const d = await api.get(`/v1/cvs/${a.cv_id}/download`, {
                  responseType: "blob",
                });
                const url = URL.createObjectURL(d.data);
                const link = document.createElement("a");
                link.href = url;
                link.download = "cv";
                link.click();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
              }
            }}
          >
            View exact submitted CV
          </Action>
          {cv && (
            <div className="card">
              {Object.entries(cv).map(([k, v]) => (
                <p key={k}>
                  <strong>{k}:</strong> {v}
                </p>
              ))}
            </div>
          )}
          <details className="card">
            <summary>Candidate profile</summary>
            <p>
              {state.data.candidate?.name} · {state.data.candidate?.email}
            </p>
            <p>
              Department: {state.data.candidate?.profile?.department} · CGPA:{" "}
              {state.data.candidate?.profile?.cgpa}
            </p>
            <p>
              Technical skills:{" "}
              {state.data.candidate?.technical_skills?.join(", ")}
            </p>
            <p>Soft skills: {state.data.candidate?.soft_skills?.join(", ")}</p>
            <p>{state.data.candidate?.profile?.career_objective}</p>
          </details>
          <h3>Eligibility at submission</h3>
          {Object.entries(
            typeof a.eligibility_snapshot === "string"
              ? JSON.parse(a.eligibility_snapshot || "{}")
              : a.eligibility_snapshot || {},
          ).map(([k, v]) => (
            <p key={k}>
              {v ? "✓" : "✕"} {k}
            </p>
          ))}
          <h3>Status history</h3>
          {state.data.history.map((h) => (
            <p key={h.id}>
              {date(h.created_at)} · <Status value={h.to_status} /> · {h.reason}
            </p>
          ))}
          {role === "admin" && a.workflow_status === "submitted" && (
            <Form
              fields={[
                [
                  "status",
                  "Review action",
                  "select",
                  [
                    ["corrections_requested", "Request corrections"],
                    ["rejected_by_university", "Reject"],
                    ["forwarded", "Approve & forward"],
                  ],
                ],
                [
                  "reason",
                  "Reason (required for corrections or rejection)",
                  "textarea",
                  null,
                  true,
                ],
              ]}
              submit={(v) => api.patch("/v1/applications/" + id, v)}
              button="Save decision"
              done={done}
            />
          )}{" "}
          {role === "student" &&
            a.workflow_status === "corrections_requested" && (
              <Form
                fields={[["cover_note", "Corrected cover note", "textarea"]]}
                initial={{ cover_note: a.cover_note }}
                submit={(v) =>
                  api.patch("/v1/applications/" + id, {
                    ...v,
                    status: "submitted",
                  })
                }
                button="Resubmit to Admin"
                done={done}
              />
            )}{" "}
          {role === "student" &&
            [
              "submitted",
              "corrections_requested",
              "forwarded",
              "shortlisted",
              "interview",
            ].includes(a.workflow_status) && (
              <details>
                <summary>Withdraw application</summary>
                <p>
                  This closes your application. You cannot submit another
                  application for this opportunity.
                </p>
                <Action
                  run={() =>
                    api.patch("/v1/applications/" + id, { status: "withdrawn" })
                  }
                  done={done}
                >
                  Confirm withdrawal
                </Action>
              </details>
            )}
          {role === "recruiter" &&
            ["forwarded", "shortlisted", "interview", "offer"].includes(
              a.workflow_status,
            ) && (
              <>
                <Form
                  fields={[
                    [
                      "status",
                      "Candidate decision",
                      "select",
                      [
                        ...(a.workflow_status === "forwarded"
                          ? [["shortlisted", "Shortlist"]]
                          : []),
                        ["rejected_by_company", "Reject candidate"],
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
                  submit={(v) => api.patch("/v1/applications/" + id, v)}
                  done={done}
                />
                {["shortlisted", "interview"].includes(a.workflow_status) && (
                  <details className="card">
                    <summary>Schedule / reschedule interview</summary>
                    <Form
                      fields={[
                        [
                          "scheduled_at",
                          "Date and time (device local time)",
                          "datetime-local",
                        ],
                        ["duration_min", "Duration in minutes", "number"],
                        ["type", "Interview type"],
                        ["mode", "Mode", "select", ["Online", "In person"]],
                        ["interviewer", "Interviewer"],
                        ["link_or_location", "Meeting link or location"],
                        [
                          "prep_notes",
                          "Preparation notes",
                          "textarea",
                          null,
                          true,
                        ],
                      ]}
                      initial={{ duration_min: 30, type: "Technical" }}
                      submit={(v) =>
                        api.post(`/v1/applications/${id}/interview`, {
                          ...v,
                          scheduled_at: utc(v.scheduled_at),
                        })
                      }
                      done={done}
                    />
                  </details>
                )}
                {["interview", "offer"].includes(a.workflow_status) && (
                  <details className="card">
                    <summary>Create / update pending offer</summary>
                    <Form
                      fields={[
                        ["details", "Offer details", "textarea"],
                        [
                          "expires_at",
                          "Expires (device local time)",
                          "datetime-local",
                        ],
                        ["start_date", "Start date", "date"],
                      ]}
                      submit={(v) =>
                        api.post(`/v1/applications/${id}/offer`, {
                          ...v,
                          expires_at: utc(v.expires_at),
                        })
                      }
                      button="Send offer"
                      done={done}
                    />
                  </details>
                )}
              </>
            )}
          {offer && (
            <section className="card">
              <h3>Offer</h3>
              <p>{offer.details}</p>
              <p>
                Expires: {date(offer.expires_at)} · Start date:{" "}
                {offer.start_date}
              </p>
              <Status value={offer.status} />
              {role === "student" &&
                offer.status === "pending" &&
                a.workflow_status === "offer" && (
                  <div className="toolbar">
                    {["accepted", "declined"].map((decision) => (
                      <Action
                        key={decision}
                        run={() =>
                          api.post(`/v1/offers/${offer.id}/respond`, {
                            decision,
                          })
                        }
                        done={done}
                      >
                        {decision === "accepted"
                          ? "Accept offer"
                          : "Decline offer"}
                      </Action>
                    ))}
                  </div>
                )}
            </section>
          )}
        </>
      )}
    </Load>
  );
}
export function InterviewsWorkspace() {
  const { user } = useAuth(),
    [page, setPage] = useState(1),
    state = useData("/v1/interviews?page=" + page);
  return (
    <>
      <h1>Interviews</h1>
      <p>All displayed times: Asia/Dhaka.</p>
      <Load state={state}>
        {!rows(state.data).length && <p>No interviews scheduled.</p>}
        {rows(state.data).map((i) => (
          <section className="card" key={i.id}>
            <h2>{i.title}</h2>
            <p>
              {i.student_name} · {date(i.scheduled_at)} · {i.duration_min}{" "}
              minutes
            </p>
            <Status value={i.status} />
            <p>
              {i.type} · {i.mode} · {i.interviewer}
            </p>
            <p>{i.link_or_location}</p>
            <p>{i.prep_notes}</p>
            {i.feedback && (
              <p>
                Feedback: {i.feedback.rating}/5 · {i.feedback.recommendation} ·{" "}
                {i.feedback.comments}
              </p>
            )}
            {user.role === "recruiter" && i.status === "scheduled" && (
              <>
                <details>
                  <summary>Record interview feedback</summary>
                  <Form
                    fields={[
                      ["rating", "Rating", "select", ["1", "2", "3", "4", "5"]],
                      [
                        "recommendation",
                        "Recommendation",
                        "select",
                        ["hire", "hold", "reject"],
                      ],
                      ["comments", "Comments", "textarea"],
                    ]}
                    submit={(v) => api.patch("/v1/interviews/" + i.id, v)}
                    done={state.reload}
                  />
                </details>
                <details>
                  <summary>Cancel interview</summary>
                  <Form
                    fields={[["comments", "Cancellation reason", "textarea"]]}
                    submit={(v) =>
                      api.patch("/v1/interviews/" + i.id, {
                        ...v,
                        action: "cancel",
                      })
                    }
                    button="Confirm cancellation"
                    done={state.reload}
                  />
                </details>
              </>
            )}
          </section>
        ))}
        <Pages state={state} page={page} setPage={setPage} />
      </Load>
    </>
  );
}
export function Placements() {
  const [page, setPage] = useState(1),
    state = useData("/v1/placements?page=" + page);
  return (
    <>
      <h1>Placement records</h1>
      <Load state={state}>
        {!rows(state.data).length && <p>No accepted offers yet.</p>}
        {rows(state.data).map((p) => (
          <section className="card" key={p.id}>
            <h2>{p.student_name}</h2>
            <p>
              {p.role_title} · {p.company_name} · {p.job_type}
            </p>
            <p>Start date: {String(p.start_date).slice(0, 10)}</p>
          </section>
        ))}
        <Pages state={state} page={page} setPage={setPage} />
      </Load>
    </>
  );
}
