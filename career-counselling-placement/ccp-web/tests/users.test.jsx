import React from "react";
import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import Users from "../src/pages/Users";

const { auth, api, users } = vi.hoisted(() => ({
  auth: { user: { id: 1 } },
  api: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
  users: [
    { id: 1, name: "Current Admin", email: "admin@example.com", role: "admin", status: "active" },
    { id: 2, name: "Advisor User", email: "advisor@example.com", role: "advisor", status: "active" },
    { id: 3, name: "Student User", email: "student@example.com", role: "student", status: "active" },
    { id: 4, name: "Recruiter User", email: "recruiter@example.com", role: "recruiter", status: "active" },
  ],
}));

vi.mock("../src/AuthContext", () => ({ useAuth: () => auth }));
vi.mock("../src/api", () => ({
  default: api,
  err: (error) => error.message || "Unable to connect.",
}));

beforeEach(() => {
  api.get.mockImplementation((url) =>
    Promise.resolve(
      url === "/companies"
        ? { data: [] }
        : { data: { data: { data: users, last_page: 1 } } },
    ),
  );
  api.patch.mockResolvedValue({ data: { message: "Account updated." } });
  api.delete.mockResolvedValue({ data: { message: "Account removed." } });
  vi.spyOn(window, "confirm").mockReturnValue(true);
});

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
  vi.clearAllMocks();
});

function renderUsers() {
  render(
    <MemoryRouter>
      <Users />
    </MemoryRouter>,
  );
}

it("offers all account roles and only offers advisor promotion", async () => {
  renderUsers();

  expect(await screen.findByText("Student User")).toBeTruthy();
  const roleOptions = [...screen.getByLabelText("Account role").options].map(
    (option) => option.value,
  );
  expect(roleOptions).toEqual(["student", "advisor", "admin", "recruiter"]);
  expect(screen.getAllByRole("button", { name: "Promote to Admin" })).toHaveLength(1);

  fireEvent.click(screen.getByRole("button", { name: "Promote to Admin" }));

  await waitFor(() =>
    expect(api.patch).toHaveBeenCalledWith("/v1/admin/users/2", {
      role: "admin",
    }),
  );

  const active = screen.getAllByRole("button", { name: "Activate" });
  const suspend = screen.getAllByRole("button", { name: "Suspend" });
  expect(active[0].parentElement.className).toBe("user-action");
  expect(suspend[0].parentElement.className).toBe("user-action");
});

it("lets admins remove other accounts but not their own", async () => {
  renderUsers();

  const removeButtons = await screen.findAllByRole("button", {
    name: "Remove account",
  });
  expect(removeButtons[0].disabled).toBe(true);
  expect(removeButtons[1].disabled).toBe(false);
  fireEvent.click(removeButtons[1]);

  await waitFor(() =>
    expect(api.delete).toHaveBeenCalledWith("/v1/admin/users/2"),
  );
  expect(window.confirm).toHaveBeenCalledWith(
    "Remove Advisor User's account? Their work data will be preserved and account access revoked.",
  );
});
