import React from "react";
import { afterEach, expect, it, vi } from "vitest";
import { cleanup, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import Dashboard from "../src/pages/Dashboard";

const { auth, dashboard } = vi.hoisted(() => ({
  auth: { user: { role: "student", name: "Test User" } },
  dashboard: { data: null },
}));

vi.mock("../src/AuthContext", () => ({ useAuth: () => auth }));
vi.mock("../src/ui", () => ({
  Card: ({ title, children }) => (
    <section>
      {title && <h2>{title}</h2>}
      {children}
    </section>
  ),
  fmt: (value) => value,
  useFetch: () => ({ data: dashboard.data, error: null }),
}));
vi.mock("../src/WorkflowUI", () => ({ label: (value) => value }));

afterEach(() => {
  cleanup();
});

function renderDashboard(role, stats, pipeline) {
  auth.user = { role, name: "Test User" };
  dashboard.data = { stats, pipeline, alerts: [], upcoming: null };
  render(
    <MemoryRouter>
      <Dashboard />
    </MemoryRouter>,
  );
}

it.each([
  [
    "student",
    [
      ["Active Applications", "/applications"],
      ["Interviews", "/interviews"],
      ["Offers", "/offers"],
      ["Latest CV", "/cv"],
    ],
  ],
  [
    "advisor",
    [
      ["Assigned Students", "/students"],
      ["CVs Awaiting Review", "/cv-review"],
    ],
  ],
  [
    "admin",
    [
      ["Students", "/users"],
      ["Pending Reviews", "/queue?review=pending"],
      ["Verified Companies", "/companies"],
      ["Offers", "/queue?status=offer"],
    ],
  ],
  [
    "recruiter",
    [
      ["Candidates", "/candidates"],
      ["Interviews", "/interviews"],
      ["Offers", "/offers"],
    ],
  ],
])("%s dashboard summary cards navigate to their workspace pages", (role, routes) => {
  renderDashboard(
    role,
    routes.map(([label]) => ({ label, value: 1 })),
  );
  for (const [label, route] of routes) {
    expect(screen.getByRole("link", { name: new RegExp(label) }).getAttribute("href")).toBe(
      route,
    );
  }
});

it("links each student pipeline stage to its filtered applications", () => {
  renderDashboard("student", [], { submitted: 2, interview: 1, offer: 0 });
  for (const stage of ["submitted", "forwarded", "shortlisted", "interview", "offer"]) {
    expect(
      screen.getByRole("link", { name: new RegExp(stage) }).getAttribute("href"),
    ).toBe(`/applications?status=${stage}`);
  }
});

it("links upcoming interviews and recent activity to their detail pages", () => {
  auth.user = { role: "student", name: "Test User" };
  dashboard.data = {
    stats: [],
    alerts: [{ id: 1, title: "New update", body: "", created_at: "today" }],
    upcoming: {
      application: {
        opportunity: { title: "Role", company: { name: "Company" } },
      },
      scheduled_at: "tomorrow",
      mode: "Online",
    },
  };
  render(
    <MemoryRouter>
      <Dashboard />
    </MemoryRouter>,
  );
  expect(screen.getByRole("link", { name: /Upcoming interview/ }).getAttribute("href")).toBe(
    "/interviews",
  );
  expect(screen.getByRole("link", { name: /Recent activity/ }).getAttribute("href")).toBe(
    "/notifications",
  );
});
