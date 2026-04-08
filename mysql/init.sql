CREATE TABLE IF NOT EXISTS phong_tro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_phong VARCHAR(50) NOT NULL,
    gia_tien INT NOT NULL,
    trang_thai VARCHAR(50) NOT NULL
);

INSERT INTO phong_tro (so_phong, gia_tien, trang_thai) VALUES
('P101', 2500000, 'Đang thuê'),
('P102', 3000000, 'Trống'),
('P201', 2800000, 'Đang thuê');

