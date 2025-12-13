# Flight Booking System - Backend API Documentation

A comprehensive backend system for a flight booking platform built with pure PHP (no frameworks).

## 🚀 Features

- **User Management**

  - Dual user types (Companies & Passengers)
  - Secure authentication with session management
  - Profile management with file uploads
  - Account balance system

- **Flight Management**

  - Create and manage flights (companies)
  - Multi-city itineraries with timestamps
  - Search flights by origin/destination
  - Real-time seat availability

- **Booking System**

  - Multiple payment methods (account/cash)
  - Automatic balance transactions
  - Booking confirmation workflow
  - Flight cancellation with automatic refunds

- **Messaging System**
  - Direct communication between passengers and companies
  - Flight-specific inquiries
  - Unread message tracking
  - Conversation history

## 📁 Project Structure

```
project/
├── config/
│   ├── config.php           # Database & app configuration
│   └── constants.php        # Application constants
├── includes/
│   ├── db.php              # PDO database singleton
│   ├── auth.php            # Authentication helpers
│   ├── validation.php      # Input validation class
│   └── helpers.php         # Utility functions
├── services/
│   ├── auth/
│   │   ├── register.php    # User registration
│   │   ├── login.php       # User login
│   │   └── logout.php      # User logout
│   ├── company/
│   │   ├── addFlight.php        # Create new flight
│   │   ├── getFlights.php       # List company flights
│   │   ├── getFlightDetails.php # Detailed flight info
│   │   ├── cancelFlight.php     # Cancel flight & refund
│   │   └── confirmBooking.php   # Confirm pending booking
│   ├── passenger/
│   │   ├── searchFlights.php    # Search available flights
│   │   ├── takeFlight.php       # Book a flight
│   │   └── getMyFlights.php     # User's bookings
│   ├── messages/
│   │   ├── send.php        # Send message
│   │   └── get.php         # Get conversations
│   └── profile/
│       ├── get.php         # Get user profile
│       └── update.php      # Update profile
├── uploads/
│   ├── logos/              # Company logos
│   ├── photos/             # Passenger photos
│   └── passports/          # Passport images
└── database_schema.sql     # Complete database schema
```

## 🔧 Setup Instructions

### 1. Database Setup

```bash
# Create database
mysql -u root -p < database_schema.sql
```

Or import via phpMyAdmin:

1. Create database `flight_booking`
2. Import `database_schema.sql`

### 2. Configuration

Edit `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'flight_booking');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', 'http://localhost/flight-booking');
```

### 3. File Permissions

```bash
chmod -R 755 uploads/
chmod -R 755 uploads/logos/
chmod -R 755 uploads/photos/
chmod -R 755 uploads/passports/
```

### 4. Create Upload Directories

```bash
mkdir -p uploads/logos
mkdir -p uploads/photos
mkdir -p uploads/passports
```

## 📡 API Endpoints

### Authentication

#### Register User

```http
POST /services/auth/register.php
Content-Type: multipart/form-data

Parameters:
- email (required)
- username (required)
- password (required, min 8 chars)
- name (required)
- tel (required)
- user_type (required: "company" or "passenger")

For Companies:
- bio (optional)
- address (optional)
- location (optional)
- logo (file, optional)

For Passengers:
- photo (file, optional)
- passport_img (file, optional)
```

#### Login

```http
POST /services/auth/login.php
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

#### Logout

```http
POST /services/auth/logout.php
```

### Company Endpoints

#### Add Flight

```http
POST /services/company/addFlight.php
Content-Type: application/json

{
  "flight_name": "Morning Express",
  "flight_code": "FL20241215001",
  "max_passengers": 150,
  "fees": 299.99,
  "itinerary": [
    {
      "city": "New York",
      "start_datetime": "2024-12-20 08:00:00",
      "end_datetime": "2024-12-20 10:00:00"
    },
    {
      "city": "Chicago",
      "start_datetime": "2024-12-20 12:00:00",
      "end_datetime": "2024-12-20 14:00:00"
    }
  ]
}
```

#### Get Company Flights

```http
GET /services/company/getFlights.php
```

#### Get Flight Details

```http
GET /services/company/getFlightDetails.php?flight_id=1
```

#### Cancel Flight

```http
POST /services/company/cancelFlight.php
Content-Type: application/json

{
  "flight_id": 1
}
```

#### Confirm Booking

```http
POST /services/company/confirmBooking.php
Content-Type: application/json

{
  "booking_id": 5
}
```

### Passenger Endpoints

#### Search Flights

```http
GET /services/passenger/searchFlights.php?from=New York&to=Chicago
GET /services/passenger/searchFlights.php?from=New York
GET /services/passenger/searchFlights.php (all flights)
```

#### Book Flight

```http
POST /services/passenger/takeFlight.php
Content-Type: application/json

{
  "flight_id": 1,
  "payment_method": "account"  // or "cash"
}
```

#### Get My Flights

```http
GET /services/passenger/getMyFlights.php
```

### Messaging

#### Send Message

```http
POST /services/messages/send.php
Content-Type: application/json

{
  "receiver_id": 2,
  "message": "Hello, I have a question about the flight",
  "message_type": "flight_inquiry",  // optional
  "flight_id": 1  // optional
}
```

#### Get Messages

```http
# Get all conversations
GET /services/messages/get.php

# Get specific conversation
GET /services/messages/get.php?with_user_id=2
```

### Profile

#### Get Profile

```http
GET /services/profile/get.php
```

#### Update Profile

```http
POST /services/profile/update.php
Content-Type: multipart/form-data

Parameters:
- name (optional)
- tel (optional)
- password (optional)

For Companies:
- bio (optional)
- address (optional)
- location (optional)
- logo (file, optional)

For Passengers:
- photo (file, optional)
- passport_img (file, optional)
```

## 🔐 Security Features

- **Password Hashing**: BCrypt with cost factor 12
- **SQL Injection Prevention**: Prepared statements with PDO
- **XSS Protection**: Input sanitization
- **Session Security**: HTTP-only cookies, regeneration on login
- **File Upload Validation**: Type and size checking
- **CSRF Protection**: Ready for token implementation
- **Input Validation**: Comprehensive validation rules

## 💾 Database Schema

### Main Tables

- **users**: Stores all user accounts (companies & passengers)
- **companies**: Company-specific details
- **passengers**: Passenger-specific details
- **flights**: Flight information
- **flight_itinerary**: Multi-city flight routes
- **bookings**: Flight reservations
- **messages**: User communications
- **transactions**: Financial transaction history

## 🎯 Response Format

All endpoints return JSON in this format:

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description",
  "data": {
    "errors": {
      "field": ["Error message"]
    }
  }
}
```

## 📊 HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `409` - Conflict
- `500` - Server Error

## 🧪 Testing

Use the sample data provided in the schema:

**Company Account:**

- Email: airline@example.com
- Password: password
- Username: skyair

**Passenger Account:**

- Email: john@example.com
- Password: password
- Username: johndoe

## 🔄 Payment Flow

### Account Payment (Automatic)

1. Passenger books flight with "account" payment
2. System checks balance
3. Deducts from passenger account
4. Adds to company account
5. Records transactions
6. Booking confirmed immediately

### Cash Payment (Manual)

1. Passenger books flight with "cash" payment
2. Booking status set to "pending"
3. Company confirms payment manually via `confirmBooking.php`
4. Booking status updated to "confirmed"

## 🎨 Frontend Integration

AJAX example using jQuery:

```javascript
// Login
$.ajax({
  url: "services/auth/login.php",
  method: "POST",
  data: JSON.stringify({
    email: "user@example.com",
    password: "password",
  }),
  contentType: "application/json",
  success: function (response) {
    if (response.success) {
      // Redirect based on user_type
      if (response.data.user_type === "company") {
        window.location.href = "public/company/home.php";
      } else {
        window.location.href = "public/passenger/home.php";
      }
    }
  },
});

// Search flights
$.get(
  "services/passenger/searchFlights.php",
  {
    from: "New York",
    to: "Chicago",
  },
  function (response) {
    if (response.success) {
      // Display flights
      response.data.flights.forEach((flight) => {
        // Render flight card
      });
    }
  }
);
```

## 🛠️ Helper Functions

Available in `includes/helpers.php`:

- `jsonResponse()` - Send JSON response
- `uploadFile()` - Handle file uploads
- `deleteFile()` - Delete uploaded files
- `checkBalance()` - Verify user balance
- `updateBalance()` - Modify account balance
- `recordTransaction()` - Log financial transactions
- `getUserInfo()` - Get complete user details
- `checkOwnership()` - Verify resource ownership

## 📝 Validation Rules

Available in `includes/validation.php`:

- `required` - Field must not be empty
- `email` - Valid email format
- `min:n` - Minimum length
- `max:n` - Maximum length
- `numeric` - Must be numeric
- `integer` - Must be integer
- `positive` - Must be positive number
- `in:a,b,c` - Must be one of values
- `unique:table,column` - Must be unique in database
- `match:field` - Must match another field
- `phone` - Valid phone format
- `date` - Valid date
- `datetime` - Valid datetime (Y-m-d H:i:s)

## 🚨 Error Handling

All errors are logged to PHP error log. Enable logging in `config/config.php`:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);  // Set to 0 in production
```

## 📈 Future Enhancements

- Email notifications
- Payment gateway integration
- Multi-language support
- API rate limiting
- Flight seat selection
- Loyalty program
- Reviews and ratings
- Real-time notifications

## 📄 License

This project is open source and available for educational purposes.

## 🤝 Support

For issues or questions:

1. Check the error logs
2. Verify database connection
3. Ensure file permissions are correct
4. Validate input data format

---

**Note**: This is a backend-only implementation. Frontend integration requires HTML, CSS, JavaScript, jQuery, and jQuery UI as specified in the requirements.
