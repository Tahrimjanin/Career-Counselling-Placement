import React from "react";
import { afterEach, expect, it, vi } from "vitest";
import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import Profile from "../src/pages/Profile";

const { api, auth } = vi.hoisted(() => ({
  api: { get: vi.fn(), patch: vi.fn() },
  auth: { user: { role: "student" }, refresh: vi.fn() },
}));

vi.mock("../src/AuthContext", () => ({ useAuth: () => auth }));
vi.mock("../src/api", () => ({
  default: api,
  err: (error) => error.message || "Unable to connect.",
}));

afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});

it("places Add entry after the first experience fields", async () => {
  api.get.mockResolvedValue({
    data: {
      data: {
        name: "Student",
        email: "student@example.com",
        profile: { wizard_step: 5, has_experience: null, has_certificates: null },
        experiences: [],
        certificates: [],
        completion: { percent: 0, missing: [] },
        readiness: {
          state: "Not started",
          approved_cv: false,
          milestones_completed: 0,
          milestones_total: 0,
          advisor_recommends: false,
        },
      },
    },
  });

  render(<Profile />);
  await screen.findByRole("heading", { name: "5. Experience" });
  fireEvent.change(screen.getByLabelText("Do you have experiences?"), {
    target: { value: "true" },
  });

  const title = await screen.findByLabelText("Title");
  const addEntry = screen.getByRole("button", { name: "Add entry" });
  expect(title.compareDocumentPosition(addEntry) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
  expect(screen.getByLabelText("Org")).toBeTruthy();
  expect(screen.getByLabelText("Start Date")).toBeTruthy();
});

it("shows the Add entry button after an existing entry", async () => {
  api.get.mockResolvedValue({
    data: {
      data: {
        name: "Student",
        email: "student@example.com",
        profile: { wizard_step: 5, has_experience: true, has_certificates: null },
        experiences: [
          { title: "Developer", org: "Company", start_date: "", end_date: "", description: "" },
        ],
        certificates: [],
        completion: { percent: 0, missing: [] },
        readiness: {
          state: "Not started",
          approved_cv: false,
          milestones_completed: 0,
          milestones_total: 0,
          advisor_recommends: false,
        },
      },
    },
  });

  render(<Profile />);
  const removeEntry = await screen.findByRole("button", { name: "Remove entry" });
  await waitFor(() => expect(screen.getByLabelText("Title").value).toBe("Developer"));
  const addEntry = screen.getByRole("button", { name: "Add entry" });
  expect(removeEntry.compareDocumentPosition(addEntry) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
});
