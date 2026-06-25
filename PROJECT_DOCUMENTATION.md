# EcoCare+ Project Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Database Structure](#database-structure)
4. [File Structure](#file-structure)
5. [Features](#features)
6. [API Reference](#api-reference)
7. [Development Guide](#development-guide)
8. [Security Features](#security-features)

---

## Project Overview

**EcoCare+** is a community-based environmental reporting and monitoring platform. It allows users to report environmental issues, track their status, and view them on an interactive map.

### Tech Stack
- **Backend**: PHP 7.0+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, Tailwind CSS, JavaScript
- **Mapping**: Leaflet.js
- **Charts**: Chart.js
- **Icons**: Font Awesome 6.4.0

---

## System Architecture

### Architecture Type: **Monolithic PHP Application**

```
┌─────────────────────────────────────────────────────────────┐
│                      Frontend (Browser)                          │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐          │
│  │  User    │ │  Admin   │ │  Public  │          │
│  │  Pages   │ │  Panel   │ │  Pages   │          │
│  └──────────┘ └──────────┘ └──────────┘          │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│              Apache/Nginx Web Server                      │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                  PHP Application                       │
│  ┌──────────────────────────────────────────────────┐    │
│  │  config.php (Database & Session Config)        │    │
│  └──────────────────────────────────────────────────┘    │
│  ┌──────────────────────────────────────────────────┐    │
│  │  Page Controllers (login, register, etc)       │    │
│  └──────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                MySQL Database                     │
│  ┌──────────────────────────────────────────────────┐    │
│  │  users, reports tables                      │    │
│  └──────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Structure

### Database Name: `ecocare`

### Table 1: `users`
Stores user account information.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Unique user identifier |
| `name` | VARCHAR(100) | NOT NULL | User's full name |
| `email` | VARCHAR(100) | UNIQUE, NOT NULL | User's email address |
| `password` | VARCHAR(255) | NOT NULL | Hashed password |
| `phone` | VARCHAR(20) | NULLABLE | User's phone number |
| `resident_id` | VARCHAR(50) | UNIQUE, NOT NULL | National ID number (NIK) |
| `role` | ENUM('masyarakat', 'admin') | DEFAULT 'masyarakat' | User role |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation time |

### Table 2: `reports`
Stores environmental issue reports.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Unique report identifier |
| `user_id` | INT | NOT NULL, FOREIGN KEY → users(id) | ID of user who submitted the report |
| `category` | ENUM('Sampah', 'Saluran Air Tersumbat', 'Genangan Air', 'Lingkungan Kurang Terawat') | NOT NULL | Report category |
| `description` | TEXT | NOT NULL | Detailed description of the issue |
| `photo_path` | VARCHAR(255) | NULLABLE | Path to uploaded photo |
| `location_address` | VARCHAR(255) | NOT NULL | Address of the issue location |
| `latitude` | DECIMAL(10, 8) | NULLABLE | GPS latitude coordinate |
| `longitude` | DECIMAL(11, 8) | NULLABLE | GPS longitude coordinate |
| `status` | ENUM('Baru', 'Diproses', 'Selesai') | DEFAULT 'Baru' | Report status |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Report submission time |

---

## File Structure

```
FinalProjectIMK/
├── config.php                 # Database & session configuration
├── index.php                # Homepage
├── login.php               # Login page
├── register.php            # Registration page
├── logout.php              # Logout handler
├── map.php                 # Interactive map page (public)
├── submit_report.php       # Report submission page
├── admin_dashboard.php     # Admin dashboard
├── admin_reports.php      # Manage reports page
├── admin_users.php         # Manage users page
├── admin_map.php          # Admin map monitoring page
├── admin_statistics.php    # Admin statistics page
├── check-database.php     # Database setup & check script
├── ecocare_db.sql        # Database schema
├── setup-admin.php       # Admin account setup script
├── api/
│   └── reports.php       # API endpoint for reports
├── uploads/              # Directory for uploaded photos
├── edukasi_plastik.php   # Plastic waste education page
├── edukasi_sungai.php    # River care education page
├── edukasi_sampah.php    # Waste management education page
└── PROJECT_DOCUMENTATION.md # This file
```

---

## Features

### User Features
1. **Registration & Login
2. **Report environmental issues
3. **Upload photos** of issues
4. **View reports** on interactive map
5. **Browse environmental education content

### Admin Features
1. **Dashboard** with statistics cards
2. **Manage Reports**: view, update status, delete
3. **Manage Users**: view, delete (non-admin)
4. **Map Monitoring** of all reports
5. **Statistics & Charts** (status, category, monthly)

---

## API Reference

### Endpoint: `api/reports.php`
*(Note: This API is for future use, currently data is fetched directly via PHP in page controllers.*

---

## Development Guide

### Setup Instructions
1. **Environment Requirements:
   - XAMPP/WAMP (Apache, MySQL, PHP)
   - Web browser

2. **Installation Steps**:
   a. Clone/copy project to `htdocs/FinalProjectIMK`
   b. Start Apache & MySQL in XAMPP
   c. Import `ecocare_db.sql` to create database
   d. Access `check-database.php` to set up admin account
   e. Open browser at `http://localhost/FinalProjectIMK`

### Default Admin Credentials
- Email: `admin@ecocare.id`
- Password: `admin123`

### Configuration (`config.php`)
Handles:
- Database connection
- Session configuration
- CSRF token generation/verification
- Authentication helper functions

---

## Security Features

1. **Password Hashing**: Uses PHP `password_hash()` and `password_verify()`
2. **CSRF Protection**: Tokens for all form submissions
3. **Session Security**:
   - `session.cookie_httponly` = 1
   - `session.cookie_samesite` = Strict
   - Session regeneration on login
4. **Input Sanitization**: All user inputs are sanitized
5. **Role-based Access Control**: Admin pages protected by `require_admin()`
6. **SQL Injection Prevention**: Uses PDO prepared statements

---

## Key Variables & Constants

### Colors (Tailwind Config)
```javascript
colors: {
  'ecocare-primary': '#6FAF8F',
  'ecocare-secondary': '#A8D5BA',
  'ecocare-accent': '#7DB7E8',
  'ecocare-cream': '#F4EBD0',
  'ecocare-beige': '#E8DCCF',
  'ecocare-orange': '#FFB86C',
  'ecocare-dark': '#2D3748',
  'ecocare-green-dark': '#3D8B6A'
}
```

### Status Meanings
- `Baru`: New report (red)
- `Diproses`: In progress (orange)
- `Selesai`: Completed (green)

---

## Helper Functions (`config.php`)
- `is_logged_in()`: Check if user is authenticated
- `is_admin()`: Check if user has admin role
- `require_login()`: Redirect to login if not authenticated
- `require_admin()`: Redirect if not admin
- `sanitize_input()`: Sanitize user input
- `validate_email()`: Validate email format
- `generate_csrf_token()`: Generate CSRF token
- `verify_csrf_token()`: Verify CSRF token

---

## Page Flow

### User Registration Flow
```
Register → Login → Homepage → Submit Report → View on Map
```

### Admin Flow
```
Login (admin) → Dashboard → Manage Reports/Users/Map/Statistics
```

---

## Future Enhancements (Potential)
1. Email notifications
2. Report comments
3. User profile page
4. More education content
5. Mobile app integration

---

## Contact & Support
For issues or questions, refer to the project files or check `admin-guide.php`
