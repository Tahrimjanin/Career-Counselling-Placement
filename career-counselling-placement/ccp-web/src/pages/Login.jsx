import Brand from "../Brand";
import { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useAuth } from "../AuthContext";
import { err } from "../api";
import PasswordInput from "../PasswordInput";
export default function Login() {
  const { login, register } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const registering = location.pathname === "/register";
  const [f, setF] = useState({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    department: "",
    role: new URLSearchParams(location.search).get("role") || "student",
    company_name: "",
  });
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [errors, setErrors] = useState({});
  const field = (key, label, type = "text") => (
    <div key={key}>
      <label htmlFor={key}>{label}</label>
      {type === "password" ? (
        <PasswordInput
          id={key}
          required
          autoComplete={
            key === "password"
              ? registering
                ? "new-password"
                : "current-password"
              : "new-password"
          }
          value={f[key]}
          onChange={(e) => setF({ ...f, [key]: e.target.value })}
          aria-invalid={!!errors[key]}
          aria-describedby={errors[key] ? `${key}-error` : undefined}
        />
      ) : (
        <input
          id={key}
          type={type}
          required
          maxLength={key === "name" ? 120 : 255}
          autoComplete={
            key === "password_confirmation"
              ? "new-password"
              : key === "email"
                ? "email"
                : key === "name"
                  ? "name"
                  : "off"
          }
          value={f[key]}
          onChange={(e) => setF({ ...f, [key]: e.target.value })}
          aria-invalid={!!errors[key]}
          aria-describedby={errors[key] ? `${key}-error` : undefined}
        />
      )}
      {errors[key] && (
        <small className="error" id={`${key}-error`}>
          {errors[key][0]}
        </small>
      )}
    </div>
  );
  async function submit(e) {
    e.preventDefault();
    setBusy(true);
    setMessage("");
    setErrors({});
    try {
      const u = registering
        ? await register(f)
        : await login(f.email, f.password, f.role);
      navigate(
        u.must_change_password
          ? "/change-password"
          : !u.email_verification_required || u.email_verified
            ? "/"
            : location.state?.returnTo?.startsWith("/verify-email?")
              ? location.state.returnTo
              : "/verify-email",
      );
    } catch (x) {
      setMessage(
        x.response?.data?.errors
          ? "Please check the highlighted field."
          : err(x),
      );
      setErrors(x.response?.data?.errors || {});
    } finally {
      setBusy(false);
    }
  }
  return (
    <div className="login">
      <div className="hero">
        <Brand light />
        <h1>
          Your career journey,
          <br />
          organized in one place.
        </h1>
        <p>Career Counselling and Placement</p>
      </div>
      <div className="formwrap">
        <form onSubmit={submit}>
          <Brand />
          <h2>{registering ? "Create account" : "Welcome back"}</h2>
          {
            <>
              <label htmlFor="role">Workspace</label>
              <select
                id="role"
                value={f.role}
                onChange={(e) => {
                  setF({ ...f, role: e.target.value });
                  setErrors({});
                  setMessage("");
                }}
              >
                <option value="student">Student</option>
                <option value="advisor">Career Advisor</option>
                <option value="admin">Admin</option>
                <option value="recruiter">Recruiter</option>
              </select>
              {errors.role && (
                <small className="error" role="alert">
                  {errors.role[0]}
                </small>
              )}
            </>
          }
          {registering && field("name", "Full name")}
          {field("email", "Email address", "email")}
          {registering &&
            f.role === "student" &&
            field("department", "Department")}
          {registering &&
            f.role === "recruiter" &&
            field("company_name", "Company name")}
          {registering && f.role !== "student" && (
            <p className="muted">
              Create your own account. An administrator will approve access to
              this workspace.
            </p>
          )}
          {field("password", "Password", "password")}
          {registering && (
            <>
              <small>
                At least 10 characters, including letters and numbers.
              </small>
              {field("password_confirmation", "Confirm password", "password")}
            </>
          )}
          <p role="alert" className="error">
            {message}
          </p>
          <button disabled={busy}>
            {busy ? "Please wait…" : registering ? "Register" : "Sign in"}
          </button>
          <p>
            <Link to={registering ? "/login" : `/register?role=${f.role}`}>
              {registering
                ? "Sign in instead"
                : `Create ${f.role === "advisor" ? "Career Advisor" : f.role} account`}
            </Link>
          </p>
          <Link to="/forgot-password">Forgot password?</Link>
        </form>
      </div>
    </div>
  );
}
