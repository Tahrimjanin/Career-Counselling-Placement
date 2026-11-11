import { useEffect, useState } from "react";
import api, { err } from "./api";
import { useAuth } from "./AuthContext";
export function Avatar({ user }) {
  const [url, setUrl] = useState("");
  useEffect(() => {
    let alive = true,
      blob;
    setUrl("");
    if (user.has_avatar)
      api
        .get("/v1/avatar", { responseType: "blob" })
        .then((r) => {
          blob = URL.createObjectURL(r.data);
          if (alive) setUrl(blob);
          else URL.revokeObjectURL(blob);
        })
        .catch(() => {});
    return () => {
      alive = false;
      if (blob) URL.revokeObjectURL(blob);
    };
  }, [user.id, user.has_avatar, user.avatar_version]);
  return url ? (
    <img className="avatar" src={url} alt={`${user.name}'s profile`} />
  ) : (
    <span className="avatar avatar-initials" aria-label="Profile image not set">
      {user.name
        ?.split(" ")
        .map((s) => s[0])
        .slice(0, 2)
        .join("")
        .toUpperCase()}
    </span>
  );
}
export default function AvatarEditor() {
  const { user, refresh } = useAuth(),
    [file, setFile] = useState(null),
    [message, setMessage] = useState(""),
    [busy, setBusy] = useState(false);
  async function save(e) {
    e.preventDefault();
    if (!file) return;
    setBusy(true);
    try {
      const data = new FormData();
      data.append("image", file);
      await api.post("/v1/avatar", data);
      await refresh();
      setMessage("Profile image updated.");
    } catch (e) {
      setMessage(err(e));
    } finally {
      setBusy(false);
    }
  }
  return (
    <section className="card">
      <h2>Profile image</h2>
      <Avatar user={user} />
      <form onSubmit={save}>
        <label htmlFor="avatar-upload">
          Choose JPG, PNG or WebP (maximum 2 MB)
        </label>
        <input
          id="avatar-upload"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          onChange={(e) => setFile(e.target.files[0])}
        />
        <button disabled={!file || busy}>Upload image</button>
        {user.has_avatar && (
          <button
            type="button"
            disabled={busy}
            onClick={async () => {
              setBusy(true);
              try {
                await api.delete("/v1/avatar");
                await refresh();
                setMessage("Profile image removed.");
              } catch (e) {
                setMessage(err(e));
              } finally {
                setBusy(false);
              }
            }}
          >
            Remove image
          </button>
        )}
        <p role="status">{message}</p>
      </form>
    </section>
  );
}
