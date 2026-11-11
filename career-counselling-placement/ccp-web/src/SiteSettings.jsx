import { createContext, useContext, useEffect, useState } from "react";
import { useData } from "./WorkflowUI";

const SiteSettingsContext = createContext({});

export function useSiteSettings() {
  return useContext(SiteSettingsContext);
}

export function SiteSettingsProvider({ children }) {
  const [revision, setRevision] = useState(0);
  const state = useData(`/public/settings?settings_revision=${revision}`);
  const settings = state.data?.data?.settings || {};

  useEffect(() => {
    const refresh = () => setRevision((value) => value + 1);
    window.addEventListener("site-settings-updated", refresh);
    return () => window.removeEventListener("site-settings-updated", refresh);
  }, []);

  useEffect(() => {
    const root = document.documentElement;
    const colors = [
      ["--teal", settings.theme_primary_color],
      ["--teal-dark", settings.theme_primary_dark_color],
      ["--primary", settings.theme_primary_color],
      ["--primary-dark", settings.theme_primary_dark_color],
      ["--focus", settings.theme_primary_color],
      ["--navy", settings.theme_navy_color],
    ];
    colors.forEach(([property, color]) => {
      if (/^#[0-9a-f]{6}$/i.test(color || "")) {
        root.style.setProperty(property, color);
      }
    });
    if (settings.site_name) document.title = settings.site_name;
    const favicon = document.querySelector('link[rel="icon"]');
    if (favicon) favicon.href = settings.logo_url || "/favicon.svg";
  }, [settings]);

  return (
    <SiteSettingsContext.Provider value={settings}>
      {children}
    </SiteSettingsContext.Provider>
  );
}
