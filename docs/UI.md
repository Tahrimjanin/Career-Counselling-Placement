# Interface system

Primary teal #08766F, navy text/sidebar #10254F, page background #F2F6FA, white surfaces, muted text #556B83. System/Inter font stack, 8–16px controls, rounded cards, Lucide outline navigation icons, reusable vector brand. 44px navigation touch targets, visible amber focus outline, text labels on status badges (color is not the only signal), associated input labels, validation descriptions and live feedback.

Header shows role, notifications and own private avatar/name. Mobile navigation uses a labelled toggle with aria-expanded/controls, Escape dismissal and a focus loop; desktop sidebar scrolls independently. Public pages have a skip link. Reports and notification tools are lazy-loaded. Forms disable submit while pending. Tables scroll inside their card, not the page.

Contrast measured by WCAG relative luminance: see FINAL_RESULTS.md. Successful desktop/mobile browser checks cover 320, 375, 768, 1024 and 1440px. Native screen-reader/browser-specific manual certification remains unperformed. No external font or image request is needed for the logo, icons or initials.
