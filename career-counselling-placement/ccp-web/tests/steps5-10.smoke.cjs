const { chromium } = require("playwright");
const assert = require("node:assert/strict");
(async () => {
  const browser = await chromium.launch({
    headless: true,
    args: ["--no-sandbox"],
  });
  const page = await browser.newPage({
    viewport: { width: 1440, height: 1000 },
  });
  const errors = [],
    badResponses = [];
  page.on("request", (r) => {
    if (r.url().includes("/sanctum/csrf-cookie"))
      errors.push("Unexpected CSRF cookie request");
  });
  page.on("console",m=>{if(m.type()==="error")errors.push(m.text());});
  page.on("pageerror", (e) => errors.push(e.message));
  page.on("response", (r) => {
    if (r.status() >= 500) badResponses.push(r.url() + ": " + r.status());
  });
  async function login(role, email) {
    await page.goto("http://localhost:5173/login");
    await page.getByLabel("Workspace", { exact: true }).selectOption(role);
    assert.equal(
      await page.getByText("Create student account", { exact: true }).count(),
      role === "student" ? 1 : 0,
    );
    await page.getByLabel("Email address", { exact: true }).fill(email);
    await page.getByLabel("Password", { exact: true }).fill("DemoPass123!");
    await page.getByRole("button", { name: "Sign in", exact: true }).click();
    await page.waitForURL("http://localhost:5173/");
    await page.getByRole("heading", { name: /Welcome,/ }).waitFor();
  }
  async function logout() {
    await page.getByRole("button", { name: "Sign out", exact: true }).click();
    await page.waitForURL("**/login");
  }
  async function api(method, path, data) {
    const result = await page.evaluate(
      async ({ method, path, data }) => {
        const { default: api } = await import("/src/api.js");
        try {
          const r = await api.request({ method, url: path, data });
          return { status: r.status, data: r.data };
        } catch (e) {
          return { status: e.response?.status, data: e.response?.data };
        }
      },
      { method, path, data },
    );
    assert.ok(
      result.status < 400,
      `${method} ${path}: ${JSON.stringify(result)}`,
    );
    return result.data;
  }
  async function route(path, title) {
    await page.goto("http://localhost:5173" + path);
    await page.getByRole("heading", { name: title, exact: true }).waitFor();
    await page.waitForTimeout(150);
    assert.equal(
      await page.getByRole("button", { name: "Retry", exact: true }).count(),
      0,
      await page.locator("body").innerText(),
    );
  }
  await login("student", "stage1@demo.test");
  for (const [p, t] of [
    ["/profile", "My profile"],
    ["/advisors", "My advisor"],
    ["/sessions", "Counselling sessions"],
    ["/career-plan", "Career plan"],
    ["/cv", "CV Builder & versions"],
    ["/opportunities", "Find opportunities"],
    ["/applications", "My applications"],
    ["/offers", "Offers"],
    ["/interviews", "Interviews"],
  ])
    await route(p, t);
  const me = (await api("get", "/me")).data;
  const cv = (
    await api("post", "/v1/cvs", {
      title: "Browser test CV",
      source: "builder",
      content: {
        name: "Demo Stage 1",
        email: me.email,
        summary: "Browser smoke test",
        education: "BSc",
        skills: "React",
        experience: "Portfolio",
      },
    })
  ).data;
  await api("post", `/v1/cvs/${cv.id}/submit`);
  await logout();
  await login("advisor", "advisor@demo.test");
  for (const [p, t] of [
    ["/cv-review", "CV review"],
    ["/opportunities", "Find opportunities"],
    ["/students", "My Students"],
    ["/sessions", "Counselling sessions"],
    ["/career-plan", "Career plan"],
  ])
    await route(p, t);
  await api("post", `/v1/cvs/${cv.id}/review`, {
    decision: "approved",
    strengths: "Clear",
    improvements: "Keep learning",
    placement_recommendation: "ready",
  });
  await logout();
  await login("recruiter", "recruiter@demo.test");
  await route("/offers", "Offers");
  await route("/post", "My opportunities");
  const opportunity = (
    await api("post", "/v1/opportunities", {
      title: "Browser test opportunity",
      type: "Job",
      location: "Dhaka",
      work_mode: "Hybrid",
      is_paid: true,
      description: "Fictional browser test vacancy",
      responsibilities: "Build software",
      requirements: "BSc",
      skills: ["React"],
      deadline: "2027-12-30",
    })
  ).data;
  await logout();
  await login("admin", "admin@demo.test");
  for (const [p, t] of [
    ["/allocations", "Advisor allocation"],
    ["/opportunity-approval", "Opportunity approval"],
    ["/queue", "Application review"],
    ["/placements", "Placement records"],
    ["/career-plan", "Career plan"],
    ["/interviews", "Interviews"],
    ["/users", "Users"],
  ])
    await route(p, t);
  await api("post", `/v1/opportunities/${opportunity.id}/review`, {
    decision: "approved",
  });
  await logout();
  await login("student", "stage1@demo.test");
  const checks = (
    await api(
      "get",
      `/v1/opportunities/${opportunity.id}/eligibility?cv_id=${cv.id}`,
    )
  ).data;
  assert.ok(Object.values(checks).every(Boolean), JSON.stringify(checks));
  const application = (
    await api("post", "/v1/applications", {
      opportunity_id: opportunity.id,
      cv_id: cv.id,
      cover_note: "Browser test",
    })
  ).data;
  await route("/applications", "My applications");
  await page.getByText("Browser test opportunity", { exact: true }).waitFor();
  assert.ok(
    await page.getByText("Submitted to Admin", { exact: true }).count(),
  );
  await logout();
  await login("admin", "admin@demo.test");
  await api("patch", `/v1/applications/${application.id}`, {
    status: "forwarded",
  });
  await logout();
  await login("recruiter", "recruiter@demo.test");
  await route("/candidates", "Forwarded candidates");
  await api("patch", `/v1/applications/${application.id}`, {
    status: "shortlisted",
  });
  await api("post", `/v1/applications/${application.id}/interview`, {
    scheduled_at: "2027-11-01T04:00:00Z",
    duration_min: 30,
    type: "Technical",
    mode: "Online",
    interviewer: "Fictional interviewer",
    link_or_location: "Demo meeting room",
  });
  await api("post", `/v1/applications/${application.id}/offer`, {
    details: "Fictional offer",
    expires_at: "2027-11-10T04:00:00Z",
    start_date: "2027-11-20",
  });
  await logout();
  await login("student", "stage1@demo.test");
  const detail = await api("get", `/v1/applications/${application.id}`);
  await api("post", `/v1/offers/${detail.offer.id}/respond`, {
    decision: "accepted",
  });
  await route("/applications", "My applications");
  await page.getByText("offer accepted", { exact: true }).first().waitFor();
  for (const width of [320, 375, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    assert.ok(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= window.innerWidth,
      ),
      `Overflow ${width}`,
    );
  }
  await logout();
  await login("admin", "admin@demo.test");
  await route("/placements", "Placement records");
  await page.getByText(/Browser test opportunity/).waitFor();
  await logout();
  await page.goto("http://localhost:5173/register");
  await page
    .getByLabel("Full name", { exact: true })
    .fill("Fictional Personal Email");
  await page
    .getByLabel("Email address", { exact: true })
    .fill("browser-personal@example.com");
  await page.getByLabel("Department", { exact: true }).fill("CSE");
  await page.getByLabel("Password", { exact: true }).fill("DemoPass123!");
  await page
    .getByLabel("Confirm password", { exact: true })
    .fill("DemoPass123!");
  await page.getByRole("button", { name: "Register", exact: true }).click();
  await page.waitForURL("http://localhost:5173/");
  assert.equal((await api("get", "/me")).data.email_verified, false);
  await page.reload();
  await page.getByRole("heading", { name: /Welcome,/ }).waitFor();
  assert.equal((await page.context().cookies()).length, 0, "No login cookies");
  await logout();
  await login("student", "browser-personal@example.com");
  await route("/profile", "My profile");
  assert.deepEqual(errors, []);
  assert.deepEqual(badResponses, []);
  await page.screenshot({ path: "/tmp/ccp-updated-admin.png", fullPage: true });
  await browser.close();
  console.log(
    "PASS: four-role login, 22 role screens, CV review → opportunity approval → application forwarding → interview → offer → placement, 5 responsive widths.",
  );
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
