# 🚀 XAMPP Setup Guide - SkyBooker

## Step 1: Download & Install XAMPP

1. **Download XAMPP**

   - Visit: https://www.apachefriends.org/download.html
   - Download the latest version (PHP 8.0+)
   - Run the installer
   - Install to default location: `C:\xampp`

2. **Important Components to Install**
   - ✅ Apache
   - ✅ MySQL
   - ✅ PHP
   - ✅ phpMyAdmin
   - (Other components are optional)

---

## Step 2: Setup MySQL Database

1. **Open XAMPP Control Panel**

   - Start → Search for "XAMPP Control Panel"
   - Or run: `C:\xampp\xampp-control.exe`

2. **Start MySQL**

   - Click "Start" button next to MySQL
   - Wait until it shows "Running" (green highlight)

3. **Open phpMyAdmin**

   - Click "Admin" button next to MySQL
   - Or go to: http://localhost/phpmyadmin

4. **Create Database**

   - Click "New" in left sidebar
   - Database name: `flight_booking_uni`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

5. **Import Database Schema**
   - Select `flight_booking_uni` database
   - Click "Import" tab
   - Click "Choose File"
   - Navigate to: `d:\Gam3a\Gam3a Fourth level\Web\FlightBookingProject\Backend\database_schema.sql`
   - Click "Go" at the bottom
   - Wait for "Import has been successfully finished" message

---

## Step 3: Run Backend Server

**Open PowerShell or Command Prompt:**

```powershell
# Navigate to Backend folder
cd "d:\Gam3a\Gam3a Fourth level\Web\FlightBookingProject\Backend"

# Start backend server on port 8000
C:\xampp\php\php.exe -S localhost:8000

# You should see:
# PHP 8.x.x Development Server (http://localhost:8000) started
```

**Keep this window open!** ⚠️

---

## Step 4: Run Frontend Server

**Open a NEW PowerShell or Command Prompt window:**

```powershell
# Navigate to Frontend folder
cd "d:\Gam3a\Gam3a Fourth level\Web\FlightBookingProject\FrontEnd"

# Start frontend server on port 3000
C:\xampp\php\php.exe -S localhost:3000

# You should see:
# PHP 8.x.x Development Server (http://localhost:3000) started
```

**Keep this window open too!** ⚠️

---

## Step 5: Open in Browser

**Open your web browser and go to:**

```
http://localhost:3000
```

**You should see the SkyBooker landing page!** 🎉

---

## 🧪 Quick Test

### Test Backend:

Visit: http://localhost:8000/services/auth/login.php

You should see JSON response (even if error, it means backend is working)

### Test Frontend:

Visit: http://localhost:3000

You should see the beautiful landing page with blue colors

---

## 📝 Create Test Accounts

### 1. Register as Passenger

1. Click "Sign Up" or "Get Started"
2. Select "Passenger"
3. Fill in:
   - Name: Test Passenger
   - Username: testpassenger
   - Email: passenger@test.com
   - Phone: +1234567890
   - Password: test1234
   - Confirm Password: test1234
4. Check "I agree to terms"
5. Click "Create Account"
6. Login with the credentials

### 2. Register as Company

1. Click "Sign Up"
2. Select "Airline Company"
3. Fill in:
   - Name: Test Airlines
   - Username: testairline
   - Email: airline@test.com
   - Phone: +0987654321
   - Password: test1234
   - Confirm Password: test1234
   - Bio: Test airline company
   - Address: Test Address
   - Location: Test City
4. Check "I agree to terms"
5. Click "Create Account"
6. Login with the credentials

### 3. Test the System

**As Company:**

- Create a flight with stops (e.g., Cairo → Dubai → London)
- View your flights list

**As Passenger:**

- Search flights
- Book a flight
- View bookings
- Send message to airline

---

## ⚠️ Troubleshooting

### Problem: "Database connection failed"

**Solution:**

1. Make sure MySQL is running in XAMPP Control Panel
2. Check `Backend/config/config.php` has correct password:
   ```php
   define('DB_PASS', ''); // XAMPP default is empty
   ```

### Problem: "Port already in use"

**Solution:**

```powershell
# Use different ports
# Backend:
C:\xampp\php\php.exe -S localhost:8001

# Frontend:
C:\xampp\php\php.exe -S localhost:3001

# Then update FrontEnd/js/config.js:
BASE_URL: 'http://localhost:8001'
```

### Problem: "Page not found"

**Solution:**

- Make sure both servers are running (check both terminals)
- Check you're using correct URL: http://localhost:3000
- Verify you're in the correct directories when starting servers

### Problem: "Login not working"

**Solution:**

1. Open browser Developer Tools (F12)
2. Check Console tab for errors
3. Check Network tab - API calls should go to http://localhost:8000
4. Verify database has tables (check phpMyAdmin)

---

## 🎯 Summary of What You Need Running

1. ✅ **XAMPP MySQL** - Running in XAMPP Control Panel
2. ✅ **Backend Server** - Terminal 1 (port 8000)
3. ✅ **Frontend Server** - Terminal 2 (port 3000)
4. ✅ **Browser** - http://localhost:3000

---

## 💡 Pro Tips

### To Stop Servers:

- Press `Ctrl + C` in the PowerShell windows

### To Restart Servers:

- Just press the up arrow key and Enter in each terminal

### Add PHP to PATH (Optional):

1. Windows Search → "Environment Variables"
2. Edit "Path" variable
3. Add: `C:\xampp\php`
4. Restart terminal
5. Now you can just use `php` instead of full path

### View Database:

- Go to http://localhost/phpmyadmin
- Select `flight_booking_uni` database
- Browse tables to see data

---

## ✨ You're All Set!

Once both servers are running and you see the landing page, you're ready to test all features!

**Enjoy your flight booking system!** ✈️
