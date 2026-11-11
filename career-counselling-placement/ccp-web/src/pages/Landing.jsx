import { useState } from "react";
import { Link, useLocation } from "react-router-dom";
import {
  ArrowLeft,
  ArrowRight,
  ArrowUpRight,
  BadgeCheck,
  BriefcaseBusiness,
  Building2,
  Compass,
  FileCheck2,
  GraduationCap,
  Handshake,
  Mail,
  MapPin,
  Phone,
  Sparkles,
  Trophy,
  Users,
} from "lucide-react";
import Brand from "../Brand";
import { useData, Load } from "../WorkflowUI";

const statIcons = {
  students: GraduationCap,
  advisors: Users,
  companies: Building2,
  placements: Trophy,
};

const journeyIcons = [GraduationCap, Handshake, FileCheck2, BriefcaseBusiness];
const defaultSteps = [
  ["Create your profile", "Tell us about your education, experience and goals."],
  ["Meet your advisor", "Request an advisor and plan your next steps."],
  ["Prepare and apply", "Get CV feedback and apply to approved opportunities."],
  ["Take the next step", "Track interviews, offers and your placement."],
];

export default function Landing() {
  const state = useData("/public/overview"),
    data = state.data?.data,
    settings = data?.settings || {},
    [activeView, setActiveView] = useState(null);
  const enabled = (name) => settings[`${name}_enabled`] !== false;
  const steps = (settings.journey_steps || defaultSteps).map((step, index) => ({
    ...step,
    icon: journeyIcons[index],
    number: String(index + 1).padStart(2, "0"),
  }));
  const showView = (view) => {
    setActiveView(view);
    window.scrollTo(0, 0);
  };
  const sections = data
    ? {
        journey:
          enabled("journey") &&
          (activeView === "journey" ||
            (!activeView && settings.journey_home_enabled === true)) ? (
            <JourneySection settings={settings} steps={steps} key="journey" />
          ) : null,
        advisors:
          enabled("advisors") &&
          (activeView === "advisors" ||
            (!activeView && settings.advisors_home_enabled !== false)) ? (
            <AdvisorSection
              settings={settings}
              advisors={data.advisors}
              key="advisors"
            />
          ) : null,
        opportunities:
          enabled("opportunities") &&
          (activeView === "opportunities" ||
            (!activeView && settings.opportunities_home_enabled === true)) ? (
            <OpportunitySection
              settings={settings}
              opportunities={data.opportunities}
              key="opportunities"
            />
          ) : null,
        faq:
          enabled("faq") &&
          !activeView &&
          settings.faq_home_enabled === true ? (
            <FaqSection settings={settings} key="faq" />
          ) : null,
        contact:
          enabled("contact") &&
          !activeView &&
          settings.contact_home_enabled === true ? (
            <ContactSection settings={settings} key="contact" />
          ) : null,
      }
    : {};
  const sectionOrder = Array.isArray(settings.section_order)
    ? settings.section_order
    : ["journey", "advisors", "opportunities", "faq", "contact"];

  return (
    <div className="landing">
      <a className="skip-link" href="#public-main">
        Skip to content
      </a>
      <header className="landing-header">
        <Brand
          name={settings.site_name || "CAREER"}
          subtitle={settings.brand_tagline || "COUNSELLING & PLACEMENT"}
          logoUrl={settings.logo_url}
        />
        <nav aria-label="Public navigation">
          {enabled("journey") && (
            <button
              type="button"
              className="landing-nav-section"
              aria-current={activeView === "journey" ? "page" : undefined}
              onClick={() => showView("journey")}
            >
              {settings.nav_journey_label || "How it works"}
            </button>
          )}
          {enabled("opportunities") && (
            <button
              type="button"
              className="landing-nav-section"
              aria-current={activeView === "opportunities" ? "page" : undefined}
              onClick={() => showView("opportunities")}
            >
              {settings.nav_opportunities_label || "Opportunities"}
            </button>
          )}
          <Link to="/login">
            {settings.nav_sign_in_label || "Sign in"}
          </Link>
          <Link className="button-link landing-nav-cta" to="/register">
            {settings.nav_create_account_label || "Create account"}{" "}
            <ArrowUpRight size={16} aria-hidden="true" />
          </Link>
        </nav>
      </header>
      <main id="public-main">
        {settings.announcement_enabled && settings.announcement_text && (
          <aside className="landing-announcement" role="status">
            {settings.announcement_text}
          </aside>
        )}
        {!activeView && settings.hero_enabled !== false && (
          <Hero settings={settings} steps={steps} />
        )}
        <Load state={state}>
          {data && (
            <div className="landing-content">
              {activeView && (
                <div className="landing-view-toolbar">
                  <button
                    className="landing-back-button"
                    type="button"
                    onClick={() => showView(null)}
                  >
                    <ArrowLeft size={17} aria-hidden="true" />
                    {settings.nav_back_home_label || "Back to home"}
                  </button>
                  <span>
                    {activeView === "journey"
                      ? "How it works"
                      : activeView === "opportunities"
                        ? "Opportunities"
                        : "Career advisors"}
                  </span>
                </div>
              )}
              {!activeView && settings.stats_enabled !== false && (
                <section
                  className="landing-stats-section"
                  aria-label="Platform at a glance"
                >
                  <div className="landing-section-intro">
                    <span className="landing-kicker">
                      {settings.stats_kicker || "A COMMUNITY IN MOTION"}
                    </span>
                    <p>
                      {settings.stats_text ||
                        "Building connections that move careers forward."}
                    </p>
                  </div>
                  <div className="stats">
                    {Object.entries(data.stats).map(([name, count]) => {
                      const StatIcon = statIcons[name] || Sparkles;
                      return (
                        <article className="landing-stat" key={name}>
                          <span className="landing-stat-icon">
                            <StatIcon size={20} aria-hidden="true" />
                          </span>
                          <div>
                            <strong>{count}</strong>
                            <p>{name.replaceAll("_", " ")}</p>
                          </div>
                        </article>
                      );
                    })}
                  </div>
                </section>
              )}
              <div className="landing-home-sections">
                {sectionOrder.map((name) => sections[name])}
              </div>
            </div>
          )}
        </Load>
      </main>
      {enabled("footer") && (
        <LandingFooter settings={settings} showView={showView} enabled={enabled} />
      )}
    </div>
  );
}

function Hero({ settings, steps }) {
  return (
    <section className="landing-hero" aria-labelledby="landing-title">
      <div className="landing-hero-copy">
        <p className="eyebrow">
          <Sparkles size={15} aria-hidden="true" />
          {settings.hero_kicker || "YOUR NEXT CHAPTER STARTS HERE"}
        </p>
        <h1 id="landing-title">
          {settings.hero_title_first || "Build your skills."}
          <br />
          <span>{settings.hero_title_second || "Find your direction."}</span>
        </h1>
        <p className="landing-hero-description">
          {settings.hero_description ||
            "Connect with a career advisor, prepare your CV and explore opportunities—all in one place."}
        </p>
        <div className="landing-hero-actions">
          <Link className="button-link landing-primary-cta" to="/register">
            {settings.hero_primary_cta || "Start your career journey"}
            <ArrowRight size={18} aria-hidden="true" />
          </Link>
          <Link className="landing-secondary-cta" to="/login">
            {settings.hero_secondary_cta || "Go to your workspace"}
            <ArrowUpRight size={16} aria-hidden="true" />
          </Link>
        </div>
        <div className="landing-hero-note">
          <BadgeCheck size={17} aria-hidden="true" />
          <span>
            {settings.hero_note ||
              "Guidance, preparation and real opportunities in one journey"}
          </span>
        </div>
      </div>
      <div className="journey-visual" aria-hidden="true">
        <div className="journey-visual-orbit orbit-one" />
        <div className="journey-visual-orbit orbit-two" />
        <div className="journey-visual-card">
          <div className="journey-visual-heading">
            <span className="journey-visual-icon">
              <Compass size={21} aria-hidden="true" />
            </span>
            <div>
              <p>{settings.hero_card_title || "Your career journey"}</p>
              <span>
                {settings.hero_card_subtitle ||
                  "A clear path, one step at a time"}
              </span>
            </div>
          </div>
          <div className="journey-visual-steps">
            {steps.map(({ number, title, icon: StepIcon }) => (
              <div className="journey-visual-step" key={number}>
                <span className="journey-step-icon">
                  <StepIcon size={17} aria-hidden="true" />
                </span>
                <span className="journey-step-number">{number}</span>
                <span className="journey-step-title">{title}</span>
                <ArrowRight
                  className="journey-step-arrow"
                  size={15}
                  aria-hidden="true"
                />
              </div>
            ))}
          </div>
          <div className="journey-visual-footer">
            <span className="journey-footer-dot" />
            {settings.hero_visual_footer ||
              "From first profile to next opportunity"}
          </div>
        </div>
        <div className="journey-float-card">
          <span className="journey-float-icon">
            <FileCheck2 size={18} aria-hidden="true" />
          </span>
          <span>
            <strong>{settings.hero_float_title || "CV feedback"}</strong>
            <small>
              {settings.hero_float_subtitle || "Prepare with confidence"}
            </small>
          </span>
        </div>
      </div>
    </section>
  );
}

function SectionHeading({ kicker, title, description, id }) {
  return (
    <div className="landing-section-heading">
      <div>
        <span className="landing-kicker">{kicker}</span>
        <h2 id={id}>{title}</h2>
      </div>
      <p>{description}</p>
    </div>
  );
}

function JourneySection({ settings, steps }) {
  return (
    <section className="landing-section" id="how-it-works" aria-labelledby="journey-heading">
      <SectionHeading
        kicker={settings.journey_kicker || "YOUR JOURNEY"}
        title={settings.journey_title || "A clear path from preparation to placement"}
        description={
          settings.journey_description ||
          "Every next step is easier when guidance, preparation and opportunities work together."
        }
        id="journey-heading"
      />
      <div className="landing-grid journey-grid">
        {steps.map(({ number, title, body, icon: StepIcon }) => (
          <article className="journey-card" key={number}>
            <div className="journey-card-top">
              <span className="journey-card-icon">
                <StepIcon size={21} aria-hidden="true" />
              </span>
              <span className="journey-card-number">{number}</span>
            </div>
            <h3>{title}</h3>
            <p>{body}</p>
          </article>
        ))}
      </div>
    </section>
  );
}

function AdvisorSection({ settings, advisors }) {
  return (
    <section className="landing-section" id="advisors" aria-labelledby="advisors-heading">
      <SectionHeading
        kicker={settings.advisors_kicker || "GUIDANCE THAT FITS"}
        title={settings.advisors_title || "Meet your career advisors"}
        description={
          settings.advisors_description ||
          "Get support as you shape your goals and plan the steps ahead."
        }
        id="advisors-heading"
      />
      {advisors.length ? (
        <div className="landing-grid advisor-grid">
          {advisors.map((advisor) => (
            <article
              className="advisor-card"
              key={`${advisor.name}-${advisor.title}`}
            >
              <div className="advisor-card-heading">
                <span className="advisor-avatar" aria-hidden="true">
                  {advisor.name?.trim().charAt(0)?.toUpperCase() || "A"}
                </span>
                <div>
                  <h3>{advisor.name}</h3>
                  <p>{advisor.title || "Career Advisor"}</p>
                </div>
              </div>
              {advisor.bio && <p className="advisor-bio">{advisor.bio}</p>}
            </article>
          ))}
        </div>
      ) : (
        <p className="landing-empty">
          {settings.advisors_empty ||
            "Advisor profiles will appear when accounts are approved."}
        </p>
      )}
    </section>
  );
}

function OpportunitySection({ settings, opportunities }) {
  return (
    <section className="landing-section" id="opportunities" aria-labelledby="opportunities-heading">
      <SectionHeading
        kicker={settings.opportunities_kicker || "EXPLORE WHAT'S NEXT"}
        title={settings.opportunities_title || "Open opportunities"}
        description={
          settings.opportunities_description ||
          "Discover current openings and find a next step that matches your goals."
        }
        id="opportunities-heading"
      />
      {opportunities.length ? (
        <div className="landing-grid opportunity-grid">
          {opportunities.map((opportunity) => (
            <article
              className="opportunity-card"
              key={`${opportunity.title}-${opportunity.company}`}
            >
              <div className="opportunity-card-top">
                <span className="opportunity-icon">
                  <BriefcaseBusiness size={20} aria-hidden="true" />
                </span>
                <span className="opportunity-type">{opportunity.type}</span>
              </div>
              <h3>{opportunity.title}</h3>
              <p className="opportunity-company">{opportunity.company}</p>
              <p className="opportunity-location">
                <MapPin size={15} aria-hidden="true" />
                {opportunity.location}
              </p>
              <Link to="/login">
                {settings.opportunity_sign_in_label || "Sign in to explore"}
                <ArrowRight size={16} aria-hidden="true" />
              </Link>
            </article>
          ))}
        </div>
      ) : (
        <p className="landing-empty">
          {settings.opportunities_empty || "No open opportunities currently."}
        </p>
      )}
    </section>
  );
}

function FaqSection({ settings }) {
  return (
    <section className="landing-section faq-section" aria-labelledby="faq-heading">
      <SectionHeading
        kicker={settings.faq_kicker || "GOOD TO KNOW"}
        title={settings.faq_title || "Questions, answered"}
        description={
          settings.faq_description ||
          "A little more clarity before you get started."
        }
        id="faq-heading"
      />
      <div className="faq-list">
        {settings.faqs?.map(({ question, answer }) => (
          <details key={question}>
            <summary>{question}</summary>
            <p>{answer}</p>
          </details>
        ))}
      </div>
    </section>
  );
}

function ContactSection({ settings }) {
  return (
    <section className="landing-contact" id="contact" aria-labelledby="contact-heading">
      <div className="landing-contact-copy">
        <span className="landing-kicker">
          {settings.contact_kicker || "HERE TO HELP"}
        </span>
        <h2 id="contact-heading">
          {settings.contact_title ||
            "Your next step starts with a conversation."}
        </h2>
        <p>
          {settings.contact_description ||
            "Have a question? Contact your department office or platform administrator for support."}
        </p>
      </div>
      <div className="landing-contact-details">
        {settings.contact_email && (
          <a href={`mailto:${settings.contact_email}`}>
            <Mail size={18} aria-hidden="true" />
            {settings.contact_email}
          </a>
        )}
        {settings.contact_phone && (
          <a href={`tel:${settings.contact_phone}`}>
            <Phone size={18} aria-hidden="true" />
            {settings.contact_phone}
          </a>
        )}
        {settings.contact_address && (
          <p>
            <MapPin size={18} aria-hidden="true" />
            {settings.contact_address}
          </p>
        )}
      </div>
      <Link className="button-link landing-contact-cta" to="/register">
        {settings.contact_cta || "Start your journey"}
        <ArrowRight size={17} aria-hidden="true" />
      </Link>
    </section>
  );
}

function LandingFooter({ settings, showView, enabled }) {
  return (
    <footer className="landing-footer">
      <div className="landing-footer-top">
        <div className="landing-footer-university">
          <span className="landing-footer-university-icon">
            <GraduationCap size={21} aria-hidden="true" />
          </span>
          <span>
            <strong>
              {settings.university_name || "Khwaja Yunus Ali University"}
            </strong>
            <small>
              {settings.brand_tagline || "KYAU · Career Counselling & Placement"}
            </small>
          </span>
        </div>
        <nav aria-label="Career links">
          <span className="landing-footer-label">
            {settings.footer_explore_label || "Explore"}
          </span>
          {enabled("journey") && (
            <button type="button" onClick={() => showView("journey")}>
              {settings.nav_journey_label || "How it works"}
            </button>
          )}
          {enabled("advisors") && (
            <button type="button" onClick={() => showView("advisors")}>
              {settings.nav_advisors_label || "Career advisors"}
            </button>
          )}
          {enabled("opportunities") && (
            <button type="button" onClick={() => showView("opportunities")}>
              Opportunities
            </button>
          )}
        </nav>
        <nav aria-label="Information links">
          <span className="landing-footer-label">
            {settings.footer_information_label || "Information"}
          </span>
          <Link to="/privacy">
            {settings.footer_privacy_label || "Privacy"}
          </Link>
          <Link to="/terms">{settings.footer_terms_label || "Terms"}</Link>
          <Link to="/login">
            {settings.footer_sign_in_label || "Sign in"}
          </Link>
        </nav>
      </div>
      <div className="landing-footer-bottom">
        <span>
          © {new Date().getFullYear()}{" "}
          {settings.university_name || "Khwaja Yunus Ali University"}.{" "}
          {settings.footer_copyright || "All rights reserved."}
        </span>
        {settings.contact_email && (
          <a href={`mailto:${settings.contact_email}`}>
            {settings.contact_email}
          </a>
        )}
      </div>
    </footer>
  );
}

export function Legal() {
  const { pathname } = useLocation(),
    state = useData("/public/overview");
  return (
    <main className="legal card">
      <Brand />
      <h1>
        {pathname === "/privacy"
          ? state.data?.data.settings.privacy_title || "Privacy notice"
          : state.data?.data.settings.terms_title || "Terms of use"}
      </h1>
      <Load state={state}>
        <p className="preserve-lines">
          {
            state.data?.data.settings[
              pathname === "/privacy" ? "privacy_text" : "terms_text"
            ]
          }
        </p>
      </Load>
      <Link to="/welcome">
        {state.data?.data.settings.legal_back_label || "Back to home"}
      </Link>
    </main>
  );
}
