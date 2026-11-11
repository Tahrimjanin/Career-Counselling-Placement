import React from "react";
import { describe, it, expect, vi, afterEach } from "vitest";
import {
  render,
  screen,
  fireEvent,
  cleanup,
  waitFor,
} from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import Login from "../src/pages/Login";
import Brand from "../src/Brand";
import { Status, Form, Action } from "../src/WorkflowUI";
const { login, register } = vi.hoisted(() => ({
  login: vi.fn(),
  register: vi.fn(),
}));
vi.mock("../src/AuthContext", () => ({ useAuth: () => ({ login, register }) }));
afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});
describe("role-aware authentication", () => {
  it.each(["advisor", "admin", "recruiter"])(
    "offers role-specific registration for %s",
    (role) => {
      render(
        <MemoryRouter>
          <Login />
        </MemoryRouter>,
      );
      fireEvent.change(screen.getByLabelText("Workspace"), {
        target: { value: role },
      });
      expect(screen.queryByText("Create student account")).toBeNull();
      expect(
        screen
          .getByRole("link", { name: /Create .* account/ })
          .getAttribute("href"),
      ).toBe(`/register?role=${role}`);
    },
  );
  it("offers student registration only in student workspace", () => {
    render(
      <MemoryRouter>
        <Login />
      </MemoryRouter>,
    );
    expect(screen.getByText("Create student account")).toBeTruthy();
  });
  it("accepts ordinary email in registration", () => {
    render(
      <MemoryRouter initialEntries={["/register"]}>
        <Login />
      </MemoryRouter>,
    );
    expect(screen.getByLabelText("Email address")).toBeTruthy();
    expect(screen.queryByText("University email address")).toBeNull();
  });
  it("toggles password visibility on login and registration fields", () => {
    const { unmount } = render(
      <MemoryRouter>
        <Login />
      </MemoryRouter>,
    );
    const loginPassword = screen.getByLabelText("Password");
    expect(loginPassword.type).toBe("password");
    fireEvent.click(screen.getByRole("button", { name: "Show password" }));
    expect(loginPassword.type).toBe("text");
    fireEvent.click(screen.getByRole("button", { name: "Hide password" }));
    expect(loginPassword.type).toBe("password");
    unmount();

    render(
      <MemoryRouter initialEntries={["/register"]}>
        <Login />
      </MemoryRouter>,
    );
    const registrationPassword = screen.getByLabelText("Password");
    const confirmationPassword = screen.getByLabelText("Confirm password");
    fireEvent.click(
      screen.getAllByRole("button", { name: "Show password" })[1],
    );
    expect(registrationPassword.type).toBe("password");
    expect(confirmationPassword.type).toBe("text");
  });
  it("shows workspace mismatch beside role field", async () => {
    login.mockRejectedValue({
      response: {
        data: {
          errors: { role: ["Selected workspace does not match this account."] },
        },
      },
    });
    render(
      <MemoryRouter>
        <Login />
      </MemoryRouter>,
    );
    fireEvent.change(screen.getByLabelText("Email address"), {
      target: { value: "test@example.com" },
    });
    fireEvent.change(screen.getByLabelText("Password"), {
      target: { value: "Password1234" },
    });
    fireEvent.submit(
      screen.getByRole("button", { name: "Sign in" }).closest("form"),
    );
    await screen.findByText("Selected workspace does not match this account.");
    expect(login).toHaveBeenCalledWith(
      "test@example.com",
      "Password1234",
      "student",
    );
  });
});
describe("shared workflow UI", () => {
  it("uses the honest submitted status", () => {
    render(<Status value="submitted" />);
    expect(screen.getByText("Submitted to Admin")).toBeTruthy();
  });
  it("renders an accessible vector logo", () => {
    render(<Brand />);
    expect(
      screen
        .getByLabelText("Career Counselling and Placement")
        .querySelector("svg"),
    ).toBeTruthy();
  });
  it("maps server validation to the field", async () => {
    render(
      <Form
        fields={[["name", "Name"]]}
        submit={() =>
          Promise.reject({
            response: {
              data: { message: "Invalid", errors: { name: ["Name required"] } },
            },
          })
        }
      />,
    );
    fireEvent.submit(
      screen.getByRole("button", { name: "Save" }).closest("form"),
    );
    await screen.findByText("Name required");
    expect(screen.getByLabelText("Name").getAttribute("aria-invalid")).toBe(
      "true",
    );
  });
  it("disables action while the request is pending", async () => {
    let resolve;
    const run = vi.fn(
      () =>
        new Promise((r) => {
          resolve = r;
        }),
    );
    render(<Action run={run}>Submit</Action>);
    fireEvent.click(screen.getByRole("button"));
    expect(screen.getByRole("button").disabled).toBe(true);
    resolve();
    await waitFor(() =>
      expect(screen.getByRole("button").disabled).toBe(false),
    );
    expect(run).toHaveBeenCalledTimes(1);
  });
});
