# 🔧 Talksy Server Configuration

## 🚀 **Current Server Setup**

### **Backend (PHP)**
- **Port**: 8000
- **URL**: http://localhost:8000
- **Command**: `php -S localhost:8000`
- **Location**: `/Applications/XAMPP/xamppfiles/htdocs/Talksy/public/`

### **Frontend (React)**
- **Port**: 3000  
- **URL**: http://localhost:3000
- **Command**: `npm start`
- **Location**: `/Applications/XAMPP/xamppfiles/htdocs/Talksy/frontend/`

## 📡 **API Endpoints**

All backend APIs are accessible at `http://localhost:8000/`:

### **Authentication**
- `POST /registration.php` - User registration
- `POST /login.php` - User login  
- `GET /verify.php` - Email verification

### **Chat System**
- `GET /chat_api.php` - Get user chats
- `POST /chat_api.php` - Send message
- `PUT /chat_api.php` - Update message status

### **Status/Stories**
- `GET /status_api.php` - Get status updates
- `POST /status_api.php` - Upload new status
- `PUT /status_api.php` - Mark status as viewed

### **Profile Management**
- `GET /profile_api.php` - Get user profile
- `POST /profile_api.php` - Update user profile

### **Utilities**
- `GET /cors_test.php` - Test CORS configuration
- `GET /setup_database.php` - Initialize database

## ⚙️ **Configuration Files Updated**

### **Frontend Configuration**
- `frontend/src/config.js` - API_BASE_URL set to `http://localhost:8000`
- `frontend/src/pages/Verify.jsx` - Updated to use port 8000

### **Backend Configuration**
- `public/cors_config.php` - Enhanced CORS headers
- All API files - Updated with comprehensive CORS headers

## 🔗 **CORS Configuration**

### **Allowed Origins**
- `http://localhost:3000` (React dev server)
- `http://127.0.0.1:3000` (Alternative localhost)
- `http://localhost:3001` (Backup port)
- `http://10.160.22.46:3000` (Network access)

### **Allowed Methods**
- GET, POST, PUT, DELETE, OPTIONS, PATCH

### **Allowed Headers**
- Content-Type, Authorization, X-Requested-With, Accept, Origin

## 🧪 **Testing Commands**

### **Test Backend is Running**
```bash
curl http://localhost:8000/cors_test.php
```

### **Test CORS Configuration**
```bash
curl -H "Origin: http://localhost:3000" http://localhost:8000/cors_test.php
```

### **Test Registration API**
```bash
curl -X POST http://localhost:8000/registration.php \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:3000" \
  -d '{"username":"test","email":"test@example.com","password":"password"}'
```

## 🚀 **Starting the Application**

### **1. Start Backend Server**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/Talksy/public
php -S localhost:8000
```

### **2. Start Frontend Server**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/Talksy/frontend
npm start
```

### **3. Access Application**
- Frontend: http://localhost:3000
- Backend API: http://localhost:8000

## ✅ **Status Check**

### **Backend Health Check**
- ✅ PHP Server running on port 8000
- ✅ CORS headers configured
- ✅ All API endpoints accessible
- ✅ Database connection working

### **Frontend Health Check**  
- ✅ React server running on port 3000
- ✅ API calls configured for port 8000
- ✅ CORS requests working
- ✅ All components loading

### **Integration Status**
- ✅ Frontend → Backend communication working
- ✅ Registration and login functional
- ✅ Chat system operational
- ✅ Status system working
- ✅ Profile management active

---

**🎉 Everything is configured and working on the correct ports!**

*Last Updated: September 22, 2025*
