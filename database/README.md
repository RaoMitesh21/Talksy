# Talksy Database ER Diagrams

This directory contains multiple versions of the Talksy database ER diagrams based on different sources.

## Files Overview

### Actual Database Diagrams (Recommended - Based on SQL Dump)
- **`Talksy_Actual_Database_ER_Diagram.png`** - Visual PNG diagram from actual database
- **`Talksy_Actual_Database_ER_Diagram.svg`** - Scalable SVG diagram from actual database  
- **`talksy_actual_er_diagram.puml`** - PlantUML source for actual database
- **`talksy_actual_er_diagram.md`** - Mermaid diagram for actual database
- **`talksy.sql`** - Original SQL dump file (Oct 27, 2025)

### Initial Diagrams (Based on PHP Code Analysis)
- **`Talksy_Database_ER_Diagram.png`** - Initial PNG diagram
- **`Talksy_Database_ER_Diagram.svg`** - Initial SVG diagram
- **`talksy_er_diagram.puml`** - Initial PlantUML source
- **`talksy_er_diagram.md`** - Initial Mermaid diagram

### Supporting Files
- **`contact_inquiries.sql`** - Contact inquiries table schema
- **`plantuml.jar`** - PlantUML JAR file for diagram generation

## Database Structure Summary (Actual Database)

### Core Tables (12 total)

1. **`users`** (13 records) - User accounts with authentication
   - Primary authentication table
   - Email verification system
   - Profile information (display_name, about, profile_picture, phone)

2. **`chats`** (9 records) - Chat conversations
   - Types: 'direct' or 'group'
   - Tracks creator and timestamps

3. **`chat_participants`** (18 records) - User participation in chats
   - Links users to chats (many-to-many)
   - Uses username (VARCHAR) instead of user_id FK

4. **`messages`** (8 records) - Chat messages
   - Multiple types: text, image, video, audio, file, snap
   - JSON attachments support
   - Read status tracking

5. **`message_reads`** (0 records) - Message read receipts
   - Tracks who read which messages

6. **`snap_messages`** (0 records) - Disappearing messages
   - Expiration timestamps
   - JSON tracking of viewers

7. **`statuses`** (0 records) - Stories/status updates
   - 24-hour expiration
   - Multiple content types
   - JSON viewer tracking

8. **`status_views`** (1 record) - Status view tracking
   - Who viewed which status

9. **`user_profiles`** (1 record) - Extended user profiles
   - Privacy settings
   - Additional profile information

10. **`user_status`** (0 records) - Online status
    - Online/offline tracking
    - Typing indicators

11. **`contacts`** (0 records) - Friend/contact relationships
    - Relationship status (pending, accepted, blocked)

12. **`contact_inquiries`** (1 record) - Website contact form
    - Business inquiries and support requests

## Key Relationships

### Explicit Foreign Key Constraints
- `chat_participants.chat_id` → `chats.id`
- `messages.chat_id` → `chats.id`
- `message_reads.message_id` → `messages.id`
- `snap_messages.message_id` → `messages.id`
- `status_views.status_id` → `statuses.id`
- `status_views.viewer_id` → `users.id`
- `user_profiles.user_id` → `users.id`
- `user_status.user_id` → `users.id`
- `user_status.typing_in_chat` → `chats.id`

### Business Logic Relationships (Username-based)
- Users participate in chats via username strings
- Messages are sent by username strings  
- Statuses are created by username strings

## Notable Features

### Authentication & Security
- Password hashing
- Email verification system
- Verification tokens

### Chat Features
- Direct and group chats
- Multiple message types
- Disappearing messages (Snap feature)
- Read receipts
- Typing indicators

### Social Features  
- User profiles with privacy settings
- Stories/status updates (24-hour expiry)
- Contact/friend system
- Profile pictures and media uploads

### Business Features
- Contact form system
- Admin inquiry management
- User activity tracking

## How to View Diagrams

### VS Code (Recommended)
1. Install PlantUML extension
2. Open `.puml` files and press `Alt+D` / `Option+D`
3. Open `.md` files for Mermaid diagrams

### Online Viewers
- PlantUML: http://www.plantuml.com/plantuml/
- Mermaid: https://mermaid.live/

### Image Files
- Open `.png` or `.svg` files directly in any image viewer or browser

## Development Notes

The actual database uses username strings instead of foreign key relationships in several places, which is different from typical relational database design but functional for the application's needs. This approach provides flexibility but requires careful handling in application code to maintain data integrity.
