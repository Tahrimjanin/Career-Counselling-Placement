import { lazy, Suspense } from "react";
import AvatarEditor from "./Avatar";
import Landing, { Legal } from "./pages/Landing";
const Notifications = lazy(() => import("./pages/Notifications"));
const Reports = lazy(() =>
  import("./pages/AdminTools").then((m) => ({ default: m.Reports })),
);
const Settings = lazy(() =>
  import("./pages/AdminTools").then((m) => ({ default: m.Settings })),
);
const AuditLog = lazy(() =>
  import("./pages/AdminTools").then((m) => ({ default: m.AuditLog })),
);
import { Advisors, Allocations, Sessions, Plan } from "./pages/Advising";
import CvLifecycle from "./pages/CvLifecycle";
import OpportunityBoard from "./pages/OpportunityBoard";
import PlacementWorkspace, {
  InterviewsWorkspace,
  Placements,
} from "./pages/PlacementWorkspace";
import { Navigate, Route, Routes } from "react-router-dom";
import { useAuth } from "./AuthContext";
import Profile from "./pages/Profile";
import StudentVerification from "./pages/StudentVerification";
import AuthActions from "./pages/AuthActions";
import Layout from "./Layout";
import Login from "./pages/Login";
import Dashboard from "./pages/Dashboard";
import Notes from "./pages/Notes";
import Students from "./pages/Students";
import Users from "./pages/Users";
import Companies from "./pages/Companies";
import { SiteSettingsProvider } from "./SiteSettings";

function Only({ roles, children }) {
  const { user } = useAuth();
  return roles.includes(user.role) ? children : <Navigate to="/" replace />;
}
const R = (roles, el) => <Only roles={roles}>{el}</Only>;

export default function App() {
  return (
    <SiteSettingsProvider>
      <Suspense fallback={<p role="status">Loading workspace…</p>}>
        <Routes>
        <Route path="/welcome" element={<Landing />} />
        <Route path="/privacy" element={<Legal />} />
        <Route path="/terms" element={<Legal />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Login />} />
        {[
          "verify-email",
          "forgot-password",
          "reset-password",
          "change-password",
        ].map((path) => (
          <Route
            key={path}
            path={"/" + path}
            element={<AuthActions key={path} />}
          />
        ))}
        <Route element={<Layout />}>
          <Route index element={<Dashboard />} />
          <Route path="advisors" element={R(["student"], <Advisors />)} />
          <Route path="allocations" element={R(["admin"], <Allocations />)} />
          <Route
            path="sessions"
            element={R(["student", "advisor", "admin"], <Sessions />)}
          />
          <Route
            path="career-plan"
            element={R(["student", "advisor", "admin"], <Plan />)}
          />
          <Route
            path="opportunity-approval"
            element={R(["admin"], <OpportunityBoard />)}
          />
          <Route path="placements" element={R(["admin"], <Placements />)} />
          <Route
            path="offers"
            element={R(
              ["student", "recruiter"],
              <PlacementWorkspace offersOnly />,
            )}
          />
          <Route
            path="profile"
            element={
              <>
                <AvatarEditor />
                <Profile />
              </>
            }
          />
          <Route path="notifications" element={<Notifications />} />
          <Route path="reports" element={R(["admin"], <Reports />)} />
          <Route path="settings" element={R(["admin"], <Settings />)} />
          <Route path="audit-log" element={R(["admin"], <AuditLog />)} />
          <Route
            path="student-verification"
            element={R(["admin"], <StudentVerification />)}
          />
          <Route
            path="opportunities"
            element={R(["student", "advisor"], <OpportunityBoard />)}
          />
          <Route
            path="applications"
            element={R(["student"], <PlacementWorkspace />)}
          />
          <Route path="cv" element={R(["student"], <CvLifecycle />)} />
          <Route
            path="interviews"
            element={R(
              ["student", "recruiter", "admin"],
              <InterviewsWorkspace />,
            )}
          />
          <Route path="notes" element={R(["student", "advisor"], <Notes />)} />
          <Route path="students" element={R(["advisor"], <Students />)} />
          <Route path="cv-review" element={R(["advisor"], <CvLifecycle />)} />
          <Route path="queue" element={R(["admin"], <PlacementWorkspace />)} />
          <Route path="users" element={R(["admin"], <Users />)} />
          <Route path="companies" element={R(["admin"], <Companies />)} />
          <Route path="post" element={R(["recruiter"], <OpportunityBoard />)} />
          <Route
            path="candidates"
            element={R(["recruiter"], <PlacementWorkspace />)}
          />
        </Route>
        <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </SiteSettingsProvider>
  );
}
