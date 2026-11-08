const json = (data, status = 200, headers = {}) => new Response(JSON.stringify(data), {
  status,
  headers: {
    "content-type": "application/json; charset=utf-8",
    "cache-control": "no-store",
    ...headers
  }
});

const COOKIE = "td_session";
const SESSION_SECONDS = 60 * 60 * 24 * 7;
const PBKDF2_ITERATIONS = 120000;

function b64url(bytes) {
  let binary = "";
  for (const b of new Uint8Array(bytes)) binary += String.fromCharCode(b);
  return btoa(binary).replace(/=/g, "").replace(/\+/g, "-").replace(/\//g, "_");
}
function fromB64url(value) {
  const s = value.replace(/-/g, "+").replace(/_/g, "/");
  const raw = atob(s + "=".repeat((4 - s.length % 4) % 4));
  return Uint8Array.from(raw, c => c.charCodeAt(0));
}
function randomHex(length = 16) {
  const bytes = crypto.getRandomValues(new Uint8Array(length));
  return Array.from(bytes, b => b.toString(16).padStart(2, "0")).join("");
}
async function passwordHash(password, salt = randomHex(16)) {
  const key = await crypto.subtle.importKey("raw", new TextEncoder().encode(password), "PBKDF2", false, ["deriveBits"]);
  const bits = await crypto.subtle.deriveBits(
    { name: "PBKDF2", salt: new TextEncoder().encode(salt), iterations: PBKDF2_ITERATIONS, hash: "SHA-256" },
    key,
    256
  );
  return `pbkdf2$${PBKDF2_ITERATIONS}$${salt}$${b64url(bits)}`;
}
async function verifyPassword(password, stored) {
  if (!stored || !stored.startsWith("pbkdf2$")) return false;
  const [, iterations, salt, expected] = stored.split("$");
  const key = await crypto.subtle.importKey("raw", new TextEncoder().encode(password), "PBKDF2", false, ["deriveBits"]);
  const bits = await crypto.subtle.deriveBits(
    { name: "PBKDF2", salt: new TextEncoder().encode(salt), iterations: Number(iterations), hash: "SHA-256" },
    key,
    256
  );
  const actual = b64url(bits);
  return actual === expected;
}
async function tokenHash(token) {
  const digest = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(token));
  return b64url(digest);
}

async function ensureSessionTable(env) {
  await env.DB.prepare(`
    CREATE TABLE IF NOT EXISTS auth_sessions (
      token_hash TEXT PRIMARY KEY,
      user_id INTEGER NOT NULL,
      expires_at INTEGER NOT NULL,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
  `).run();
}

async function makeSession(user, env) {
  await ensureSessionTable(env);
  const token = randomHex(32);
  const expiresAt = Math.floor(Date.now() / 1000) + SESSION_SECONDS;
  await env.DB.prepare(
    "INSERT INTO auth_sessions (token_hash, user_id, expires_at) VALUES (?, ?, ?)"
  ).bind(await tokenHash(token), user.id, expiresAt).run();
  return token;
}

async function currentUser(request, env) {
  const cookie = request.headers.get("Cookie") || "";
  const match = cookie.match(new RegExp("(?:^|;\\s*)" + COOKIE + "=([^;]+)"));
  if (!match || !env.DB) return null;
  try {
    await ensureSessionTable(env);
    const row = await env.DB.prepare(
      `SELECT u.id, u.email, u.display_name AS name, u.role
       FROM auth_sessions s JOIN users u ON u.id = s.user_id
       WHERE s.token_hash = ? AND s.expires_at > ? LIMIT 1`
    ).bind(await tokenHash(match[1]), Math.floor(Date.now() / 1000)).first();
    return row || null;
  } catch {
    return null;
  }
}
function sessionCookie(value) {
  return `${COOKIE}=${value}; HttpOnly; Secure; SameSite=Lax; Path=/; Max-Age=${SESSION_SECONDS}`;
}
function clearCookie() {
  return `${COOKIE}=; HttpOnly; Secure; SameSite=Lax; Path=/; Max-Age=0`;
}
async function bodyJson(request) {
  try { return await request.json(); } catch { return null; }
}
function slugify(value) {
  return String(value || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d").replace(/Đ/g, "D").toLowerCase()
    .replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "").slice(0, 100);
}
function clean(value, max = 500) {
  return String(value ?? "").trim().slice(0, max);
}
async function requireAdmin(request, env) {
  const user = await currentUser(request, env);
  return user && user.role === "admin" ? user : null;
}

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    const path = url.pathname;
    const method = request.method;

    if (path.startsWith("/api/") && !env.DB && path !== "/api/health") {
      return json({ ok: false, error: "D1 chưa được kết nối. Hãy tạo database và gắn binding DB." }, 503);
    }

    if (path === "/api/health" && method === "GET") {
      return json({ ok: true, name: "Truyện Đêm", service: "Cloudflare Workers", database: Boolean(env.DB), sessionStorage: "D1", time: new Date().toISOString() });
    }

    if (path === "/api/stories" && method === "GET") {
      try {
        const result = await env.DB.prepare(
          `SELECT id, title, slug, author, description, cover_url, story_type, created_at
           FROM stories WHERE status = 'published' ORDER BY created_at DESC LIMIT 100`
        ).all();
        return json({ ok: true, stories: result.results || [] });
      } catch (error) {
        return json({ ok: false, error: "Không thể tải danh sách truyện. Hãy kiểm tra schema.sql trong D1." }, 500);
      }
    }

    const publicStoryMatch = path.match(/^\/api\/stories\/([a-z0-9-]+)$/);
    if (publicStoryMatch && method === "GET") {
      const story = await env.DB.prepare(
        "SELECT id, title, slug, author, description, cover_url, story_type, created_at FROM stories WHERE slug=? AND status='published'"
      ).bind(publicStoryMatch[1]).first();
      if (!story) return json({ ok: false, error: "Không tìm thấy truyện." }, 404);
      const chapters = await env.DB.prepare(
        "SELECT id, title, chapter_number, content, audio_url, duration_seconds FROM chapters WHERE story_id=? AND status='published' ORDER BY chapter_number ASC"
      ).bind(story.id).all();
      return json({ ok: true, story, chapters: chapters.results || [] });
    }

    if (path === "/api/auth/me" && method === "GET") {
      const user = await currentUser(request, env);
      return json({ ok: true, user: user ? { id: user.id, email: user.email, name: user.name, role: user.role } : null });
    }

    if (path === "/api/admin/setup-status" && method === "GET") {
      const count = await env.DB.prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'").first();
      return json({ ok: true, setupRequired: !count || count.total === 0 });
    }

    if (path === "/api/admin/setup" && method === "POST") {
      
      const data = await bodyJson(request);
      if (!data) return json({ ok: false, error: "Dữ liệu gửi lên không hợp lệ." }, 400);
      const email = clean(data.email, 190).toLowerCase();
      const name = clean(data.name, 80);
      const password = String(data.password || "");
      if (email.length < 6 || !email.includes('@') || !email.split('@')[1].includes('.') || !name || password.length < 10) {
        return json({ ok: false, error: "Email không hợp lệ, tên không được trống và mật khẩu phải có ít nhất 10 ký tự." }, 400);
      }
      const hash = await passwordHash(password);
      // Chỉ tài khoản quản trị đầu tiên được tạo; câu lệnh này ngăn hai yêu cầu đồng thời tạo thêm admin.
      const result = await env.DB.prepare(
        "INSERT INTO users (email, display_name, password_hash, role) SELECT ?, ?, ?, 'admin' WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'admin')"
      ).bind(email, name, hash).run();
      if (!result.meta.changes) return json({ ok: false, error: "Tài khoản quản trị đã được tạo. Hãy đăng nhập." }, 409);
      const user = { id: result.meta.last_row_id, email, display_name: name, role: "admin" };
      const token = await makeSession(user, env);
      return json({ ok: true, message: "Đã tạo tài khoản quản trị.", user: { email, name, role: "admin" } }, 201, { "set-cookie": sessionCookie(token) });
    }

    if (path === "/api/auth/login" && method === "POST") {
      const data = await bodyJson(request);
      if (!data) return json({ ok: false, error: "Dữ liệu gửi lên không hợp lệ." }, 400);
      const email = clean(data.email, 190).toLowerCase();
      const password = String(data.password || "");
      const user = await env.DB.prepare("SELECT id, email, display_name, password_hash, role FROM users WHERE email = ?").bind(email).first();
      if (!user || !(await verifyPassword(password, user.password_hash))) {
        return json({ ok: false, error: "Email hoặc mật khẩu không đúng." }, 401);
      }
      const token = await makeSession(user, env);
      return json({ ok: true, user: { email: user.email, name: user.display_name, role: user.role } }, 200, { "set-cookie": sessionCookie(token) });
    }

    if (path === "/api/auth/logout" && method === "POST") {
      return json({ ok: true }, 200, { "set-cookie": clearCookie() });
    }

    if (path === "/api/admin/stories" && method === "GET") {
      if (!(await requireAdmin(request, env))) return json({ ok: false, error: "Bạn cần đăng nhập bằng tài khoản quản trị." }, 401);
      const result = await env.DB.prepare(
        "SELECT id, title, slug, author, description, cover_url, story_type, status, created_at FROM stories ORDER BY created_at DESC LIMIT 200"
      ).all();
      return json({ ok: true, stories: result.results || [] });
    }

    if (path === "/api/admin/stories" && method === "POST") {
      if (!(await requireAdmin(request, env))) return json({ ok: false, error: "Không có quyền quản trị." }, 401);
      const data = await bodyJson(request);
      const title = clean(data?.title, 180);
      const slug = slugify(data?.slug || title);
      const author = clean(data?.author, 120);
      const description = clean(data?.description, 2000);
      const cover = clean(data?.cover_url, 1000);
      const type = ["text", "audio", "both"].includes(data?.story_type) ? data.story_type : "text";
      const status = ["draft", "published", "hidden"].includes(data?.status) ? data.status : "draft";
      if (!title || !slug) return json({ ok: false, error: "Vui lòng nhập tên truyện hợp lệ." }, 400);
      try {
        await env.DB.prepare(
          "INSERT INTO stories (title, slug, author, description, cover_url, story_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)"
        ).bind(title, slug, author, description, cover, type, status).run();
        return json({ ok: true, message: "Đã thêm truyện." }, 201);
      } catch (error) {
        return json({ ok: false, error: "Không thể thêm truyện. Có thể đường dẫn slug đã tồn tại." }, 409);
      }
    }

    const adminChaptersMatch = path.match(/^\/api\/admin\/stories\/(\d+)\/chapters$/);
    if (adminChaptersMatch && method === "GET") {
      if (!(await requireAdmin(request, env))) return json({ ok: false, error: "Không có quyền quản trị." }, 401);
      const result = await env.DB.prepare(
        "SELECT id, title, chapter_number, content, audio_url, duration_seconds, status FROM chapters WHERE story_id=? ORDER BY chapter_number ASC"
      ).bind(Number(adminChaptersMatch[1])).all();
      return json({ ok: true, chapters: result.results || [] });
    }
    if (adminChaptersMatch && method === "POST") {
      if (!(await requireAdmin(request, env))) return json({ ok: false, error: "Không có quyền quản trị." }, 401);
      const data = await bodyJson(request);
      const title = clean(data?.title, 180);
      const number = Math.max(1, Math.floor(Number(data?.chapter_number) || 1));
      const content = String(data?.content || "").slice(0, 100000);
      const audio = clean(data?.audio_url, 1000);
      const duration = Math.max(0, Math.floor(Number(data?.duration_seconds) || 0));
      const status = ["draft", "published", "hidden"].includes(data?.status) ? data.status : "draft";
      if (!title || !content.trim()) return json({ ok: false, error: "Tên chương và nội dung không được để trống." }, 400);
      try {
        await env.DB.prepare(
          "INSERT INTO chapters (story_id,title,chapter_number,content,audio_url,duration_seconds,status) VALUES (?,?,?,?,?,?,?)"
        ).bind(Number(adminChaptersMatch[1]),title,number,content,audio,duration,status).run();
        return json({ ok: true, message: "Đã thêm chương." }, 201);
      } catch (error) {
        return json({ ok: false, error: "Không thể thêm chương. Kiểm tra truyện và số chương có bị trùng không." }, 409);
      }
    }

    const chapterMatch = path.match(/^\/api\/admin\/chapters\/(\d+)$/);
    if (chapterMatch && method === "PUT") {
      if (!(await requireAdmin(request, env))) return json({ ok: false, error: "Không có quyền quản trị." }, 401);
      const data = await bodyJson(request);
      const title = clean(data?.title, 180);
      const number = Math.max(1, Math.floor(Number(data?.chapter_number) || 1));
      const content = String(data?.content || "").slice(0, 100000);
      const audio = clean(data?.audio_url, 1000);
      const duration = Math.max(0, Math.floor(Number(data?.duration_seconds) || 0));
      const status = ["draft", "published", "hidden"].includes(data?.status) ? data.status : "draft";
      if (!title || !content.trim()) return json({ ok: false, error: "Tên chương và nội dung không được để trống." }, 400);
      try {
        const result = await env.DB.prepare(
          "UPDATE chapters SET title=?,chapter_number=?,content=?,audio_url=?,duration_seconds=?,status=? WHERE id=?"
        ).bind(title,number,content,audio,duration,status,Number(chapterMatch[1])).run();
        if (!result.meta.changes) return json({ ok: false, error: "Không tìm thấy chương." }, 404);
        return json({ ok: true, message: "Đã cập nhật chương." });
      } catch (error) {
        return json({ ok: false, error: "Không thể cập nhật chương. Có thể số chương đã tồn tại." }, 409);
      }
    }
    if (chapterMatch && method === "DELETE") {
      if (!(await requireAdmin(request, env))) return json({ ok: false, error: "Không có quyền quản trị." }, 401);
      const result = await env.DB.prepare("DELETE FROM chapters WHERE id=?").bind(Number(chapterMatch[1])).run();
      if (!result.meta.changes) return json({ ok: false, error: "Không tìm thấy chương." }, 404);
      return json({ ok: true, message: "Đã xóa chương." });
    }

    const storyMatch = path.match(/^\/api\/admin\/stories\/(\d+)$/);
    if (storyMatch && method === "PUT") {
      if (!(await requireAdmin(request, env))) return json({ ok: false, error: "Không có quyền quản trị." }, 401);
      const id = Number(storyMatch[1]);
      const data = await bodyJson(request);
      const title = clean(data?.title, 180);
      const slug = slugify(data?.slug || title);
      const type = ["text", "audio", "both"].includes(data?.story_type) ? data.story_type : "text";
      const status = ["draft", "published", "hidden"].includes(data?.status) ? data.status : "draft";
      if (!title || !slug) return json({ ok: false, error: "Tên truyện không hợp lệ." }, 400);
      try {
        const result = await env.DB.prepare(
          "UPDATE stories SET title=?, slug=?, author=?, description=?, cover_url=?, story_type=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?"
        ).bind(title, slug, clean(data.author,120), clean(data.description,2000), clean(data.cover_url,1000), type, status, id).run();
        if (!result.meta.changes) return json({ ok: false, error: "Không tìm thấy truyện." }, 404);
        return json({ ok: true, message: "Đã cập nhật truyện." });
      } catch (error) {
        return json({ ok: false, error: "Không thể cập nhật. Có thể slug đã được dùng." }, 409);
      }
    }

    if (storyMatch && method === "DELETE") {
      if (!(await requireAdmin(request, env))) return json({ ok: false, error: "Không có quyền quản trị." }, 401);
      const result = await env.DB.prepare("DELETE FROM stories WHERE id=?").bind(Number(storyMatch[1])).run();
      if (!result.meta.changes) return json({ ok: false, error: "Không tìm thấy truyện." }, 404);
      return json({ ok: true, message: "Đã xóa truyện." });
    }

    if (path.startsWith("/api/")) return json({ ok: false, error: "Không tìm thấy API." }, 404);
    if (env.ASSETS) return env.ASSETS.fetch(request);
    return new Response("Truyện Đêm đang được cấu hình.", { headers: { "content-type": "text/plain; charset=utf-8" } });
  }
};
