const { chromium } = require("playwright");
const assert = require("node:assert/strict");
(async () => {
  const browser = await chromium.launch({
    headless: true,
    args: ["--no-sandbox"],
  });
  const page = await browser.newPage({
    viewport: { width: 1280, height: 900 },
  });
  const errors = [];
  page.on("pageerror", (e) => errors.push(e.message));
  page.on("requestfailed", (r) =>
    console.error("Request failed:", r.url(), r.failure()),
  );
  page.on("console", (m) => {
    if (m.type() === "error") errors.push(m.text());
  });
  async function login(role, email) {
    await page.goto("http://localhost:5173/login");
    await page.getByLabel("Workspace", { exact: true }).selectOption(role);
    await page.getByLabel("Email address", { exact: true }).fill(email);
    await page.getByLabel("Password", { exact: true }).fill("DemoPass123!");
    await page.getByRole("button", { name: "Sign in", exact: true }).click();
    await page
      .waitForURL("http://localhost:5173/", { timeout: 10000 })
      .catch(async (e) => {
        console.error(await page.locator("body").innerText());
        throw e;
      });
  }
  await login("student", "student@demo.test");
  assert.equal(await page.evaluate(() => localStorage.getItem("token")), null);
  await page.getByRole("link", { name: "My profile", exact: true }).click();
  await page.getByLabel("Phone", { exact: true }).fill("+0000000000");
  await page
    .getByRole("button", { name: "Save & continue →", exact: true })
    .click();
  await page.getByLabel("Student No", { exact: true }).fill("SMOKE-001");
  await page.getByLabel("Department", { exact: true }).fill("CSE");
  await page
    .getByRole("button", { name: "Save & continue →", exact: true })
    .click();

  await page
    .getByLabel("Technical skills (comma separated)")
    .fill("React, Python");
  await page
    .getByRole("button", { name: "Save & continue →", exact: true })
    .click();
  await page.getByLabel("Soft skills (comma separated)").fill("Communication");
  await page
    .getByRole("button", { name: "Save & continue →", exact: true })
    .click();
  await page.getByLabel("Do you have experiences?").selectOption("false");
  await page
    .getByRole("button", { name: "Save & continue →", exact: true })
    .click();
  await page.getByLabel("Do you have certificates?").selectOption("false");
  await page
    .getByRole("button", { name: "Save & continue →", exact: true })
    .click();
  await page
    .getByLabel("Career objective", { exact: true })
    .fill("Build useful software.");
  await page
    .getByLabel("Career interests (comma separated)")
    .fill("Engineering");
  await page
    .getByRole("button", { name: "Save & continue →", exact: true })
    .click();
  await page
    .getByRole("heading", { name: "Required fields checklist" })
    .waitFor();
  await page.reload();
  await page.getByRole("heading", { name: "8. Review" }).waitFor();
  for (const width of [320, 768, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    assert(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= window.innerWidth,
      ),
      `overflow at ${width}`,
    );
  }
  await page.getByRole("button", { name: "Sign out", exact: true }).click();
  await page.waitForURL("**/login");
  await login("admin", "admin@demo.test");
  await page
    .getByRole("link", { name: "Student verification", exact: true })
    .click();
  const card = page.locator("section").filter({
    has: page.getByRole("heading", { name: "Demo Student", exact: true }),
  });
  await card.getByRole("button", { name: "Review student" }).click();
  await page
    .getByRole("button", { name: "Approve student", exact: true })
    .click();
  await page
    .getByRole("status")
    .filter({ hasText: "Student verified." })
    .waitFor();
  await page.getByLabel("Status", { exact: true }).selectOption("verified");
  await page
    .getByRole("heading", { name: "Demo Student", exact: true })
    .waitFor();
  assert.deepEqual(errors, []);
  console.log(
    "PASS: token login, wizard steps 1–8, reload, three widths, admin verification; no page errors.",
  );
  await browser.close();
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
