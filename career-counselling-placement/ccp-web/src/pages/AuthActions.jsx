import Brand from "../Brand";
import { useState } from "react";
import { Link, Navigate, useLocation, useNavigate } from "react-router-dom";
import { useAuth } from "../AuthContext";
import api, { err, verificationPath } from "../api";
import PasswordInput from "../PasswordInput";
export default function AuthActions() {
  const { user, loading, refresh, logout } = useAuth();
  const { pathname, search } = useLocation();
  const navigate = useNavigate();
  const query = new URLSearchParams(search);
  const [f, setF] = useState({
    email: query.get("email") || "",
    password: "",
    password_confirmation: "",
    current_password: "",
  });
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [errors, setErrors] = useState({});
  const verify = pathname === "/verify-email";
  const change = pathname === "/change-password";
  const forgot = pathname === "/forgot-password";
  if (loading) return <p className="pad">Loading…</p>;
  if ((verify || change) && !user)
    return (
      <Navigate to="/login" replace state={{ returnTo: pathname + search }} />
    );
  if (verify && user && !user.email_verification_required)
    return <Navigate to="/" replace />;
  async function run(action) {
    setBusy(true);
    setMessage("");
    setErrors({});
    try {
      await action();
    } catch (e) {
      setMessage(
        e.message === "Invalid verification link." ? e.message : err(e),
      );
      setErrors(e.response?.data?.errors || {});
    } finally {
      setBusy(false);
    }
  }
  async function submit(e) {
    e.preventDefault();
    await run(async () => {
      const r = await api.post(
        change
          ? "/change-password"
          : forgot
            ? "/forgot-password"
            : "/reset-password",
        { ...f, token: query.get("token") },
      );
      setMessage(r.data.message || "Password changed.");
      if (change) {
        await refresh();
        navigate("/");
      }
    });
  }
  const field = (key, label, type) => (
    <div key={key}>
      <label htmlFor={key}>{label}</label>
      {type === "password" ? (
        <PasswordInput
          id={key}
          required
          value={f[key]}
          autoComplete={
            key === "current_password" ? "current-password" : "new-password"
          }
          onChange={(e) => setF({ ...f, [key]: e.target.value })}
        />
      ) : (
        <input
          id={key}
          type={type}
          required
          value={f[key]}
          autoComplete={type === "email" ? "email" : "off"}
          onChange={(e) => setF({ ...f, [key]: e.target.value })}
        />
      )}
      {errors[key] && <small className="error">{errors[key][0]}</small>}
    </div>
  );
  return (
    <main className="auth-card">
      <Brand />
      <h1>
        {verify
          ? "Verify your email"
          : change
            ? "Change temporary password"
            : forgot
              ? "Reset your password"
              : "Choose a new password"}
      </h1>
      {verify ? (
        <>
          <p>
            Open the verification link sent to {user.email}. In local mode, find
            it in the Laravel mail log.
          </p>
          {query.get("url") && (
            <button
              disabled={busy}
              onClick={() =>
                run(async () => {
                  await api.get(verificationPath(query.get("url")));
                  await refresh();
                  navigate("/");
                })
              }
            >
              Verify email
            </button>
          )}
          <button
            disabled={busy}
            onClick={() =>
              run(async () => {
                const r = await api.post("/email/verification-notification");
                setMessage(r.data.message);
              })
            }
          >
            Resend verification email
          </button>
          <button
            disabled={busy}
            onClick={() =>
              run(async () => {
                const u = await refresh();
                if (u.email_verified) navigate("/");
                else setMessage("Email has not been verified yet.");
              })
            }
          >
            Check verification status
          </button>
        </>
      ) : (
        <form onSubmit={submit}>
          {!change && field("email", "Email address", "email")}
          {change && field("current_password", "Current password", "password")}
          {!forgot && (
            <>
              {field("password", "New password", "password")}
              {field(
                "password_confirmation",
                "Confirm new password",
                "password",
              )}
              <p>At least 10 characters, including letters and numbers.</p>
            </>
          )}
          <button disabled={busy}>
            {busy
              ? "Please wait…"
              : forgot
                ? "Send reset link"
                : "Save password"}
          </button>
        </form>
      )}
      <p role="status">{message}</p>
      {user ? (
        <button disabled={busy} onClick={() => run(logout)}>
          Sign out
        </button>
      ) : (
        <Link to="/login">Back to sign in</Link>
      )}
    </main>
  );
}
