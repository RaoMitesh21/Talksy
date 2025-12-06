```mermaid
erDiagram
    USERS {
        int id PK "AUTO_INCREMENT"
        varchar username UK "UNIQUE NOT NULL"
        varchar email UK "UNIQUE NOT NULL"
        varchar password_hash "NOT NULL"
        varchar display_name
        text about
        varchar profile_picture
        varchar phone
        boolean is_online
        timestamp last_seen
        timestamp created_at
        timestamp updated_at
    }

    CHATS {
        int id PK "AUTO_INCREMENT"
        varchar name
        enum type "private/group/direct"
        varchar created_by
        timestamp created_at
        timestamp updated_at
    }

    CHAT_PARTICIPANTS {
        int id PK "AUTO_INCREMENT"
        int chat_id FK
        int user_id FK
        varchar username
        timestamp joined_at
        boolean is_admin
    }

    MESSAGES {
        int id PK "AUTO_INCREMENT"
        int chat_id FK
        int sender_id FK
        varchar sender
        text message
        text content
        enum message_type "text/image/file/snap/audio/video"
        text file_url
        json attachments
        boolean is_read
        timestamp created_at
        timestamp updated_at
    }

    MESSAGE_READS {
        int id PK "AUTO_INCREMENT"
        int message_id FK
        varchar username
        timestamp read_at
    }

    USER_STATUS {
        int user_id PK_FK
        boolean is_online
        timestamp last_seen
        int typing_in_chat FK
    }

    USER_PROFILES {
        int user_id PK_FK
        text about
        varchar phone
        text profile_picture
        enum last_seen_privacy "everyone/contacts/nobody"
        enum profile_photo_privacy "everyone/contacts/nobody"
        boolean read_receipts_enabled
        timestamp created_at
        timestamp updated_at
    }

    STATUSES {
        int id PK "AUTO_INCREMENT"
        int user_id FK
        varchar username
        text content
        enum status_type "text/image/video"
        varchar file_url
        varchar background_color
        varchar filter_name
        json viewed_by
        timestamp created_at
        timestamp expires_at
    }

    STATUS_VIEWS {
        int id PK "AUTO_INCREMENT"
        int status_id FK
        int viewer_id FK
        timestamp viewed_at
    }

    SNAP_MESSAGES {
        int id PK "AUTO_INCREMENT"
        int message_id FK
        timestamp expires_at
        json viewed_by
        timestamp created_at
    }

    CONTACTS {
        int id PK "AUTO_INCREMENT"
        varchar user1
        varchar user2
        enum status "pending/accepted/blocked"
        timestamp created_at
    }

    CONTACT_INQUIRIES {
        int id PK "AUTO_INCREMENT"
        varchar name "NOT NULL"
        varchar email "NOT NULL"
        varchar company
        varchar subject "NOT NULL"
        text message "NOT NULL"
        timestamp created_at
        varchar ip_address
        enum status "new/in_progress/resolved/archived"
        int assigned_to
        text notes
        timestamp updated_at
    }

    %% Relationships
    USERS ||--o{ CHAT_PARTICIPANTS : "participates_in"
    CHATS ||--o{ CHAT_PARTICIPANTS : "has_participants"
    USERS ||--o{ MESSAGES : "sends"
    CHATS ||--o{ MESSAGES : "contains"
    MESSAGES ||--o{ MESSAGE_READS : "read_by"
    MESSAGES ||--o| SNAP_MESSAGES : "can_be_snap"
    USERS ||--o| USER_STATUS : "has_status"
    USERS ||--o| USER_PROFILES : "has_profile"
    USERS ||--o{ STATUSES : "creates"
    STATUSES ||--o{ STATUS_VIEWS : "viewed_by"
    USERS ||--o{ STATUS_VIEWS : "views"
    CHATS ||--o{ USER_STATUS : "typing_in"
```
