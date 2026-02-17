# 🎮 Games Store

A comprehensive e-commerce platform for selling video games across multiple platforms (Nintendo, PS5, Xbox, and Retro Games).

---

## 🛠️ Tech Stack

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34C26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-D42029?style=for-the-badge&logo=apache&logoColor=white)

---

---

## 📋 Project Structure

### Main Files:
- **index.php** - Registration and new account creation page
- **loogin.php** - Login page for users and administrators
- **Secoand-Page.php** - Main homepage for browsing and shopping
- **admin-page.php** - Admin dashboard and control panel
- **nintendo-page.php** - Nintendo games page
- **ps5.php** - PlayStation 5 games page
- **xbox-page.php** - Xbox games page
- **retro.php** - Retro/Classic games page
- **add-game-page.php** - Add new games page (Admin only)
- **refil-page.php** - Payment/Refill page
- **test.php** - Development test file

### Database Files:
- **web_project.sql** - Main database backup
- **web_project (1).sql** - Additional database backup

---

## ⚙️ Requirements

- **PHP**: 7.4 or higher
- **MySQL/MariaDB**: 5.7 or higher
- **Apache**: with `.htaccess` support (optional)
- **Web Browser**: Modern browser supporting HTML5 & CSS3

---

## 🚀 Installation & Setup

### 1️⃣ Extract Files
```bash
# Copy the project folder to your htdocs directory (XAMPP/WAMP)
cp -r "Games Store" /path/to/htdocs/
```

### 2️⃣ Create Database
```bash
# Open phpMyAdmin and import the database:
# Import the web_project.sql file
# Or use command line:
mysql -u root < web_project.sql
```

### 3️⃣ Configure Database Connection
Ensure database credentials match in all files:
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "web_project";
```

### 4️⃣ Run the Project
```bash
# Start your XAMPP/WAMP server
# Then access the project in your browser:
http://localhost/Games%20Store/
```

---

## 📊 Database Structure

### Main Tables:

#### `users` - Users Table
```
- id: Unique identifier
- first_name: User's first name
- email: Email address (unique)
- password: Hashed password
- is_active: Account status (0 or 1)
```

#### `boss` - Administrators Table
```
- id: Unique identifier
- username: Admin username
- email: Email address
- password: Hashed password
```

#### `cart` & `cart_items` - Shopping Cart
```
- id: Unique identifier
- user_id: User reference
- game_name: Game name
- price: Game price
- quantity: Quantity ordered
```

#### Product Tables
- Nintendo games
- PS5 games
- Xbox games
- Retro games

---

## 🔐 Security & Authentication

### Security Features ⚠️
- **Password Encryption**: Using `password_hash()` and `password_verify()`
- **Input Validation**: `validateInput()` function for data sanitization
- **Email Validation**: `validateEmail()` function
- **Password Strength Requirements**: 
  - Minimum 8 characters
  - At least one uppercase letter
  - At least one lowercase letter
  - At least one number
- **Session Management**: Using `$_SESSION` for user tracking

### Account Types:
1. **Regular User Account**: Standard user for shopping
2. **Admin Account**: Store and product management

---

## 📖 Main Files Explanation

### 1. **index.php** - Registration
- New account creation form
- Input validation and sanitization
- Database user creation
- Error and success message handling

### 2. **loogin.php** - Login
- User and admin login system
- Credentials verification
- User session creation
- Redirect based on account type

### 3. **Secoand-Page.php** - Homepage
- Display available games
- Search and filter functionality
- Add games to cart
- AJAX cart management

### 4. **admin-page.php** - Admin Dashboard
- Add/edit/delete games
- View orders
- User management
- Secure logout

### 5. **Category Pages**
- nintendo-page.php
- ps5.php
- xbox-page.php
- retro.php

Each page displays games specific to that console with purchase options.

---

## 🛠️ Features

### For Regular Users:
✅ Account creation and login
✅ Browse games by console
✅ Advanced search and filtering
✅ Add/remove games from cart
✅ View total price
✅ Complete purchases
✅ View order history

### For Admin:
✅ Secure admin login
✅ Add new games
✅ Edit game information and prices
✅ Delete unavailable games
✅ View all orders
✅ Manage user accounts (enable/disable)
✅ Statistics and reports
✅ Secure logout

---

## 💻 Technologies Used

### Backend:
- **PHP** (OOP & Procedural)
- **MySQL** (MySQLi & PDO)
- **Session Management**
- **Server-side Validation**

### Frontend:
- **HTML5**
- **CSS3** (gradients and modern effects)
- **JavaScript** (AJAX for asynchronous operations)
- **Responsive Design**

### Security:
- **Password Hashing** (`password_hash/verify`)
- **Input Sanitization** (`htmlspecialchars`, `stripslashes`)
- **SQL Injection Prevention** (Prepared Statements)
- **Session-based Authentication**

---

## 🔧 Troubleshooting

### Issue: Database Connection Error
**Solution:**
```php
// Make sure MySQL is running
// Verify:
- Correct server hostname
- Database name
- Username and password
```

### Issue: 404 Error When Accessing Pages
**Solution:**
```bash
# Ensure files are in the correct directory
# Use the correct path in browser:
http://localhost/Games-Store/index.php
```

### Issue: Login Not Working
**Solution:**
- Verify account exists in database
- Check password correctness
- Review error_log for debugging

---

## 📝 Additional Notes

- Full support for Arabic and English languages
- Code follows security best practices
- Comprehensive error handling system
- Database includes test data

---

## 📧 Support

If you encounter any issues:
- Check the error_log file
- Open browser console (F12)
- Review database documentation

---

## 📄 License

This project is available for personal and educational use.

---

**Last Updated:** February 2026
**Status:** Under Development ✨

![Build Status](https://img.shields.io/badge/build-passing-brightgreen?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-blue?style=flat-square)
![Version](https://img.shields.io/badge/version-1.0-blue?style=flat-square)
![GitHub Stars](https://img.shields.io/github/stars/Ahmad71IXK/Games-shop?style=flat-square)
![Contributors](https://img.shields.io/badge/contributors-1-brightgreen?style=flat-square)

---

## 🚀 Getting Started Quick Guide

1. **Install XAMPP/WAMP** and start it
2. **Import database**: `web_project.sql`
3. **Copy files** to `htdocs` folder
4. **Visit**: `http://localhost/Games%20Store/`
5. **Create account** or login with admin credentials
6. **Start shopping** or managing the store!

---

## 📞 Contributing

Feel free to fork, modify, and improve this project!

**Author:** Ahmad71IXK
**Repository:** [Ahmad71IXK/Games-shop](https://github.com/Ahmad71IXK/Games-shop)
