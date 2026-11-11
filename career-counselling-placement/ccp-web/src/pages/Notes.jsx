import { useState } from "react";
import api, { err } from "../api";
import { useAuth } from "../AuthContext";
import { Card, fmt, Msg, useFetch } from "../ui";

function NoteBox({ studentId, url }) {
  const { data, reload } = useFetch(url);
  const [body, setBody] = useState("");
  const [msg, setMsg] = useState("");
  const add = async (e) => {
    e.preventDefault();
    try {
      await api.post("/notes", { body, student_id: studentId });
      setBody("");
      reload();
    } catch (x) {
      setMsg(err(x));
    }
  };
  return (
    <>
      <Card title="Add note">
        <form onSubmit={add}>
          <textarea
            rows={3}
            required
            value={body}
            onChange={(e) => setBody(e.target.value)}
            aria-label="Note"
          />
          <p>
            <button>Save note</button>
          </p>
        </form>
        <Msg text={msg} />
      </Card>
      {data?.map((n) => (
        <Card key={n.id}>
          <p>{n.body}</p>
          <small>
            {fmt(n.created_at)}
            {n.private && " · private"}
          </small>
        </Card>
      ))}
    </>
  );
}

function AdvisorNotes() {
  const { data: students } = useFetch("/students");
  const [sid, setSid] = useState("");
  return (
    <>
      <p className="muted">
        Private counselling notes are visible only to the advisor who wrote them
        — never to recruiters.
      </p>
      <select
        aria-label="Student"
        value={sid}
        onChange={(e) => setSid(e.target.value)}
      >
        <option value="">Select a student…</option>
        {students?.map((s) => (
          <option key={s.id} value={s.id}>
            {s.name}
          </option>
        ))}
      </select>
      {sid && (
        <NoteBox key={sid} studentId={sid} url={`/notes?student_id=${sid}`} />
      )}
    </>
  );
}

export default function Notes() {
  const { user } = useAuth();
  return (
    <>
      <h1>Notes</h1>
      {user.role === "advisor" ? <AdvisorNotes /> : <NoteBox url="/notes" />}
    </>
  );
}
