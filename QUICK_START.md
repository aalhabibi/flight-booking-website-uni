# 🚀 Quick Start Guide - SkyBooker

## Fast Setup (5 Minutes)

### 1️⃣ Database Setup

```bash
# Open MySQL
mysql -u root -p

# Create database
CREATE DATABASE flight_booking_uni CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Import schema
mysql -u root -p flight_booking_uni < Backend/database_schema.sql
```

### 2️⃣ Backend Configuration

Create `Backend/config/config.php`:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'flight_booking_uni');
define('DB_USER', 'root');
define('DB_PASS', 'YOUR_PASSWORD');  // ← Change this!
define('DB_CHARSET', 'utf8mb4');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PASSWORD_MIN_LENGTH', 8);
```

### 3️⃣ Run Servers

**Terminal 1 - Backend:**

```bash
cd Backend
php -S localhost:8000
```

**Terminal 2 - Frontend:**

```bash
cd FrontEnd
php -S localhost:3000
```

### 4️⃣ Open Browser

```
http://localhost:3000
```

---

## 🎯 Test Accounts

### Create Passenger:

- Email: `passenger@test.com`
- Password: `test1234`
- Type: Passenger

### Create Company:

- Email: `airline@test.com`
- Password: `test1234`
- Type: Company

---

## ✅ Verification Checklist

- [ ] MySQL running
- [ ] Database created & schema imported
- [ ] config.php created with correct password
- [ ] Backend server running on port 8000
- [ ] Frontend server running on port 3000
- [ ] Can access http://localhost:3000
- [ ] Can register new account
- [ ] Can login successfully

---

## 🐛 Common Issues

**Can't connect to database?**
→ Check MySQL password in `config.php`

**Page not found?**
→ Check both servers are running

**Login not working?**
→ Open browser console (F12) and check for errors

---

## 📖 Full Documentation

See `README.md` for complete setup guide and troubleshooting.

---

**Ready? Let's fly! ✈️**
