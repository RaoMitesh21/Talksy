Chapter 3 & 4 Draft Improvements

This draft contains improved, expanded content for Chapter 3 (System Analysis & Design) and Chapter 4 (Implementation). It includes a complete database schema derived directly from the `talksy.sql` dump and clearer implementation guidance referencing the generated ER diagrams.

---

CHAPTER 3: SYSTEM ANALYSIS & DESIGN (IMPROVED)

3.1 Overview

This chapter refines the system analysis and design for Talksy, focusing on a production-ready database schema, clear entity relationships, data integrity, and design rationale. The database design balances normalization, query performance, and practical flexibility for a real-time chat application.

3.2 Database Design Goals

- Ensure data integrity and referential consistency where appropriate.
- Use indexes to accelerate read-heavy chat operations.
- Favor username-based references for compatibility with existing code (as seen in the dump) while documenting where integer FKs are preferable.
- Include JSON columns for extensible metadata (attachments, viewed_by).

3.3 ER Diagram

Refer to the diagram files in `database/`:
- `Talksy_Actual_Database_ER_Diagram.png` (visual)
- `talksy_actual_er_diagram.puml` (PlantUML source)
- `talksy_actual_er_diagram.md` (Mermaid source)

3.4 Complete Database Schema (Derived from talksy.sql)

Below are CREATE TABLE statements adapted from the SQL dump with clear PK/FK declarations and indexes. These statements are ready to apply to a MySQL/MariaDB server.

-- users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `about` text DEFAULT 'Hey there! I am using Talksy.',
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- chats
CREATE TABLE `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `type` enum('direct','group') DEFAULT 'direct',
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- chat_participants
CREATE TABLE `chat_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- messages
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) DEFAULT NULL,
  `sender` varchar(50) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `type` enum('text','image','video','audio','file','snap') DEFAULT 'text',
  `attachments` longtext DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- message_reads
CREATE TABLE `message_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- snap_messages
CREATE TABLE `snap_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `viewed_by` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- statuses
CREATE TABLE `statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `type` enum('text','image','video') DEFAULT 'text',
  `file_url` varchar(255) DEFAULT NULL,
  `background_color` varchar(7) DEFAULT NULL,
  `filter` varchar(50) DEFAULT NULL,
  `viewed_by` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 24 hour),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- status_views
CREATE TABLE `status_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_id` int(11) NOT NULL,
  `viewer_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_view` (`status_id`,`viewer_id`),
  KEY `viewer_id` (`viewer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- user_profiles
CREATE TABLE `user_profiles` (
  `user_id` int(11) NOT NULL,
  `about` text DEFAULT 'Hey there! I am using Talksy.',
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` text DEFAULT NULL,
  `last_seen_privacy` enum('everyone','contacts','nobody') DEFAULT 'everyone',
  `profile_photo_privacy` enum('everyone','contacts','nobody') DEFAULT 'everyone',
  `read_receipts_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- user_status
CREATE TABLE `user_status` (
  `user_id` int(11) NOT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `typing_in_chat` int(11) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- contacts
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user1` varchar(50) DEFAULT NULL,
  `user2` varchar(50) DEFAULT NULL,
  `status` enum('pending','accepted','blocked') DEFAULT 'accepted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- contact_inquiries
CREATE TABLE `contact_inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL DEFAULT 'General Inquiry',
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('new','in_progress','resolved','archived') DEFAULT 'new',
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

3.5 Indexes & Performance Considerations

- Create composite indexes for frequent queries (chat_id, created_at) on `messages`.
- Index foreign key columns used in JOINs.
- Use JSON fields for flexible data but avoid them in frequent WHERE clauses.

3.6 Design Decisions & Rationale

- Username vs numeric FK: The dump uses username strings in several places (messages, statuses, chat_participants). This preserves backwards compatibility but is less efficient for large-scale joins; consider migrating to integer FKs for scalability.
- Snap messages and statuses use JSON to track viewers to simplify writes for high-frequency events.
- TTL for statuses is implemented using `expires_at`.

---

CHAPTER 4: IMPLEMENTATION (IMPROVED)

4.1 Overview

This chapter expands implementation details with explicit code snippets, database deployment steps, and guidance for production hardening.

4.2 Database Deployment & Seed

- Import SQL dump: `mysql -u root -p talksy < talksy.sql`
- Verify indexes and constraints using `SHOW CREATE TABLE` and `SHOW INDEX`.
- Seed initial data (talksy_official user, default chats) — included in dump.

4.3 Full CREATE Scripts (ready-to-run)

-- (Same CREATE TABLE statements as Chapter 3.4 above)

4.4 Back-end API & Database Integration

- Use the `Database` class (`db_config.php`) with PDO and prepared statements for safe queries.
- Transaction handling: wrap multi-step operations (e.g., message insert + status update) in transactions to ensure atomicity.
- Example: Insert message and record read-status in a transaction.

4.5 WebSocket Server & Notifications

- Implement duplicate detection (signature + short cache) in WebSocket server.
- Use HTTP notify endpoint (or a message queue like Redis/ZeroMQ for scale) for backend → WebSocket communication.

4.6 Security & Hardening

- Use HTTPS and WSS in production.
- Store DB credentials securely (env files or secrets manager).
- Rate-limit WebSocket and API endpoints.
- Sanitize and validate all inputs before DB writes.

4.7 Maintenance & Migration Notes

- Migration to integer FKs: plan schema migration scripts and backfill numeric IDs while preserving username references during transition.
- Add retention jobs for snap messages and expired statuses.

---

Next steps: review and apply the content to `TALKSY_PROJECT_REPORT.md` replacing Chapter 3 and 4 sections.
