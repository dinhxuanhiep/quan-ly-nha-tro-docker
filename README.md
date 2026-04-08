# 🌟 Hệ Thống Quản Lý Nhà Trọ VIP (Docker Edition)

Dự án cuối kỳ môn **Điện toán đám mây**. Hệ thống quản lý nhà trọ được đóng gói bằng Docker và triển khai trên Google Cloud Platform.

## 🚀 Tính năng chính
- **Phân quyền người dùng:** Chủ trọ (đăng phòng, sửa phòng) và Khách hàng (tìm kiếm phòng).
- **Bộ lọc nâng cao:** Tìm kiếm theo 6 tiêu chí (giá, diện tích, địa chỉ, tiện ích...).
- **Giao diện hiện đại:** Sử dụng Bootstrap 5 và Modal Popup.

## 🛠 Công nghệ sử dụng
- [cite_start]**Docker & Docker Compose:** Đóng gói Microservices.
- **Web Server:** Nginx (với cấu hình Friendly URL).
- **Ngôn ngữ:** PHP 8.2 & PDO (Ngăn chặn SQL Injection).
- **Cơ sở dữ liệu:** MySQL 8.0.

## 📦 Cách cài đặt nhanh
1. Tải mã nguồn về máy đã cài sẵn Docker.
2. Chạy lệnh:
   ```bash
   docker-compose up -d --build

