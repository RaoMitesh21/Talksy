# Talksy API Documentation

## Overview

Talksy provides a comprehensive real-time messaging API that combines RESTful HTTP endpoints with WebSocket connections for instant communication. This documentation covers both API types and their integration.

---

## Architecture Overview

![Project Structure](Talksy_Project_Structure.png)
*Complete project structure showing frontend, backend, and database layers*

![Component Architecture](talksy_component_architecture.png)
*Detailed component architecture with service interactions*

![Deployment Structure](Talksy_Deployment_Structure.png)
*Runtime environment and deployment configuration*

---

# 1. REST API Documentation

## Base URL
```
http://localhost/Talksy/public/enhanced_api.php
```

## API Architecture

![REST API Flow](Talksy%20REST%20API%20Flow.png)
*Complete REST API request/response flow diagram*

![API Overview](Talksy%20API%20Overview.png)
*High-level API endpoint overview*

## Authentication

All API requests require authentication except registration and login endpoints.

### Headers
```http
Content-Type: application/json
Authorization: Bearer <token> (when applicable)
```

---

## 1.1 Authentication Endpoints

### POST /enhanced_api.php - User Registration
**Action:** `register`

**Request Body:**
```json
{
  "action": "register",
  "username": "john_doe",
  "email": "john@example.com",
  "password": "securePassword123",
  "displayName": "John Doe"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    "displayName": "John Doe",
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

### POST /enhanced_api.php - User Login
**Action:** `login`

**Request Body:**
```json
{
  "action": "login",
  "username": "john_doe",
  "password": "securePassword123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "username": "john_doe",
    "displayName": "John Doe",
    "email": "john@example.com"
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

### POST /enhanced_api.php - Email Verification
**Action:** `verify_email`

**Request Body:**
```json
{
  "action": "verify_email",
  "email": "john@example.com",
  "verification_code": "123456"
}
```

---

## 1.2 Chat Management Endpoints

### GET /enhanced_api.php - Get User Chats
**Action:** `get_chats`

**Request Body:**
```json
{
  "action": "get_chats",
  "username": "john_doe"
}
```

**Response:**
```json
{
  "success": true,
  "chats": [
    {
      "id": 1,
      "name": "Jane Smith",
      "type": "private",
      "avatar": "uploads/profiles/jane_avatar.jpg",
      "lastMessage": "Hey, how are you?",
      "lastMessageTime": "2024-01-15T14:30:00Z",
      "unreadCount": 2,
      "online": true
    }
  ]
}
```

### POST /enhanced_api.php - Create New Chat
**Action:** `create_chat`

**Request Body:**
```json
{
  "action": "create_chat",
  "creator": "john_doe",
  "participants": ["jane_smith"],
  "chatName": "John & Jane",
  "type": "private"
}
```

### POST /enhanced_api.php - Add Participant to Chat
**Action:** `add_participant`

**Request Body:**
```json
{
  "action": "add_participant",
  "chatId": 1,
  "username": "mike_wilson",
  "addedBy": "john_doe"
}
```

---

## 1.3 Message Endpoints

### GET /enhanced_api.php - Get Messages
**Action:** `get_messages`

**Request Body:**
```json
{
  "action": "get_messages",
  "chatId": 1,
  "username": "john_doe",
  "limit": 50,
  "offset": 0
}
```

**Response:**
```json
{
  "success": true,
  "messages": [
    {
      "id": 101,
      "chatId": 1,
      "sender": "jane_smith",
      "content": "Hey, how are you?",
      "type": "text",
      "timestamp": "2024-01-15T14:30:00Z",
      "isRead": false,
      "attachments": null
    }
  ]
}
```

### POST /enhanced_api.php - Send Message
**Action:** `send_message`

**Request Body:**
```json
{
  "action": "send_message",
  "chatId": 1,
  "sender": "john_doe",
  "content": "Hello! I'm doing great, thanks!",
  "type": "text",
  "timestamp": "2024-01-15T14:35:00Z"
}
```

**Response:**
```json
{
  "success": true,
  "message": {
    "id": 102,
    "chatId": 1,
    "sender": "john_doe",
    "content": "Hello! I'm doing great, thanks!",
    "type": "text",
    "timestamp": "2024-01-15T14:35:00Z"
  }
}
```

### DELETE /enhanced_api.php - Delete Message
**Action:** `delete_message`

**Request Body:**
```json
{
  "action": "delete_message",
  "messageId": 102,
  "username": "john_doe",
  "chatId": 1
}
```

---

## 1.4 Profile Management Endpoints

### GET /enhanced_api.php - Get User Profile
**Action:** `get_user_profile`

**Request Body:**
```json
{
  "action": "get_user_profile",
  "username": "john_doe"
}
```

**Response:**
```json
{
  "success": true,
  "user": {
    "username": "john_doe",
    "displayName": "John Doe",
    "email": "john@example.com",
    "about": "Hey there! I am using Talksy.",
    "phone": "+1234567890",
    "profilePicture": "uploads/profiles/john_avatar.jpg",
    "lastSeen": "2024-01-15T14:40:00Z",
    "isOnline": true
  }
}
```

### POST /enhanced_api.php - Update Profile
**Action:** `update_user_profile`

**Request Body:**
```json
{
  "action": "update_user_profile",
  "username": "john_doe",
  "displayName": "John Doe Jr.",
  "about": "Updated status message",
  "phone": "+1234567890"
}
```

### POST /enhanced_api.php - Upload Profile Picture
**Action:** `upload_profile_picture`

**Request:** Multipart form data
```
profile_picture: [file]
username: john_doe
```

---

## 1.5 File Management Endpoints

### POST /enhanced_api.php - Upload File
**Action:** `upload_file`

**Request:** Multipart form data
```
file: [file]
chatId: 1
sender: john_doe
type: image
```

**Response:**
```json
{
  "success": true,
  "fileUrl": "uploads/chat_files/12345_image.jpg",
  "fileType": "image",
  "fileSize": 256000
}
```

---

## 1.6 Status Management Endpoints

### POST /enhanced_api.php - Upload Status
**Action:** `upload_status`

**Request Body:**
```json
{
  "action": "upload_status",
  "username": "john_doe",
  "content": "Having a great day!",
  "type": "text",
  "backgroundColor": "#FF6B6B",
  "expiresAt": "2024-01-16T14:00:00Z"
}
```

### GET /enhanced_api.php - Get Statuses
**Action:** `get_statuses`

**Request Body:**
```json
{
  "action": "get_statuses",
  "username": "john_doe"
}
```

**Response:**
```json
{
  "success": true,
  "statuses": [
    {
      "id": 1,
      "username": "jane_smith",
      "content": "Beautiful sunset today!",
      "type": "text",
      "backgroundColor": "#FF9F43",
      "timestamp": "2024-01-15T18:00:00Z",
      "expiresAt": "2024-01-16T18:00:00Z",
      "viewed": false
    }
  ]
}
```

---

# 2. WebSocket API Documentation

## Connection URL
```
ws://localhost:8080
```

## WebSocket Architecture

![WebSocket Communication Flow](Talksy%20WebSocket%20Communication%20Flow.png)
*Detailed WebSocket message flow and event handling*

![WebSocket Basic Flow](Talksy%20WebSocket%20Basic%20Flow.png)
*Simplified WebSocket connection and messaging flow*

---

## 2.1 Connection Management

### Authentication
After establishing WebSocket connection, authenticate the user:

**Send:**
```json
{
  "type": "auth",
  "username": "john_doe",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Receive:**
```json
{
  "type": "auth_success",
  "username": "john_doe",
  "message": "Authentication successful"
}
```

---

## 2.2 Real-time Messaging

### Send Message
**Client to Server:**
```json
{
  "type": "message",
  "chatId": 1,
  "content": "Hello from WebSocket!",
  "messageType": "text",
  "senderId": "john_doe",
  "tempId": 1234567890
}
```

### Receive Message
**Server to Client:**
```json
{
  "type": "new_message",
  "chatId": 1,
  "senderId": "jane_smith",
  "content": "Hi there!",
  "messageType": "text",
  "timestamp": "2024-01-15T14:45:00Z",
  "messageId": 103,
  "instant": true
}
```

---

## 2.3 Typing Indicators

### Send Typing Status
**Client to Server:**
```json
{
  "type": "typing",
  "chatId": 1,
  "username": "john_doe",
  "isTyping": true
}
```

### Receive Typing Status
**Server to Client:**
```json
{
  "type": "typing",
  "chatId": 1,
  "username": "jane_smith",
  "isTyping": true
}
```

---

## 2.4 Online Status

### Update Online Status
**Client to Server:**
```json
{
  "type": "online_status",
  "username": "john_doe",
  "isOnline": true
}
```

### Receive Status Update
**Server to Client:**
```json
{
  "type": "online_status",
  "username": "jane_smith",
  "isOnline": false,
  "lastSeen": "2024-01-15T14:40:00Z"
}
```

---

## 2.5 Profile Updates

### Broadcast Profile Update
**Client to Server:**
```json
{
  "type": "profile_update",
  "username": "john_doe",
  "displayName": "John Doe Jr.",
  "profilePicture": "uploads/profiles/john_new_avatar.jpg",
  "about": "Updated status"
}
```

### Receive Profile Update
**Server to Client:**
```json
{
  "type": "profile_update",
  "username": "john_doe",
  "displayName": "John Doe Jr.",
  "profilePicture": "uploads/profiles/john_new_avatar.jpg",
  "about": "Updated status",
  "timestamp": "2024-01-15T14:50:00Z"
}
```

---

## 2.6 Message Management

### Delete Message
**Client to Server:**
```json
{
  "type": "delete_message",
  "messageId": 103,
  "chatId": 1,
  "username": "john_doe"
}
```

### Message Deleted Notification
**Server to Client:**
```json
{
  "type": "message_deleted",
  "messageId": 103,
  "chatId": 1,
  "deletedBy": "john_doe",
  "timestamp": "2024-01-15T14:55:00Z"
}
```

---

# 3. Error Handling

## REST API Errors

### Standard Error Response
```json
{
  "success": false,
  "error": "VALIDATION_ERROR",
  "message": "Username is required",
  "code": 400
}
```

### Common Error Codes
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (authentication required)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (resource doesn't exist)
- `409` - Conflict (duplicate data)
- `500` - Internal Server Error

## WebSocket Errors

### Connection Errors
```json
{
  "type": "error",
  "error": "AUTH_FAILED",
  "message": "Authentication failed",
  "code": 4001
}
```

### Message Errors
```json
{
  "type": "error",
  "error": "MESSAGE_SEND_FAILED",
  "message": "Failed to send message",
  "tempId": 1234567890,
  "code": 4002
}
```

---

# 4. Rate Limiting

## REST API Limits
- **Authentication:** 5 requests per minute per IP
- **Messages:** 100 requests per minute per user
- **File Uploads:** 10 files per minute per user
- **Profile Updates:** 5 updates per minute per user

## WebSocket Limits
- **Messages:** 60 messages per minute per user
- **Typing Indicators:** 30 updates per minute per chat
- **Connection Rate:** 10 connections per minute per IP

---

# 5. Database Schema

![Database ER Diagram](Talksy_Actual_Database_ER_Diagram.png)
*Complete database entity-relationship diagram*

## Core Tables
- **users**: User authentication and profile data
- **chats**: Chat room information
- **messages**: Chat messages and content
- **chat_participants**: Chat membership
- **user_sessions**: Active user sessions
- **statuses**: User status updates
- **attachments**: File attachments metadata

---

# 6. Integration Examples

## JavaScript/React Integration

### REST API Client
```javascript
class TalksyAPI {
  constructor(baseUrl = 'http://localhost/Talksy/public/enhanced_api.php') {
    this.baseUrl = baseUrl;
  }

  async sendMessage(chatId, sender, content) {
    const response = await fetch(this.baseUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'send_message',
        chatId,
        sender,
        content,
        type: 'text'
      })
    });
    return response.json();
  }
}
```

### WebSocket Client
```javascript
class TalksyWebSocket {
  constructor(url = 'ws://localhost:8080') {
    this.ws = new WebSocket(url);
    this.setupEventHandlers();
  }

  authenticate(username, token) {
    this.send({
      type: 'auth',
      username,
      token
    });
  }

  sendMessage(chatId, content, senderId) {
    this.send({
      type: 'message',
      chatId,
      content,
      messageType: 'text',
      senderId,
      tempId: Date.now()
    });
  }

  send(data) {
    if (this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(data));
    }
  }
}
```

---

# 7. Testing & Development

## API Testing Tools
- **REST API**: Postman, curl, or Insomnia
- **WebSocket**: Browser DevTools, wscat, or Socket.io tester

## Test Endpoints
```bash
# Test REST API
curl -X POST http://localhost/Talksy/public/enhanced_api.php \
  -H "Content-Type: application/json" \
  -d '{"action": "get_chats", "username": "test_user"}'

# Test WebSocket (using wscat)
wscat -c ws://localhost:8080
```

## Development Environment
- **Frontend**: React dev server on port 3000
- **Backend**: Apache/XAMPP on port 80
- **WebSocket**: Node.js/PHP server on port 8080
- **Database**: MySQL on port 3306

---

## Support & Contact

For API support, issues, or feature requests:
- **Documentation**: This file and UML diagrams
- **Source Code**: Check the project repository
- **Issues**: Report bugs and feature requests

---

*Last Updated: October 28, 2025*
*API Version: 1.0*
