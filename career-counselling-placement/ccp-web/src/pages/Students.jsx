import { useState } from "react";
import { useData, Load } from "../WorkflowUI";
export default function Students() {
  const state = useData("/students"),
    [selected, setSelected] = useState(null);
  return (
    <>
      <h1>My Students</h1>
      <Load state={state}>
        {state.data?.length === 0 && <p>No students assigned yet.</p>}
        {state.data?.map((s) => (
          <section className="card" key={s.id}>
            <h2>{s.name}</h2>
            <p>
              {s.email} · {s.department || "Department not set"}
            </p>
            <button
              onClick={() => setSelected(selected === s.id ? null : s.id)}
            >
              View profile
            </button>
            {selected === s.id && <StudentDetail id={s.id} />}
          </section>
        ))}
      </Load>
    </>
  );
}
function StudentDetail({ id }) {
  const state = useData("/v1/students/" + id);
  const p = state.data?.data;
  return (
    <Load state={state}>
      {p && (
        <>
          <p>Phone: {p.phone}</p>
          <p>
            Profile completion: {p.completion.percent}% · Readiness:{" "}
            {p.readiness.state}
          </p>
          <dl>
            {Object.entries(p.profile).map(([k, v]) => (
              <div key={k}>
                <dt>{k.replaceAll("_", " ")}</dt>
                <dd>
                  {Array.isArray(v)
                    ? v.join(", ")
                    : String(v ?? "Not provided")}
                </dd>
              </div>
            ))}
          </dl>
          <p>Technical skills: {p.technical_skills.join(", ")}</p>
          <p>Soft skills: {p.soft_skills.join(", ")}</p>
          {["experiences", "certificates"].map((k) => (
            <section key={k}>
              <h3>{k}</h3>
              {p[k].map((e, i) => (
                <p key={i}>
                  {e.title} · {e.org} · {e.description}
                </p>
              ))}
            </section>
          ))}
        </>
      )}
    </Load>
  );
}
