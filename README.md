# 🎯 Talksy - Modern Real-Time Chat Application

<div align="center">
  <img src="talksy_logo.png" alt="Talksy Logo" width="300"/>
  
  [![React](https://img.shields.io/badge/React-19.0.0-blue.svg)](https://reactjs.org/)
  [![PHP](https://img.shields.io/badge/PHP-8.2-purple.svg)](https://www.php.net/)
  [![WebSocket](https://img.shields.io/badge/WebSocket-Ratchet-green.svg)](http://socketo.me/)
  [![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
</div>

## 📖 Overview

Talksy is a modern, feature-rich real-time chat application built with React and PHP. It offers instant messaging, file sharing, snap photos, email verification, and much more!

## ✨ Features

- 💬 **Real-time Messaging** - Instant communication via WebSocket
- 📁 **File Sharing** - Share documents, images, and files
- 📸 **Snap Photos** - Camera integration for quick photo sharing
- ✉️ **Email Verification** - Secure user registration with email confirmation
- 🔐 **Authentication** - User login/registration with password encryption
- 📧 **Contact Form** - Email notifications with auto-reply
- 🎨 **Modern UI** - Beautiful, responsive design with animations
- 🔔 **Real-time Notifications** - Instant updates for messages and activity
- 👤 **User Profiles** - Profile photos and customization
- 🌙 **Dark/Light Mode** - Theme switching support

## 🛠️ Tech Stack

### Frontend
- React 19
- React Router
- Axios
- Spring Animations
- Modern CSS3

### Backend
- PHP 8.2
- MySQL Database
- PHPMailer for emails
- WebSocket (Ratchet)
- RESTful API

## 📦 Installation

### Prerequisites
- PHP 8.2 or higher
- MySQL 5.7 or higher
- Node.js 16+ and npm
- Composer
- XAMPP/WAMP/LAMP (or similar)

### Step 1: Clone the Repository
```bash
git clone https://github.com/yourusername/talksy.git
cd talksy
```

### Step 2: Backend Setup

1. **Install PHP Dependencies**
```bash
composer install
```

2. **Configure Database**
```bash
# Copy the example config
cp public/db_config.php.example public/db_config.php

# Edit db_config.php with your database credentials
nano public/db_config.php
```

3. **Import Database**
```bash
mysql -u your_user -p your_database < database/talksy_schema.sql
```

4. **Configure Email**
```bash
# Copy the example config
cp public/email_config.php.example public/email_config.php

# Edit email_config.php with your SMTP credentials
nano public/email_config.php
```

### Step 3: Frontend Setup

1. **Install Node Dependencies**
```bash
cd frontend
npm install
```

2. **Configure API**
```bash
# Copy the example config
cp src/config.js.example src/config.js

# Edit config.js with your API URL
nano src/config.js
```

### Step 4: Start Services

1. **Start Backend** (in Talksy directory)
```bash
# If using XAMPP, ensure Apache is running
# Or start PHP built-in server
php -S localhost:8000 -t public
```

2. **Start WebSocket Server**
```bash
php public/websocket_server.php
```

3. **Start Frontend** (in frontend directory)
```bash
npm start
```

The application will open at `http://localhost:3000`

## 🗂️ Project Structure

```
Talksy/
├── frontend/               # React frontend application
│   ├── public/
│   ├── src/
│   │   ├── components/    # React components
│   │   ├── pages/        # Page components
│   │   ├── assets/       # Images, styles
│   │   └── config.js     # API configuration
│   └── package.json
├── public/                # PHP backend
│   ├── enhanced_api.php  # Main API endpoints
│   ├── login.php         # Authentication
│   ├── registration.php  # User registration
│   ├── verify.php        # Email verification
│   ├── contact_form.php  # Contact handling
│   ├── websocket_server.php
│   ├── db_config.php.example
│   └── email_config.php.example
├── database/             # Database schemas and docs
├── uploads/              # File upload directory
├── talksy_logo.png      # Application logo
└── README.md
```

## ⚙️ Configuration

### Database Configuration (`public/db_config.php`)
```php
$host = 'localhost';
$db = 'talksy_db';
$user = 'your_user';
$pass = 'your_password';
```

### Email Configuration (`public/email_config.php`)
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

### Frontend Configuration (`frontend/src/config.js`)
```javascript
export const API_BASE_URL = 'http://localhost/Talksy/public';
export const WEBSOCKET_URL = 'ws://localhost:8090';
```

## 🚀 Usage

1. **Register a new account** at `/register`
2. **Verify your email** by clicking the link sent to your inbox
3. **Login** at `/login`
4. **Start chatting** with other users
5. **Share files** by clicking the attachment icon
6. **Take snaps** using the camera feature

## 📧 Email Features

- **Registration Verification** - Secure email confirmation
- **Contact Form** - Admin notifications with auto-reply
- **Beautiful Email Templates** - Professional HTML emails with logo

## 🔒 Security Features

- Password hashing with PHP password_hash()
- Email verification required for login
- SQL injection prevention with PDO prepared statements
- XSS protection
- CSRF token support
- Secure WebSocket connections

## 📱 Responsive Design

Talksy is fully responsive and works seamlessly on:
- 💻 Desktop computers
- 📱 Mobile phones
- 📱 Tablets

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👨‍💻 Author

**Your Name**
- GitHub: [@yourusername](https://github.com/yourusername)
- Email: your-email@example.com

## 🙏 Acknowledgments

- React team for the amazing framework
- PHPMailer for email functionality
- Ratchet for WebSocket support
- All contributors and users

## 📞 Support

If you have any questions or need help, please:
- Open an issue on GitHub
- Contact via email
- Check the documentation in `/database/README.md`

---

<div align="center">
  Made with ❤️ by the Talksy Team
  
  ⭐ Star this repo if you like it!
</div>
