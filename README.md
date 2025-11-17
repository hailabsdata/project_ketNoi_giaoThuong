# TradeHub / Kết Nối Giao Thương — Backend (Laravel) + Docker

Tài liệu này hướng dẫn **chạy Backend bằng Docker**, **xử lý lỗi thường gặp**, và **kết nối với Frontend (Vite + React) — cả chạy ngoài máy và chạy bằng Docker**. Mặc định viết cho **Windows + PowerShell** (chạy được tương tự trên macOS/Linux).

> ✅ Mục tiêu: `docker compose up -d` là xong, API chạy ở `http://localhost:8010`, DB chạy trong container MariaDB, có **Adminer** để xem bảng, và có hướng dẫn nối FE.

---

## 0) Yêu cầu tối thiểu

- Docker Desktop
- Git, VS Code (khuyến nghị)
- Không cần cài PHP/MySQL/Composer trên máy host

> **Thư mục giả định**: bạn đang đứng tại thư mục backend (Laravel) `project_ketNoi_giaoThuong` (cùng nơi chứa `composer.json`).

---

## 1) Cấu trúc Docker

### 1.1. `Dockerfile` (đặt ở thư mục gốc Laravel)
```Dockerfile
FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev libonig-dev libxml2-dev default-mysql-client \
 && docker-php-ext-install pdo_mysql zip intl gd bcmath

# Composer chính chủ
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
EXPOSE 8000
```

### 1.2. `docker-compose.yml`
```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: tradehub-app
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    ports:
      - "8010:8000"     # API: http://localhost:8010
    depends_on:
      - db
    environment:
      APP_ENV: local
      APP_DEBUG: "true"
      APP_URL: http://localhost:8010
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: tradehub
      DB_USERNAME: haicon
      DB_PASSWORD: HaiCon2508@
    command: >
      bash -lc "php artisan serve --host=0.0.0.0 --port=8000"
    restart: unless-stopped
    networks: [appnet]

  db:
    image: mariadb:10.6
    container_name: tradehub-db
    environment:
      MYSQL_DATABASE: tradehub
      MYSQL_USER: haicon
      MYSQL_PASSWORD: HaiCon2508@
      MYSQL_ROOT_PASSWORD: rootpass
    volumes:
      - db_data:/var/lib/mysql
    ports:
      - "3307:3306"     # host 3307 -> container 3306 (tránh đụng XAMPP:3306)
    healthcheck:
      test: ["CMD-SHELL", "mysqladmin ping -uroot -prootpass || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 10
      start_period: 10s
    restart: unless-stopped
    networks: [appnet]

  adminer:
    image: adminer:4
    container_name: tradehub-adminer
    depends_on: [db]
    ports:
      - "8081:8080"     # http://localhost:8081
    restart: unless-stopped
    networks: [appnet]

  # (Tuỳ chọn) FE chạy bằng Docker — cần sửa đường dẫn context cho đúng
  # fe:
  #   build:
  #     context: ../KetNoiGiaoThuong-Client   # sửa: trỏ tới thư mục FE
  #     dockerfile: Dockerfile
  #   container_name: tradehub-fe
  #   working_dir: /app
  #   volumes:
  #     - ../KetNoiGiaoThuong-Client:/app
  #   ports:
  #     - "5173:5173"    # http://localhost:5173
  #   command: >
  #     bash -lc "npm ci && npm run dev -- --host 0.0.0.0 --port 5173"
  #   restart: unless-stopped
  #   networks: [appnet]

volumes:
  db_data:

networks:
  appnet:
```

> Vì FE là repo riêng, nếu muốn chạy FE bằng Docker, hãy **bỏ comment block `fe`** và sửa `context`, `volumes` cho đúng đường dẫn máy bạn.

---

## 2) Thiết lập `.env` của Laravel

- Nếu chưa có, tạo nhanh từ mẫu:
  ```powershell
  if (!(Test-Path .env)) { Copy-Item .env.example .env }
  ```

- Các biến **bắt buộc** (khớp `docker-compose.yml` ở trên):

  ```dotenv
  APP_URL=http://localhost:8010

  DB_CONNECTION=mysql
  DB_HOST=db
  DB_PORT=3306
  DB_DATABASE=tradehub
  DB_USERNAME=Admin
  DB_PASSWORD=Admin123@

  # Liên quan tới Sanctum/CORS khi nối FE dev (Vite)
  SANCTUM_STATEFUL_DOMAINS=localhost:5173
  SESSION_DOMAIN=localhost
  FRONTEND_URL=http://localhost:5173
  ```

> Nếu bạn đổi cổng API khác 8010 → nhớ đổi `APP_URL`. Nếu FE chạy cổng khác 5173 thì cập nhật lại 3 biến cuối.

---

## 3) Chạy dự án

```powershell
# 1) Bật Docker Desktop trước
# 2) Mở PowerShell tại thư mục backend
docker compose up -d --build

# 3) Cài vendor (lần đầu)
docker compose run --rm app composer install --no-interaction --prefer-dist

# 4) Khởi tạo app key + clear cache
docker compose run --rm app php artisan config:clear
docker compose run --rm app php artisan key:generate

# 5) Migrate (nếu muốn Laravel tạo bảng còn thiếu)
docker compose exec app php artisan migrate
```

- API chạy tại: **http://localhost:8010**
  - Ví dụ: `GET /api/ping`
  - (Nếu đã bật L5-Swagger) tài liệu: **http://localhost:8010/api/documentation**

- Database GUI (Adminer): **http://localhost:8081**
  - Server: `db` (nếu từ máy host thì `localhost:3307`)
  - User: `admin`
  - Pass: `Admin123@`
  - DB: `tradehub`

---

## 4) Kết nối với Frontend

### 4.1. FE chạy **ngoài Docker** (Vite)
- Trong FE, set **API base URL**: `http://localhost:8010/api`
  - Ví dụ `src/http.js` (axios):
    ```js
    import axios from "axios";

    export const http = axios.create({
      baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8010/api",
      withCredentials: true, // nếu dùng Sanctum
    });
    ```
- Trong `.env` của FE (Vite):
  ```dotenv
  VITE_API_BASE_URL=http://localhost:8010/api
  ```
- Chạy FE:
  ```bash
  npm i
  npm run dev -- --host 0.0.0.0 --port 5173
  ```

### 4.2. FE chạy **bằng Docker**
- Bỏ comment block `fe` trong `docker-compose.yml` và sửa `context`, `volumes` tới thư mục FE của bạn.
- Chạy:
  ```powershell
  docker compose up -d --build
  ```
- Truy cập FE: **http://localhost:5173**

> **Sanctum/CORS**: đảm bảo BE `.env` có `SANCTUM_STATEFUL_DOMAINS=localhost:5173`, `SESSION_DOMAIN=localhost`, `FRONTEND_URL=http://localhost:5173`.

---

## 5) Kiểm tra bảng dữ liệu (3 cách)

### 5.1. Bằng Adminer (đề xuất, dễ nhìn)
- Mở **http://localhost:8081**
- Server: `db` · User/Pass: `haicon` / `HaiCon2508@` · DB: `tradehub`
- Vào menu **Tables** → xem danh sách bảng, click để duyệt dữ liệu.

### 5.2. Bằng lệnh (từ container app)
```powershell
# Liệt kê bảng
docker compose exec app php -r "echo json_encode(DB::select(\"SELECT table_name FROM information_schema.tables WHERE table_schema='tradehub'\"), JSON_PRETTY_PRINT), PHP_EOL;"

# Đếm số dòng bảng users (ví dụ)
docker compose exec app php -r "echo json_encode(DB::select(\"SELECT COUNT(*) AS c FROM users\"), JSON_PRETTY_PRINT), PHP_EOL;"
```

### 5.3. Bằng Tinker
```powershell
docker compose exec app php artisan tinker
# trong Tinker:
# DB::select('SELECT 1');
# \App\Models\User::count();
```

---

## 6) Import dữ liệu từ XAMPP (nếu đã có DB cũ)

**Xuất từ XAMPP** (phpMyAdmin → Export → SQL) thành file, ví dụ `C:\Users\Admin\Desktop\tradehub_dump.sql`, sau đó:

```powershell
docker cp "C:\Users\Admin\Desktop\tradehub_dump.sql" $(docker compose ps -q db):/dump.sql
docker compose exec -T db sh -c "mysql -uroot -prootpass -e 'DROP DATABASE IF EXISTS tradehub; CREATE DATABASE tradehub;'"
docker compose exec -T db sh -c "mysql -uroot -prootpass tradehub < /dump.sql"
```

---

## 7) Tổng hợp lỗi thường gặp & cách xử lý

### Lỗi cổng: `Bind for 0.0.0.0:8000 failed: port is already allocated`
- Có process khác chiếm 8000. Hai cách:
  1. Đổi cổng trong `docker-compose.yml` (ví dụ `8010:8000`) **và** đổi `APP_URL`.
  2. Tìm và kill process đang chiếm:
     ```powershell
     netstat -ano | findstr ":8000"
     taskkill /PID <PID> /F
     ```

### `service "app" is not running`
- Container **chưa chạy** (build fail hoặc artisan serve lỗi). Xem log:
  ```powershell
  docker compose logs -f app
  ```
- Cài vendor trước khi `artisan serve`:
  ```powershell
  docker compose run --rm app composer install --no-interaction --prefer-dist
  docker compose up -d
  ```

### `docker-php-entrypoint: exec: composer: not found`
- Image chưa có Composer → kiểm tra `Dockerfile` phải có:
  ```Dockerfile
  COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
  ```
- Rebuild không cache:
  ```powershell
  docker compose down -v --remove-orphans
  docker compose up -d --build --no-cache
  ```

### Cảnh báo `the attribute 'version' is obsolete` khi compose
- Do Docker Compose V2 không cần `version:` → hãy **xoá dòng `version:`** khỏi `docker-compose.yml`.

### Laravel migrate lỗi `Base table already exists` / `Class ... not found`
- Bảng đã tồn tại do import trước → bỏ migration tương ứng, hoặc dùng `--path` để chỉ chạy các migration mới.
- Báo **`Class ... not found`** → regenerate autoload:
  ```powershell
  docker compose exec app composer dump-autoload
  docker compose exec app php artisan migrate
  ```
- Thiếu file migration? Tạo mới và migrate lại.

### Không kết nối được DB: `SQLSTATE[HY000] [2002]`
- Khi BE chạy **trong Docker**, `DB_HOST` phải là **`db`** (tên service), **không** phải `127.0.0.1`.
- Kiểm tra DB đã up:
  ```powershell
  docker compose ps
  docker compose logs -f db
  ```

### Xoá sạch, làm lại từ đầu
```powershell
docker compose down -v --remove-orphans
docker volume rm project_ketNoi_giaoThuong_db_data  # tên volume có thể khác, xem `docker volume ls`
docker compose up -d --build
```

---

## 8) Lệnh nhanh (cheat sheet)

```powershell
# Dựng/khởi động
docker compose up -d --build

# Xem trạng thái
docker compose ps

# Log app / db
docker compose logs -f app
docker compose logs -f db

# Cài vendor
docker compose run --rm app composer install --no-interaction --prefer-dist

# Clear cache + tạo key
docker compose run --rm app php artisan config:clear
docker compose run --rm app php artisan key:generate

# Migrate/Seed
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed

# Dừng / xoá
docker compose down
docker compose down -v --remove-orphans
```

---

## 9) Kết nối FE ↔ BE (Sanctum/CORS)

Trong **BE `.env`**:  
```
APP_URL=http://localhost:8010
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
FRONTEND_URL=http://localhost:5173
```

Trong **FE `.env`** (Vite):  
```
VITE_API_BASE_URL=http://localhost:8010/api
```

Ví dụ **route BE** test nhanh:
```php
// routes/api.php
Route::get('/ping', fn() => response()->json(['ok' => true, 'ts' => now()]));
```

Ví dụ **axios FE**:
```js
import axios from "axios";
export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8010/api",
  withCredentials: true, // nếu dùng Sanctum
});
```

---

## 10) Ghi chú khác
- Nếu bạn bật **XAMPP MySQL** đồng thời, không ảnh hưởng vì DB docker đang map **3307** ở host. Khi dùng tool ngoài (DataGrip) để xem DB docker, hãy dùng host = `localhost`, port = `3307`.
- Nếu muốn thêm Nginx + PHP‑FPM thay vì `artisan serve`, có thể tách thêm service `nginx` & chuyển `app` sang `php-fpm` — liên hệ file `docker/nginx/default.conf` nếu cần.

---

**Done.** Bạn có thể chạy ngay:  
```powershell
docker compose up -d --build
docker compose run --rm app composer install --no-interaction --prefer-dist
docker compose run --rm app php artisan key:generate
docker compose exec app php artisan migrate
```
- API: http://localhost:8010  
- DB GUI (Adminer): http://localhost:8081

Chúc bạn build vui vẻ! 🚀


docker compose up -d   


composer require --dev barryvdh/laravel-ide-helper
php artisan ide-helper:generate
php artisan ide-helper:meta
php artisan ide-helper:models --write
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
