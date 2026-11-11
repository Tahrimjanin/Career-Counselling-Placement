import { label } from "../WorkflowUI";
import { useAuth } from "../AuthContext";
import { Card, fmt, useFetch } from "../ui";
import { Link } from "react-router-dom";

const STAGES = ["submitted", "forwarded", "shortlisted", "interview", "offer"];
const STAT_ROUTES = {
  student: {
    "Active Applications": "/applications",
    Interviews: "/interviews",
    Offers: "/offers",
    "Latest CV": "/cv",
  },
  advisor: {
    "Assigned Students": "/students",
    "CVs Awaiting Review": "/cv-review",
  },
  admin: {
    Students: "/users",
    "Pending Reviews": "/queue?review=pending",
    "Verified Companies": "/companies",
    Offers: "/queue?status=offer",
  },
  recruiter: {
    Candidates: "/candidates",
    Interviews: "/interviews",
    Offers: "/offers",
  },
};

export default function Dashboard() {
  const { user } = useAuth();
  const { data, error } = useFetch("/dashboard");
  if (error) return <p className="error">{error}</p>;
  if (!data) return <p>Loading…</p>;
  return (
    <>
      <h1>Welcome, {user.name}</h1>
      <p className="muted">Your {user.role} workspace at a glance.</p>
      <div className="grid4">
        {data.stats.map((s) => (
          <Link
            className="dashboard-card-link"
            key={s.label}
            to={STAT_ROUTES[user.role][s.label]}
          >
            <Card>
              <small>{s.label}</small>
              <div className="big">{s.value}</div>
            </Card>
          </Link>
        ))}
      </div>
      {data.pipeline && (
        <Card title="Application pipeline">
          <div className="pipe">
            {STAGES.map((s) => (
              <Link
                className="dashboard-pipeline-link"
                key={s}
                to={`/applications?status=${s}`}
              >
                <div>
                  <small>{label(s)}</small>
                  <b>{data.pipeline[s] ?? 0}</b>
                </div>
              </Link>
            ))}
          </div>
        </Card>
      )}
      {data.upcoming && (
        <Link className="dashboard-card-link" to="/interviews">
          <Card title="Upcoming interview">
            <b>{data.upcoming.application.opportunity.title}</b> ·{" "}
            {data.upcoming.application.opportunity.company.name}
            <br />
            {fmt(data.upcoming.scheduled_at)} · {data.upcoming.mode}
          </Card>
        </Link>
      )}
      <Link className="dashboard-card-link" to="/notifications">
        <Card title="Recent activity">
          {data.alerts.length ? (
            data.alerts.map((a) => (
              <p key={a.id}>
                <b>{a.title}</b>
                {a.body && <> — {a.body}</>}
                <br />
                <small>{fmt(a.created_at)}</small>
              </p>
            ))
          ) : (
            <p className="muted">Nothing yet.</p>
          )}
        </Card>
      </Link>
    </>
  );
}
