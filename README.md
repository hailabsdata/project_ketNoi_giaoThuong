# KetNoiGiaoThuong - Trade Connection Platform API

API Backend cho nền tảng kết nối giao thương doanh nghiệp, xây dựng bằng Laravel 9 với JWT Authentication.

## ✨ Tính năng

### 🔐 Authentication (JWT)

-   ✅ Đăng ký tài khoản với xác thực email qua OTP
-   ✅ Đăng nhập với Access Token & Refresh Token
-   ✅ Làm mới token (Refresh Token)
-   ✅ Quên mật khẩu với OTP qua email
-   ✅ Đặt lại mật khẩu
-   ✅ Gửi lại OTP khi hết hạn
-   ✅ Đăng xuất (invalidate tokens)

### 👤 Identity Management (KYC)

-   ✅ Xem và cập nhật hồ sơ cá nhân/doanh nghiệp
-   ✅ Gửi yêu cầu xác minh doanh nghiệp (Business Verification)
-   ✅ Admin duyệt/từ chối yêu cầu xác minh
-   ✅ Xem lịch sử xác minh

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

### Authentication Endpoints

| Method | Endpoint                            | Description            | Rate Limit |
| ------ | ----------------------------------- | ---------------------- | ---------- |
| POST   | `/api/auth/register`                | Đăng ký tài khoản      | 5/min      |
| POST   | `/api/auth/verify-email`            | Xác thực email với OTP | 5/min      |
| POST   | `/api/auth/resend-verification-otp` | Gửi lại OTP xác thực   | 5/min      |
| POST   | `/api/auth/login`                   | Đăng nhập              | -          |
| POST   | `/api/auth/refresh`                 | Làm mới token          | -          |
| POST   | `/api/auth/forgot-password`         | Quên mật khẩu          | 5/min      |
| POST   | `/api/auth/reset-password`          | Đặt lại mật khẩu       | 5/min      |
| POST   | `/api/auth/logout`                  | Đăng xuất              | -          |

### Identity Endpoints

| Method | Endpoint                                    | Description          | Role  |
| ------ | ------------------------------------------- | -------------------- | ----- |
| GET    | `/api/identity/profile`                     | Lấy thông tin hồ sơ  | User  |
| PUT    | `/api/identity/profile`                     | Cập nhật hồ sơ       | User  |
| POST   | `/api/identity/verify-request`              | Gửi yêu cầu xác minh | User  |
| GET    | `/api/identity/verify-history`              | Xem lịch sử xác minh | User  |
| PUT    | `/api/identity/verify-request/{id}/approve` | Duyệt yêu cầu        | Admin |
| PUT    | `/api/identity/verify-request/{id}/reject`  | Từ chối yêu cầu      | Admin |


## 📁 Cấu trúc thư mục

```
KetNoiGiaoThuong-Server/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php          # JWT Authentication
│   │   │   └── IdentityController.php      # Identity/KYC Management
│   │   └── Middleware/
│   │       └── CheckAdmin.php              # Admin authorization
│   ├── Mail/
│   │   ├── VerifyEmailMail.php
│   │   └── PasswordResetOtpMail.php
│   └── Models/
│       ├── User.php
│       ├── OtpCode.php
│       ├── UserIdentity.php
│       ├── UserToken.php
│       └── IdentityVerificationRequest.php
├── database/
│   └── migrations/
├── routes/
│   └── api.php                             # API routes
├── storage/
│   ├── api-docs/
│   │   └── api-docs.json                   # Swagger docs
│   └── logs/
└── .env
```



