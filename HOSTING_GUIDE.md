# 🚀 Talksy Hosting Deployment Guide

This guide explains how to deploy Talksy on various hosting platforms while maintaining both frontend and backend + WebSocket functionality.

## 📋 Pre-Deployment Checklist

- [ ] Frontend built and ready (`npm run build`)
- [ ] Backend PHP files configured
- [ ] Database credentials updated
- [ ] WebSocket server configured
- [ ] CORS settings adjusted for your domain
- [ ] Environment variables set

## 🌐 Hosting Options

### 1. 🖥️ VPS/Dedicated Server (Recommended)

**Best for:** Full control, WebSocket support, custom configurations

**Setup Steps:**

1. **Server Requirements:**
   - Ubuntu 20.04+ or CentOS 8+
   - PHP 8.0+
   - MySQL 8.0+
   - Node.js 16+ (for WebSocket server)
   - Apache/Nginx
   - SSL Certificate

2. **Upload Files:**
   ```bash
   # Upload Talksy folder to server
   scp -r Talksy/ user@yourserver.com:/var/www/html/
   ```

3. **Configure Environment:**
   ```bash
   # Create environment file
   cp frontend/.env.example frontend/.env
   
   # Edit with your domain
   nano frontend/.env
   ```

4. **Update Configuration:**
   ```env
   REACT_APP_API_BASE_URL=https://yourdomain.com/Talksy/public
   REACT_APP_WS_URL=wss://yourdomain.com:8080
   REACT_APP_ENV=production
   ```

5. **Start Services:**
   ```bash
   # Install dependencies
   cd Talksy/frontend && npm install && npm run build
   
   # Start WebSocket server
   cd ../
   php websocket_server.php &
   
   # Configure Apache/Nginx virtual host
   ```

### 2. 📡 Shared Hosting (Limited WebSocket)

**Best for:** Budget hosting, simple deployment

**Limitations:** May not support WebSocket servers

**Setup Steps:**

1. **Upload via FTP/cPanel:**
   - Upload `public/` folder contents to `public_html/Talksy/`
   - Upload `frontend/build/` contents to `public_html/`

2. **Database Setup:**
   - Create MySQL database via hosting control panel
   - Update `public/host_config.php` with database credentials

3. **Configure for Shared Hosting:**
   ```php
   // In host_config.php
   $host = "your_db_host";
   $user = "your_db_user";
   $pass = "your_db_password";
   $db = "your_db_name";
   ```

4. **Frontend Configuration:**
   ```env
   REACT_APP_API_BASE_URL=https://yourdomain.com/Talksy
   REACT_APP_WS_URL=wss://yourdomain.com:8080
   # Note: WebSocket may fall back to polling
   ```

### 3. ☁️ Cloud Platform (Heroku/Railway)

**Best for:** Easy deployment, auto-scaling

**Setup Steps:**

1. **Prepare for Heroku:**
   ```bash
   # Create Procfile
   echo "web: php -S 0.0.0.0:\$PORT -t public" > Procfile
   
   # Create composer.json in root
   echo '{"require": {"php": "^8.0"}}' > composer.json
   ```

2. **Deploy Backend:**
   ```bash
   heroku create talksy-backend
   git add .
   git commit -m "Deploy to Heroku"
   git push heroku main
   ```

3. **Deploy Frontend (Netlify/Vercel):**
   ```bash
   # Build frontend
   cd frontend && npm run build
   
   # Deploy to Netlify/Vercel
   netlify deploy --prod --dir=build
   ```

4. **Environment Variables:**
   ```bash
   heroku config:set DB_HOST=your_db_host
   heroku config:set DB_USER=your_db_user
   heroku config:set DB_PASS=your_db_password
   ```

### 4. 🐳 Docker Deployment

**Best for:** Containerized deployment, consistency

**Setup Steps:**

1. **Create Dockerfile:**
   ```dockerfile
   FROM php:8.0-apache
   
   # Install dependencies
   RUN apt-get update && apt-get install -y nodejs npm
   
   # Copy application
   COPY . /var/www/html/
   
   # Install frontend dependencies and build
   WORKDIR /var/www/html/frontend
   RUN npm install && npm run build
   
   # Move built files
   RUN cp -r build/* /var/www/html/
   
   EXPOSE 80 8080
   ```

2. **Docker Compose:**
   ```yaml
   version: '3.8'
   services:
     talksy:
       build: .
       ports:
         - "80:80"
         - "8080:8080"
       environment:
         - DB_HOST=db
         - DB_USER=talksy
         - DB_PASS=password
     
     db:
       image: mysql:8.0
       environment:
         MYSQL_ROOT_PASSWORD: rootpassword
         MYSQL_DATABASE: talksy
   ```

## ⚙️ Configuration for Different Environments

### Frontend Configuration (config.js)

The configuration automatically detects your environment:

```javascript
// Automatically detects localhost vs hosted
const isLocalhost = window.location.hostname === 'localhost';

export const API_BASE_URL = isLocalhost 
  ? 'http://localhost/Talksy/public'
  : `${window.location.protocol}//${window.location.hostname}/Talksy/public`;

export const WS_URL = isLocalhost 
  ? 'ws://localhost:8080'
  : `wss://${window.location.hostname}:8080`;
```

### Backend Configuration (host_config.php)

The backend automatically detects hosting environment:

```php
// Detects local vs production environment
$isLocalhost = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);

if ($isLocalhost) {
    // XAMPP configuration
    $host = "localhost";
    $user = "root";
    $pass = "";
} else {
    // Production configuration
    $host = $_ENV['DB_HOST'] ?? "localhost";
    $user = $_ENV['DB_USER'] ?? "your_db_user";
    $pass = $_ENV['DB_PASS'] ?? "your_db_password";
}
```

## 🔧 WebSocket Server Setup

### Option 1: Same Server (Recommended)

```bash
# Start WebSocket server on port 8080
php websocket_server.php

# For production, use process manager
pm2 start websocket_server.php --name talksy-ws
```

### Option 2: Separate WebSocket Service

```bash
# Use external WebSocket service
# Update WS_URL to point to external service
export REACT_APP_WS_URL=wss://ws.yourserver.com:8080
```

### Option 3: Polling Fallback

If WebSocket is not available, the system automatically falls back to polling:

```javascript
// Automatic fallback in enhancedChatService.js
static enablePollingMode() {
    console.log('🔄 Enabling polling mode for real-time updates');
    this.pollingInterval = setInterval(() => {
        this.pollForUpdates();
    }, 5000);
}
```

## 🛡️ Security Configuration

### SSL/HTTPS Setup

1. **Get SSL Certificate:**
   ```bash
   # Using Let's Encrypt
   certbot --apache -d yourdomain.com
   ```

2. **Force HTTPS:**
   ```apache
   # In .htaccess
   RewriteEngine On
   RewriteCond %{HTTPS} !=on
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **Update WebSocket to WSS:**
   ```javascript
   // Frontend will automatically use wss:// for HTTPS sites
   export const WS_URL = window.location.protocol === 'https:' 
     ? `wss://${window.location.hostname}:8080`
     : `ws://${window.location.hostname}:8080`;
   ```

## 📊 Testing Your Deployment

### 1. Test API Endpoints

```bash
# Test backend API
curl https://yourdomain.com/Talksy/public/enhanced_api.php \
  -H "Content-Type: application/json" \
  -d '{"action":"test"}'
```

### 2. Test CORS Configuration

```bash
# Visit in browser
https://yourdomain.com/Talksy/public/cors_config.php?cors_debug=1
```

### 3. Test WebSocket Connection

Open browser console on your site and run:

```javascript
// Test WebSocket connection
const ws = new WebSocket('wss://yourdomain.com:8080');
ws.onopen = () => console.log('✅ WebSocket connected');
ws.onerror = (e) => console.log('❌ WebSocket failed:', e);
```

## 🔄 Auto-Deployment Setup

### GitHub Actions (for VPS)

```yaml
# .github/workflows/deploy.yml
name: Deploy Talksy
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Deploy to server
        run: |
          rsync -avz . user@yourserver.com:/var/www/html/Talksy/
          ssh user@yourserver.com 'cd /var/www/html/Talksy && ./deploy.sh'
```

### Deploy Script (deploy.sh)

```bash
#!/bin/bash
echo "🚀 Deploying Talksy..."

# Update dependencies
cd frontend && npm install

# Build frontend
npm run build

# Copy built files
cp -r build/* ../public/

# Restart services
sudo systemctl restart apache2
pm2 restart talksy-ws

echo "✅ Deployment complete!"
```

## 🐛 Troubleshooting

### Common Issues

1. **CORS Errors:**
   - Check `cors_config.php` has your domain
   - Verify HTTPS/HTTP protocol matching

2. **WebSocket Connection Failed:**
   - Check firewall allows port 8080
   - Verify SSL certificate for WSS
   - Check WebSocket server is running

3. **Database Connection Failed:**
   - Verify database credentials in `host_config.php`
   - Check database server is accessible

4. **Frontend Not Loading:**
   - Check `build/` files are in correct directory
   - Verify web server configuration
   - Check for JavaScript errors in browser console

### Debug Mode

Enable debug mode by visiting:
```
https://yourdomain.com/Talksy/public/host_config.php?debug=1
```

This will show your current configuration and help identify issues.

## 📞 Support

If you encounter issues:

1. Check the browser console for errors
2. Review server error logs
3. Test API endpoints individually
4. Verify WebSocket connectivity
5. Check CORS configuration

Your Talksy application is now ready for production hosting! 🎉
