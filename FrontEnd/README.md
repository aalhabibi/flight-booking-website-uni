# SkyBooker - Flight Booking System

A comprehensive flight booking platform built with HTML, CSS, JavaScript (jQuery), and PHP. This system allows passengers to search and book flights, while airline companies can manage their flights and bookings.

## 🌟 Features

### For Passengers

- **User Registration & Authentication** - Create account with profile and passport details
- **Search Flights** - Search flights by departure and arrival cities
- **Book Flights** - Book flights with account balance or cash payment
- **View Bookings** - Track all your flight bookings
- **Messaging** - Communicate directly with airline companies
- **Profile Management** - Update personal information and manage account balance

### For Airline Companies

- **Company Registration** - Register your airline with company details and logo
- **Flight Management** - Add, edit, and cancel flights
- **Itinerary Management** - Create detailed flight routes with multiple stops
- **Booking Management** - View all passenger bookings for your flights
- **Messaging** - Communicate with passengers
- **Revenue Tracking** - Monitor flight revenue and booking statistics

### General Features

- Modern, responsive design with professional UI/UX
- Real-time data updates
- Secure authentication and session management
- File upload support (logos, photos, passports)
- Payment system with account balance and cash options

## 🛠️ Technology Stack

### Frontend

- **HTML5** - Semantic markup
- **CSS3** - Modern styling with custom color palette
- **JavaScript** - Core functionality
- **jQuery 3.6.0** - DOM manipulation and AJAX
- **jQuery UI 1.13.2** - Enhanced UI components

### Backend

- **PHP 8.0+** - Server-side logic
- **MySQL 8.0+** - Database
- **PDO** - Database access layer

## 📋 Requirements

Before you begin, ensure you have the following installed:

1. **PHP 8.0 or higher**

   - Check version: `php -v`
   - Download: [https://www.php.net/downloads](https://www.php.net/downloads)

2. **MySQL 8.0 or higher**

   - Check version: `mysql --version`
   - Download: [https://dev.mysql.com/downloads/mysql/](https://dev.mysql.com/downloads/mysql/)

3. **Web Browser**
   - Chrome, Firefox, Safari, or Edge (latest versions)

## 🚀 Installation & Setup

### Step 1: Clone or Extract the Project

```bash
# Navigate to your working directory
cd d:\Gam3a\Gam3a Fourth level\Web\FlightBookingProject
```

### Step 2: Database Setup

1. **Start MySQL Server**

   - Open MySQL command line or phpMyAdmin

2. **Create Database and Import Schema**

   ```bash
   # Using MySQL command line:
   mysql -u root -p
   ```

   ```sql
   -- In MySQL prompt:
   CREATE DATABASE flight_booking_uni CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   exit;
   ```

   ```bash
   # Import the schema:
   mysql -u root -p flight_booking_uni < Backend/database_schema.sql
   ```

   **OR** using phpMyAdmin:

   - Open phpMyAdmin in your browser
   - Click "New" to create database named `flight_booking_uni`
   - Set charset to `utf8mb4_unicode_ci`
   - Click "Import" tab
   - Choose file: `Backend/database_schema.sql`
   - Click "Go"

### Step 3: Configure Backend

1. **Create configuration file**

   Navigate to `Backend/config/` and create `config.php`:

   ```bash
   cd Backend/config
   ```

   Create a new file named `config.php` with the following content:

   ```php
   <?php
   // Database Configuration
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3306');
   define('DB_NAME', 'flight_booking_uni');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_mysql_password');  // Change this to your MySQL password
   define('DB_CHARSET', 'utf8mb4');

   // Upload Directory
   define('UPLOAD_DIR', __DIR__ . '/../uploads/');

   // Password Settings
   define('PASSWORD_MIN_LENGTH', 8);
   ```

   **⚠️ Important:** Replace `'your_mysql_password'` with your actual MySQL root password.

2. **Create Upload Directory**

   The uploads directory should already exist in `Backend/uploads/`. If not:

   ```bash
   cd Backend
   mkdir uploads
   ```

### Step 4: Configure Frontend

The frontend is already configured to use `http://localhost:8000` as the backend API URL in `FrontEnd/js/config.js`.

If you need to change the port or host, edit `FrontEnd/js/config.js`:

```javascript
const API_CONFIG = {
  BASE_URL: "http://localhost:8000", // Change port if needed
  // ... rest of config
};
```

### Step 5: Verify Directory Structure

Your project should look like this:

```
FlightBookingProject/
├── Backend/
│   ├── config/
│   │   ├── config.php          (YOU CREATED THIS)
│   │   └── constants.php
│   ├── includes/
│   │   ├── auth.php
│   │   ├── db.php
│   │   ├── helpers.php
│   │   └── validation.php
│   ├── services/
│   │   ├── auth/
│   │   ├── company/
│   │   ├── messages/
│   │   ├── passenger/
│   │   └── profile/
│   ├── uploads/                (SHOULD EXIST)
│   ├── database_schema.sql
│   └── README.md
└── FrontEnd/
    ├── css/
    │   └── main.css
    ├── js/
    │   ├── api.js
    │   ├── auth.js
    │   ├── company.js
    │   ├── config.js
    │   ├── main.js
    │   └── passenger.js
    ├── index.html
    ├── login.html
    ├── register.html
    ├── passenger-dashboard.html
    └── company-dashboard.html
```

## ▶️ Running the Application

### Method 1: Using Separate Terminals (Recommended)

**Terminal 1 - Backend Server:**

```bash
# Navigate to Backend directory
cd Backend

# Start PHP built-in server on port 8000
php -S localhost:8000
```

You should see:

```
PHP 8.x Development Server (http://localhost:8000) started
```

**Terminal 2 - Frontend Server:**

```bash
# Navigate to FrontEnd directory (in a NEW terminal)
cd FrontEnd

# Start PHP built-in server on port 3000
php -S localhost:3000
```

You should see:

```
PHP 8.x Development Server (http://localhost:3000) started
```

### Method 2: Using a Single Server (Alternative)

If you prefer to run everything from one server:

```bash
# From the project root
php -S localhost:8000
```

Then access:

- Frontend: `http://localhost:8000/FrontEnd/index.html`
- Backend API: `http://localhost:8000/Backend/services/...`

Update `FrontEnd/js/config.js` to:

```javascript
BASE_URL: "http://localhost:8000/Backend";
```

## 🌐 Accessing the Application

1. **Open your web browser**

2. **Navigate to:**

   ```
   http://localhost:3000
   ```

   Or if using single server:

   ```
   http://localhost:8000/FrontEnd/index.html
   ```

3. **You should see the SkyBooker landing page!**

## 👤 Testing the Application

### Creating Test Accounts

**Register a Passenger Account:**

1. Click "Sign Up" or "Get Started"
2. Select "Passenger" user type
3. Fill in the form:
   - Name: John Doe
   - Username: johndoe
   - Email: john@example.com
   - Phone: +1234567890
   - Password: password123
4. (Optional) Upload profile photo and passport
5. Click "Create Account"
6. Login with your credentials

**Register a Company Account:**

1. Click "Sign Up"
2. Select "Airline Company" user type
3. Fill in the form:
   - Company Name: SkyAir Airlines
   - Username: skyair
   - Email: airline@example.com
   - Phone: +0987654321
   - Password: password123
   - Bio: Leading airline service
   - Address: 123 Airport Road
   - Location: New York, USA
4. (Optional) Upload company logo
5. Click "Create Account"
6. Login with your credentials

### Testing Features

**As a Company:**

1. Login to company dashboard
2. Click "Add New Flight"
3. Fill in flight details:
   - Flight Name: Morning Express
   - Flight Code: ME001
   - Max Passengers: 150
   - Ticket Price: 299.99
4. Add itinerary stops:
   - Stop 1: New York (start and end times)
   - Stop 2: Chicago (start and end times)
   - Stop 3: Los Angeles (start and end times)
5. Click "Save Flight"
6. View your created flight
7. Check bookings when passengers book

**As a Passenger:**

1. Login to passenger dashboard
2. Search for flights:
   - From: New York
   - To: Los Angeles
3. View available flights
4. Click "Book Flight" on a flight
5. Select payment method (Account or Cash)
6. Confirm booking
7. View your bookings in "My Bookings"
8. Send message to airline company

## 🔧 Troubleshooting

### Common Issues and Solutions

**Issue: "Database connection failed"**

- ✅ Check MySQL is running: `mysql --version`
- ✅ Verify database exists: `SHOW DATABASES;`
- ✅ Check credentials in `Backend/config/config.php`
- ✅ Ensure database schema is imported

**Issue: "CORS errors" in browser console**

- ✅ Backend and frontend must run on different ports
- ✅ Backend should be on port 8000
- ✅ Frontend should be on port 3000 (or different from backend)

**Issue: "File upload failed"**

- ✅ Check `Backend/uploads/` directory exists
- ✅ Check directory permissions (should be writable)
- ✅ Windows: Right-click folder → Properties → Security
- ✅ Linux/Mac: `chmod 755 Backend/uploads`

**Issue: "Page not found" or 404 errors**

- ✅ Check you're accessing correct URL: `http://localhost:3000`
- ✅ Ensure PHP server is running (check terminal)
- ✅ Verify file paths are correct

**Issue: "Login not working"**

- ✅ Open browser Developer Tools (F12)
- ✅ Check Console tab for errors
- ✅ Check Network tab for API responses
- ✅ Verify backend server is running on port 8000
- ✅ Check database has `users` table

**Issue: "Images/logos not displaying"**

- ✅ Check files uploaded to `Backend/uploads/`
- ✅ Check file permissions
- ✅ Verify image paths in database

## 📱 Browser Support

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

## 🎨 Design Features

- **Color Palette:**

  - Primary Blue: #0066CC
  - Secondary Orange: #FF6B35
  - Teal Accent: #00B4D8
  - Modern gray scale for text and backgrounds

- **Responsive Design:**

  - Desktop: Full featured layout
  - Tablet: Optimized sidebar and grid
  - Mobile: Stacked layout with hamburger menu

- **UI Components:**
  - Modern card-based design
  - Smooth animations and transitions
  - Professional typography
  - Intuitive navigation
  - Interactive modals and forms

## 📂 Project Structure Details

### Frontend Pages

- `index.html` - Landing page with features
- `login.html` - User authentication
- `register.html` - User registration (passenger/company)
- `passenger-dashboard.html` - Passenger interface
- `company-dashboard.html` - Company interface

### Frontend JavaScript

- `config.js` - API configuration
- `api.js` - API wrapper and utilities
- `main.js` - Common functionality
- `auth.js` - Authentication logic
- `passenger.js` - Passenger dashboard logic
- `company.js` - Company dashboard logic

### Backend Services

- `auth/` - Login, register, logout
- `passenger/` - Search flights, book, view bookings
- `company/` - Manage flights, view bookings
- `messages/` - Send and receive messages
- `profile/` - Get and update user profile

## 🔐 Security Features

- Password hashing with bcrypt
- Session management
- SQL injection protection (PDO prepared statements)
- XSS prevention
- File upload validation
- User authentication and authorization

## 📊 Database Schema

- **users** - All user accounts (passengers and companies)
- **passengers** - Passenger-specific data
- **companies** - Company-specific data
- **flights** - Flight information
- **flight_itinerary** - Flight route details
- **bookings** - Flight bookings
- **messages** - User messaging
- **transactions** - Payment tracking

## 🎓 Academic Notes

This project fulfills the requirements:

- ✅ Frontend: HTML, CSS, JavaScript, jQuery, jQuery UI, PHP
- ✅ Backend: PHP services & MySQL Database
- ✅ No frameworks used
- ✅ Modern, professional design
- ✅ Complete functionality

## 📞 Support

If you encounter any issues:

1. Check the troubleshooting section
2. Verify all installation steps
3. Check browser console for errors (F12)
4. Ensure both servers are running

## 🎉 Success!

If you can:

- ✅ Access the landing page
- ✅ Register new accounts
- ✅ Login successfully
- ✅ See the dashboards
- ✅ Perform core functions

**Congratulations! Your SkyBooker flight booking system is running successfully!**

---

**Developed with ❤️ for Flight Booking Excellence**

**Version:** 1.0.0  
**Last Updated:** December 2025
