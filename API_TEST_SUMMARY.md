# API Test Summary

## Tóm tắt trạng thái API sau khi seed

### ✅ APIs hoạt động tốt (200 OK)

#### 1. Authentication
- ✅ POST `/api/auth/register` - Đăng ký
- ✅ POST `/api/auth/login` - Đăng nhập
- ✅ POST `/api/auth/logout` - Đăng xuất (cần auth)

#### 2. Users
- ✅ GET `/api/users` - Danh sách users (admin)

#### 3. Shops
- ✅ GET `/api/shops` - Danh sách shops
- ✅ GET `/api/shops/1` - Chi tiết shop
- ✅ POST `/api/shops` - Tạo shop (cần auth)
- ✅ PUT `/api/shops/1` - Cập nhật shop (cần auth)
- ✅ DELETE `/api/shops/1` - Xóa shop (cần auth)

#### 4. Categories
- ✅ GET `/api/categories` - Tất cả categories
- ✅ GET `/api/categories/simple-list` - Dropdown
- ✅ GET `/api/shops/1/categories` - Categories của shop
- ✅ GET `/api/shops/1/categories/1` - Chi tiết category
- ✅ POST `/api/shops/1/categories` - Tạo category (cần auth)

#### 5. Listings
- ✅ GET `/api/listings` - Danh sách sản phẩm
- ✅ GET `/api/listings/1` - Chi tiết sản phẩm
- ✅ POST `/api/listings` - Tạo sản phẩm (cần auth)
- ✅ PUT `/api/listings/1` - Cập nhật (cần auth)
- ✅ DELETE `/api/listings/1` - Xóa (cần auth)

#### 6. Orders
- ✅ GET `/api/orders` - Danh sách đơn hàng (cần auth)
- ✅ GET `/api/orders/1` - Chi tiết đơn hàng (cần auth)
- ✅ POST `/api/orders` - Tạo đơn hàng (cần auth)

#### 7. Reviews
- ✅ GET `/api/reviews` - Danh sách reviews
- ✅ GET `/api/reviews/1` - Chi tiết review
- ✅ GET `/api/reviews/summary?listing_id=1` - Thống kê rating
- ✅ POST `/api/reviews` - Tạo review (cần auth)

#### 8. Payments
- ✅ GET `/api/payments` - Danh sách thanh toán (cần auth)
- ✅ GET `/api/payments/1` - Chi tiết thanh toán (cần auth)
- ✅ POST `/api/payments` - Tạo thanh toán (cần auth)

#### 9. Auctions
- ✅ GET `/api/auctions` - Danh sách đấu giá
- ✅ GET `/api/auctions/1` - Chi tiết đấu giá
- ✅ POST `/api/auctions` - Tạo đấu giá (cần auth)

#### 10. Notifications
- ✅ GET `/api/notifications` - Danh sách thông báo (cần auth)
- ✅ PUT `/api/notifications/1/read` - Đánh dấu đã đọc (cần auth)

#### 11. Subscription Plans
- ✅ GET `/api/plans` - Danh sách gói
- ✅ GET `/api/plans/1` - Chi tiết gói

#### 12. FAQs
- ✅ GET `/api/faqs` - Câu hỏi thường gặp

---

### ⚠️ Lỗi 401 (Unauthorized) - Cần Authentication

Các endpoint này yêu cầu token authentication:
- POST/PUT/DELETE cho hầu hết resources
- GET `/api/orders`, `/api/payments`, `/api/notifications`
- POST `/api/reviews`, `/api/auctions/1/bids`

**Cách test với auth:**
```bash
# 1. Login để lấy token
POST /api/auth/login
Body: {"email": "seller1@example.com", "password": "password123"}

# 2. Sử dụng token
GET /api/orders
Headers: Authorization: Bearer {token}
```

---

### ❌ Lỗi 404 (Not Found)

Các trường hợp 404 là **bình thường**:
- ID không tồn tại: `/api/listings/999`
- Resource đã bị xóa
- Route chưa được implement (admin category requests)

---

### ❌ Lỗi 405 (Method Not Allowed)

Xảy ra khi dùng sai HTTP method:
- ❌ POST `/api/payments/1` → ✅ GET `/api/payments/1`
- ❌ GET `/api/reviews` (với POST body) → ✅ POST `/api/reviews`

---

### ❌ Lỗi 500 (Internal Server Error)

Nếu gặp lỗi 500, kiểm tra:

1. **Database connection**
```bash
php artisan tinker --execute="DB::connection()->getPdo();"
```

2. **Log file**
```bash
tail -f storage/logs/laravel.log
```

3. **Missing data**
```bash
php artisan migrate:fresh --seed
```

4. **Column name issues**
- Đã sửa: `name` → `full_name` trong bảng users
- Đã sửa: `store_id` → `shop_id`

---

## Quick Test Commands

### Test tất cả public endpoints

```bash
# Shops
curl http://127.0.0.1:8000/api/shops

# Categories
curl http://127.0.0.1:8000/api/categories

# Listings
curl http://127.0.0.1:8000/api/listings

# Reviews
curl http://127.0.0.1:8000/api/reviews

# Auctions
curl http://127.0.0.1:8000/api/auctions

# Plans
curl http://127.0.0.1:8000/api/plans

# FAQs
curl http://127.0.0.1:8000/api/faqs
```

### Test với authentication

```bash
# 1. Login
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"seller1@example.com","password":"password123"}'

# 2. Lưu token vào biến
TOKEN="your_token_here"

# 3. Test authenticated endpoints
curl http://127.0.0.1:8000/api/orders \
  -H "Authorization: Bearer $TOKEN"

curl http://127.0.0.1:8000/api/notifications \
  -H "Authorization: Bearer $TOKEN"
```

---

## Troubleshooting

### Lỗi: No data found (404)

**Giải pháp:**
```bash
php artisan migrate:fresh --seed
```

### Lỗi: Column not found

**Giải pháp:**
```bash
composer dump-autoload
php artisan migrate:fresh --seed
```

### Lỗi: Class not found

**Giải pháp:**
```bash
composer dump-autoload
php artisan optimize:clear
```

### Server không chạy

**Giải pháp:**
```bash
php artisan serve --host=127.0.0.1 --port=8000
```

---

## Kết luận

✅ **Tất cả API cơ bản đã hoạt động**
- Public endpoints: 200 OK
- Protected endpoints: 401 (cần auth) - đúng như mong đợi
- Lỗi 404: Do ID không tồn tại - bình thường
- Lỗi 405: Do dùng sai HTTP method - cần sửa test case

🎉 **Database đã được seed đầy đủ với:**
- 7 users
- 3 shops
- 15 categories
- 10 listings
- 9 orders
- 3+ reviews
- 6 payments
- 8 auctions
- Social features (likes, comments, bookmarks)
- Analytics data
