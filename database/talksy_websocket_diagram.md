# Talksy WebSocket Communication Flow

```mermaid
sequenceDiagram
    participant UA as 👤 User A (Client)
    participant FA as 🌐 Frontend A<br/>(React)
    participant WS as 🔗 WebSocket Server<br/>(PHP/Ratchet)
    participant DB as 🗄️ MySQL Database
    participant API as 🔧 Backend API<br/>(PHP)
    participant FB as 🌐 Frontend B<br/>(React)
    participant UB as 👤 User B (Client)

    Note over UA, UB: Connection & Authentication Phase

    UA->>FA: Opens chat app
    FA->>WS: Connect ws://localhost:8080
    WS->>FA: ✅ Connection opened
    FA->>WS: 🔐 AUTH REQUEST<br/>{"type": "auth", "username": "userA"}
    WS->>WS: 📝 Store connection<br/>userConnections["userA"] = conn
    WS->>FA: ✅ AUTH SUCCESS<br/>{"type": "auth_success"}
    WS->>FA: 🟢 ONLINE STATUS<br/>{"username": "userA", "isOnline": true}

    UB->>FB: Opens chat app
    FB->>WS: Connect ws://localhost:8080
    WS->>FB: ✅ Connection opened
    FB->>WS: 🔐 AUTH REQUEST<br/>{"type": "auth", "username": "userB"}
    WS->>WS: 📝 Store connection<br/>userConnections["userB"] = conn
    WS->>FB: ✅ AUTH SUCCESS<br/>{"type": "auth_success"}
    WS->>FA: 🟢 ONLINE STATUS<br/>{"username": "userB", "isOnline": true}

    Note over UA, UB: Real-time Messaging Flow

    UA->>FA: Types "Hello!" 💬
    FA->>FA: 🚀 Generate tempId<br/>Optimistically add to UI
    
    par WebSocket (Instant Delivery)
        FA->>WS: 📤 SEND MESSAGE<br/>{"type": "message", "chatId": 1,<br/>"content": "Hello!", "senderId": "userA"}
        WS->>WS: 🔍 Duplicate Detection<br/>md5(sender+chatId+content+time)
        WS->>DB: 🔍 Query Participants<br/>SELECT username FROM chat_participants
        DB->>WS: ["userA", "userB"]
        WS->>FB: ⚡ INSTANT BROADCAST<br/>{"type": "new_message",<br/>"content": "Hello!", "instant": true}
        FB->>FB: 📝 Add to UI<br/>onMessage callback
        FB->>UB: Display message instantly
    and HTTP API (Persistence)
        FA->>API: 💾 HTTP POST /enhanced_api.php<br/>{"action": "send_message"}
        API->>DB: 💾 INSERT INTO messages
        DB->>API: ✅ Message saved with ID
        API->>FA: {"success": true}
    end

    Note over UA, UB: Typing Indicators

    UA->>FA: Starts typing ✏️
    FA->>WS: 📝 TYPING INDICATOR<br/>{"type": "typing", "isTyping": true}
    WS->>DB: 🔍 Query participants
    WS->>FB: 📝 TYPING NOTIFICATION<br/>{"username": "userA", "isTyping": true}
    FB->>UB: Show "userA is typing..."

    UA->>FA: Stops typing (3s timeout)
    FA->>WS: 📝 TYPING INDICATOR<br/>{"type": "typing", "isTyping": false}
    WS->>FB: 📝 TYPING NOTIFICATION<br/>{"username": "userA", "isTyping": false}
    FB->>UB: Hide typing indicator

    Note over UA, UB: Message Deletion

    UA->>FA: Deletes message 🗑️
    FA->>WS: ❌ DELETE MESSAGE<br/>{"type": "delete_message", "messageId": 123}
    WS->>DB: 🔍 Query participants
    WS->>FA: ❌ MESSAGE DELETED<br/>{"messageId": 123, "deletedBy": "userA"}
    WS->>FB: ❌ MESSAGE DELETED<br/>{"messageId": 123, "deletedBy": "userA"}
    FA->>FA: 🗑️ Remove from UI
    FB->>FB: 🗑️ Remove from UI

    Note over UA, UB: Online Status Updates

    UA->>FA: Sets online status 🟢
    FA->>WS: 🟢 ONLINE STATUS<br/>{"type": "online_status"}
    WS->>FA: 🟢 STATUS UPDATE<br/>{"username": "userA", "isOnline": true}
    WS->>FB: 🟢 STATUS UPDATE<br/>{"username": "userA", "isOnline": true}
    FB->>UB: Show "userA is online"

    Note over UA, UB: Connection Recovery

    WS--xFA: ❌ Connection lost (network)
    FA->>FA: 🔄 Auto-Reconnect<br/>setTimeout(3000ms)
    FA->>WS: 🔄 Reconnect ws://localhost:8080
    WS->>FA: ✅ Connection re-established
    FA->>WS: 🔐 Re-authenticate<br/>{"type": "auth", "username": "userA"}
    FA->>WS: 📤 Send pending messages<br/>from pendingMessages queue

    Note over UA, UB: Error Handling

    FA->>WS: ❌ Invalid message format
    WS->>WS: ⚠️ Validation failed<br/>Silently ignore
    
    WS--xFA: ❌ Connection error
    FA->>FA: 🚨 Error Handling<br/>Log error + Queue messages
```

## WebSocket Message Types

### Client-to-Server Messages

| Message Type | Purpose | Payload Example |
|--------------|---------|-----------------|
| `auth` | Authenticate user connection | `{"type": "auth", "username": "userA"}` |
| `message` | Send chat message | `{"type": "message", "chatId": 1, "content": "Hello!", "senderId": "userA", "messageType": "text", "tempId": "temp_123"}` |
| `delete_message` | Delete a message | `{"type": "delete_message", "messageId": 123, "chatId": 1}` |
| `typing` | Send typing indicator | `{"type": "typing", "chatId": 1, "isTyping": true}` |
| `online_status` | Update online status | `{"type": "online_status"}` |

### Server-to-Client Messages

| Message Type | Purpose | Payload Example |
|--------------|---------|-----------------|
| `auth_success` | Confirm authentication | `{"type": "auth_success", "username": "userA"}` |
| `new_message` | Broadcast new message | `{"type": "new_message", "chatId": 1, "senderId": "userA", "content": "Hello!", "timestamp": "2025-10-28T10:30:00Z", "instant": true}` |
| `message_deleted` | Notify message deletion | `{"type": "message_deleted", "messageId": 123, "chatId": 1, "deletedBy": "userA", "timestamp": "2025-10-28T10:30:00Z"}` |
| `typing` | Notify typing status | `{"type": "typing", "chatId": 1, "username": "userA", "isTyping": true}` |
| `online_status` | Notify user online/offline | `{"type": "online_status", "username": "userA", "isOnline": true}` |

## Key Features

### 🚀 Zero-Latency Messaging
- **Instant WebSocket delivery** to all chat participants
- **Optimistic UI updates** for immediate feedback
- **Parallel HTTP persistence** doesn't block real-time delivery

### 🔍 Duplicate Prevention
- **Message signature** using MD5 hash of sender + chatId + content + time window
- **3-second timeout** window for duplicate detection
- **Recent messages cache** with automatic cleanup

### 🔄 Connection Management
- **Auto-reconnection** after 3 seconds on disconnect
- **Pending message queue** for offline messages
- **Connection state tracking** (wsConnected boolean)

### 📝 Real-time Indicators
- **Typing indicators** with automatic timeout
- **Online/offline status** broadcast to all users
- **Message read receipts** (via HTTP API)

### 🛡️ Error Handling
- **Invalid message validation** on server
- **Silent error recovery** on client
- **Graceful degradation** when WebSocket unavailable
