import { beforeEach, describe, it, expect, vi } from "vitest";
import api, { getToken, setToken } from "../src/api";
beforeEach(() => sessionStorage.clear());
describe("cookie-free API transport", () => {
  it("stores only the tab token and sends a bearer header without credentials", async () => {
    setToken("test-token");
    const adapter = vi.fn(async (config) => ({
      status: 200,
      statusText: "OK",
      data: {},
      headers: {},
      config,
    }));
    await api.get("/me", { adapter });
    expect(adapter.mock.calls[0][0].headers.Authorization).toBe(
      "Bearer test-token",
    );
    expect(adapter.mock.calls[0][0].withCredentials).toBe(false);
    expect(getToken()).toBe("test-token");
    setToken(null);
    expect(getToken()).toBeNull();
  });
  it("clears an expired token and signals authentication failure", async () => {
    setToken("expired");
    const expired = vi.fn();
    window.addEventListener("auth-expired", expired);
    await expect(
      api.get("/me", {
        adapter: () => Promise.reject({ response: { status: 401 } }),
      }),
    ).rejects.toBeTruthy();
    expect(getToken()).toBeNull();
    expect(expired).toHaveBeenCalledOnce();
    window.removeEventListener("auth-expired", expired);
  });
});
