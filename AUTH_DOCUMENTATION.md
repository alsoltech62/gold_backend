# Authentication API Documentation

This document describes the updated authentication flow for the Gold Saving Platform.

## 1. Signup API
Used to register a new user with their details.

- **URL:** `/api/auth/signup.php`
- **Method:** `POST`
- **Content-Type:** `application/json`

### Request Body:
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

### Response (Success):
```json
{
  "success": true,
  "message": "User registered successfully. You can now login with OTP."
}
```

### Response (Error - Already Registered):
```json
{
  "success": false,
  "message": "User already registered with this mobile number"
}
```

---

## 2. Login - Send OTP API
Used to initiate login by sending an OTP to a registered mobile number.

- **URL:** `/api/auth/send_otp.php`
- **Method:** `POST`
- **Content-Type:** `application/json`

### Request Body:
```json
{
  "mobile": "9876543210"
}
```

### Response (Success):
```json
{
  "success": true,
  "message": "OTP sent successfully",
  "dev_otp": "123456"
}
```

### Response (Error - Not Registered):
```json
{
  "success": false,
  "message": "User not registered. Please signup first."
}
```

---

## 3. Login - Verify OTP API
Used to verify the OTP and obtain an authentication token.

- **URL:** `/api/auth/verify_otp.php`
- **Method:** `POST`
- **Content-Type:** `application/json`

### Request Body:
```json
{
  "mobile": "9876543210",
  "otp": "123456"
}
```

### Response (Success):
```json
{
  "success": true,
  "token": "JWT_TOKEN_HERE",
  "user": {
    "id": 1,
    "name": "John Doe",
    "mobile": "9876543210",
    "email": "john@example.com",
    "is_admin": false
  }
}
```

### Response (Error - Invalid OTP):
```json
{
  "success": false,
  "message": "Invalid OTP"
}
```

---

## Admin APIs

### 1. Update Gold Rate
Used by admins to update the current gold rate.

- **URL:** `/api/admin/update_rate.php`
- **Method:** `POST`
- **Content-Type:** `application/json`
- **Authentication:** Required (Admin Token)

#### Request Body:
```json
{
  "rate_per_gram": 7250.50,
  "date": "2026-05-11"
}
```

#### Response (Success):
```json
{
  "success": true,
  "message": "Gold rate updated successfully"
}
```

#### Response (Error - Unauthorized):
```json
{
  "success": false,
  "message": "Admin access required"
}
```

---

## User APIs

### 1. User Transactions
Used to list user transaction history or create a new transaction (buy/sell).

- **URL:** `/api/user/transactions.php`
- **Method:** `GET` | `POST`
- **Authentication:** Required (User Token)

#### GET (List Transactions):
**Query Params:**
- `page` (optional, default 1)
- `type` (optional: buy, sell, delivery)

#### POST (Create Transaction):
**Request Body:**
```json
{
  "type": "buy",
  "amount_inr": 5000,
  "payment_method": "UPI",
  "payment_id": "txn_123456"
}
```

#### Response (Success):
```json
{
  "success": true,
  "message": "Buy request submitted successfully",
  "transaction_id": 42
}
```
