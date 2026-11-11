import { afterEach, expect, it, vi } from "vitest";
import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import { Settings } from "../src/pages/AdminTools";

const { settings, reload, put } = vi.hoisted(() => ({
  settings: {
    site_name: "Career Counselling & Placement",
    university_name: "Khwaja Yunus Ali University",
    brand_tagline: "KYAU · Career Counselling & Placement",
    theme_primary_color: "#08766F",
    theme_primary_dark_color: "#06645E",
    theme_navy_color: "#10254F",
  },
  reload: vi.fn(),
  put: vi.fn(),
}));

vi.mock("../src/WorkflowUI", async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    useData: () => ({
      data: { data: settings, logo_url: null },
      error: "",
      reload,
    }),
  };
});

vi.mock("../src/api", () => ({
  default: {
    put,
    post: vi.fn(),
    delete: vi.fn(),
  },
}));

afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});

it("lets admins edit and save site branding independently", async () => {
  put.mockResolvedValue({ data: { message: "Saved" } });
  render(
    <MemoryRouter>
      <Settings />
    </MemoryRouter>,
  );

  fireEvent.change(screen.getByLabelText("Site name"), {
    target: { value: "KYAU Career Portal" },
  });
  fireEvent.submit(screen.getByLabelText("Site name").closest("form"));

  await waitFor(() =>
    expect(put).toHaveBeenCalledWith(
      "/v1/admin/settings",
      expect.objectContaining({ site_name: "KYAU Career Portal" }),
    ),
  );
  expect(screen.getAllByRole("status")[0].textContent).toBe("Saved");
  expect(reload).toHaveBeenCalled();
});
