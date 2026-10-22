# StudentHub – Student Management Portal

## 1. Project Overview

StudentHub is a web-based student portal developed as part of the Web Development Framework (WDF) practicals. It provides student registration, login, student management, event management, and an admin dashboard.

## 2. Technologies Used

- HTML
- CSS
- JavaScript
- PHP
- MySQL
- XAMPP
- phpMyAdmin
- Fetch API and JSON

## 3. Main Features

- Student registration and login
- Password hashing and validation
- Session management and logout
- Role-based access control for administrators
- Admin dashboard with database statistics
- Student add, search, update, and delete operations
- Event add, update, and delete operations
- Event data handling using JSON and Fetch API
- Audit logs for important administrative actions
- Session timeout for improved security

## 4. Software Requirements

- XAMPP with Apache and MySQL
- A web browser such as Google Chrome
- Visual Studio Code (recommended)

## 5. Installation and Execution

1. Install and open XAMPP.
2. Start Apache and MySQL.
3. Place the StudentPortal project folder inside `C:\xampp\htdocs\`.
4. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
5. Create or import the required database named `studenthub`.
6. Configure the database connection in `db.php` and `db_mysqli.php`, if required.
7. Open `http://localhost/StudentPortal/` in a web browser.

## 6. Important Project Pages

- `index.html` – Home page
- `register.html` – Student registration
- `login.html` – Login page
- `dashboard.php` – Student dashboard
- `admin_dashboard.php` – Admin dashboard
- `student_management.php` – Student management
- `event_management.php` – Event management
- `audit_log.php` – Audit logging functionality

## 7. Testing

The project is tested for registration, login, logout, session protection, student management, event management, and audit log recording.

## 8. Conclusion

StudentHub integrates front-end technologies, PHP, and MySQL to provide student portal functionality with administrative controls and database operations.
