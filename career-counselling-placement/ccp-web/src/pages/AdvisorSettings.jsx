import api from "../api";
import { useData, Load, Form } from "../WorkflowUI";
export default function AdvisorSettings() {
  const state = useData("/v1/advisor-profile");
  return (
    <section className="card">
      <h2>Advisor availability and expertise</h2>
      <Load state={state}>
        <Form
          initial={{
            ...state.data?.data,
            is_accepting: state.data?.data?.is_accepting ? "1" : "0",
            expertise: state.data?.expertise?.join(", "),
            interests: state.data?.interests?.join(", ") || "",
          }}
          fields={[
            ["title", "Professional title"],
            ["bio", "Advisor biography", "textarea"],
            ["capacity", "Maximum active students", "number"],
            [
              "is_accepting",
              "Accept new students?",
              "select",
              [
                ["1", "Yes"],
                ["0", "No"],
              ],
            ],
            ["expertise", "Expertise (comma separated)"],
            ["interests", "Areas of interest (comma separated)"],
          ]}
          submit={(v) =>
            api.put("/v1/advisor-profile", {
              ...v,
              is_accepting: v.is_accepting === "1",
              expertise: v.expertise
                .split(",")
                .map((s) => s.trim())
                .filter(Boolean),
              interests: v.interests
                .split(",")
                .map((s) => s.trim())
                .filter(Boolean),
            })
          }
        />
      </Load>
    </section>
  );
}
