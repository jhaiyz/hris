# HRIS Employee Portal — Setup Guide

## Requirements
- XAMPP (Apache + MySQL/MariaDB + PHP 7.4+)
- Place project folder in: `C:\xampp\htdocs\hris\`

## File Structure
```
C:\xampp\htdocs\hris\
│
├── index.php            ← Login page
├── portal.php           ← Employee portal / dashboard
├── change-password.php  ← Force password change on first login
├── db.php               ← Database config & session
│
├── api/
│   ├── login.php        ← POST: authenticate employee
│   ├── change-password.php ← POST: update password
│   ├── upload-photo.php ← POST: upload/update profile photo
│   └── logout.php       ← Destroy session & redirect
│
└── photos/              ← Employee photos (auto-created if missing)
```

## Database Setup

Run this SQL in phpMyAdmin (database: **hris**):

```sql
CREATE DATABASE IF NOT EXISTS hris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE hris;

CREATE TABLE tblemp (
    emp_ID     INT AUTO_INCREMENT PRIMARY KEY,
    Full_Name  VARCHAR(150) NOT NULL,
    imgPath    VARCHAR(255) DEFAULT NULL,
    Password   VARCHAR(255) NOT NULL DEFAULT '123456'
);

-- Sample data
INSERT INTO tblemp (Full_Name, imgPath, Password) VALUES
    ('Juan dela Cruz', NULL, '123456'),
    ('Maria Santos',   NULL, '123456'),
    ('Pedro Reyes',    NULL, 'MyStr0ngPw');
```

## How It Works

### Login Flow
1. Employee enters **Full_Name** + **Password**
2. If password is `123456` → redirected to **Change Password** page
3. Otherwise → redirected to **Employee Portal**

### Photo Upload
- Click the **circle profile photo** in the sidebar
- Select an image (JPEG, PNG, GIF, WEBP) up to **1.5 MB**
- On upload:
  - **If imgPath is NULL** → saved as `{emp_ID}.{ext}` (e.g., `6.jpg`)
  - **If imgPath exists** → old file is deleted first, then new file saved
  - `imgPath` column in `tblemp` is updated automatically

### Photos Directory
Photos are saved to: `C:\xampp\htdocs\hris\photos\`
The directory will be **created automatically** if it doesn't exist.

## Configuration
Edit `db.php` to change:
- `DB_HOST` — default: `localhost`
- `DB_USER` — default: `root`
- `DB_PASS` — default: `` (empty)
- `DB_NAME` — default: `hris`
- `PHOTOS_DIR` — default: `C:/xampp/htdocs/hris/photos/`

## Access
Open browser: **http://localhost/hris/**
