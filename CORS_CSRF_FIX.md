# Fix CSRF Token Mismatch - React Frontend

## ✅ Đã sửa

### 1. Backend (Laravel) - ĐÃ HOÀN THÀNH

#### File: `app/Http/Middleware/VerifyCsrfToken.php`
```php
protected $except = [
    'api/*', // Exclude all API routes from CSRF verification
];
```

#### File: `.env` - ĐÃ CÓ SẴN
```env
APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
```

#### File: `config/cors.php` - ĐÃ CÓ SẴN
```php
'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
'allowed_methods' => ['*'],
'allowed_origins' => ['http://127.0.0.1:5173','http://localhost:5173'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

---

## 🔧 Cần sửa ở Frontend (React)

### 2. Frontend Setup

#### Cài đặt Axios (nếu chưa có)
```bash
npm install axios
```

#### Tạo file `src/api/axios.js`
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  withCredentials: true, // Quan trọng!
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});

export default api;
```

#### Sử dụng trong Login Component

**CÁCH 1: Dùng Token Authentication (Khuyến nghị cho SPA)**

```javascript
import api from './api/axios';

// Login function
const handleLogin = async (email, password) => {
  try {
    const response = await api.post('/auth/login', {
      email,
      password
    });
    
    // Lưu token vào localStorage
    const { token, user } = response.data;
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(user));
    
    // Set token cho các request tiếp theo
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    
    console.log('Login success:', user);
    // Redirect hoặc update state
  } catch (error) {
    console.error('Login failed:', error.response?.data);
  }
};
```

**CÁCH 2: Dùng Session (Nếu cần CSRF)**

```javascript
import api from './api/axios';

// Login function với CSRF
const handleLogin = async (email, password) => {
  try {
    // Bước 1: Lấy CSRF cookie (chỉ cần nếu dùng session)
    await axios.get('http://127.0.0.1:8000/sanctum/csrf-cookie', {
      withCredentials: true
    });
    
    // Bước 2: Login
    const response = await api.post('/auth/login', {
      email,
      password
    });
    
    console.log('Login success:', response.data);
  } catch (error) {
    console.error('Login failed:', error.response?.data);
  }
};
```

---

## 📝 Ví dụ đầy đủ Login Component

```javascript
// src/components/Login.jsx
import { useState } from 'react';
import api from '../api/axios';

function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const response = await api.post('/auth/login', {
        email,
        password
      });

      // Lưu token
      const { token, user } = response.data;
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      
      // Set token cho các request tiếp theo
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      // Redirect hoặc update state
      window.location.href = '/dashboard';
    } catch (err) {
      setError(err.response?.data?.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        type="email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        placeholder="Email"
        required
      />
      <input
        type="password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        placeholder="Password"
        required
      />
      {error && <div className="error">{error}</div>}
      <button type="submit" disabled={loading}>
        {loading ? 'Loading...' : 'Login'}
      </button>
    </form>
  );
}

export default Login;
```

---

## 🔐 Setup Axios Interceptor (Tự động thêm token)

```javascript
// src/api/axios.js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});

// Request interceptor - Tự động thêm token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Xử lý lỗi 401
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token hết hạn hoặc không hợp lệ
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

---

## 🧪 Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@tradehub.com | admin123 |
| Seller | seller1@example.com | password123 |
| Buyer | buyer1@example.com | password123 |

---

## ✅ Checklist

### Backend (Laravel) - ĐÃ XONG
- [x] Exclude `api/*` from CSRF verification
- [x] Configure CORS for React frontend
- [x] Set SANCTUM_STATEFUL_DOMAINS in .env

### Frontend (React) - CẦN LÀM
- [ ] Cài đặt axios
- [ ] Tạo file `src/api/axios.js`
- [ ] Setup axios interceptors
- [ ] Update Login component
- [ ] Lưu token vào localStorage
- [ ] Thêm Authorization header cho các request

---

## 🚀 Cách test

1. **Start Laravel server:**
```bash
php artisan serve --host=127.0.0.1 --port=8000
```

2. **Start React dev server:**
```bash
npm run dev
```

3. **Test login:**
- Mở http://localhost:5173
- Login với: seller1@example.com / password123
- Check console để xem response

---

## 🐛 Troubleshooting

### Vẫn lỗi CSRF?
```bash
# Clear cache Laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### CORS error?
- Kiểm tra `config/cors.php` có đúng frontend URL
- Kiểm tra `withCredentials: true` trong axios config

### 401 Unauthorized?
- Kiểm tra token đã được lưu vào localStorage
- Kiểm tra Authorization header có đúng format: `Bearer {token}`

---

## 📚 Tài liệu tham khảo

- [Laravel Sanctum](https://laravel.com/docs/10.x/sanctum)
- [Axios Documentation](https://axios-http.com/docs/intro)
- [CORS in Laravel](https://laravel.com/docs/10.x/routing#cors)
