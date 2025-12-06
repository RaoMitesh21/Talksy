# 🚀 Talksy Installation Guide

Complete step-by-step guide to install Talksy on your local machine or server.

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.2+** with extensions:
  - PDO
  - PDO_MySQL
  - OpenSSL
  - mbstring
  - JSON
- **MySQL 5.7+** or MariaDB 10.3+
- **Node.js 16+** and npm
- **Composer** (PHP package manager)
- **Web Server** (Apache/Nginx or PHP built-in server)

## 🔧 Quick Start

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/talksy.git
cd talksy
```

### 2. Install Dependencies

**Backend:**
```bash
composer install
```

**Frontend:**
```bash
cd frontend
npm install
cd ..
```

### 3. Configure Database

**Create Database:**
```sql
CREATE DATABASE talksy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Import Schema:**
```bash
mysql -u root -p talksy_db < database/talksy_schema.sql
```

**Configure Connection:**
```bash
cp public/db_config.php.example public/db_config.php
nano public/db_config.php
```

Update with your credentials:
```php
$host = 'localhost';
$db = 'talksy_db';
$user = 'your_mysql_user';
$pass = 'your_mysql_password';
```

### 4. Configure Email

**Setup SMTP:**
```bash
cp public/email_config.php.example public/email_config.php
nano public/email_config.php
```

**For Gmail:**
1. Enable 2-factor authentication
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Update config:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-16-char-app-password');
```

### 5. Configure Frontend

```bash
cp frontend/src/config.js.example frontend/src/config.js
nano frontend/src/config.js
```

Update API URLs:
```javascript
export const API_BASE_URL = 'http://localhost/talksy/public';
export const WEBSOCKET_URL = 'ws://localhost:8090';
```

### 6. Create Upload Directories

```bash
mkdir -p public/uploads
mkdir -p public/chat_files
mkdir -p public/snaps
chmod -R 755 public/uploads
chmod -R 755 public/chat_files
chmod -R 755 public/snaps
```

### 7. Start Services

**Terminal 1 - Backend:**
```bash
php -S localhost:8000 -t public
```

**Terminal 2 - WebSocket:**
```bash
php public/websocket_server.php
```

**Terminal 3 - Frontend:**
```bash
cd frontend
npm start
```

### 8. Access Application

Open your browser and navigate to:
```
http://localhost:3000
```

## 🔍 Verification

Test your installation:

1. **Register a new user** - Should send verification email
2. **Check email inbox** - Click verification link
3. **Login** - Should succeed after verification
4. **Send a message** - Test real-time messaging
5. **Upload a file** - Test file sharing
6. **Submit contact form** - Test email notifications

## 🐛 Troubleshooting

### Email Not Sending
- Verify SMTP credentials
- Check PHP error logs: `tail -f /path/to/php_error.log`
- Test SMTP: `php public/test_email.php`

### Database Connection Failed
- Verify MySQL is running: `mysql -u root -p`
- Check credentials in `db_config.php`
- Ensure database exists: `SHOW DATABASES;`

### WebSocket Not Connecting
- Check if port 8090 is free: `lsof -i :8090`
- Verify WebSocket server is running
- Check browser console for errors

### Frontend Not Loading
- Verify Node.js version: `node --version` (should be 16+)
- Clear npm cache: `npm cache clean --force`
- Reinstall dependencies: `rm -rf node_modules && npm install`

### File Upload Issues
- Check directory permissions: `ls -la public/uploads`
- Verify PHP upload settings in `php.ini`:
  ```ini
  upload_max_filesize = 20M
  post_max_size = 25M
  ```

## 🌐 Production Deployment

### Apache Configuration

Create `.htaccess` in `public/`:
```apache
RewriteEngine On
RewriteBase /talksy/public/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/talksy/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Build Frontend for Production

```bash
cd frontend
npm run build
```

## 📚 Additional Resources

- **API Documentation:** `/database/API_DOCUMENTATION.md`
- **Database Schema:** `/database/talksy_schema.sql`
- **Email Testing:** `/public/test_email.php`

## 💡 Tips

- Use `.env` files for sensitive configuration
- Enable HTTPS in production
- Set up database backups
- Monitor error logs regularly
- Use process manager (PM2) for WebSocket server

## 📞 Need Help?

- Check existing issues: https://github.com/yourusername/talksy/issues
- Create new issue with detailed information
- Include error logs and configuration (without sensitive data)

---

Happy Chatting! 🎉
