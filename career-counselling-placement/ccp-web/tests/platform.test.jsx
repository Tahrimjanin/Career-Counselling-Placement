import React from "react";
import { it, expect, vi, afterEach } from "vitest";
import { render, screen, cleanup, fireEvent } from "@testing-library/react";
import { MemoryRouter, Routes, Route } from "react-router-dom";
import Layout from "../src/Layout";
import Login from "../src/pages/Login";
const { auth } = vi.hoisted(() => ({
  auth: {
    user: null,
    loading: false,
    logout: vi.fn(),
    refresh: vi.fn(),
    login: vi.fn(),
    register: vi.fn(),
  },
}));
vi.mock("../src/AuthContext", () => ({ useAuth: () => auth }));
vi.mock("../src/pages/Landing", () => ({
  default: () => <h1>Public home</h1>,
}));
afterEach(() => {
  cleanup();
  auth.user = null;
  vi.clearAllMocks();
});
it("shows public home to a signed-out visitor", () => {
  render(
    <MemoryRouter>
      <Layout />
    </MemoryRouter>,
  );
  expect(screen.getByText("Public home")).toBeTruthy();
});
it.each(["advisor", "recruiter", "admin"])(
  "gates pending %s workspace",
  (role) => {
    auth.user = { role, status: "pending", name: "Test User" };
    render(
      <MemoryRouter>
        <Layout />
      </MemoryRouter>,
    );
    expect(
      screen.getByRole("heading", { name: "Account awaiting approval" }),
    ).toBeTruthy();
    expect(screen.queryByRole("navigation")).toBeNull();
    fireEvent.click(
      screen.getByRole("button", { name: "Check approval status" }),
    );
    expect(auth.refresh).toHaveBeenCalledTimes(1);
  },
);
it("redirects a signed-out private route to login", () => {
  render(
    <MemoryRouter initialEntries={["/reports"]}>
      <Routes>
        <Route path="/reports" element={<Layout />} />
        <Route path="/login" element={<h1>Sign in screen</h1>} />
      </Routes>
    </MemoryRouter>,
  );
  expect(screen.getByText("Sign in screen")).toBeTruthy();
});
it("shows recruiter registration company field without student department", () => {
  render(
    <MemoryRouter initialEntries={["/register?role=recruiter"]}>
      <Login />
    </MemoryRouter>,
  );
  expect(screen.getByLabelText("Company name")).toBeTruthy();
  expect(screen.queryByLabelText("Department")).toBeNull();
  expect(screen.getByLabelText("Workspace").value).toBe("recruiter");
});
