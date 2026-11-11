import { afterEach, expect, it, vi } from "vitest";
import { cleanup, render, screen, waitFor } from "@testing-library/react";
import Brand from "../src/Brand";
import { SiteSettingsProvider } from "../src/SiteSettings";

const { settings, useDataMock } = vi.hoisted(() => ({
  settings: {
    site_name: "KYAU Career Portal",
    brand_tagline: "Career development",
    logo_url: "http://localhost:8000/api/public/site-logo?v=1",
    theme_primary_color: "#123456",
    theme_primary_dark_color: "#102030",
    theme_navy_color: "#08090A",
  },
  useDataMock: vi.fn(),
}));

vi.mock("../src/WorkflowUI", async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    useData: useDataMock,
  };
});

afterEach(() => {
  cleanup();
  vi.clearAllMocks();
  document.title = "Career Counselling & Placement";
  document.querySelector('link[rel="icon"]')?.remove();
  [
    "--teal",
    "--teal-dark",
    "--primary",
    "--primary-dark",
    "--focus",
    "--navy",
  ].forEach((name) => document.documentElement.style.removeProperty(name));
});

it("applies admin branding and refreshes it after a settings update", async () => {
  const favicon = document.createElement("link");
  favicon.rel = "icon";
  favicon.href = "/favicon.svg";
  document.head.append(favicon);
  useDataMock.mockReturnValue({
    data: { data: { settings } },
    error: "",
    reload: vi.fn(),
  });
  render(
    <SiteSettingsProvider>
      <Brand />
    </SiteSettingsProvider>,
  );

  await waitFor(() => {
    expect(document.title).toBe("KYAU Career Portal");
    expect(
      document.documentElement.style.getPropertyValue("--teal"),
    ).toBe("#123456");
  });
  expect(screen.getByText("KYAU Career Portal")).toBeTruthy();
  const brand = document.querySelector(".ccp-brand");
  expect(brand.querySelector("img").getAttribute("src")).toBe(settings.logo_url);
  expect(favicon.href).toBe(settings.logo_url);

  window.dispatchEvent(new Event("site-settings-updated"));
  await waitFor(() => expect(useDataMock).toHaveBeenCalledTimes(2));
});
