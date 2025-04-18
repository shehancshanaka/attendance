# Attendance Management System

A web-based attendance management system that handles employee attendance tracking, leave requests, and supervisor approvals.

## Features

- Employee attendance tracking
- Leave request management
- Supervisor approval workflow
- Role-based access control
- Real-time attendance status

## Setup Instructions

1. Install XAMPP
2. Clone this repository to `c:/xampp/htdocs/attendance`
3. Import the database schema from `database/schema.sql`
4. Configure database connection in `config/database.php`
5. Start Apache and MySQL services
6. Access the application at `http://localhost/attendance`

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- XAMPP (recommended)

## User Roles

- **Employees**: Can mark attendance and submit leave requests
- **Supervisors**: Can approve/reject leave requests and view team attendance
- **Admin**: Can manage users and view system-wide reports

## Directory Structure

```
attendance/
├── config/
├── models/
├── assets/
├── database/
└── README.md
```

## License

This project is licensed under the MIT License.
