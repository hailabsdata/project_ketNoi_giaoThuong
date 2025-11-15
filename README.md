# KetNoiGiaoThuong - Trade Connection Platform API

API Backend cho nền tảng kết nối giao thương doanh nghiệp, xây dựng bằng Laravel 9 với JWT Authentication.

## ✨ Tính năng

- **Authentication (JWT)**
  - Đăng ký tài khoản, xác thực email bằng OTP.
  - Đăng nhập với Access Token + Refresh Token.
  - Làm mới access token, quên mật khẩu, đặt lại mật khẩu.
  - Đăng xuất, thu hồi refresh token.

- **Identity Management (KYC)**
  - Người dùng xem/cập nhật hồ sơ cá nhân/doanh nghiệp.
  - Gửi yêu cầu xác minh danh tính (đính kèm tài liệu).
  - Xem lịch sử các yêu cầu xác minh của chính mình.
  - Admin duyệt / từ chối yêu cầu xác minh.
  - Admin xem danh sách & chi tiết mọi yêu cầu xác minh (có filter, phân trang).

- **Login History**
  - Ghi log mỗi lần đăng nhập (thành công/thất bại) kèm IP, User-Agent.
  - Người dùng xem lịch sử đăng nhập của chính mình.
  - Admin xem lịch sử đăng nhập của tất cả user, hoặc theo từng user.

- **Moderation**
  - Người dùng gửi báo cáo vi phạm (user/bài viết).
  - Admin xem, xử lý, và quản lý các báo cáo.

### 🔒 Security Features

-   JWT Authentication với tymon/jwt-auth
-   OTP 6 digits với thời hạn 10 phút
-   Rate limiting (5 requests/phút) cho endpoints nhạy cảm
-   Role-based authorization (Admin middleware)
-   Password hashing với bcrypt
-   Email verification required
---

## 🛠 Công nghệ sử dụng

-   **Framework**: Laravel 9
-   **PHP**: ^8.0.2
-   **Database**: MySQL
-   **Authentication**: JWT (tymon/jwt-auth ^2.2)
-   **Email**: Gmail SMTP
-   **API Documentation**: L5-Swagger (OpenAPI 3.0)

---

## 📦 Yêu cầu hệ thống

-   PHP >= 8.0.2
-   Composer
-   MySQL >= 5.7
-   Node.js & NPM (optional)

---

## 🚀 Cài đặt

### 1. Clone repository

```bash
git clone https://github.com/nguyentrong3114/KetNoiGiaoThuong-Server.git
cd KetNoiGiaoThuong-Server
```

### 2. Install dependencies

```bash
composer install
```

### 3. Copy file môi trường

```bash
cp .env.example .env
```

### 4. Generate keys

```bash
php artisan key:generate
php artisan jwt:secret
```

---

## ⚙️ Cấu hình

### Database Configuration

Mở file `.env` và cấu hình database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tradehub
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Email Configuration (Gmail SMTP)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Lưu ý**: Để sử dụng Gmail SMTP:

1. Bật xác thực 2 bước cho Gmail
2. Tạo App Password tại: https://myaccount.google.com/apppasswords
3. Sử dụng App Password thay vì mật khẩu Gmail

---

## 🏃 Chạy dự án

### 1. Chạy migrations

```bash
php artisan migrate
```

### 2. Generate Swagger documentation

```bash
php artisan l5-swagger:generate
```

### 3. Start development server

```bash
php artisan serve
```

Server chạy tại: `http://127.0.0.1:8000`

---

## 📚 API Documentation

### Swagger UI

Truy cập: **http://127.0.0.1:8000/api/documentation**

## Authentication Endpoints

| Method | Endpoint                            | Mô tả                                           | Rate limit |
| ------ | ----------------------------------- | ----------------------------------------------- | ---------- |
| POST   | `/api/auth/register`                | Đăng ký tài khoản                               | 5/min      |
| POST   | `/api/auth/verify-email`            | Xác thực email bằng OTP                         | 5/min      |
| POST   | `/api/auth/resend-verification-otp` | Gửi lại OTP xác thực email                      | 5/min      |
| POST   | `/api/auth/login`                   | Đăng nhập (trả về access + refresh token)       | 5/min      |
| POST   | `/api/auth/refresh`                 | Làm mới access token bằng refresh token         | -          |
| POST   | `/api/auth/forgot-password`         | Quên mật khẩu (gửi OTP/token qua email)         | 5/min      |
| POST   | `/api/auth/reset-password`          | Đặt lại mật khẩu bằng OTP hoặc token            | 5/min      |
| POST   | `/api/auth/logout`                  | Đăng xuất, thu hồi refresh token hiện tại       | -          |

---

## Identity (KYC) Endpoints

### User

| Method | Endpoint                       | Mô tả                                              | Role |
| ------ | ------------------------------ | -------------------------------------------------- | ---- |
| GET    | `/api/identity/profile`        | Lấy thông tin hồ sơ danh tính của chính user       | User |
| PUT    | `/api/identity/profile`        | Cập nhật hồ sơ danh tính                           | User |
| POST   | `/api/identity/verify-request` | Gửi yêu cầu xác minh danh tính (tài liệu KYC)      | User |
| GET    | `/api/identity/verify-history` | Xem lịch sử các yêu cầu xác minh đã gửi            | User |

### Admin

| Method | Endpoint                                    | Mô tả                                                           | Role  |
| ------ | ------------------------------------------- | --------------------------------------------------------------- | ----- |
| GET    | `/api/identity/verify-requests`             | Xem danh sách tất cả yêu cầu xác minh (filter, phân trang)      | Admin |
| GET    | `/api/identity/verify-requests/{id}`        | Xem chi tiết 1 yêu cầu xác minh                                 | Admin |
| PUT    | `/api/identity/verify-request/{id}/approve` | Duyệt yêu cầu xác minh                                          | Admin |
| PUT    | `/api/identity/verify-request/{id}/reject`  | Từ chối yêu cầu xác minh (bắt buộc ghi chú lý do `admin_note`)  | Admin |

---

## Login History Endpoints

### User

| Method | Endpoint               | Mô tả                                                  | Role |
| ------ | ---------------------- | ------------------------------------------------------ | ---- |
| GET    | `/api/login-history`   | Xem lịch sử đăng nhập của chính user (có phân trang)   | User |

### Admin

| Method | Endpoint                                  | Mô tả                                                                 | Role  |
| ------ | ----------------------------------------- | --------------------------------------------------------------------- | ----- |
| GET    | `/api/admin/login-history`                | Xem lịch sử đăng nhập của tất cả user (filter theo user, thời gian…)  | Admin |
| GET    | `/api/admin/users/{userId}/login-history` | Xem lịch sử đăng nhập của một user cụ thể                             | Admin |
 
---

## Moderation Endpoints (tóm tắt)

| Method | Endpoint                               | Mô tả                                        | Role  |
| ------ | -----------------------------------    | ------------------------------------------   | ----- |
| POST   | `/api/moderation/report`               | Gửi báo cáo vi phạm (user hoặc bài viết)     | User  |
| GET    | `/api/moderation/my-reports`           | Xem các báo cáo do chính mình gửi            | User  |
| GET    | `/api/moderation/reports`              | Danh sách báo cáo (kèm filter, phân trang)   | Admin |
| GET    | `/api/moderation/reports/{id}`         | Xem chi tiết một báo cáo                     | Admin |
| PUT    | `/api/moderation/reports/{id}/resolve` | Xử lý báo cáo (action_taken / dismissed)     | Admin |
| DELETE | `/api/moderation/reports/{id}`         | Xoá một báo cáo                              | Admin |

---

## Cấu trúc thư mục (rút gọn)

```text
app/
  Http/
    Controllers/
      AuthController.php
      IdentityController.php
      AdminIdentityController.php
      ModerationController.php
      LoginHistoryController.php
    Middleware/
      Authenticate.php
      CheckAdmin.php
  Models/
    User.php
    UserIdentity.php
    IdentityVerificationRequest.php
    LoginHistory.php
    UserToken.php
    OtpCode.php
database/
  migrations/
routes/
  api.php
storage/
  api-docs/
    api-docs.json   # Swagger/OpenAPI spec
```
