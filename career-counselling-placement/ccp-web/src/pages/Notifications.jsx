import { useState } from "react";
import { Link } from "react-router-dom";
import api from "../api";
import { useData, Load, rows, Action, Form, Pages, date } from "../WorkflowUI";
import { useAuth } from "../AuthContext";
export default function Notifications() {
  const { user } = useAuth(),
    [filter, setFilter] = useState("all"),
    [page, setPage] = useState(1),
    state = useData(`/v1/notifications?filter=${filter}&page=${page}`),
    reminders = useData("/v1/reminders");
  const reload = () => {
    state.reload();
    window.dispatchEvent(new Event("notifications-updated"));
  };
  return (
    <>
      <h1>Notifications</h1>
      <div className="toolbar">
        <label htmlFor="notification-filter">Show</label>
        <select
          id="notification-filter"
          value={filter}
          onChange={(e) => {
            setFilter(e.target.value);
            setPage(1);
          }}
        >
          {["all", "unread", "deadlines", "interviews"].map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
        <Action
          run={() => api.post("/v1/notifications/read-all")}
          done={reload}
        >
          Mark all as read
        </Action>
      </div>
      <Load state={state}>
        {!rows(state.data).length && (
          <p className="card">
            No notifications in this view. Updates will appear as your work
            progresses.
          </p>
        )}
        {rows(state.data).map((n) => (
          <article className="card" key={n.id}>
            <h2>{n.data.title}</h2>
            <p>{n.data.body}</p>
            <small>
              {date(n.created_at)} · {n.read_at ? "Read" : "Unread"}
            </small>
            <div className="toolbar">
              <Link to={n.data.url || "/"}>Open workspace</Link>
              {!n.read_at && (
                <Action
                  run={() => api.post(`/v1/notifications/${n.id}/read`)}
                  done={reload}
                >
                  Mark as read
                </Action>
              )}
            </div>
          </article>
        ))}
        <Pages state={state} page={page} setPage={setPage} />
      </Load>
      {user.role === "student" && (
        <section className="card">
          <h2>Follow-up reminders</h2>
          <p>
            Find the application ID in your application detail. Choose how often
            to be reminded.
          </p>
          <Form
            fields={[
              ["application_id", "Application ID", "number"],
              ["interval_days", "Repeat every (days)", "number"],
            ]}
            initial={{ interval_days: 7 }}
            submit={(v) => api.post("/v1/reminders", v)}
            button="Set reminder"
            done={reminders.reload}
          />
          <Load state={reminders}>
            {!rows(reminders.data).length && <p>No follow-up reminders set.</p>}
            {rows(reminders.data).map((n) => (
              <div key={n.id}>
                <p>
                  {n.title} · {n.sent_at ? "Finished" : date(n.remind_at)}
                </p>
                <Action
                  run={() => api.delete(`/v1/reminders/${n.id}`)}
                  done={reminders.reload}
                >
                  Remove reminder
                </Action>
              </div>
            ))}
          </Load>
        </section>
      )}
    </>
  );
}
