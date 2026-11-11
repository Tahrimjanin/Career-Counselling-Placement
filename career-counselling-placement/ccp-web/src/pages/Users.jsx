import { useState } from "react";
import { Link } from "react-router-dom";
import api from "../api";
import { useAuth } from "../AuthContext";
import { useData, Load, Form, rows, Pages, Action } from "../WorkflowUI";
export default function Users() {
  const { user } = useAuth();
  const [page, setPage] = useState(1),
    [status, setStatus] = useState(""),
    state = useData("/v1/admin/users?" + new URLSearchParams({ page, status })),
    companies = useData("/companies"),
    [role, setRole] = useState("advisor"),
    [created, setCreated] = useState("");
  return (
    <>
      <h1>Users</h1>
      <section className="card">
        <h2>Create an account</h2>
        <p>
          Any valid email address is accepted. The user must change their
          temporary password before accessing the workspace.
        </p>
        <label htmlFor="create-role">Account role</label>
        <select
          id="create-role"
          value={role}
          onChange={(e) => setRole(e.target.value)}
        >
          <option value="student">Student</option>
          <option value="advisor">Career Advisor</option>
          <option value="admin">Admin</option>
          <option value="recruiter">Recruiter</option>
        </select>
        <Form
          key={role}
          fields={[
            ["name", "Full name"],
            ["email", "Email address", "email"],
            [
              "password",
              "Temporary password (10+ characters, letters and numbers)",
              "password",
            ],
            ...(role === "recruiter"
              ? [
                  [
                    "company_id",
                    "Company",
                    "select",
                    (companies.data || []).map((c) => [c.id, c.name]),
                  ],
                ]
              : []),
          ]}
          submit={(v) => api.post("/users", { ...v, role })}
          button="Create account"
          done={(r) => {
            setCreated(
              `Created ${r.data.data.email} as ${r.data.data.role}. Share the temporary password securely.`,
            );
            state.reload();
          }}
        />
        <p role="status">{created}</p>
      </section>
      <Load state={state}>
        <section className="card">
          <h2>All users</h2>
          <label htmlFor="user-status">Account status</label>
          <select
            id="user-status"
            value={status}
            onChange={(e) => {
              setStatus(e.target.value);
              setPage(1);
            }}
          >
            <option value="">All</option>
            <option value="pending">Pending approval</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
          </select>
          <p>
            <Link to="/allocations">
              Manage advisor allocations and capacity
            </Link>
          </p>
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status / actions</th>
              </tr>
            </thead>
            <tbody>
              {rows(state.data).map((u) => (
                <tr key={u.id}>
                  <td>{u.name}</td>
                  <td>{u.email}</td>
                  <td>{u.role}</td>
                  <td>
                    <p>{u.status}</p>
                    <Action
                      className="user-action"
                      run={() =>
                        api.patch(`/v1/admin/users/${u.id}`, {
                          status: "active",
                        })
                      }
                      done={state.reload}
                    >
                      {u.status === "pending" ? "Approve account" : "Activate"}
                    </Action>
                    <Action
                      className="user-action"
                      run={() => {
                        if (!window.confirm(`Suspend ${u.name}?`))
                          return Promise.resolve();
                        return api.patch(`/v1/admin/users/${u.id}`, {
                          status: "suspended",
                        });
                      }}
                      done={state.reload}
                    >
                      Suspend
                    </Action>
                    {u.role === "advisor" && (
                      <Action
                        className="user-action"
                        run={() => {
                          if (!window.confirm(`Promote ${u.name} to Admin?`))
                            return Promise.resolve();
                          return api.patch(`/v1/admin/users/${u.id}`, {
                            role: "admin",
                          });
                        }}
                        done={state.reload}
                      >
                        Promote to Admin
                      </Action>
                    )}
                    <details>
                      <summary>Reset password</summary>
                      <Form
                        fields={[
                          ["password", "New temporary password", "password"],
                        ]}
                        submit={(v) => api.patch(`/v1/admin/users/${u.id}`, v)}
                        button="Reset password"
                        done={state.reload}
                      />
                    </details>
                    <Action
                      className="user-action"
                      disabled={u.id === user.id}
                      run={() => {
                        if (
                          !window.confirm(
                            `Remove ${u.name}'s account? Their work data will be preserved and account access revoked.`,
                          )
                        )
                          return Promise.resolve();
                        return api.delete(`/v1/admin/users/${u.id}`);
                      }}
                      done={state.reload}
                    >
                      Remove account
                    </Action>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <Pages state={state} page={page} setPage={setPage} />
        </section>
      </Load>
    </>
  );
}
