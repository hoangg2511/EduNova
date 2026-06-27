# Cấu Hình Xác Thực & Quản Lý Phiên Đăng Nhập

## 1. Middleware

### IsAdmin Middleware
- **Vị trí**: `app/Http/Middleware/IsAdmin.php`
- **Chức năng**: Kiểm tra xem người dùng có quyền admin không
- **Cách dùng**: Thêm `is_admin` vào middleware của route

### IsUser Middleware
- **Vị trí**: `app/Http/Middleware/IsUser.php`
- **Chức năng**: Kiểm tra xem người dùng có quyền user thường không
- **Cách dùng**: Thêm `is_user` vào middleware của route

### Authenticate Middleware
- **Vị trí**: `app/Http/Middleware/Authenticate.php`
- **Chức năng**: Kiểm tra xem người dùng đã đăng nhập chưa
- **Hành vi**: Tự động chuyển hướng đến trang login nếu chưa đăng nhập

## 2. Routes

### Guest Routes (Không cần đăng nhập)
```
GET  /login              - Hiển thị form đăng nhập
POST /login              - Xử lý đăng nhập
```

### Authenticated Routes (Cần đăng nhập)
```
GET  /dashboard          - Dashboard chung
POST /logout             - Đăng xuất
```

### User Routes (Cần đăng nhập + role = user)
```
GET  /user/dashboard     - Dashboard người dùng
GET  /user/courses       - Danh sách khóa học
GET  /user/profile       - Hồ sơ người dùng
```

### Admin Routes (Cần đăng nhập + role = admin)
```
GET  /admin/dashboard    - Dashboard admin
GET  /admin/users        - Quản lý người dùng
GET  /admin/courses      - Quản lý khóa học
GET  /admin/settings     - Cài đặt hệ thống
```

## 3. Session Configuration

### Cấu hình trong .env
```
SESSION_DRIVER=database          # Lưu session vào database
SESSION_LIFETIME=120             # Thời gian sống: 120 phút (2 giờ)
SESSION_ENCRYPT=false            # Không mã hóa session
SESSION_PATH=/                   # Đường dẫn cookie
SESSION_DOMAIN=null              # Tất cả domain
SESSION_SECURE_COOKIE=false      # Cho phép HTTP (set true cho HTTPS)
SESSION_HTTP_ONLY=true           # Chỉ HTTP, không cho JavaScript truy cập
SESSION_SAME_SITE=lax            # Bảo vệ CSRF
```

### Cấu hình trong config/session.php
- **Driver**: database (lưu vào bảng `sessions`)
- **Lifetime**: 120 phút
- **Cookie Name**: `laravel-session`
- **HTTP Only**: true (bảo mật)
- **Same Site**: lax (chống CSRF)

## 4. Model User

### Thuộc tính
- `name` - Tên người dùng
- `email` - Email
- `password` - Mật khẩu (được hash)
- `role` - Vai trò (admin hoặc user)

### Phương thức
```php
$user->isAdmin()  // Kiểm tra có phải admin không
$user->isUser()   // Kiểm tra có phải user thường không
```

## 5. Cách Sử Dụng

### Kiểm tra quyền trong Controller
```php
if (auth()->user()->isAdmin()) {
    // Làm gì đó cho admin
}
```

### Kiểm tra quyền trong Blade Template
```blade
@if(auth()->user()->isAdmin())
    <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
@else
    <a href="{{ route('user.dashboard') }}">My Dashboard</a>
@endif
```

### Lấy thông tin người dùng hiện tại
```php
auth()->user()           // Lấy đối tượng User
auth()->user()->name     // Lấy tên
auth()->user()->email    // Lấy email
auth()->user()->role     // Lấy vai trò
```

## 6. Migration

Chạy migration để thêm cột `role` vào bảng users:
```bash
php artisan migrate
```

## 7. Tạo Dữ Liệu Test

Sử dụng DatabaseSeeder để tạo admin và user test:
```php
User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'role' => 'admin',
]);

User::create([
    'name' => 'Regular User',
    'email' => 'user@example.com',
    'password' => bcrypt('password'),
    'role' => 'user',
]);
```

## 8. Bảo Mật

- Session được lưu trong database (an toàn hơn file)
- Cookie chỉ có thể truy cập qua HTTP (không JavaScript)
- Bảo vệ CSRF với Same-Site cookie
- Mật khẩu được hash với Bcrypt
- Session tự động hết hạn sau 120 phút
