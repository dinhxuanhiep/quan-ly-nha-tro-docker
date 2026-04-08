<?php
session_start();

$host = 'db';
$db   = 'nhatro_db';
$user = 'admin';
$pass = 'password123';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // BẢNG USERS TÀI KHOẢN
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // BẢNG PHÒNG TRỌ V5
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS phong_tro_v5 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            so_phong VARCHAR(50) NOT NULL,
            dia_chi VARCHAR(255) NOT NULL,
            gia_tien INT NOT NULL,
            trang_thai VARCHAR(50) NOT NULL,
            dien_tich INT NOT NULL,
            tien_ich VARCHAR(255) NOT NULL,
            hinh_anh VARCHAR(500) NOT NULL,
            ten_chu_thue VARCHAR(100) DEFAULT '',
            ngay_sua_xong DATE NULL,
            ten_chu_tro VARCHAR(100) NOT NULL,
            sdt_chu_tro VARCHAR(20) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $check = $pdo->query("SELECT COUNT(*) FROM phong_tro_v5")->fetchColumn();
    if ($check == 0) {
        $pdo->exec("INSERT INTO phong_tro_v5 (so_phong, dia_chi, gia_tien, trang_thai, dien_tich, tien_ich, hinh_anh, ten_chu_thue, ngay_sua_xong, ten_chu_tro, sdt_chu_tro) VALUES 
        ('P101', 'Số 10, Ngõ 20 Cầu Giấy', 2500000, 'Đang thuê', 20, 'Điều hòa, Nóng lạnh, Giường tủ', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=600&q=80', 'Nguyễn Văn A', NULL, 'Đinh Xuân Hiệp', '0912345678'),
        ('P102', '15 Lê Thanh Nghị, Hai Bà Trưng', 3000000, 'Trống', 25, 'Điều hòa, Nóng lạnh, Máy giặt, Ban công', 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=600&q=80', '', NULL, 'Đinh Xuân Hiệp', '0988777666')");
    }
} catch (\PDOException $e) {
    die("Lỗi kết nối DB: " . $e->getMessage());
}

$msg = '';
$error = '';
// KIỂM TRA QUYỀN CHỦ TRỌ
$is_landlord = (isset($_SESSION['role']) && $_SESSION['role'] === 'chu_tro');

// ==========================================
// 1. XỬ LÝ ĐĂNG KÝ, ĐĂNG NHẬP, ĐĂNG XUẤT
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'register') {
        $u = trim($_POST['username']);
        $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $r = $_POST['role'];
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$u, $p, $r]);
            $msg = "🎉 Đăng ký thành công! Vui lòng đăng nhập.";
        } catch (Exception $e) {
            $error = "❌ Tên đăng nhập đã tồn tại!";
        }
    } 
    elseif ($_POST['action'] === 'login') {
        $u = trim($_POST['username']);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$u]);
        $userData = $stmt->fetch();
        if ($userData && password_verify($_POST['password'], $userData['password'])) {
            $_SESSION['user'] = $userData['username'];
            $_SESSION['role'] = $userData['role'];
            header("Location: /"); exit;
        } else {
            $error = "❌ Sai tài khoản hoặc mật khẩu!";
        }
    } 
    elseif ($_POST['action'] === 'logout') {
        session_destroy();
        header("Location: /"); exit;
    }
    
    // ==========================================
    // 2. XỬ LÝ THÊM PHÒNG TRỌ (Quyền Chủ Trọ)
    // ==========================================
    elseif ($_POST['action'] === 'add_room' && $is_landlord) {
        $ten_khach = ($_POST['trang_thai'] === 'Đang thuê') ? $_POST['ten_chu_thue'] : ''; 
        $ngay_sua  = ($_POST['trang_thai'] === 'Đang sửa chữa' && !empty($_POST['ngay_sua_xong'])) ? $_POST['ngay_sua_xong'] : NULL;
        
        $stmt = $pdo->prepare('INSERT INTO phong_tro_v5 (so_phong, dia_chi, gia_tien, trang_thai, dien_tich, tien_ich, hinh_anh, ten_chu_thue, ngay_sua_xong, ten_chu_tro, sdt_chu_tro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['so_phong'], $_POST['dia_chi'], $_POST['gia_tien'], $_POST['trang_thai'], 
            $_POST['dien_tich'], $_POST['tien_ich'], $_POST['hinh_anh'], $ten_khach, $ngay_sua, $_POST['ten_chu_tro'], $_POST['sdt_chu_tro']
        ]);
        header("Location: /"); exit;
    }

    // ==========================================
    // 3. XỬ LÝ SỬA THÔNG TIN PHÒNG (Quyền Chủ Trọ)
    // ==========================================
    elseif ($_POST['action'] === 'edit_room' && $is_landlord) {
        $id = $_POST['room_id'];
        $ten_khach = ($_POST['trang_thai'] === 'Đang thuê') ? $_POST['ten_chu_thue'] : ''; 
        $ngay_sua  = ($_POST['trang_thai'] === 'Đang sửa chữa' && !empty($_POST['ngay_sua_xong'])) ? $_POST['ngay_sua_xong'] : NULL;
        
        $stmt = $pdo->prepare('UPDATE phong_tro_v5 SET so_phong=?, dia_chi=?, gia_tien=?, trang_thai=?, dien_tich=?, tien_ich=?, hinh_anh=?, ten_chu_thue=?, ngay_sua_xong=?, ten_chu_tro=?, sdt_chu_tro=? WHERE id=?');
        $stmt->execute([
            $_POST['so_phong'], $_POST['dia_chi'], $_POST['gia_tien'], $_POST['trang_thai'], 
            $_POST['dien_tich'], $_POST['tien_ich'], $_POST['hinh_anh'], $ten_khach, $ngay_sua, $_POST['ten_chu_tro'], $_POST['sdt_chu_tro'], $id
        ]);
        header("Location: /"); exit;
    }
}
// ==========================================
// 4. LỌC KẾT QUẢ TÌM KIẾM (ĐÃ KHÔI PHỤC FULL 6 TIÊU CHÍ)
// ==========================================
$search_name      = $_GET['search_name'] ?? '';
$search_price     = $_GET['search_price'] ?? '';
$search_address   = $_GET['search_address'] ?? '';
$search_status    = $_GET['search_status'] ?? 'Tất cả';
$search_area      = $_GET['search_area'] ?? '';
$search_amenities = $_GET['search_amenities'] ?? '';

$query = "SELECT * FROM phong_tro_v5 WHERE 1=1";
$params = [];
if ($search_name !== '') { $query .= " AND so_phong LIKE ?"; $params[] = "%$search_name%"; }
if ($search_price !== '') { $query .= " AND gia_tien <= ?"; $params[] = $search_price; }
if ($search_address !== '') { $query .= " AND dia_chi LIKE ?"; $params[] = "%$search_address%"; }
if ($search_status !== 'Tất cả') { $query .= " AND trang_thai = ?"; $params[] = $search_status; }
if ($search_area !== '') { $query .= " AND dien_tich >= ?"; $params[] = $search_area; }
if ($search_amenities !== '') { $query .= " AND tien_ich LIKE ?"; $params[] = "%$search_amenities%"; }

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Lý Nhà Trọ VIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 30px; padding-bottom: 50px; }
        .card { border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: none; }
        .card-header { font-weight: bold; border-radius: 12px 12px 0 0 !important; }
        .room-link { color: #0d6efd; cursor: pointer; text-decoration: underline; text-underline-offset: 4px; }
        .modal-img { width: 100%; height: 250px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border">
            <h2 class="text-primary m-0 fw-bold">🌟 NHÀ TRỌ VIP</h2>
            <div>
                <?php if(isset($_SESSION['user'])): ?>
                    <span class="me-3 fs-6">Xin chào, <span class="fw-bold text-success"><?= htmlspecialchars($_SESSION['user']) ?></span> <span class="badge bg-secondary"><?= $_SESSION['role'] === 'chu_tro' ? 'Chủ Trọ' : 'Khách Hàng' ?></span></span>
                    <form method="POST" action="/" class="d-inline"><input type="hidden" name="action" value="logout"><button class="btn btn-sm btn-outline-danger">Đăng Xuất</button></form>
                <?php else: ?>
                    <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#loginModal">🔑 Đăng Nhập</button>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#registerModal">📝 Đăng Ký</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if($msg): ?><div class="alert alert-success alert-dismissible"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger alert-dismissible"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <div class="row mb-4">
            <div class="<?= $is_landlord ? 'col-md-5' : 'col-md-8 mx-auto' ?> mb-3">
                <div class="card h-100 border-success">
                    <div class="card-header bg-success text-white">🔍 Bộ Lọc Tìm Kiếm</div>
                    <div class="card-body">
                        <form method="GET" action="/">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label text-muted small">Tên / Số phòng:</label>
                                    <input type="text" name="search_name" class="form-control form-control-sm" value="<?= htmlspecialchars($search_name) ?>">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label text-muted small">Mức giá tối đa:</label>
                                    <input type="number" name="search_price" class="form-control form-control-sm" value="<?= htmlspecialchars($search_price) ?>">
                                </div>
                                <div class="col-md-12 mb-2">
                                    <label class="form-label text-muted small">Khu vực / Địa chỉ:</label>
                                    <input type="text" name="search_address" class="form-control form-control-sm" value="<?= htmlspecialchars($search_address) ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted small">Trạng thái:</label>
                                    <select name="search_status" class="form-select form-select-sm">
                                        <option value="Tất cả" <?= $search_status == 'Tất cả' ? 'selected' : '' ?>>Tất cả</option>
                                        <option value="Trống" <?= $search_status == 'Trống' ? 'selected' : '' ?>>Trống</option>
                                        <option value="Đang thuê" <?= $search_status == 'Đang thuê' ? 'selected' : '' ?>>Đang thuê</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted small">Diện tích (>= m²):</label>
                                    <input type="number" name="search_area" class="form-control form-control-sm" value="<?= htmlspecialchars($search_area) ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted small">Tiện ích:</label>
                                    <input type="text" name="search_amenities" class="form-control form-control-sm" value="<?= htmlspecialchars($search_amenities) ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100 mb-2">Tìm Kiếm</button>
                            <a href="/" class="btn btn-outline-secondary btn-sm w-100">Xóa Lọc</a>
                        </form>
                    </div>
                </div>
            </div>
<?php if ($is_landlord): ?>
            <div class="col-md-7 mb-3">
                <div class="card h-100 border-primary">
                    <div class="card-header bg-primary text-white">➕ Thêm Phòng Mới (Quyền Chủ Trọ)</div>
                    <div class="card-body">
                        <form method="POST" action="/">
                            <input type="hidden" name="action" value="add_room">
                            <div class="row">
                                <div class="col-md-4 mb-2"><label class="form-label text-muted small">Số phòng</label><input type="text" name="so_phong" class="form-control form-control-sm" required></div>
                                <div class="col-md-4 mb-2"><label class="form-label text-muted small">Giá tiền (VNĐ)</label><input type="number" name="gia_tien" class="form-control form-control-sm" required></div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label text-muted small">Trạng thái</label>
                                    <select name="trang_thai" id="add_trang_thai" class="form-select form-select-sm" onchange="toggleFields('add')">
                                        <option value="Trống">Trống</option><option value="Đang thuê">Đang thuê</option><option value="Đang sửa chữa">Đang sửa chữa</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-2"><label class="form-label text-muted small">Địa chỉ phòng trọ</label><input type="text" name="dia_chi" class="form-control form-control-sm" required></div>
                                
                                <div class="col-md-6 mb-2"><label class="form-label text-muted small text-primary fw-bold">Tên Chủ Trọ (Liên hệ)</label><input type="text" name="ten_chu_tro" class="form-control form-control-sm border-primary" required></div>
                                <div class="col-md-6 mb-2"><label class="form-label text-muted small text-primary fw-bold">SĐT Chủ Trọ</label><input type="text" name="sdt_chu_tro" class="form-control form-control-sm border-primary" required></div>

                                <div class="col-md-6 mb-2" id="add_div_chu_thue" style="display: none;"><label class="form-label text-primary small fw-bold">Tên Khách Thuê</label><input type="text" name="ten_chu_thue" class="form-control form-control-sm"></div>
                                <div class="col-md-6 mb-2" id="add_div_ngay_sua" style="display: none;"><label class="form-label text-warning small fw-bold">Dự kiến sửa xong</label><input type="date" name="ngay_sua_xong" class="form-control form-control-sm"></div>

                                <div class="col-md-4 mb-2"><label class="form-label text-muted small">Diện tích (m²)</label><input type="number" name="dien_tich" class="form-control form-control-sm" required></div>
                                <div class="col-md-8 mb-2"><label class="form-label text-muted small">Tiện ích</label><input type="text" name="tien_ich" class="form-control form-control-sm" required></div>
                                <div class="col-md-12 mb-3"><label class="form-label text-muted small">Link ảnh (URL)</label><input type="text" name="hinh_anh" class="form-control form-control-sm" required></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">Lưu Thông Tin Phòng</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card border-dark mb-5">
            <div class="card-header bg-dark text-white">📋 Danh Sách Phòng Trọ</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover text-center align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Số Phòng</th>
                                <th>Giá Tiền</th>
                                <th>Trạng Thái</th>
                                <th>Liên Hệ Chủ Trọ</th>
                                <?= $is_landlord ? '<th class="bg-primary text-white">Hành Động</th>' : '' ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rooms) > 0): ?>
                                <?php foreach ($rooms as $row): ?>
                                    <?php 
                                        $tt = strtolower($row['trang_thai']);
                                        $badge = 'bg-secondary';
                                        if (strpos($tt, 'trống') !== false) $badge = 'bg-success';
                                        if (strpos($tt, 'thuê') !== false) $badge = 'bg-warning text-dark';
                                    ?>
                                    <tr>
                                        <td class="fw-bold fs-5"><span class="room-link" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>"><?= htmlspecialchars($row['so_phong']) ?></span></td>
                                        <td class="text-danger fw-bold"><?= number_format($row['gia_tien'], 0, ',', '.') ?> đ</td>
                                        <td><span class="badge <?= $badge ?> px-3 py-2 fs-6"><?= htmlspecialchars($row['trang_thai']) ?></span></td>
                                        <td>
                                            <div class="small fw-bold text-success">👤 <?= htmlspecialchars($row['ten_chu_tro']) ?></div>
                                            <div class="small">📞 <?= htmlspecialchars($row['sdt_chu_tro']) ?></div>
                                        </td>
                                        
                                        <?php if($is_landlord): ?>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">✏️ Sửa</button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>

                                    <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header bg-dark text-white">
                                                    <h5 class="modal-title">Thông Tin - <?= htmlspecialchars($row['so_phong']) ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <img src="<?= htmlspecialchars($row['hinh_anh']) ?>" class="modal-img mb-3">
                                                    
                                                    <div class="alert alert-success p-2 mb-3 text-center border-success">
                                                        <h6 class="m-0 fw-bold">📞 LIÊN HỆ THUÊ PHÒNG</h6>
                                                        <span class="d-block mt-1">Chủ trọ: <strong><?= htmlspecialchars($row['ten_chu_tro']) ?></strong> - Hotline: <strong><?= htmlspecialchars($row['sdt_chu_tro']) ?></strong></span>
                                                    </div>

                                                    <ul class="list-group list-group-flush">
                                                        <li class="list-group-item"><strong>📌 Trạng thái:</strong> <span class="badge <?= $badge ?>"><?= htmlspecialchars($row['trang_thai']) ?></span></li>
                                                        <?php if($is_landlord && $row['trang_thai'] === 'Đang thuê'): ?>
                                                            <li class="list-group-item bg-light text-primary"><strong>👤 Khách đang thuê:</strong> <?= htmlspecialchars($row['ten_chu_thue']) ?></li>
                                                        <?php endif; ?>
                                                        <li class="list-group-item"><strong>💰 Giá thuê:</strong> <span class="text-danger fw-bold"><?= number_format($row['gia_tien'], 0, ',', '.') ?> VNĐ</span></li>
                                                        <li class="list-group-item"><strong>📍 Địa chỉ:</strong> <?= htmlspecialchars($row['dia_chi']) ?></li>
                                                        <li class="list-group-item"><strong>📏 Diện tích:</strong> <?= htmlspecialchars($row['dien_tich']) ?> m² | <strong>🛋️ Tiện ích:</strong> <?= htmlspecialchars($row['tien_ich']) ?></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
<?php if($is_landlord): ?>
                                    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">✏️ Chỉnh Sửa Phòng <?= htmlspecialchars($row['so_phong']) ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST" action="/">
                                                        <input type="hidden" name="action" value="edit_room">
                                                        <input type="hidden" name="room_id" value="<?= $row['id'] ?>">
                                                        <div class="row">
                                                            <div class="col-md-4 mb-2"><label class="form-label small">Số phòng</label><input type="text" name="so_phong" class="form-control" value="<?= htmlspecialchars($row['so_phong']) ?>" required></div>
                                                            <div class="col-md-4 mb-2"><label class="form-label small">Giá tiền</label><input type="number" name="gia_tien" class="form-control" value="<?= htmlspecialchars($row['gia_tien']) ?>" required></div>
                                                            <div class="col-md-4 mb-2">
                                                                <label class="form-label small">Trạng thái</label>
                                                                <select name="trang_thai" id="edit_trang_thai_<?= $row['id'] ?>" class="form-select" onchange="toggleFields('edit_<?= $row['id'] ?>')">
                                                                    <option value="Trống" <?= $row['trang_thai']=='Trống'?'selected':'' ?>>Trống</option>
                                                                    <option value="Đang thuê" <?= $row['trang_thai']=='Đang thuê'?'selected':'' ?>>Đang thuê</option>
                                                                    <option value="Đang sửa chữa" <?= $row['trang_thai']=='Đang sửa chữa'?'selected':'' ?>>Đang sửa</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="col-md-12 mb-2"><label class="form-label small">Địa chỉ</label><input type="text" name="dia_chi" class="form-control" value="<?= htmlspecialchars($row['dia_chi']) ?>" required></div>
                                                            
                                                            <div class="col-md-6 mb-2"><label class="form-label small text-primary fw-bold">Tên Chủ Trọ</label><input type="text" name="ten_chu_tro" class="form-control" value="<?= htmlspecialchars($row['ten_chu_tro']) ?>" required></div>
                                                            <div class="col-md-6 mb-2"><label class="form-label small text-primary fw-bold">SĐT Chủ Trọ</label><input type="text" name="sdt_chu_tro" class="form-control" value="<?= htmlspecialchars($row['sdt_chu_tro']) ?>" required></div>

                                                            <div class="col-md-6 mb-2" id="edit_div_chu_thue_<?= $row['id'] ?>" style="display: <?= $row['trang_thai']=='Đang thuê'?'block':'none' ?>;"><label class="form-label text-primary small fw-bold">Tên Khách Thuê</label><input type="text" name="ten_chu_thue" class="form-control" value="<?= htmlspecialchars($row['ten_chu_thue']) ?>"></div>
                                                            <div class="col-md-6 mb-2" id="edit_div_ngay_sua_<?= $row['id'] ?>" style="display: <?= $row['trang_thai']=='Đang sửa chữa'?'block':'none' ?>;"><label class="form-label text-warning small fw-bold">Dự kiến sửa xong</label><input type="date" name="ngay_sua_xong" class="form-control" value="<?= htmlspecialchars($row['ngay_sua_xong']) ?>"></div>

                                                            <div class="col-md-4 mb-2"><label class="form-label small">Diện tích (m²)</label><input type="number" name="dien_tich" class="form-control" value="<?= htmlspecialchars($row['dien_tich']) ?>" required></div>
                                                            <div class="col-md-8 mb-2"><label class="form-label small">Tiện ích</label><input type="text" name="tien_ich" class="form-control" value="<?= htmlspecialchars($row['tien_ich']) ?>" required></div>
                                                            <div class="col-md-12 mb-3"><label class="form-label small">Link ảnh</label><input type="text" name="hinh_anh" class="form-control" value="<?= htmlspecialchars($row['hinh_anh']) ?>" required></div>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary w-100">Cập Nhật Thay Đổi</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-muted py-4">Không có dữ liệu phù hợp.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="loginModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5 class="modal-title">🔑 Đăng Nhập</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="/"><input type="hidden" name="action" value="login"><div class="mb-3"><label class="form-label">Tài khoản</label><input type="text" name="username" class="form-control" required></div><div class="mb-3"><label class="form-label">Mật khẩu</label><input type="password" name="password" class="form-control" required></div><button type="submit" class="btn btn-primary w-100">Đăng Nhập</button></form></div></div></div></div>
    <div class="modal fade" id="registerModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-success text-white"><h5 class="modal-title">📝 Đăng Ký</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="/"><input type="hidden" name="action" value="register"><div class="mb-3"><label class="form-label">Vai trò</label><select name="role" class="form-select border-success"><option value="khach_hang">Khách hàng</option><option value="chu_tro">Chủ trọ</option></select></div><div class="mb-3"><label class="form-label">Tài khoản</label><input type="text" name="username" class="form-control" required></div><div class="mb-3"><label class="form-label">Mật khẩu</label><input type="password" name="password" class="form-control" required></div><button type="submit" class="btn btn-success w-100">Tạo Tài Khoản</button></form></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hàm đa năng để Ẩn/Hiện ô Nhập Tên Khách / Lịch Sửa cho cả form Add và Edit
        function toggleFields(prefix) {
            var val = document.getElementById(prefix + '_trang_thai').value;
            document.getElementById(prefix + '_div_chu_thue').style.display = (val === 'Đang thuê') ? 'block' : 'none';
            document.getElementById(prefix + '_div_ngay_sua').style.display = (val === 'Đang sửa chữa') ? 'block' : 'none';
        }
    </script>
</body>
</html>
