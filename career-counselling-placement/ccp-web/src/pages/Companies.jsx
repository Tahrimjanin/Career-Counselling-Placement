import api from "../api";
import { useData, Load, Form, Action } from "../WorkflowUI";
export default function Companies() {
  const state = useData("/companies");
  return (
    <>
      <h1>Companies</h1>
      <section className="card">
        <h2>Create company</h2>
        <Form
          fields={[
            ["name", "Company name"],
            ["industry", "Industry", "text", null, true],
            ["website", "Website", "url", null, true],
            ["description", "Description", "textarea", null, true],
          ]}
          submit={(v) => api.post("/v1/admin/companies", v)}
          done={state.reload}
        />
      </section>
      <Load state={state}>
        {!state.data?.length && <p>No companies yet.</p>}
        {state.data?.map((c) => (
          <section className="card" key={c.id}>
            <h2>{c.name}</h2>
            <p>
              {c.verified ? "Verified" : "Pending verification"} ·{" "}
              {c.opportunities_count} opportunities
            </p>
            <Action
              run={() => api.patch(`/companies/${c.id}/verify`)}
              done={state.reload}
            >
              {c.verified ? "Revoke verification" : "Verify company"}
            </Action>
          </section>
        ))}
      </Load>
    </>
  );
}
