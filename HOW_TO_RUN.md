# 🎯 Step-by-Step Guide: How to Run SkyBooker Locally

## 📋 Prerequisites Check

Before starting, verify you have:

- [ ] PHP 8.0+ installed
- [ ] MySQL 8.0+ installed
- [ ] A code editor (VS Code, Notepad++, etc.)
- [ ] A web browser (Chrome, Firefox, etc.)

### Check PHP Installation

Open Command Prompt (Windows) or Terminal (Mac/Linux):

```bash
php -v
```

You should see: `PHP 8.x.x`

If not installed, download from: https://www.php.net/downloads

### Check MySQL Installation

```bash
mysql --version
```

You should see: `mysql Ver 8.x.x`

If not installed, download from: https://dev.mysql.com/downloads/mysql/

---

## 🗄️ Step 1: Database Setup

### Option A: Using MySQL Command Line

1. **Open Command Prompt/Terminal**

2. **Login to MySQL**:

   ```bash
   mysql -u root -p
   ```

   Enter your MySQL password when prompted.

3. **Create Database**:

   ```sql
   CREATE DATABASE flight_booking_uni CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   You should see: `Query OK, 1 row affected`

4. **Exit MySQL**:

   ```sql
   exit;
   ```

5. **Import Schema**:

   ```bash
   cd "d:\Gam3a\Gam3a Fourth level\Web\FlightBookingProject"
   mysql -u root -p flight_booking_uni < Backend/database_schema.sql
   ```

   Enter password when prompted.

6. **Verify Import** (optional):
   ```bash
   mysql -u root -p
   USE flight_booking_uni;
   SHOW TABLES;
   ```
   You should see 8 tables listed.

### Option B: Using phpMyAdmin

1. **Open phpMyAdmin** in browser (usually `http://localhost/phpmyadmin`)

2. **Create Database**:

   - Click "New" in left sidebar
   - Database name: `flight_booking_uni`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Import Schema**:
   - Select `flight_booking_uni` database
   - Click "Import" tab
   - Click "Choose File"
   - Navigate to: `Backend/database_schema.sql`
   - Click "Go" at bottom
   - Wait for "Import successful" message

---

## ⚙️ Step 2: Backend Configuration

1. **Navigate to Backend config folder**:

   ```bash
   cd "d:\Gam3a\Gam3a Fourth level\Web\FlightBookingProject\Backend\config"
   ```

2. **Create config.php file**:

   **Windows (using Command Prompt)**:

   ```bash
   notepad config.php
   ```

   **Mac/Linux (using Terminal)**:

   ```bash
   nano config.php
   ```

   Or use any text editor to create the file.

3. **Copy and paste this content** into `config.php`:

   ```php
   <?php
   // Database Configuration
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3306');
   define('DB_NAME', 'flight_booking_uni');
   define('DB_USER', 'root');
   define('DB_PASS', 'YOUR_MYSQL_PASSWORD_HERE');  // ← CHANGE THIS!
   define('DB_CHARSET', 'utf8mb4');

   // Upload Directory
   define('UPLOAD_DIR', __DIR__ . '/../uploads/');

   // Password Settings
   define('PASSWORD_MIN_LENGTH', 8);
   ```

4. **IMPORTANT**: Replace `YOUR_MYSQL_PASSWORD_HERE` with your actual MySQL root password!

5. **Save the file**:

   - Notepad: File → Save
   - Nano: Ctrl+O, Enter, Ctrl+X

6. **Verify the file was created**:
   ```bash
   dir config.php    # Windows
   ls config.php     # Mac/Linux
   ```

---

## 🚀 Step 3: Start the Servers

### You Need TWO Terminal Windows!

### Terminal Window 1 - Backend Server

1. **Open Command Prompt/Terminal**

2. **Navigate to Backend folder**:

   ```bash
   cd "d:\Gam3a\Gam3a Fourth level\Web\FlightBookingProject\Backend"
   ```

3. **Start PHP server on port 8000**:

   ```bash
   php -S localhost:8000
   ```

4. **You should see**:

   ```
   PHP 8.x.x Development Server (http://localhost:8000) started
   ```

5. **✅ Leave this terminal window OPEN and RUNNING!**

### Terminal Window 2 - Frontend Server

1. **Open a NEW Command Prompt/Terminal** (don't close the first one!)

2. **Navigate to Frontend folder**:

   ```bash
   cd "d:\Gam3a\Gam3a Fourth level\Web\FlightBookingProject\FrontEnd"
   ```

3. **Start PHP server on port 3000**:

   ```bash
   php -S localhost:3000
   ```

4. **You should see**:

   ```
   PHP 8.x.x Development Server (http://localhost:3000) started
   ```

5. **✅ Leave this terminal window OPEN and RUNNING too!**

**Now you have TWO servers running simultaneously!**

---

## 🌐 Step 4: Open the Application

1. **Open your web browser** (Chrome, Firefox, Safari, Edge)

2. **Type in the address bar**:

   ```
   http://localhost:3000
   ```

3. **Press Enter**

4. **🎉 You should see the SkyBooker landing page!**

---

## 👤 Step 5: Test the Application

### Create Your First Passenger Account

1. **Click "Sign Up" or "Get Started"**

2. **Select "Passenger" user type** (click the Passenger button)

3. **Fill in the form**:

   - Full Name: `John Doe`
   - Username: `johndoe`
   - Email: `john@test.com`
   - Phone Number: `+1234567890`
   - Password: `test1234`
   - Confirm Password: `test1234`
   - Check "I agree to the Terms"

4. **Click "Create Account"**

5. **Wait for "Registration successful!" message**

6. **You'll be redirected to login page**

7. **Login with**:

   - Email: `john@test.com`
   - Password: `test1234`

8. **Click "Login"**

9. **🎊 You should now see the Passenger Dashboard!**

### Create a Company Account

1. **Logout** from passenger account (click Logout button)

2. **Go back to homepage**: `http://localhost:3000`

3. **Click "Sign Up"**

4. **Select "Airline Company" user type**

5. **Fill in the form**:

   - Company Name: `SkyAir Airlines`
   - Username: `skyair`
   - Email: `airline@test.com`
   - Phone Number: `+0987654321`
   - Password: `test1234`
   - Confirm Password: `test1234`
   - Company Bio: `Leading airline service`
   - Address: `123 Airport Road`
   - Location: `New York, USA`
   - Check "I agree to the Terms"

6. **Click "Create Account"**

7. **Login with**:

   - Email: `airline@test.com`
   - Password: `test1234`

8. **🎊 You should now see the Company Dashboard!**

---

## ✈️ Step 6: Test Main Features

### As a Company - Create a Flight

1. **In Company Dashboard**, click **"Add New Flight"** button

2. **Fill in flight details**:

   - Flight Name: `Morning Express`
   - Flight Code: `ME001`
   - Max Passengers: `150`
   - Ticket Price: `299.99`

3. **Add Itinerary**:

   **Stop 1 (already there)**:

   - City: `New York`
   - Arrival: `2025-12-25T08:00` (format: YYYY-MM-DDTHH:MM)
   - Departure: `2025-12-25T10:00`

   **Click "Add City Stop"**

   **Stop 2**:

   - City: `Chicago`
   - Arrival: `2025-12-25T12:00`
   - Departure: `2025-12-25T14:00`

   **Click "Add City Stop" again**

   **Stop 3**:

   - City: `Los Angeles`
   - Arrival: `2025-12-25T16:00`
   - Departure: `2025-12-25T18:00`

4. **Click "Save Flight"**

5. **✅ You should see your flight in the list!**

### As a Passenger - Search and Book

1. **Logout** from company account

2. **Login as passenger** (john@test.com / test1234)

3. **In Passenger Dashboard**, you're on **"Search Flights"** by default

4. **Search**:

   - From: `New York`
   - To: `Los Angeles`
   - Click "Search"

5. **You should see the flight you created!**

6. **Click "Book Flight"** on the flight

7. **In the booking modal**:

   - Select payment method (Account or Cash)
   - Click "Confirm Booking"

8. **✅ Flight booked successfully!**

9. **Click "My Bookings"** in sidebar to see your booking

---

## 🔍 Verification Checklist

After following all steps, verify:

- [ ] ✅ Both terminal windows are running
- [ ] ✅ Can access http://localhost:3000
- [ ] ✅ Can register new passenger account
- [ ] ✅ Can login as passenger
- [ ] ✅ Can see passenger dashboard
- [ ] ✅ Can register new company account
- [ ] ✅ Can login as company
- [ ] ✅ Can see company dashboard
- [ ] ✅ Can create a new flight
- [ ] ✅ Can search for flights
- [ ] ✅ Can book a flight
- [ ] ✅ Can view bookings

**If all checked ✅ - CONGRATULATIONS! Everything works!**

---

## 🐛 Troubleshooting

### Problem: "Database connection failed"

**Solution**:

1. Check MySQL is running:
   ```bash
   mysql --version
   ```
2. Verify password in `Backend/config/config.php`
3. Make sure database exists:
   ```sql
   mysql -u root -p
   SHOW DATABASES;
   ```

### Problem: "Page not found" when opening localhost:3000

**Solution**:

1. Check frontend server is running in Terminal 2
2. Look for errors in the terminal
3. Make sure you're in the FrontEnd directory
4. Try restarting the server:
   - Press Ctrl+C to stop
   - Run `php -S localhost:3000` again

### Problem: "Cannot login" or "Invalid credentials"

**Solution**:

1. Open browser Developer Tools (Press F12)
2. Go to Console tab
3. Look for red error messages
4. Check Network tab for failed requests
5. Verify backend server is running (Terminal 1)

### Problem: "CORS error" in browser console

**Solution**:

1. Make sure backend is on port 8000
2. Make sure frontend is on port 3000
3. They MUST be on different ports
4. Restart both servers if needed

### Problem: "File upload failed"

**Solution**:

1. Check `Backend/uploads/` folder exists
2. Make folder writable:
   - Windows: Right-click → Properties → Security
   - Mac/Linux: `chmod 755 Backend/uploads`

---

## 🛑 How to Stop the Servers

When you're done testing:

1. **Go to Terminal 1** (Backend)

   - Press `Ctrl + C`
   - Server stopped

2. **Go to Terminal 2** (Frontend)

   - Press `Ctrl + C`
   - Server stopped

3. **Close browser tabs**

---

## 🔄 How to Restart Later

Next time you want to run the project:

1. **Open Terminal 1**:

   ```bash
   cd "d:\Gam3a\Gam3a Fourth level\Web\FlightBookingProject\Backend"
   php -S localhost:8000
   ```

2. **Open Terminal 2**:

   ```bash
   cd "d:\Gam3a\Gam3a Fourth level\Web\FlightBookingProject\FrontEnd"
   php -S localhost:3000
   ```

3. **Open browser**: `http://localhost:3000`

---

## 📞 Need Help?

If something isn't working:

1. ✅ Read the error message carefully
2. ✅ Check the Troubleshooting section above
3. ✅ Verify all steps were followed
4. ✅ Check browser console (F12) for errors
5. ✅ Make sure both servers are running
6. ✅ Try restarting the servers

---

## 🎉 Success Indicators

**You know everything is working when**:

✅ Landing page loads with blue gradient hero section  
✅ Can register and login  
✅ Dashboard shows your name and balance  
✅ Can navigate between sections  
✅ Company can create flights  
✅ Passenger can search and book  
✅ Messages can be sent  
✅ Profile can be updated

---

**🚀 Happy Flying with SkyBooker! ✈️**

If you followed all steps and everything works - **CONGRATULATIONS!** 🎊  
Your flight booking system is fully operational!
