# Gold Platform – PHP Backend

## Requirements
- PHP 8.1+
- MySQL 8.0+
- Apache with mod_rewrite enabled

## Setup
1. Create MySQL database: `mysql -u root -p < sql/schema.sql`
2. Copy `.env.example` to `.env` and fill in your credentials
3. Point your web server document root to this `backend/` folder
4. All API calls go through `index.php` via `.htaccess` routing

## API Endpoints
| Method | Path | Description |
|--------|------|-------------|
| POST | /api/auth/send-otp | Send OTP to mobile |
| POST | /api/auth/verify-otp | Verify OTP, get JWT token |
| GET | /api/gold/rate | Get today's gold rate |
| GET | /api/user/dashboard | User dashboard data |
| GET/PUT | /api/user/profile | Get/update profile |
| POST | /api/gold/buy | Buy gold |
| POST | /api/gold/sell | Request sell |
| GET | /api/transactions | Transaction history |
| POST | /api/delivery/request | Request physical delivery |
| GET | /api/admin/dashboard | Admin stats |
| GET/POST/PUT | /api/admin/customers | Manage customers |
| GET/POST/PUT | /api/admin/transactions | Manage transactions |
| GET/POST | /api/admin/gold-rate | Get/update gold rate |

## OTP in Development
OTPs are logged to `otp_log.txt` file. In production, configure an SMS provider in `middleware/auth.php`.
