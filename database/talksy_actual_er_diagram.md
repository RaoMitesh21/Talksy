```mermaid
erDiagram
    USERS {
        int id PK "AUTO_INCREMENT"
        varchar username UK "UNIQUE NOT NULL"
        varchar email UK "UNIQUE NOT NULL"  
        varchar password "NOT NULL"
        varchar display_name
        text about "DEFAULT: Hey there! I am using Talksy."
        varchar profile_picture
        varchar phone
        tinyint is_online "DEFAULT 0"
        timestamp last_seen
        timestamp created_at
        timestamp updated_at
        tinyint email_verified "DEFAULT 0"
        varchar verification_token
    }

    CHATS {
        int id PK "AUTO_INCREMENT"
        varchar name
        enum type "direct/group DEFAULT direct"
        varchar created_by
        timestamp created_at
        timestamp updated_at
    }

    CHAT_PARTICIPANTS {
        int id PK "AUTO_INCREMENT"
        int chat_id FK
        varchar username
        timestamp joined_at
    }

    MESSAGES {
        int id PK "AUTO_INCREMENT"
        int chat_id FK
        varchar sender
        text content
        enum type "text/image/video/audio/file/snap DEFAULT text"
        json attachments
        tinyint is_read "DEFAULT 0"
        timestamp created_at
    }

    MESSAGE_READS {
        int id PK "AUTO_INCREMENT"
        int message_id FK
        varchar username
        timestamp read_at
    }

    SNAP_MESSAGES {
        int id PK "AUTO_INCREMENT"
        int message_id FK "NOT NULL"
        timestamp expires_at
        json viewed_by
        timestamp created_at
    }

    STATUSES {
        int id PK "AUTO_INCREMENT"
        varchar username
        text content
        enum type "text/image/video DEFAULT text"
        varchar file_url
        varchar background_color
        varchar filter
        json viewed_by
        timestamp created_at
        timestamp expires_at "DEFAULT +24 hours"
    }

    STATUS_VIEWS {
        int id PK "AUTO_INCREMENT"
        int status_id FK "NOT NULL"
        int viewer_id FK "NOT NULL"  
        timestamp viewed_at
    }

    USER_PROFILES {
        int user_id PK_FK
        text about "DEFAULT: Hey there! I am using Talksy."
        varchar phone
        text profile_picture
        enum last_seen_privacy "everyone/contacts/nobody DEFAULT everyone"
        enum profile_photo_privacy "everyone/contacts/nobody DEFAULT everyone"
        tinyint read_receipts_enabled "DEFAULT 1"
        timestamp created_at
        timestamp updated_at
    }

    USER_STATUS {
        int user_id PK_FK
        tinyint is_online "DEFAULT 0"
        timestamp last_seen
        int typing_in_chat FK
    }

    CONTACTS {
        int id PK "AUTO_INCREMENT"
        varchar user1
        varchar user2
        enum status "pending/accepted/blocked DEFAULT accepted"
        timestamp created_at
    }

    CONTACT_INQUIRIES {
        int id PK "AUTO_INCREMENT"
        varchar name "NOT NULL"
        varchar email "NOT NULL"
        varchar company
        varchar subject "NOT NULL DEFAULT General Inquiry"
        text message "NOT NULL"
        timestamp created_at
        varchar ip_address
        enum status "new/in_progress/resolved/archived DEFAULT new"
        int assigned_to
        text notes
        timestamp updated_at
    }

    %% Foreign Key Relationships (Explicit Constraints)
    CHATS ||--o{ CHAT_PARTICIPANTS : "chat_participants_ibfk_1"
    CHATS ||--o{ MESSAGES : "messages_ibfk_1"
    MESSAGES ||--o{ MESSAGE_READS : "message_reads_ibfk_1"
    MESSAGES ||--o| SNAP_MESSAGES : "snap_messages_ibfk_1"
    STATUSES ||--o{ STATUS_VIEWS : "status_views_ibfk_1"
    USERS ||--o{ STATUS_VIEWS : "status_views_ibfk_2"  
    USERS ||--o| USER_PROFILES : "user_profiles_ibfk_1"
    USERS ||--o| USER_STATUS : "user_status_ibfk_1"
    CHATS ||--o{ USER_STATUS : "user_status_ibfk_2"

    %% Implied Business Logic Relationships
    USERS ||--o{ CHAT_PARTICIPANTS : "user_participates"
    USERS ||--o{ MESSAGES : "user_sends"
    USERS ||--o{ STATUSES : "user_creates"
```

## Key Differences From Actual Database:

### **Actual Structure (From SQL Dump):**
1. **`users` table** has `password` (not `password_hash`) and includes `email_verified` + `verification_token`
2. **`chat_participants`** uses `username` (VARCHAR) instead of `user_id` (INT FK)
3. **`messages`** uses `sender` (VARCHAR) instead of `sender_id` (INT FK) 
4. **`statuses`** uses `username` (VARCHAR) instead of `user_id` (INT FK)
5. **No explicit foreign keys** between users and chat_participants/messages/statuses (uses username strings)

### **Sample Data Present:**
- 13 users (including `talksy_official`)
- 9 direct chats 
- 18 chat participants
- 8 messages
- 1 contact inquiry
- 1 status view record
- 1 user profile

### **Notable Features:**
- Email verification system
- Profile picture uploads
- JSON fields for attachments and viewed_by
- 24-hour status expiry
- Snap message support
- Contact form system
