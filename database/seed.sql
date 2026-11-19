-- =============================================================
--  Cổ Phục Việt — sample data. Run AFTER schema.sql.
--  Admin login: admin@cophucviet.vn / Admin@123  (đổi mật khẩu khi triển khai thật)
--  Image URLs starting with "placeholder:" are rendered by the client as labelled colour boxes.
-- =============================================================
USE co_phuc_viet;
SET NAMES utf8mb4;

-- ---------- Admin ----------
INSERT INTO users (full_name, email, password_hash, role) VALUES
('Quản trị viên', 'admin@cophucviet.vn',
 '$2y$10$F2Q8g79ueXcyH6mK0gw8f.e4dsJfSBVpX0PCjRRNC2Cn9tnEjoOsC', 'admin');

-- ---------- Dynasties ----------
INSERT INTO dynasties (slug, name_vi, name_en, period, sort_order) VALUES
('ly-tran', 'Thời Lý – Trần', 'Lý – Trần Era', '1009 – 1400', 1),
('le',      'Thời Lê',        'Lê Era',        '1428 – 1789', 2),
('nguyen',  'Thời Nguyễn',    'Nguyễn Era',    '1802 – 1945', 3);

-- ---------- Fabrics ----------
INSERT INTO fabrics (slug, name_vi, name_en, price_modifier) VALUES
('gam',        'Gấm',         'Brocade',   500000),
('lua-to-tam', 'Lụa tơ tằm',  'Pure silk', 800000),
('dui',        'Đũi',         'Raw silk',  0);

-- ---------- Categories ----------
INSERT INTO categories (id, parent_id, slug, name_vi, name_en, description_vi, description_en, sort_order) VALUES
(1, NULL, 'ao-nhat-binh', 'Áo Nhật Bình', 'Nhật Bình Robe',
 'Lễ phục cổ vuông của hoàng hậu, công chúa và mệnh phụ triều Nguyễn.',
 'Square-collared ceremonial robe of Nguyễn-dynasty queens, princesses and noblewomen.', 1),
(2, NULL, 'ao-tac', 'Áo Tấc', 'Áo Tấc Robe',
 'Áo lễ tay rộng, thân dài – trang phục trang trọng của nam nữ thời Nguyễn.',
 'Wide-sleeved long formal robe worn by men and women in the Nguyễn era.', 2),
(3, NULL, 'ao-giao-linh', 'Áo Giao Lĩnh', 'Giao Lĩnh Robe',
 'Áo cổ chéo vạt – kiểu áo phổ biến từ thời Lý, Trần đến Lê.',
 'Cross-collared robe common from the Lý and Trần to the Lê dynasties.', 3),
(4, NULL, 'ao-ngu-than', 'Áo Ngũ Thân', 'Ngũ Thân Robe',
 'Áo năm thân, tiền thân của áo dài ngày nay.',
 'Five-panel robe, the forerunner of the modern áo dài.', 4),
(5, NULL, 'phu-kien', 'Phụ kiện', 'Accessories',
 'Mấn, quạt, hài và các phụ kiện đi kèm cổ phục.',
 'Headwear, fans, shoes and other accessories for traditional attire.', 5),
(6, 5, 'man',  'Mấn',  'Headwear', NULL, NULL, 1),
(7, 5, 'quat', 'Quạt', 'Fans',     NULL, NULL, 2),
(8, 5, 'hai',  'Hài',  'Shoes',    NULL, NULL, 3);

-- ---------- Products ----------
INSERT INTO products (id, category_id, dynasty_id, slug, name_vi, name_en,
  short_description_vi, short_description_en, historical_context_vi, historical_context_en,
  base_price, gender, allow_custom_tailoring, custom_tailoring_fee, is_featured) VALUES
(1, 1, 3, 'ao-nhat-binh-phuong-hoang', 'Áo Nhật Bình Phượng Hoàng', 'Phoenix Nhật Bình Robe',
 'Áo Nhật Bình thêu phượng hoàng trên nền gấm đỏ son, viền cổ vuông thêu chỉ kim tuyến.',
 'Nhật Bình robe with phoenix embroidery on vermilion brocade and a gold-threaded square collar.',
 'Áo Nhật Bình là lễ phục của hoàng hậu, công chúa và các mệnh phụ triều Nguyễn. Tên gọi xuất phát từ phần cổ áo vuông rộng phủ qua vai và ngực. Họa tiết phượng hoàng tượng trưng cho đức hạnh và địa vị cao quý của người phụ nữ trong cung đình.',
 'The Nhật Bình robe was the ceremonial dress of Nguyễn-dynasty queens, princesses and noblewomen. Its name refers to the broad square collar draped over the shoulders and chest. The phoenix motif symbolises virtue and high rank for women of the court.',
 4500000, 'nu', 1, 600000, 1),
(2, 2, 3, 'ao-tac-nam-the-uyen', 'Áo Tấc Nam Thể Uyên', 'Men''s Áo Tấc – Thể Uyên',
 'Áo Tấc nam tay rộng, dệt hoa văn chữ Thọ, phù hợp lễ cưới và lễ hội.',
 'Wide-sleeved men''s Áo Tấc woven with longevity characters, ideal for weddings and festivals.',
 'Áo Tấc là áo lễ thân dài, tay rộng, được mặc trong các dịp trang trọng thời Nguyễn. Nam giới thường phối áo Tấc với khăn vấn và quần dài, thể hiện sự chỉn chu và tôn kính trong nghi lễ.',
 'The Áo Tấc is a long, wide-sleeved formal robe worn on solemn occasions in the Nguyễn era. Men paired it with a wrapped turban and long trousers, expressing propriety and respect during rituals.',
 3200000, 'nam', 1, 500000, 1),
(3, 2, 2, 'ao-tac-nu-thanh-thuy', 'Áo Tấc Nữ Thanh Thủy', 'Women''s Áo Tấc – Thanh Thủy',
 'Áo Tấc nữ màu xanh ngọc, thêu hoa sen ở gấu và tay áo.',
 'Jade-green women''s Áo Tấc with lotus embroidery at the hem and sleeves.',
 'Kiểu áo tay rộng thân dài xuất hiện trong nhiều tư liệu tranh vẽ thời Lê. Họa tiết hoa sen gắn liền với văn hóa Phật giáo và vẻ thanh cao của người Việt.',
 'Long, wide-sleeved robes appear in many paintings from the Lê era. The lotus motif is tied to Buddhist culture and the Vietnamese ideal of purity.',
 3500000, 'nu', 1, 500000, 0),
(4, 3, 2, 'ao-giao-linh-truc-lam', 'Áo Giao Lĩnh Trúc Lâm', 'Trúc Lâm Giao Lĩnh Robe',
 'Áo Giao Lĩnh cổ chéo vạt, chất đũi mộc mạc, phom rộng thoải mái.',
 'Cross-collared Giao Lĩnh robe in rustic raw silk with a relaxed fit.',
 'Áo Giao Lĩnh có hai vạt áo chéo nhau tạo thành cổ chéo, là kiểu áo phổ biến của người Việt qua nhiều triều đại. Năm 1744, chúa Nguyễn Phúc Khoát cải cách y phục ở Đàng Trong, nhưng ở Đàng Ngoài áo Giao Lĩnh vẫn được mặc rộng rãi đến đầu thế kỷ XIX.',
 'The Giao Lĩnh robe has two front panels that cross to form a diagonal collar and was worn by Vietnamese people across many dynasties. Lord Nguyễn Phúc Khoát reformed dress in the South in 1744, but in the North the Giao Lĩnh remained common into the early 19th century.',
 2800000, 'unisex', 1, 400000, 1),
(5, 3, 1, 'ao-giao-linh-thang-long', 'Áo Giao Lĩnh Thăng Long', 'Thăng Long Giao Lĩnh Robe',
 'Áo Giao Lĩnh nam phong cách Lý – Trần, viền cổ tương phản.',
 'Men''s Giao Lĩnh robe in Lý – Trần style with a contrasting collar trim.',
 'Dưới thời Lý – Trần, áo cổ chéo là trang phục quen thuộc của tầng lớp quý tộc và quan lại. Thiết kế lấy cảm hứng từ tư liệu tượng và phù điêu thời kỳ này.',
 'During the Lý – Trần period, cross-collared robes were familiar attire among nobles and officials. This design draws on statues and reliefs from the era.',
 2600000, 'nam', 1, 400000, 1),
(6, 4, 3, 'ao-ngu-than-hue', 'Áo Ngũ Thân Huế', 'Huế Ngũ Thân Robe',
 'Áo Ngũ Thân năm thân, cổ đứng, cài khuy bên phải – dáng áo xứ Huế.',
 'Five-panel robe with a standing collar and right-side buttons, in the Huế style.',
 'Áo Ngũ Thân ra đời sau cải cách y phục năm 1744 của chúa Nguyễn Phúc Khoát. Năm thân áo được cho là tượng trưng cho Ngũ thường: Nhân, Nghĩa, Lễ, Trí, Tín. Đây là tiền thân trực tiếp của chiếc áo dài hiện đại.',
 'The Ngũ Thân robe emerged after Lord Nguyễn Phúc Khoát''s dress reform of 1744. Its five panels are said to represent the five Confucian virtues. It is the direct forerunner of the modern áo dài.',
 1900000, 'nu', 1, 300000, 0),
(7, 6, NULL, 'man-dai-nhat-binh', 'Mấn Đại Nhật Bình', 'Nhật Bình Headdress',
 'Mấn vấn kết hoa văn, phối cùng áo Nhật Bình.',
 'Wrapped headdress with ornaments, designed to pair with the Nhật Bình robe.',
 'Mấn và khăn vấn là phần không thể thiếu của bộ lễ phục nữ. Hình dáng và màu sắc mấn cho biết địa vị của người đội.',
 'Headdresses and wrapped turbans were essential to women''s ceremonial attire; their shape and colour indicated the wearer''s rank.',
 450000, 'nu', 0, 0, 0),
(8, 7, NULL, 'quat-lua-ve-sen', 'Quạt Lụa Vẽ Sen', 'Hand-painted Lotus Silk Fan',
 'Quạt xếp nan tre, mặt lụa vẽ tay hoa sen.',
 'Bamboo folding fan with a hand-painted lotus on silk.',
 'Quạt là phụ kiện tao nhã của văn nhân và quý nữ xưa, thường dùng trong các dịp lễ hội và thưởng ngoạn.',
 'Fans were refined accessories for scholars and noblewomen, carried at festivals and leisure gatherings.',
 350000, 'unisex', 0, 0, 0),
(9, 8, NULL, 'hai-theu-phuong', 'Hài Thêu Phượng', 'Phoenix-embroidered Slippers',
 'Hài mũi cong thêu phượng, đế mềm.',
 'Curved-toe slippers with phoenix embroidery and a soft sole.',
 'Hài mũi cong là kiểu giày truyền thống đi cùng lễ phục, thể hiện sự duyên dáng trong từng bước chân.',
 'Curved-toe slippers were the traditional footwear for ceremonial dress, adding grace to each step.',
 550000, 'nu', 0, 0, 0);

-- ---------- Images (front / back / detail) ----------
INSERT INTO product_images (product_id, url, view_type, sort_order)
SELECT p.id, CONCAT('placeholder:', v.label), v.view_type, v.sort_order
FROM products p
JOIN (SELECT 'front' view_type, 'Mặt trước' label, 0 sort_order
      UNION ALL SELECT 'back', 'Mặt sau', 1
      UNION ALL SELECT 'detail', 'Chi tiết thêu', 2) v
WHERE p.id <= 6 OR v.view_type = 'front';

-- ---------- Colours available per product ----------
CREATE TEMPORARY TABLE tmp_colors (
  product_id INT UNSIGNED, color_vi VARCHAR(60), color_en VARCHAR(60), color_hex CHAR(7), code VARCHAR(10)
);
INSERT INTO tmp_colors VALUES
(1, 'Đỏ son',     'Vermilion',   '#9E2A2B', 'DO'),
(1, 'Vàng hoàng', 'Imperial gold','#C59B27', 'VA'),
(2, 'Xanh lam',   'Indigo',      '#2E4A7D', 'XL'),
(2, 'Đỏ son',     'Vermilion',   '#9E2A2B', 'DO'),
(3, 'Xanh ngọc',  'Jade',        '#3E7C6B', 'XN'),
(4, 'Nâu gụ',     'Mahogany',    '#6B3A2A', 'NA'),
(4, 'Trắng ngà',  'Ivory',       '#EDE3D1', 'TR'),
(5, 'Đen tuyền',  'Ink black',   '#2B2623', 'DE'),
(6, 'Tím Huế',    'Huế purple',  '#5B2A5E', 'TI'),
(6, 'Hồng đào',   'Peach',       '#D98C7A', 'HO'),
(7, 'Đen tuyền',  'Ink black',   '#2B2623', 'DE'),
(8, 'Trắng ngà',  'Ivory',       '#EDE3D1', 'TR'),
(9, 'Đỏ son',     'Vermilion',   '#9E2A2B', 'DO');

-- Garments: every size × every fabric × each colour
INSERT INTO product_variants (product_id, size, fabric_id, color_vi, color_en, color_hex, stock, sku)
SELECT c.product_id, s.size, f.id, c.color_vi, c.color_en, c.color_hex,
       3 + ((c.product_id + f.id + LENGTH(s.size)) % 6),
       CONCAT('CP', LPAD(c.product_id, 3, '0'), '-', s.size, '-', UPPER(LEFT(f.slug, 3)), '-', c.code)
FROM tmp_colors c
JOIN (SELECT 'S' size UNION ALL SELECT 'M' UNION ALL SELECT 'L' UNION ALL SELECT 'XL') s
JOIN fabrics f
WHERE c.product_id <= 6;

-- Accessories: one size, no fabric choice
INSERT INTO product_variants (product_id, size, fabric_id, color_vi, color_en, color_hex, stock, sku)
SELECT c.product_id, 'FREE', NULL, c.color_vi, c.color_en, c.color_hex, 15,
       CONCAT('CP', LPAD(c.product_id, 3, '0'), '-FREE-', c.code)
FROM tmp_colors c
WHERE c.product_id > 6;

DROP TEMPORARY TABLE tmp_colors;
