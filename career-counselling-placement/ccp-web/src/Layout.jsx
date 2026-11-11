import Brand from "./Brand";
import Icon from "./Icon";
import { useEffect, useRef, useState } from "react";
import api, { err } from "./api";
import {
  Navigate,
  NavLink,
  Outlet,
  Link,
  useLocation,
  useNavigate,
} from "react-router-dom";
import { useAuth } from "./AuthContext";
import { Avatar } from "./Avatar";
import Landing from "./pages/Landing";
const NAV = {
  student: [
    ["/offers", "Offers"],
    ["/advisors", "My advisor"],
    ["/sessions", "Counselling sessions"],
    ["/career-plan", "Career plan"],
    ["/", "Dashboard"],
    ["/profile", "My profile"],
    ["/opportunities", "Find Opportunities"],
    ["/applications", "Applications"],
    ["/cv", "CV Builder"],
    ["/interviews", "Interviews"],
    ["/notes", "Notes"],
  ],
  advisor: [
    ["/opportunities", "Opportunities"],
    ["/sessions", "Counselling sessions"],
    ["/career-plan", "Career plans"],
    ["/", "Dashboard"],
    ["/profile", "My profile"],
    ["/students", "My Students"],
    ["/cv-review", "CV Review"],
    ["/notes", "Notes"],
  ],
  admin: [
    ["/career-plan", "Career plans"],
    ["/interviews", "Interviews"],
    ["/allocations", "Advisor allocation"],
    ["/sessions", "Counselling sessions"],
    ["/opportunity-approval", "Opportunity approval"],
    ["/placements", "Placement records"],
    ["/student-verification", "Student verification"],
    ["/", "Dashboard"],
    ["/profile", "My profile"],
    ["/queue", "Application Queue"],
    ["/users", "Users"],
    ["/companies", "Companies"],
  ],
  recruiter: [
    ["/offers", "Offers"],
    ["/", "Dashboard"],
    ["/profile", "My profile"],
    ["/post", "Post Opportunity"],
    ["/candidates", "Candidates"],
    ["/interviews", "Interviews"],
  ],
};

export default function Layout() {
  const { user, loading, logout, refresh } = useAuth();
  const location = useLocation();
  const [message, setMessage] = useState("");
  if (loading)
    return (
      <p className="pad" role="status">
        Loading…
      </p>
    );
  if (!user)
    return location.pathname === "/" ? (
      <Landing />
    ) : (
      <Navigate to="/login" replace />
    );
  if (user.must_change_password)
    return <Navigate to="/change-password" replace />;
  if (user.status === "pending")
    return (
      <main className="auth">
        <Brand />
        <section className="card">
          <h1>Account awaiting approval</h1>
          <p>
            {user.name}, your {user.role} account has been created. An
            administrator must approve it before you can use this workspace.
          </p>
          <button
            onClick={async () => {
              try {
                await refresh();
                setMessage("Account status checked.");
              } catch (e) {
                setMessage(err(e));
              }
            }}
          >
            Check approval status
          </button>
          <button
            onClick={async () => {
              try {
                await logout();
              } catch (e) {
                setMessage(err(e));
              }
            }}
          >
            Sign out
          </button>
          <p role="status">{message}</p>
        </section>
      </main>
    );
  if (user.email_verification_required && !user.email_verified)
    return <Navigate to="/verify-email" replace />;
  return <Workspace user={user} />;
}
function Workspace({ user }) {
  const navigate = useNavigate();
  const { logout } = useAuth(),
    location = useLocation(),
    [message, setMessage] = useState(""),
    [open, setOpen] = useState(false),
    [unread, setUnread] = useState(0),
    panel = useRef(null),
    toggle = useRef(null);
  useEffect(() => {
    const read = () =>
      api
        .get("/v1/notifications?per_page=1")
        .then((r) => setUnread(r.data.unread || 0))
        .catch(() => {});
    read();
    window.addEventListener("notifications-updated", read);
    const timer = setInterval(read, 60000);
    return () => {
      clearInterval(timer);
      window.removeEventListener("notifications-updated", read);
    };
  }, [location.pathname]);
  useEffect(() => {
    if (!open) return;
    const old = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    panel.current?.querySelector("a,button")?.focus();
    const key = (e) => {
      if (e.key === "Escape") {
        setOpen(false);
        toggle.current?.focus();
      }
      if (e.key === "Tab") {
        const els = [...panel.current.querySelectorAll("a,button")].filter(
            (el) => el.offsetParent !== null,
          ),
          first = els[0],
          last = els.at(-1);
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };
    document.addEventListener("keydown", key);
    return () => {
      document.body.style.overflow = old;
      document.removeEventListener("keydown", key);
    };
  }, [open]);
  const links = [
    ...NAV[user.role],
    ["/notifications", "Notifications"],
    ...(user.role === "admin"
      ? [
          ["/reports", "Reports"],
          ["/audit-log", "Audit log"],
          ["/settings", "Settings"],
        ]
      : []),
  ];
  return (
    <div className="shell">
      <a className="skip-link" href="#workspace-main">
        Skip to content
      </a>
      {open && (
        <button
          className="menu-backdrop"
          aria-label="Close navigation"
          onClick={() => setOpen(false)}
        />
      )}
      <aside
        id="workspace-navigation"
        ref={panel}
        className={open ? "navigation-open" : ""}
        aria-label="Workspace navigation"
      >
        <Brand light />
        <button
          className="mobile-menu"
          onClick={() => {
            setOpen(false);
            toggle.current?.focus();
          }}
        >
          Close menu
        </button>
        <nav>
          {links
            .sort((a, b) => (a[0] === "/" ? -1 : b[0] === "/" ? 1 : 0))
            .map(([to, label]) => (
              <NavLink
                key={to}
                to={to}
                end={to === "/"}
                onClick={() => setOpen(false)}
              >
                <Icon name={to === "/" ? "dashboard" : to.slice(1)} />
                {label}
              </NavLink>
            ))}
        </nav>
        <button
          className="ghost"
          onClick={async () => {
            try {
              await logout();
              navigate("/login");
            } catch (e) {
              setMessage(err(e));
            }
          }}
        >
          Sign out
        </button>
      </aside>
      <main>
        <header>
          <button
            ref={toggle}
            className="mobile-menu"
            aria-expanded={open}
            aria-controls="workspace-navigation"
            onClick={() => setOpen((v) => !v)}
          >
            Menu
          </button>
          <span className="role">{user.role.toUpperCase()} WORKSPACE</span>
          <div className="header-account">
            <Link
              to="/notifications"
              className="notification-link"
              aria-label={`Notifications, ${unread} unread`}
            >
              <Icon name="notifications" />
              {unread > 0 && <span>{unread}</span>}
            </Link>
            <Link to="/profile" className="profile-link">
              <span>{user.name}</span>
              <Avatar user={user} />
            </Link>
          </div>
        </header>
        <div id="workspace-main" tabIndex={-1} className="content">
          <p role="alert">{message}</p>
          <Outlet />
        </div>
      </main>
    </div>
  );
}
