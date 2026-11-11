import { afterEach, expect, it, vi } from "vitest";
import {
  cleanup,
  fireEvent,
  render,
  screen,
  within,
} from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import Landing from "../src/pages/Landing";

const { overview } = vi.hoisted(() => ({
  overview: {
    stats: { students: 12, advisors: 2, companies: 3, placements: 4 },
    advisors: [{ name: "Test Advisor", title: "Career Advisor", bio: "Support" }],
    opportunities: [
      {
        title: "Graduate Trainee",
        company: "Example Company",
        type: "Full-time",
        location: "Dhaka",
      },
    ],
    settings: { contact_email: "", contact_phone: "", contact_address: "" },
  },
}));

overview.settings = {
  site_name: "KYAU Careers",
  brand_tagline: "Career development",
  hero_title_first: "Prepare today.",
  hero_title_second: "Lead tomorrow.",
  hero_enabled: true,
  stats_enabled: true,
  journey_enabled: true,
  journey_home_enabled: false,
  advisors_enabled: true,
  advisors_home_enabled: true,
  opportunities_enabled: true,
  opportunities_home_enabled: false,
  faq_enabled: true,
  faq_home_enabled: false,
  contact_enabled: true,
  contact_home_enabled: false,
  footer_enabled: true,
  announcement_enabled: false,
  section_order: ["journey", "advisors", "opportunities", "faq", "contact"],
  journey_steps: [
    { title: "Build a profile", body: "Add your career details." },
  ],
  faqs: [],
  contact_email: "",
  contact_phone: "",
  contact_address: "",
};

vi.mock("../src/WorkflowUI", () => ({
  useData: () => ({ data: { data: overview }, error: null }),
  Load: ({ children }) => children,
}));

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
  overview.settings.opportunities_home_enabled = false;
  overview.settings.section_order = [
    "journey",
    "advisors",
    "opportunities",
    "faq",
    "contact",
  ];
});

function renderLanding() {
  vi.spyOn(window, "scrollTo").mockImplementation(() => {});
  return render(
    <MemoryRouter>
      <Landing />
    </MemoryRouter>,
  );
}

it("switches between standalone public sections and returns home", () => {
  renderLanding();

  expect(screen.queryByRole("heading", { name: "Open opportunities" })).toBeNull();
  expect(
    screen.queryByRole("heading", {
      name: "A clear path from preparation to placement",
    }),
  ).toBeNull();
  expect(
    screen.getByRole("heading", { name: "Meet your career advisors" }),
  ).toBeTruthy();
  expect(
    screen.getByRole("heading", { name: "Prepare today.Lead tomorrow." }),
  ).toBeTruthy();

  fireEvent.click(
    within(screen.getByRole("navigation", { name: "Public navigation" })).getByRole(
      "button",
      { name: "How it works" },
    ),
  );
  expect(
    screen.getByRole("heading", {
      name: "A clear path from preparation to placement",
    }),
  ).toBeTruthy();
  expect(screen.queryByRole("heading", { name: "Open opportunities" })).toBeNull();
  expect(screen.queryByRole("heading", { level: 1 })).toBeNull();

  fireEvent.click(screen.getByRole("button", { name: "Back to home" }));
  expect(screen.queryByRole("heading", { name: "Open opportunities" })).toBeNull();
  expect(
    screen.getByRole("heading", { name: "Meet your career advisors" }),
  ).toBeTruthy();

  const careerLinks = within(
    screen.getByRole("navigation", { name: "Career links" }),
  );
  fireEvent.click(careerLinks.getByRole("button", { name: "Opportunities" }));
  expect(screen.getByRole("heading", { name: "Open opportunities" })).toBeTruthy();
  expect(screen.queryByText("Test Advisor")).toBeNull();
  expect(screen.queryByText("From first profile to next opportunity")).toBeNull();
  expect(
    within(
      screen.getByRole("navigation", { name: "Public navigation" }),
    ).getByRole("link", { name: "Sign in" }).getAttribute("href"),
  ).toBe("/login");
});

it("uses admin-controlled section visibility and ordering on the home page", () => {
  overview.settings.opportunities_home_enabled = true;
  overview.settings.section_order = [
    "opportunities",
    "advisors",
    "journey",
    "faq",
    "contact",
  ];
  renderLanding();

  const opportunityHeading = screen.getByRole("heading", {
    name: "Open opportunities",
  });
  const advisorHeading = screen.getByRole("heading", {
    name: "Meet your career advisors",
  });
  expect(
    opportunityHeading.compareDocumentPosition(advisorHeading) &
      Node.DOCUMENT_POSITION_FOLLOWING,
  ).toBeTruthy();
});

it("shows career advisors alone when selected from the footer", () => {
  renderLanding();
  fireEvent.click(
    within(screen.getByRole("navigation", { name: "Career links" })).getByRole(
      "button",
      { name: "Career advisors" },
    ),
  );

  expect(screen.getByText("Test Advisor")).toBeTruthy();
  expect(screen.queryByRole("heading", { name: "Open opportunities" })).toBeNull();
  expect(
    screen.queryByRole("heading", {
      name: "A clear path from preparation to placement",
    }),
  ).toBeNull();
});
