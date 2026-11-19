# Cổ Phục Việt

Cửa hàng trực tuyến cổ phục Việt Nam — Áo Nhật Bình, Áo Tấc, Áo Giao Lĩnh, Áo Ngũ Thân và phụ kiện.

**Kiến trúc: PHP + Ajax.** Mọi trang được PHP render trên server; phần động (lọc, tab, giỏ hàng…) gọi Ajax tới
`/api/*` — PHP trả về JSON hoặc đoạn HTML đã render sẵn. JavaScript chỉ là một file nhỏ không dùng framework.

| Phần | Công nghệ |
|---|---|
| Server | PHP 8.2 thuần (không cần Composer), PDO, template PHP |
| Ajax | `fetch` trong `assets/js/app.js` (vanilla JS) |
| Giao diện | Tailwind CSS v4 — build sẵn thành `assets/css/app.css` |
| Cơ sở dữ liệu | MariaDB / MySQL (XAMPP) |
| Song ngữ | `app/lang/vi.php`, `app/lang/en.php` + cột `*_vi` / `*_en` trong DB, lưu lựa chọn bằng cookie |

## Chạy trên máy

1. **Bật XAMPP**: Apache + MySQL. Apache trên máy này dùng cổng **8080** (cổng 80 bị IIS chiếm).
2. **Tạo database** (chạy lại sẽ xoá và tạo lại dữ liệu):
   ```bash
   C:\xampp\mysql\bin\mysql -h 127.0.0.1 -u root --default-character-set=utf8mb4 < database/schema.sql
   C:\xampp\mysql\bin\mysql -h 127.0.0.1 -u root --default-character-set=utf8mb4 < database/seed.sql
   ```
3. **Cấu hình**: sao chép `config/config.example.php` → `config/config.php`, chỉnh DB và `base_path` nếu cần.
4. Mở <http://localhost:8080/co-phuc-viet/>

Không cần Node để chạy web. Node chỉ cần khi **sửa class Tailwind** trong view:
```bash
npm install
npm run css:watch     # tự build lại CSS khi sửa file .php
npm run css:build     # build bản nén
```

Tài khoản admin mẫu: `admin@cophucviet.vn` / `Admin@123`.

## Cấu trúc

```
index.php              front controller — khai báo route trang + API
.htaccess              rewrite về index.php, chặn truy cập app/ config/ views/ database/
app/
  bootstrap.php        autoload, config, ngôn ngữ
  helpers.php          e(), url(), asset(), t(), money(), icon(), lotus(), product_image()
  Core/                Config, Database, Request, Response, Router, View, Lang, HttpException
  Controllers/         HomeController, PageController, LangController
  Controllers/Api/     MetaController, ProductController
  Repositories/        ProductRepository, MetaRepository
  lang/                vi.php, en.php
views/
  layouts/main.php
  partials/            header, footer, motif-divider, product-card, product-grid
  pages/               home, coming-soon, 404, error
assets/
  src/app.css          nguồn Tailwind (theme, component classes)
  css/app.css          CSS đã build
  js/app.js            Ajax + menu mobile + hiệu ứng
  img/
uploads/               ảnh tải lên (không cho chạy script)
database/              schema.sql, seed.sql
```

## Trang & API

| Đường dẫn | Mô tả |
|---|---|
| `/` | Trang chủ |
| `/san-pham/{slug}` | Chi tiết sản phẩm (có thể truyền sẵn `fabric`, `color`, `size`) |
| `/bo-suu-tap` | Bộ sưu tập: `q`, `category`, `dynasty[]` (chọn nhiều), `gender`, `price` (duoi-1tr / 1-3tr / 3-4tr / tren-4tr), `sort`, `page` |
| `/thanh-toan` | Thanh toán (GET form; POST là đường dự phòng khi tắt JS) |
| `/dat-hang-thanh-cong/{code}` | Xác nhận đơn — chỉ xem được từ trình duyệt đã đặt |
| `/lang/{vi\|en}` | Đổi ngôn ngữ |
| `GET /api/health` | Kiểm tra PHP + DB |
| `GET /api/meta` | Danh mục (dạng cây, kèm số sản phẩm) + triều đại + chất liệu |
| `GET /api/products` | Lọc: `category`, `dynasty`, `gender` (nam/nu), `q`, `min_price`, `max_price`, `featured`, `sort` (newest/price_asc/price_desc/name), `page`, `per_page`. `format=html` → thẻ sản phẩm đã render; `format=catalog` → cả khối kết quả trang Bộ sưu tập (chip, lưới, phân trang) + tiêu đề, số lượng |
| `GET /api/products/{slug}` | Chi tiết sản phẩm + ảnh + biến thể |
| `GET /api/cart` | Giỏ hàng (HTML ngăn kéo đã render + số lượng). `?view=summary` thêm khối tóm tắt cho trang thanh toán |
| `POST /api/cart` | Thêm: `{slug, fabric, color, size, qty, measurements}` — kiểm tra tồn kho / số đo, 422 nếu lỗi |
| `PATCH /api/cart/{key}` / `DELETE /api/cart/{key}` | Sửa số lượng / xoá dòng |
| `POST /api/orders` | Đặt hàng: `{customer_name, phone, email, address, city, note, payment_method: COD\|BANK_TRANSFER}` → `{code, redirect}` hoặc 422 |
| `POST /api/products/{slug}/quote` | JSON `{fabric, color, size (S/M/L/XL/FREE/CUSTOM), qty, measurements{height,weight,bust,waist,hip,note}, check}` → giá, tồn kho, size gợi ý. `check: true` kiểm tra đơn (tồn kho, số đo) và trả 422 kèm lỗi từng trường |
| `/dang-nhap`, `/dang-ky` | Đăng nhập / tạo tài khoản khách hàng (cùng một trang; `?next=` để quay lại trang trước). Đơn đã đặt và mục yêu thích lúc chưa đăng nhập được gộp vào tài khoản |
| `/tai-khoan` | Tài khoản: tổng quan (số đơn, đang xử lý, đã mua sắm), **đơn hàng** (lọc theo trạng thái, chi tiết + thanh tiến trình, tự huỷ khi còn “Chờ xác nhận”), **sổ địa chỉ**, **số đo may đo** (nhiều hồ sơ), thông tin & đổi mật khẩu — chuyển mục bằng Ajax |
| `/yeu-thich` | Sản phẩm yêu thích (khách vãng lai lưu trong session, thành viên lưu trong DB) |
| `/tra-cuu-don-hang` | Tra cứu đơn bằng mã đơn + SĐT đặt hàng (khoá 10 phút sau 8 lần sai) |
| `POST /api/wishlist/{id}` | Bật / tắt yêu thích → `{saved, count}` |
| `POST /api/orders/lookup` | `{code, phone}` → HTML tiến trình đơn |
| `/api/account/*` | (cần đăng nhập) `PUT profile`, `POST password`, `POST/PUT/DELETE addresses[/{id}]`, `PATCH addresses/{id}/default`, `POST/PUT/DELETE measurements[/{id}]`, `GET orders?tab=&page=`, `POST orders/{code}/cancel` |
| `/tai-khoan/danh-gia` | Sản phẩm chờ đánh giá (từ đơn đã hoàn thành) + đánh giá của tôi kèm trạng thái duyệt |
| `GET /api/products/{slug}/reviews` | Đánh giá đã duyệt: `rating` (1–5), `photos=1`, `sort` (newest/helpful/highest/lowest), `page` → HTML |
| `POST /api/products/{slug}/reviews` | (cần đăng nhập, đã nhận hàng) multipart: `rating`, `title`, `body` (10–2000 ký tự), `fit` (small/true/large), `images[]` (≤ 3 ảnh JPG/PNG/WebP, 5MB) |
| `POST /api/reviews/{id}/helpful` | Bình chọn “Hữu ích” (1 lần / tài khoản hoặc phiên; không tự bình chọn) |

Ảnh có URL dạng `placeholder:<nhãn>` sẽ hiển thị thành ô màu có nhãn cho tới khi có ảnh thật.

## Trang quản trị

<http://localhost:8080/co-phuc-viet/admin> — đăng nhập `admin@cophucviet.vn` / `Admin@123` (**đổi mật khẩu trước khi triển khai**).

| Trang | Chức năng |
|---|---|
| `/admin?range=7\|30\|90` | Tổng quan: doanh thu, số đơn, giá trị TB/đơn, tỷ lệ may đo (so với kỳ trước), biểu đồ doanh thu theo ngày/tuần, đơn theo trạng thái, top bán chạy, sắp hết hàng |
| 🔔 (mọi trang admin) | Chuông đơn chờ xác nhận — tự kiểm tra mỗi 20 giây, báo âm thanh + thông báo khi có đơn mới |
| `/admin/san-pham` | Danh sách: tìm kiếm tức thì, lọc danh mục / trạng thái, bật-tắt “Đang bán” & “Nổi bật”, xoá |
| `/admin/san-pham/them`, `/admin/san-pham/{id}` | Thêm / sửa (song ngữ, slug tự sinh), tải ảnh, tồn kho từng biến thể (sửa trực tiếp), tạo nhanh biến thể size × chất liệu × màu |
| `/admin/don-hang` | Lọc theo trạng thái / khoảng ngày, tìm theo mã / SĐT / tên; chọn nhiều đơn để đổi trạng thái hoặc in hàng loạt; **Xuất Excel** (.xlsx, 2 sheet: đơn hàng + chi tiết sản phẩm kèm số đo) |
| `/admin/don-hang/{code}` | Chi tiết, số đo may đo, đổi trạng thái đơn & thanh toán, **lịch sử xử lý**, **ghi chú nội bộ**, sửa thông tin giao hàng, in hoá đơn / phiếu giao hàng. Huỷ đơn hoàn lại tồn kho (không mở lại được) |
| `/admin/don-hang/in?codes=A,B&type=invoice\|packing` | Trang in A4, mỗi đơn một trang (phiếu giao hàng ghi rõ số tiền thu hộ COD) |

| `/admin/khach-hang` | Khách hàng (gộp theo SĐT): chi tiêu, số đơn, nhóm VIP / mua lại / mới / từng may đo; chi tiết có lịch sử đơn, **số đo đã dùng**, địa chỉ, sản phẩm hay mua |
| `/admin/khuyen-mai` | Mã giảm giá: giảm % (có mức tối đa) / số tiền / miễn phí ship, đơn tối thiểu, thời gian, tổng lượt & lượt mỗi SĐT; thống kê số đơn, tiền đã giảm, doanh thu mang lại |
| `/admin/danh-gia` | Kiểm duyệt đánh giá: lọc trạng thái / số sao / từ khoá, duyệt hoặc ẩn (từng cái hoặc chọn nhiều), **trả lời công khai**, xoá (kèm ảnh). Menu hiện số đánh giá chờ duyệt; bật “tự động hiển thị” trong Cài đặt |
| `/admin/kho` | Tồn kho mọi biến thể (lọc sắp hết / hết, bán 30 ngày), điều chỉnh nhập hàng / kiểm kê / hư hỏng kèm ghi chú; tab **Lịch sử nhập xuất** ghi mọi thay đổi (bán, huỷ đơn, nhập, kiểm kê…) và người làm |
| `/admin/danh-muc` | Thêm / sửa / xoá danh mục (2 cấp), triều đại, chất liệu (phụ phí). Không xoá được mục đang dùng |
| `/admin/cai-dat` | Thông tin cửa hàng, tài khoản ngân hàng, phí ship & mức miễn phí, ngưỡng “sắp hết hàng” — có hiệu lực ngay |
| `/admin/nhan-vien` | Tài khoản nhân viên, vai trò, khoá / mở, đặt lại mật khẩu |
| `/admin/nhat-ky` | Nhật ký hoạt động: ai làm gì, lúc nào, IP |
| `/admin/tai-khoan` | Đổi mật khẩu của mình |

Sản phẩm còn có: **giá khuyến mãi theo lịch** (bắt đầu / kết thúc; chọn nhiều → “Giảm giá %…”), **nhân bản**, chọn nhiều để bật / ẩn / nổi bật / xoá, **kéo thả sắp xếp ảnh**, đổi loại ảnh.

### Cập nhật database cho bản đang chạy

Khi kéo code mới về, chạy các file trong `database/migrations/` theo thứ tự tên (chỉ thêm bảng/cột, không xoá dữ liệu):
```bash
C:\xampp\mysql\bin\mysql -h 127.0.0.1 -u root --default-character-set=utf8mb4 < database/migrations/2026_11_15_01_order_events.sql
C:\xampp\mysql\bin\mysql -h 127.0.0.1 -u root --default-character-set=utf8mb4 < database/migrations/2026_11_15_02_phase7b.sql
C:\xampp\mysql\bin\mysql -h 127.0.0.1 -u root --default-character-set=utf8mb4 < database/migrations/2026_11_16_01_promotions.sql
C:\xampp\mysql\bin\mysql -h 127.0.0.1 -u root --default-character-set=utf8mb4 < database/migrations/2026_11_16_02_customer_accounts.sql
C:\xampp\mysql\bin\mysql -h 127.0.0.1 -u root --default-character-set=utf8mb4 < database/migrations/2026_11_16_03_reviews.sql
```

### Vai trò

| Vai trò | Được dùng |
|---|---|
| Quản trị viên | Tất cả |
| Nhân viên bán hàng | Tổng quan, đơn hàng (xử lý, in, xuất Excel), khách hàng, mã giảm giá |
| Nhân viên kho | Tổng quan, sản phẩm, kho; xem & in đơn (không đổi trạng thái) |

Luôn phải còn ít nhất một quản trị viên đang hoạt động. Khoá tài khoản / đổi vai trò có hiệu lực ngay, kể cả khi nhân viên đang đăng nhập.

Bảo mật: mật khẩu bcrypt, khoá 5 phút sau 5 lần sai, đổi session ID khi đăng nhập, CSRF cho mọi request ghi,
ảnh tải lên được kiểm tra bằng nội dung file (JPG/PNG/WebP ≤ 5MB), thư mục `uploads/` không chạy được script,
trang admin gửi `noindex` + `no-store`.

Đổi mật khẩu admin (thay `MatKhauMoi`):
```bash
C:\xampp\php\php -r "echo password_hash('MatKhauMoi', PASSWORD_BCRYPT);"
# rồi: UPDATE users SET password_hash = '<chuỗi vừa in>' WHERE email = 'admin@cophucviet.vn';
```

## Lộ trình

- [x] 1. Khung dự án, theme, font, song ngữ, dữ liệu mẫu, kết nối DB
- [x] 2. API sản phẩm + Trang chủ (hero, danh mục nổi bật, sản phẩm nổi bật theo triều đại qua Ajax, câu chuyện văn hóa)
- [x] 3. Bộ sưu tập: bộ lọc (loại áo, thời kỳ, giới tính, giá), tìm kiếm tức thì, sắp xếp, phân trang — Ajax, vẫn chạy khi tắt JS
- [x] 4. Trang chi tiết sản phẩm: gallery + phóng to, chất liệu/màu/size, may đo theo số đo (PHP kiểm tra), giá & tồn kho cập nhật qua Ajax, bảng size, bối cảnh lịch sử, sản phẩm liên quan
- [x] 5. Giỏ hàng trượt (session PHP + Ajax) + Thanh toán (COD / chuyển khoản, QR minh hoạ) + lưu đơn trong transaction có khoá & trừ tồn kho, CSRF cho mọi request ghi
- [x] 6. Trang quản trị: đăng nhập, tổng quan, CRUD sản phẩm + ảnh + tồn kho biến thể, quản lý đơn hàng (huỷ hoàn kho)
- [x] 7–8. Quản trị nâng cao (biểu đồ, lịch sử đơn, in, Excel, chuông thông báo, khách hàng, kho, danh mục, cài đặt, phân quyền, nhật ký) + khuyến mãi (giá sale theo lịch, mã giảm giá)
- [x] 9. Tài khoản khách hàng: đăng ký / đăng nhập, lịch sử & theo dõi đơn, tự huỷ đơn chờ xác nhận, sổ địa chỉ (tự điền khi thanh toán), hồ sơ số đo (dùng lại khi may đo), yêu thích, tra cứu đơn bằng mã + SĐT
- [x] 10. Đánh giá sản phẩm: chỉ khách đã nhận hàng, sao + nhận xét + độ vừa vặn + ảnh, lọc / sắp xếp / xem thêm qua Ajax, “Hữu ích”, phản hồi của shop, kiểm duyệt trong admin, điểm trung bình trên thẻ sản phẩm + sắp xếp “Đánh giá cao”
- [ ] 11. UX / SEO / email xác nhận
