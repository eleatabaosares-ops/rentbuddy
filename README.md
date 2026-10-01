# RentBuddy 🏠 — Property/Rental Management System

A simple, responsive web-based rental management system built with **HTML, CSS, JavaScript, PHP, and MySQL**.

## Features
- 👥 Tenant profile & lease tracking
- 💵 Rent status & demo online payments (with receipts)
- 🔔 Automated rent reminders
- 🛠 Maintenance requests & tracking
- 📁 Digital contract & document storage
- 📊 Reports & dashboard
- 🔒 Role-based access (Admin / Tenant), password hashing, CSRF protection
- 📱 Fully responsive

## Tech Stack
| Layer | Technology |
|-------|------------|
| Frontend | HTML, CSS, JavaScript |
| Backend  | PHP 7.4+ (PDO) |
| Database | MySQL 5.7+ / MariaDB |

## Setup Instructions

### 1. Requirements
- PHP 7.4 or newer with PDO MySQL
- MySQL 5.7+ or MariaDB
- Apache/Nginx (or PHP built-in server)

### 2. Database
```bash
mysql -u root -p < database/rentbuddy.sql