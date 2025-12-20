# Flight Booking System – PHP Backend

This repository contains the **PHP backend API** for the Flight Booking System project.

The backend is built using **core PHP**, **PDO**, and **MySQL**, and is run using PHP’s built-in development server.

---

## 📦 Requirements

Make sure you have the following installed:

- **PHP 8.0+**
- **MySQL 8.0+**
- A web browser or API client (Postman, Insomnia, etc.)

To verify PHP:

```bash
php -v
```

---

## 🗂 Project Structure (Simplified)

```
project-root/
│
├── services/            # API endpoints
│   ├── auth/
│   ├── company/
│   └── passenger/
│
├── includes/            # Core backend logic
│   ├── db.php
│   ├── auth.php
│   ├── validation.php
│   └── helpers.php
│
├── config/
│   ├── config.php       # Database configuration (NOT committed)
│   └── config.example.php
│
├── uploads/             # User-uploaded files (ignored by git)
├── .gitignore
└── README.md
```

---

## ⚙️ Configuration

### 1️⃣ Database Setup

Create the database and tables using the provided SQL schema:

```sql
flight_booking_uni.sql
```

Make sure MySQL is running.

---

### 2️⃣ Configure Database Credentials

Create a config file:

```bash
config/config.php
```

Example:

```php
<?php

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'flight_booking_uni');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');
```

⚠️ **Do not commit this file** — it is ignored by `.gitignore`.

---

## ▶️ Running the Backend

From the project root directory, run:

```bash
php -S localhost:8000
```

If your entry point is inside a subfolder (e.g. `services`):

```bash
php -S localhost:8000 -t .
```

You should see output similar to:

```
PHP 8.x Development Server started
Listening on http://localhost:8000
```

---

## 🔌 Accessing the API

All endpoints are accessed via the `services/` directory.

Example:

```http
POST http://localhost:8000/services/auth/login.php
```

Use **Postman** or **Insomnia** to test API requests.

---

## 🔐 Authentication

- Authentication is **session-based** (PHP sessions)
- Login stores the user in `$_SESSION`
- Protected routes check session state

### Important (Frontend / API Clients)

You **must send cookies** with requests:

- Fetch: `credentials: 'include'`
- Axios: `withCredentials: true`

---

## 🧪 Common Issues

### ❌ `could not find driver`

Enable PDO MySQL in `php.ini`:

```ini
extension=pdo_mysql
```

Restart the PHP server after enabling.

---

### ❌ Unauthorized / Session Lost

Make sure your client:

- Sends cookies
- Uses the same domain and port

---

## 📌 Notes

- This backend is designed for **educational purposes**
- Uses **plain PHP**, no framework
- Suitable for REST-style APIs

---

## 🚀 Future Improvements (Optional)

- JWT authentication
- Docker support
- Role-based access middleware
- Rate limiting

---

## 👤 Author

Developed as part of a **Web Engineering** university project.
