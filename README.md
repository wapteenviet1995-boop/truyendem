# Truyện Đêm

Website đọc truyện và nghe truyện, triển khai bằng JavaScript, Cloudflare Workers, D1 và static assets.

## Cấu trúc

- `public/`: giao diện website và trang quản trị.
- `src/index.js`: API Worker, đăng nhập quản trị và quản lý truyện/chương.
- `wrangler.jsonc`: cấu hình deploy Workers.
- `schema.sql`: cấu trúc database Cloudflare D1.

## API

- `GET /api/health`: kiểm tra Worker và binding D1.
- `GET /api/stories`: danh sách truyện đã xuất bản.
- `GET /api/stories/:slug`: thông tin truyện và các chương đã xuất bản.
- `POST /api/admin/setup`: khởi tạo tài khoản admin lần đầu.
- `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`.
- `/api/admin/stories`: thêm, xem, sửa, xóa truyện (yêu cầu admin).
- `/api/admin/stories/:id/chapters`: xem và thêm chương.
- `/api/admin/chapters/:id`: sửa và xóa chương.

## Thiết lập trên Cloudflare

1. Mở Cloudflare Dashboard → Workers & Pages → chọn Worker `truyendem`.
2. Trong Settings → Bindings, thêm D1 database binding với **Variable name: `DB`**.
3. Tạo D1 database nếu chưa có. Mở D1 → database vừa tạo → Console, chạy toàn bộ nội dung file `schema.sql`.
4. Trong Worker → Settings → Variables and Secrets, thêm hai secret:
   - `SESSION_SECRET`: chuỗi ngẫu nhiên dài tối thiểu 32 ký tự, dùng để ký phiên đăng nhập.
   - `ADMIN_SETUP_KEY`: khóa bí mật riêng để tạo admin lần đầu.
5. Lưu thay đổi và deploy lại Worker.
6. Mở `https://TEN-WORKER.workers.dev/api/health`; kết quả cần có `"database":true`.
7. Mở `https://TEN-WORKER.workers.dev/admin.html`, chọn phần khởi tạo tài khoản lần đầu và nhập ADMIN_SETUP_KEY cùng thông tin admin.
8. Sau khi tạo admin, đăng xuất khỏi phần khởi tạo nếu cần và đăng nhập bằng email/mật khẩu vừa tạo.

## Quản trị

Vào `/admin.html` để:
- Tạo, sửa, xóa truyện.
- Chọn trạng thái bản nháp, xuất bản hoặc ẩn.
- Thêm, sửa, xóa chương và nội dung từng chương.
- Chương ở trạng thái Xuất bản mới hiển thị công khai.

## Lưu ý quan trọng

- Không đưa `SESSION_SECRET` hoặc `ADMIN_SETUP_KEY` vào GitHub, mã HTML hay JavaScript phía trình duyệt. Chỉ lưu chúng dưới dạng Secret trong Cloudflare.
- Sau khi tạo admin đầu tiên, endpoint khởi tạo sẽ từ chối tạo thêm admin nếu database đã có admin.
- Bản audio hiện mới lưu trường URL audio trong D1; trình phát file audio riêng và lưu trữ R2 là bước triển khai tiếp theo.
- Dữ liệu truyện mẫu phía trình duyệt được dùng làm dự phòng khi D1 chưa có truyện xuất bản.
