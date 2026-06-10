# Gold Saving Platform - API Documentation

This document provides details for all API endpoints available in the Gold Saving Platform backend.

## Base URL
`http://localhost:8000` (or your server domain)

## Authentication
Most APIs require a Bearer token in the `Authorization` header:
`Authorization: Bearer <your_jwt_token>`

---

## 1. Authentication APIs (`/api/auth/`)

### 1.1 Signup
Register a new user.
- **URL:** `/api/auth/signup.php`
- **Method:** `POST`
- **Body (JSON):**
  ```json
  {
    "name": "John Doe",
    "mobile": "9876543210",
    "email": "john@example.com",
    "address": "123 Main St",
    "city": "Mumbai",
    "state": "Maharashtra",
    "pincode": "400001",
    "aadhar_number": "123456789012",
    "pan_number": "ABCDE1234F"
  }
  ```
- **Response:** `{"success": true, "message": "User registered successfully..."}`

### 1.2 Send OTP
Request a login OTP.
- **URL:** `/api/auth/send_otp.php`
- **Method:** `POST`
- **Body (JSON):** `{"mobile": "9876543210"}`
- **Response:** `{"success": true, "message": "OTP sent", "dev_otp": "123456"}`

### 1.3 Verify OTP
Verify OTP and get JWT token.
- **URL:** `/api/auth/verify_otp.php`
- **Method:** `POST`
- **Body (JSON):** `{"mobile": "9876543210", "otp": "123456"}`
- **Response:** `{"success": true, "token": "...", "user": {...}}`

---

## 2. Gold APIs (`/api/gold/`)

### 2.1 Get Current Rate
Fetch the latest gold price.
- **URL:** `/api/gold/rate.php`
- **Method:** `GET`
- **Auth:** Required
- **Response:** 
  ```json
  {
    "success": true, 
    "data": {
      "rate_per_gram": 7250.5, 
      "change": 10.5, 
      "change_percent": 0.15
    }
  }
  ```

### 2.2 Buy Gold
Purchase gold in INR.
- **URL:** `/api/gold/buy.php`
- **Method:** `POST`
- **Auth:** Required
- **Body (JSON):** `{"amount_inr": 5000, "payment_method": "UPI", "payment_id": "TXN123"}`
- **Response:** `{"success": true, "message": "Gold purchased", "data": {"gold_grams": 0.6896, ...}}`

### 2.3 Sell Gold
Sell gold grams.
- **URL:** `/api/gold/sell.php`
- **Method:** `POST`
- **Auth:** Required
- **Body (JSON):** `{"gold_grams": 0.5}`
- **Response:** `{"success": true, "message": "Sell request submitted"}`

---

## 3. User & Transaction APIs (`/api/user/`, `/api/transactions/`, `/api/delivery/`)

### 3.1 User Dashboard
Get balance, profit/loss, and recent activity.
- **URL:** `/api/user/dashboard.php`
- **Method:** `GET`
- **Auth:** Required
- **Response:** `{"success": true, "data": {"total_gold_grams": 1.5, "current_value_inr": 10875, ...}}`

### 3.2 User Profile
Get or update profile.
- **URL:** `/api/user/profile.php`
- **Method:** `GET` | `PUT`
- **Auth:** Required
- **PUT Body:** Fields to update (e.g., `{"name": "New Name"}`)

### 3.3 Transaction List
List all transactions for the user.
- **URL:** `/api/transactions/list.php`
- **Method:** `GET`
- **Params:** `page`, `type` (buy/sell/delivery)
- **Auth:** Required

### 3.4 Request Delivery
Request physical delivery of gold.
- **URL:** `/api/delivery/request.php`
- **Method:** `POST`
- **Auth:** Required
- **Body (JSON):** `{"gold_grams": 1.0, "address": "Full Address...", "city": "Mumbai", ...}`

---

## 4. Admin APIs (`/api/admin/`)

### 4.1 Admin Dashboard
System-wide stats.
- **URL:** `/api/admin/dashboard.php`
- **Method:** `GET`
- **Auth:** Required (Admin)

### 4.2 Manage Customers
List, create, or update users.
- **URL:** `/api/admin/customers.php`
- **Method:** `GET` | `POST` | `PUT`
- **Params (GET):** `page`, `search`
- **Auth:** Required (Admin)

### 4.3 Manage Transactions
View or update transaction status.
- **URL:** `/api/admin/transactions.php`
- **Method:** `GET` | `POST` | `PUT`
- **Params (PUT):** `id` (transaction ID)
- **PUT Body:** `{"status": "completed", "notes": "Approved"}`
- **Auth:** Required (Admin)

### 4.4 Update Gold Rate
Update daily gold price.
- **URL:** `/api/admin/gold_rate.php`
- **Method:** `GET` | `POST`
- **Body (POST):** `{"rate_per_gram": 7300, "date": "2026-05-11"}`
- **Auth:** Required (Admin)
