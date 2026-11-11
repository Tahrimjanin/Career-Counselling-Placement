import { useState } from "react";
import api from "../api";
import { useData, Load, rows, Form, Action, Pages, date } from "../WorkflowUI";
export function Reports() {
  const [filter, setFilter] = useState({}),
    state = useData("/v1/admin/reports?" + new URLSearchParams(filter));
  const data = state.data?.data;
  return (
    <>
      <h1>Reports</h1>
      <section className="card">
        <Form
          fields={[
            ["from", "From date", "date", null, true],
            ["to", "To date", "date", null, true],
          ]}
          button="Apply dates"
          submit={async (v) => setFilter(v)}
        />
        <Action
          run={async () => {
            const r = await api.get(
              "/v1/admin/reports.csv?" + new URLSearchParams(filter),
              { responseType: "blob" },
            );
            const url = URL.createObjectURL(r.data),
              a = document.createElement("a");
            a.href = url;
            a.download = "ccp-report.csv";
            a.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
          }}
        >
          Export CSV
        </Action>
      </section>
      <Load state={state}>
        {data && (
          <>
            <p>{data.definition}</p>
            <div className="stats">
              {[
                ["Applicants", data.applicants],
                ["Placed students", data.placed_students],
                ["Placement rate", data.placement_rate + "%"],
                ["Applications", data.applications],
              ].map(([label, value]) => (
                <section className="card" key={label}>
                  <h2>{value}</h2>
                  <p>{label}</p>
                </section>
              ))}
            </div>
            {[
              ["Application funnel", data.funnel],
              ["Advisor workload", data.advisor_workload],
              ["Top skills", data.top_skills],
            ].map(([title, items]) => (
              <section className="card" key={title}>
                <h2>{title}</h2>
                {!items.length ? (
                  <p>No matching records.</p>
                ) : (
                  <table>
                    <thead>
                      <tr>
                        <th scope="col">Name / status</th>
                        <th scope="col">Count</th>
                      </tr>
                    </thead>
                    <tbody>
                      {items.map((x, i) => (
                        <tr key={i}>
                          <td>{x.name || x.status}</td>
                          <td>{x.total}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </section>
            ))}
          </>
        )}
      </Load>
    </>
  );
}
export function Settings() {
  const state = useData("/v1/admin/settings");
  const logo = state.data?.logo_url;
  return (
    <>
      <h1>Site and platform settings</h1>
      <Load state={state}>
        {state.data && (
          <>
            <section className="card">
              <h2>University and branding</h2>
              <SettingsForm
                state={state}
                fields={[
                  ["site_name", "Site name"],
                  ["university_name", "University name"],
                  ["brand_tagline", "Brand tagline"],
                  ["nav_journey_label", "How it works navigation label"],
                  ["nav_advisors_label", "Career advisors link label"],
                  ["nav_opportunities_label", "Opportunities navigation label"],
                  ["nav_sign_in_label", "Sign-in navigation label"],
                  ["nav_create_account_label", "Create-account navigation label"],
                  ["nav_back_home_label", "Back-to-home button label"],
                  ["theme_primary_color", "Primary color", "color"],
                  ["theme_primary_dark_color", "Primary hover color", "color"],
                  ["theme_navy_color", "Navy color", "color"],
                ]}
              />
              <p>Color combinations are checked to retain at least 4.5:1 contrast for key text.</p>
              <LogoManager logo={logo} reload={state.reload} />
            </section>
            <section className="card">
              <h2>Home page sections and notice</h2>
              <SettingsForm
                state={state}
                fields={[
                  ["hero_enabled", "Show home hero", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["stats_enabled", "Show platform statistics", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["journey_enabled", "Show How it works", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["journey_home_enabled", "Show How it works on home page", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["advisors_enabled", "Show career advisors", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["advisors_home_enabled", "Show career advisors on home page", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["opportunities_enabled", "Show opportunities", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["opportunities_home_enabled", "Show opportunities on home page", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["faq_enabled", "Show FAQ", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["faq_home_enabled", "Show FAQ on home page", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["contact_enabled", "Show contact section", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["contact_home_enabled", "Show contact on home page", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["footer_enabled", "Show footer", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["section_order", "Section order (comma-separated IDs)", "text"],
                  ["announcement_enabled", "Show site announcement", "select", [["1", "Shown"], ["0", "Hidden"]]],
                  ["announcement_text", "Announcement text", "textarea", null, true],
                ]}
              />
              <p>Use each section ID once: journey, advisors, opportunities, faq, contact. Hidden sections keep their position and return when shown.</p>
            </section>
            <section className="card">
              <h2>Home hero</h2>
              <SettingsForm
                state={state}
                fields={[
                  ["hero_kicker", "Eyebrow text"],
                  ["hero_title_first", "Title line one"],
                  ["hero_title_second", "Title line two"],
                  ["hero_description", "Description", "textarea"],
                  ["hero_primary_cta", "Create account button label"],
                  ["hero_secondary_cta", "Sign-in button label"],
                  ["hero_note", "Supporting note"],
                  ["hero_card_title", "Hero journey card title"],
                  ["hero_card_subtitle", "Hero journey card subtitle"],
                  ["hero_visual_footer", "Hero journey card footer"],
                  ["hero_float_title", "Floating card title"],
                  ["hero_float_subtitle", "Floating card subtitle"],
                ]}
              />
            </section>
            <section className="card">
              <h2>Statistics and journey</h2>
              <SettingsForm
                state={state}
                fields={[
                  ["stats_kicker", "Statistics label"],
                  ["stats_text", "Statistics introduction", "textarea"],
                  ["journey_kicker", "Journey label"],
                  ["journey_title", "Journey heading"],
                  ["journey_description", "Journey description", "textarea"],
                  ...[1, 2, 3, 4].flatMap((i) => [
                    [`journey_step_${i}_title`, `Step ${i} title`],
                    [`journey_step_${i}_body`, `Step ${i} description`, "textarea"],
                  ]),
                ]}
              />
            </section>
            <section className="card">
              <h2>Advisors and opportunities</h2>
              <SettingsForm
                state={state}
                fields={[
                  ["advisors_kicker", "Advisors label"],
                  ["advisors_title", "Advisors heading"],
                  ["advisors_description", "Advisors description", "textarea"],
                  ["advisors_empty", "No advisors message"],
                  ["opportunities_kicker", "Opportunities label"],
                  ["opportunities_title", "Opportunities heading"],
                  ["opportunities_description", "Opportunities description", "textarea"],
                  ["opportunities_empty", "No opportunities message"],
                  ["opportunity_sign_in_label", "Opportunity sign-in link label"],
                ]}
              />
            </section>
            <section className="card">
              <h2>Frequently asked questions</h2>
              <SettingsForm
                state={state}
                fields={[
                  ["faq_kicker", "FAQ label"],
                  ["faq_title", "FAQ heading"],
                  ["faq_description", "FAQ introduction", "textarea"],
                  ...[1, 2, 3, 4].flatMap((i) => [
                    [`faq_${i}_question`, `Question ${i}`, "text", null, true],
                    [`faq_${i}_answer`, `Answer ${i}`, "textarea", null, true],
                  ]),
                ]}
              />
            </section>
            <section className="card">
              <h2>Contact, footer and policies</h2>
              <SettingsForm
                state={state}
                fields={[
                  ["contact_kicker", "Contact label"],
                  ["contact_title", "Contact heading"],
                  ["contact_description", "Contact description", "textarea"],
                  ["contact_cta", "Contact button label"],
                  ["contact_email", "Contact email", "email", null, true],
                  ["contact_phone", "Contact phone", "text", null, true],
                  ["contact_address", "Contact address", "textarea", null, true],
                  ["privacy_text", "Privacy notice", "textarea"],
                  ["terms_text", "Terms of use", "textarea"],
                  ["privacy_title", "Privacy page title"],
                  ["terms_title", "Terms page title"],
                  ["legal_back_label", "Legal page return link label"],
                  ["footer_explore_label", "Footer career links label"],
                  ["footer_information_label", "Footer information label"],
                  ["footer_privacy_label", "Footer privacy link label"],
                  ["footer_terms_label", "Footer terms link label"],
                  ["footer_sign_in_label", "Footer sign-in label"],
                  ["footer_copyright", "Footer copyright message"],
                ]}
              />
            </section>
            <section className="card">
              <h2>Platform operation</h2>
              <SettingsForm
                state={state}
                fields={[
                  ["academic_session", "Academic session", "text", null, true],
                  ["default_capacity", "Default advisor capacity", "number"],
                ]}
              />
              <p>Advisor capacity applies to new advisor accounts. Contact and policy text appear on public pages.</p>
            </section>
          </>
        )}
      </Load>
    </>
  );
}

function SettingsForm({ state, fields }) {
  const settings = state.data.data;
  const initial = Object.fromEntries(
    fields.map(([key]) => [key, settings[key] ?? ""]),
  );
  return (
    <Form
      key={JSON.stringify(initial)}
      initial={initial}
      fields={fields}
      submit={(values) => api.put("/v1/admin/settings", values)}
      done={() => {
        state.reload();
        window.dispatchEvent(new Event("site-settings-updated"));
      }}
    />
  );
}

function LogoManager({ logo, reload }) {
  const [file, setFile] = useState(null);
  return (
    <div className="stack">
      <h3>Site logo</h3>
      {logo && <img src={logo} alt="Current site logo" width="120" />}
      <label htmlFor="site-logo-upload">Upload logo (JPG, PNG or WebP; max 2 MB)</label>
      <input
        id="site-logo-upload"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        onChange={(event) => setFile(event.target.files?.[0] || null)}
      />
      <Action
        disabled={!file}
        run={async () => {
          const form = new FormData();
          form.append("image", file);
          await api.post("/v1/admin/settings/logo", form);
          setFile(null);
        }}
        done={() => {
          reload();
          window.dispatchEvent(new Event("site-settings-updated"));
        }}
      >
        Upload logo
      </Action>
      {logo && (
        <Action
          run={() => api.delete("/v1/admin/settings/logo")}
          done={() => {
            reload();
            window.dispatchEvent(new Event("site-settings-updated"));
          }}
        >
          Remove logo
        </Action>
      )}
    </div>
  );
}
export function AuditLog() {
  const [q, setQ] = useState(""),
    [page, setPage] = useState(1),
    state = useData("/v1/admin/audit?" + new URLSearchParams({ q, page }));
  return (
    <>
      <h1>Audit log</h1>
      <label htmlFor="audit-query">Filter action</label>
      <input
        id="audit-query"
        value={q}
        onChange={(e) => {
          setQ(e.target.value);
          setPage(1);
        }}
      />
      <Load state={state}>
        {!rows(state.data).length && <p>No matching audit events.</p>}
        {rows(state.data).map((a) => (
          <article className="card" key={a.id}>
            <h2>{a.action}</h2>
            <p>
              {a.actor_name || "System"} · {date(a.created_at)} ·{" "}
              {a.entity_type} #{a.entity_id}
            </p>
            <details>
              <summary>Decision details</summary>
              <pre className="wrap-code">
                {JSON.stringify(
                  typeof a.new_values === "string"
                    ? JSON.parse(a.new_values)
                    : a.new_values,
                  null,
                  2,
                )}
              </pre>
            </details>
          </article>
        ))}
        <Pages state={state} page={page} setPage={setPage} />
      </Load>
    </>
  );
}
