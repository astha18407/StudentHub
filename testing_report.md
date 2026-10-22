# StudentHub – Practical 15 Testing Report

## 1. Objective

To test the integration of StudentHub modules, database connectivity, authentication, student management, event management, and administrative functions.

## 2. Test Environment

- Project: StudentHub
- Server: XAMPP
- Web Server: Apache
- Database: MySQL
- Database Name: studenthub
- Browser: Google Chrome

## 3. Test Cases

| Sr. No. | Test Case | Expected Result | Actual Result | Status |
|---|---|---|---|---|
| 1 | Open homepage | Homepage loads correctly | Homepage opened | PASS |
| 2 | Register a new student | Registration succeeds | Registration succeeded | PASS |
| 3 | Login with valid credentials | Correct dashboard opens | Student Dashboard opened | PASS |
| 4 | Logout | User is logged out | Login page opened | PASS |
| 5 | Access dashboard after logout | Redirects to login | Redirected to login | PASS |
| 6 | Add, search, update and delete student | Operations work correctly | Operations tested successfully | PASS |
| 7 | Add, update and delete event | Operations work correctly | Operations tested successfully | PASS |
| 8 | Record administrative actions | Audit logs appear | Audit logs appeared | PASS |
| 9 | Database connectivity | Database operations work | Database operations worked | PASS |

## 4. Conclusion

The tested StudentHub modules worked as expected in the local XAMPP environment. Registration, login, logout, session protection, student management, event management, and audit logging were tested successfully.
