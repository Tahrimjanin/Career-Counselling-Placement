import { useSiteSettings } from "./SiteSettings";

export default function Brand({
  light = false,
  name,
  subtitle,
  logoUrl,
}) {
  const settings = useSiteSettings();
  name ||= settings.site_name || "CAREER";
  subtitle ||= settings.brand_tagline || "COUNSELLING & PLACEMENT";
  logoUrl ||= settings.logo_url;
  return (
    <div
      className={`ccp-brand ${light ? "light" : ""}`}
      aria-label={
        name === "CAREER" && subtitle === "COUNSELLING & PLACEMENT"
          ? "Career Counselling and Placement"
          : `${name} ${subtitle}`
      }
    >
      {logoUrl ? (
        <img src={logoUrl} alt="" />
      ) : (
        <svg viewBox="0 0 48 48" width="48" height="48" aria-hidden="true">
          <rect width="48" height="48" rx="13" fill="var(--teal)" />
          <path
            d="M12 32V17l12 6 12-6v15l-12 6z"
            fill="none"
            stroke="white"
            strokeWidth="2.5"
            strokeLinejoin="round"
          />
          <path
            d="M24 23v15M11 16l13-7 13 7-13 7z"
            fill="none"
            stroke="white"
            strokeWidth="2.5"
            strokeLinejoin="round"
          />
          <path d="M37 16v10" stroke="#A7E5D9" strokeWidth="2.5" />
        </svg>
      )}
      <div>
        <strong>{name}</strong>
        <span>{subtitle}</span>
      </div>
    </div>
  );
}
