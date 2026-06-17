DROP DATABASE IF EXISTS sip_serve;
CREATE DATABASE sip_serve
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sip_serve;

--  TABEL 1: kategori
CREATE TABLE kategori (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(60)  NOT NULL,
  tipe        ENUM('cocktail','mocktail','coffee','tea','smoothie') NOT NULL,
  deskripsi   TEXT,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--  TABEL 2: bahan
CREATE TABLE bahan (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama            VARCHAR(100) NOT NULL,
  satuan          ENUM('ml','gr','pcs','sdm','sdt') NOT NULL DEFAULT 'ml',
  stok            DECIMAL(10,2) NOT NULL DEFAULT 0,
  harga_per_unit  DECIMAL(10,2) NOT NULL DEFAULT 0,
  kategori_bahan  VARCHAR(60),
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--  TABEL 3: users
CREATE TABLE users (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('admin','barista') NOT NULL DEFAULT 'barista',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--  TABEL 4: resep
CREATE TABLE resep (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama              VARCHAR(150) NOT NULL,
  kategori_id       INT UNSIGNED NOT NULL,
  deskripsi         TEXT,
  langkah           TEXT,
  waktu_buat        TINYINT UNSIGNED DEFAULT 5 COMMENT 'dalam menit',
  tingkat_kesulitan ENUM('mudah','sedang','sulit') NOT NULL DEFAULT 'mudah',
  gambar            VARCHAR(255),
  created_by        INT UNSIGNED,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by)  REFERENCES users(id)    ON DELETE SET NULL
);

--  TABEL 5: resep_bahan  (tabel pivot — relasi N:M)
CREATE TABLE resep_bahan (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resep_id  INT UNSIGNED NOT NULL,
  bahan_id  INT UNSIGNED NOT NULL,
  jumlah    DECIMAL(10,2) NOT NULL,
  catatan   VARCHAR(200),
  UNIQUE KEY uq_resep_bahan (resep_id, bahan_id),
  FOREIGN KEY (resep_id) REFERENCES resep(id) ON DELETE CASCADE,
  FOREIGN KEY (bahan_id) REFERENCES bahan(id) ON DELETE RESTRICT
);

--  TABEL LOG (untuk trigger)
CREATE TABLE log_resep (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resep_id    INT UNSIGNED NOT NULL,
  aksi        ENUM('INSERT','UPDATE','DELETE') NOT NULL,
  data_lama   TEXT,
  data_baru   TEXT,
  diubah_oleh INT UNSIGNED,
  diubah_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Query Kompleks 1
SELECT
  r.id,
  r.nama,
  r.tingkat_kesulitan,
  r.waktu_buat,
  k.nama                   AS nama_kategori,
  k.tipe                   AS tipe_minuman,
  u.nama                   AS pembuat,
  hitung_biaya_resep(r.id) AS estimasi_biaya
FROM resep r
JOIN  kategori k ON r.kategori_id = k.id
LEFT JOIN users u ON r.created_by  = u.id
WHERE r.nama LIKE '%lemon%'   
ORDER BY r.created_at DESC;

-- Query Kompleks 2
SELECT
  k.id,
  k.nama,
  k.tipe,
  k.deskripsi,
  COUNT(r.id) AS jumlah_resep
FROM kategori k
LEFT JOIN resep r ON r.kategori_id = k.id
GROUP BY k.id, k.nama, k.tipe, k.deskripsi, k.created_at
ORDER BY k.nama ASC;

-- query 3
SELECT
  rb.bahan_id,
  rb.jumlah,
  b.nama,
  b.stok,
  b.satuan,
  cek_stok_cukup(b.id, rb.jumlah) AS stok_cukup
FROM resep_bahan rb
JOIN bahan b ON rb.bahan_id = b.id
WHERE rb.resep_id = 1;

--  VIEW 1: view_resep_lengkap
CREATE VIEW view_resep_lengkap AS
SELECT
  r.id,
  r.nama                  AS nama_resep,
  k.nama                  AS kategori,
  k.tipe                  AS tipe_minuman,
  r.tingkat_kesulitan,
  r.waktu_buat,
  r.deskripsi,
  r.gambar,
  u.nama                  AS dibuat_oleh,
  r.created_at
FROM resep r
JOIN kategori k ON r.kategori_id = k.id
LEFT JOIN users u ON r.created_by = u.id;

--  VIEW 2: view_stok_bahan
CREATE VIEW view_stok_bahan AS
SELECT
  b.id,
  b.nama,
  b.satuan,
  b.stok,
  b.harga_per_unit,
  CASE
    WHEN b.stok = 0        THEN 'habis'
    WHEN b.stok < 50       THEN 'rendah'
    ELSE                        'aman'
  END AS status_stok,
  b.kategori_bahan
FROM bahan b;

--  FUNCTION 1: hitung_biaya_resep(resep_id)
DELIMITER $$

CREATE FUNCTION hitung_biaya_resep(p_resep_id INT UNSIGNED)
RETURNS DECIMAL(12,2)
READS SQL DATA
DETERMINISTIC
BEGIN
  DECLARE total DECIMAL(12,2) DEFAULT 0;

  SELECT COALESCE(SUM(rb.jumlah * b.harga_per_unit), 0)
  INTO   total
  FROM   resep_bahan rb
  JOIN   bahan b ON rb.bahan_id = b.id
  WHERE  rb.resep_id = p_resep_id;

  RETURN total;
END$$

--  FUNCTION 2: cek_stok_cukup(bahan_id, jumlah_butuh)
CREATE FUNCTION cek_stok_cukup(p_bahan_id INT UNSIGNED, p_jumlah DECIMAL(10,2))
RETURNS TINYINT(1)
READS SQL DATA
DETERMINISTIC
BEGIN
  DECLARE stok_ada DECIMAL(10,2) DEFAULT 0;

  SELECT stok INTO stok_ada FROM bahan WHERE id = p_bahan_id;

  IF stok_ada >= p_jumlah THEN
    RETURN 1;
  ELSE
    RETURN 0;
  END IF;
END$$

--  TRIGGER 1: kurangi_stok_setelah_resep_dibuat
CREATE TRIGGER kurangi_stok_setelah_resep_dibuat
AFTER INSERT ON resep_bahan
FOR EACH ROW
BEGIN
  UPDATE bahan
  SET stok = stok - NEW.jumlah
  WHERE id = NEW.bahan_id;
END$$

--  TRIGGER 2: log_perubahan_resep
CREATE TRIGGER log_perubahan_resep
AFTER UPDATE ON resep
FOR EACH ROW
BEGIN
  INSERT INTO log_resep (resep_id, aksi, data_lama, data_baru)
  VALUES (
    OLD.id,
    'UPDATE',
    CONCAT('nama=', OLD.nama, ' | kategori_id=', OLD.kategori_id, ' | kesulitan=', OLD.tingkat_kesulitan),
    CONCAT('nama=', NEW.nama, ' | kategori_id=', NEW.kategori_id, ' | kesulitan=', NEW.tingkat_kesulitan)
  );
END$$

DELIMITER ;


--  DATA DUMMY

-- Kategori
INSERT INTO kategori (nama, tipe, deskripsi) VALUES
('Signature Cocktail',  'cocktail',  'Minuman beralkohol kreasi sendiri'),
('Mocktail Segar',      'mocktail',  'Minuman non-alkohol dengan buah segar'),
('Espresso Drinks',     'coffee',    'Minuman berbasis espresso'),
('Herbal Tea Blend',    'tea',       'Racikan teh herbal'),
('Smoothie Sehat',      'smoothie',  'Smoothie buah dan sayuran');

-- Bahan
INSERT INTO bahan (nama, satuan, stok, harga_per_unit, kategori_bahan) VALUES
('Espresso',        'ml',  500,   500,   'coffee'),
('Susu Full Cream', 'ml',  2000,  50,    'dairy'),
('Simple Syrup',    'ml',  800,   200,   'sweetener'),
('Jus Lemon',       'ml',  600,   300,   'juice'),
('Mint Segar',      'gr',  200,   100,   'herb'),
('Strawberry',      'gr',  500,   150,   'fruit'),
('Es Batu',         'pcs', 999,   10,    'other'),
('Blue Curacao',    'ml',  400,   1500,  'syrup'),
('Soda Water',      'ml',  1500,  80,    'soda'),
('Madu',            'sdm', 300,   250,   'sweetener');

-- User (password: admin123 — di-hash dengan password_hash PHP, ini placeholder)
INSERT INTO users (nama, email, password, role) VALUES
('Admin Utama',  'admin@sipserve.com',   '$2y$10$placeholder_hash_admin',   'admin'),
('Budi Barista', 'budi@sipserve.com',    '$2y$10$placeholder_hash_budi',    'barista'),
('Sari Mixologist', 'sari@sipserve.com', '$2y$10$placeholder_hash_sari',    'barista');

-- Resep
INSERT INTO resep (nama, kategori_id, deskripsi, langkah, waktu_buat, tingkat_kesulitan, created_by) VALUES
('Lemon Mint Cooler',  2, 'Mocktail segar perpaduan lemon dan mint.',
 '1. Peras lemon\n2. Masukkan mint\n3. Tambah es dan soda', 5, 'mudah', 2),

('Caffe Latte',        3, 'Espresso klasik dengan susu steamed.',
 '1. Buat espresso\n2. Steam susu\n3. Tuang perlahan', 7, 'sedang', 2),

('Strawberry Smash',   2, 'Mocktail merah segar dari stroberi.',
 '1. Hancurkan stroberi\n2. Tambah syrup dan soda\n3. Garnish mint', 8, 'mudah', 3),

('Blue Ocean Mocktail', 2, 'Tampilan biru cantik dengan rasa manis segar.',
 '1. Masukkan es\n2. Tuang blue curacao\n3. Tambah soda dan garnish', 6, 'mudah', 3),

('Honey Lemon Tea',    4, 'Teh herbal hangat dengan madu dan lemon.',
 '1. Seduh teh\n2. Tambah madu\n3. Peras lemon secukupnya', 10, 'mudah', 1);

-- Resep-Bahan (pivot)
INSERT INTO resep_bahan (resep_id, bahan_id, jumlah, catatan) VALUES
-- Lemon Mint Cooler
(1, 4, 60,  'peras segar'),
(1, 5, 10,  'daun utuh'),
(1, 3, 30,  NULL),
(1, 7, 5,   NULL),
(1, 9, 100, NULL),
-- Caffe Latte
(2, 1, 60,  'double shot'),
(2, 2, 180, 'steamed 65°C'),
(2, 3, 15,  'opsional'),
-- Strawberry Smash
(3, 6, 80,  'segar, bukan frozen'),
(3, 3, 20,  NULL),
(3, 9, 120, NULL),
(3, 5, 5,   'garnish'),
-- Blue Ocean Mocktail
(4, 8, 45,  NULL),
(4, 9, 150, NULL),
(4, 7, 6,   NULL),
-- Honey Lemon Tea
(5, 10, 2,  'sdm madu asli'),
(5, 4,  30, 'setengah lemon'),
(5, 7,  3,  'opsional');

