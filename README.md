# Truyện Đêm

Website đọc truyện và nghe truyện, triển khai bằng HTML/CSS/JavaScript tĩnh và Cloudflare Workers.

## Cấu trúc

- `public/`: giao diện website.
- `src/index.js`: Worker xử lý API.
- `wrangler.jsonc`: cấu hình deploy Workers.
- `schema.sql`: cấu trúc database Cloudflare D1.

## API hiện có

- `GET /api/health`: kiểm tra Worker và trạng thái binding D1.
- `GET /api/stories`: lấy tối đa 50 truyện đã xuất bản từ D1.

## Thiết lập Cloudflare

1. Trong Cloudflare Workers, kết nối repository này và chọn nhánh `main`.
2. Build command để trống; deploy command dùng `npx wrangler deploy`.
3. Tạo D1 database, chạy nội dung `schema.sql` trong D1 Console.
4. Thêm binding cho Worker với biến `DB` trỏ tới database vừa tạo.
5. Deploy lại và mở `/api/health` để kiểm tra.

## Lưu ý

Bản giao diện hiện tại vẫn dùng dữ liệu truyện mẫu và tài khoản lưu trong trình duyệt. Chưa có đăng ký/đăng nhập phía máy chủ, trang quản trị hoặc phát file audio riêng. Không dùng dữ liệu tài khoản mẫu cho người dùng thật. Các phần này sẽ được triển khai tiếp cùng D1 và R2.
