const json = (data, status = 200) => new Response(JSON.stringify(data), {
  status,
  headers: {
    "content-type": "application/json; charset=utf-8",
    "cache-control": "no-store"
  }
});

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    const path = url.pathname;

    if (path === "/api/health" && request.method === "GET") {
      return json({
        ok: true,
        name: "Truyện Đêm",
        service: "Cloudflare Workers",
        database: Boolean(env.DB),
        time: new Date().toISOString()
      });
    }

    if (path === "/api/stories" && request.method === "GET") {
      if (!env.DB) {
        return json({
          ok: true,
          stories: [],
          message: "D1 chưa được kết nối. Hãy tạo database và gắn binding DB."
        });
      }

      try {
        const result = await env.DB.prepare(
          `SELECT id, title, slug, author, description, cover_url, story_type, created_at
           FROM stories
           WHERE status = ?
           ORDER BY created_at DESC
           LIMIT 50`
        ).bind("published").all();

        return json({ ok: true, stories: result.results || [] });
      } catch (error) {
        return json({ ok: false, error: "Không thể tải danh sách truyện." }, 500);
      }
    }

    if (path.startsWith("/api/")) {
      return json({ ok: false, error: "Không tìm thấy API." }, 404);
    }

    if (env.ASSETS) {
      return env.ASSETS.fetch(request);
    }

    return new Response("Truyện Đêm đang được cấu hình.", {
      headers: { "content-type": "text/plain; charset=utf-8" }
    });
  }
};
