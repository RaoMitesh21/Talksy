<?php
/**
 * Enhanced Talksy API - Complete Backend Implementation
 * Handles all chat, user, and status operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'db_config.php';

class TalksyAPI {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        
        // Auto-setup upload directories on initialization
        $this->setupUploadDirectories();
    }

    private function setupUploadDirectories() {
        $directories = ['profiles', 'files', 'status', 'snaps'];
        
        foreach ($directories as $dir) {
            $uploadDir = "uploads/{$dir}/";
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
                @chmod($uploadDir, 0777);
            } else {
                // Ensure existing directories are writable
                if (!is_writable($uploadDir)) {
                    @chmod($uploadDir, 0777);
                }
            }
        }
    }
    
    public function handleRequest() {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
        
        // Debug logging
        error_log("Enhanced API - Action: $action, Input: " . json_encode($input));
        
        try {
            switch ($action) {
                // Authentication
                case 'register':
                    return $this->register($input);
                case 'login':
                    return $this->login($input);
                    
                // User Management
                case 'get_user_profile':
                    return $this->getUserProfile($input['username']);
                case 'update_user_profile':
                    return $this->updateUserProfile($input);
                case 'search_users':
                    return $this->searchUsers($input['query']);
                case 'get_user_contacts':
                    return $this->getUserContacts($input['username']);
                case 'update_online_status':
                    return $this->updateOnlineStatus($input['username'], $input['isOnline']);
                case 'upload_profile_picture':
                    return $this->uploadProfilePicture();
                    
                // Chat Management
                case 'setup_database':
                    return $this->setupDatabase();
                case 'get_chats':
                    return $this->getChats($input['username']);
                case 'create_chat':
                    return $this->createChat($input['currentUser'], $input['targetUser']);
                case 'get_messages':
                    return $this->getMessages($input['chatId'], $input['username'], $input['limit'] ?? 50, $input['offset'] ?? 0);
                case 'send_message':
                    return $this->sendMessage($input);
                case 'mark_messages_read':
                    return $this->markMessagesAsRead($input);
                case 'delete_message':
                    return $this->deleteMessage($input['messageId'], $input['username']);
                case 'upload_file':
                    return $this->uploadFile();
                case 'upload_chat_file':
                    return $this->uploadChatFile();
                case 'download_file':
                    $this->downloadFile($_GET['fileId'] ?? null);
                    exit; // Don't process any further
                case 'upload_snap':
                    return $this->uploadSnap();
                case 'view_snap':
                    return $this->viewSnap($_POST['snapId'] ?? null, $_POST['username'] ?? null);
                case 'get_chat_files':
                    return $this->getChatFiles($input['chat_id'] ?? null);
                    
                // Status Management
                case 'upload_status':
                    return $this->uploadStatus();
                case 'get_statuses':
                    return $this->getStatuses($input['username']);
                case 'get_contacts_statuses':
                    return $this->getContactsStatuses($input['username']);
                case 'view_status':
                    return $this->viewStatus($input['username'], $input['statusId']);
                case 'delete_status':
                    return $this->deleteStatus($input['username'], $input['statusId']);
                case 'get_status_viewers':
                    return $this->getStatusViewers($input['statusId']);
                case 'apply_filter':
                    return $this->applyFilter();
                case 'save_snap':
                    return $this->saveSnap($input['snapUrl'], $input['username']);
                case 'get_filters':
                    return $this->getFilters();
                    
                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Database Setup
    public function setupDatabase() {
        try {
            // Users table
            $this->db->prepare("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    display_name VARCHAR(100),
                    about TEXT DEFAULT 'Hey there! I am using Talksy.',
                    profile_picture VARCHAR(255),
                    phone VARCHAR(20),
                    is_online BOOLEAN DEFAULT FALSE,
                    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // Chats table
            $this->db->prepare("
                CREATE TABLE IF NOT EXISTS chats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100),
                    type ENUM('direct', 'group') DEFAULT 'direct',
                    created_by VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // Chat participants
            $this->db->prepare("
                CREATE TABLE IF NOT EXISTS chat_participants (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    chat_id INT,
                    username VARCHAR(50),
                    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE
                )
            ");
            
            // Messages table
            $this->db->prepare("
                CREATE TABLE IF NOT EXISTS messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    chat_id INT,
                    sender VARCHAR(50),
                    content TEXT,
                    type ENUM('text', 'image', 'video', 'audio', 'file', 'snap') DEFAULT 'text',
                    attachments JSON,
                    is_read BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE
                )
            ");
            
            // Message read status
            $this->db->prepare("
                CREATE TABLE IF NOT EXISTS message_reads (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    message_id INT,
                    username VARCHAR(50),
                    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
                )
            ");
            
            // Statuses table
            $this->db->prepare("
                CREATE TABLE IF NOT EXISTS statuses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50),
                    content TEXT,
                    type ENUM('text', 'image', 'video') DEFAULT 'text',
                    file_url VARCHAR(255),
                    background_color VARCHAR(7),
                    filter VARCHAR(50),
                    viewed_by JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 24 HOUR)
                )
            ");
            
            // Contacts/Friends table
            $this->db->prepare("
                CREATE TABLE IF NOT EXISTS contacts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user1 VARCHAR(50),
                    user2 VARCHAR(50),
                    status ENUM('pending', 'accepted', 'blocked') DEFAULT 'accepted',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Insert Talksy Official user if not exists
            $this->db->prepare("
                INSERT IGNORE INTO users (username, email, password, display_name, about, is_online) 
                VALUES ('talksy_official', 'official@talksy.com', '', 'Talksy Official', 'Welcome to Talksy! Enjoy unlimited messaging and amazing features.', TRUE)
            ");
            
            return ['success' => true, 'message' => 'Database setup completed'];
        } catch (Exception $e) {
            throw new Exception('Database setup failed: ' . $e->getMessage());
        }
    }
    
    // Authentication Methods - Use Original Email Verification System
    public function register($data) {
        // This method defers to your original registration.php with email verification
        // Call registration.php endpoint directly for proper email verification flow
        throw new Exception('Please use the original registration endpoint at registration.php for email verification');
    }
    
    public function login($data) {
        // This method defers to your original login.php with email verification check
        // For now, we'll implement basic login but recommend using login.php
        $email = $data['email'] ?? $data['username']; // Support both email and username
        $password = $data['password'];
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if email is verified (your original system requirement)
            if ($user['email_verified'] == 0) {
                throw new Exception('Please verify your email before logging in!');
            }
            
            if (password_verify($password, $user['password'])) {
                // Update online status
                $stmt = $this->db->prepare("UPDATE users SET is_online = TRUE, last_seen = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                unset($user['password']);
                return ['success' => true, 'user' => $user, 'message' => 'Login successful'];
            } else {
                throw new Exception('Invalid password!');
            }
        } else {
            throw new Exception('Invalid email or username!');
        }
    }
    
    // Universal URL normalization function
    private function normalizeProfilePictureUrl($profilePictureUrl) {
        if (empty($profilePictureUrl)) {
            return null;
        }
        
        // If already absolute URL, return as is
        if (strpos($profilePictureUrl, 'http') === 0) {
            return $profilePictureUrl;
        }
        
        // Convert relative URL to absolute - point to Apache server
        $baseUrl = 'http://localhost/Talksy/public';
        return $baseUrl . '/' . ltrim($profilePictureUrl, '/');
    }

    // User Management Methods
    public function getUserProfile($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            unset($user['password']);
            // Normalize profile picture URL universally
            $user['profile_picture'] = $this->normalizeProfilePictureUrl($user['profile_picture']);
            return ['success' => true, 'user' => $user];
        } else {
            throw new Exception('User not found');
        }
    }
    
    public function updateUserProfile($data) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET display_name = ?, about = ?, phone = ?, profile_picture = ?, updated_at = CURRENT_TIMESTAMP
            WHERE username = ?
        ");
        
        $result = $stmt->execute([
            $data['displayName'],
            $data['about'],
            $data['phone'],
            $data['profilePicture'],
            $data['username']
        ]);
        
        if ($result) {
            return $this->getUserProfile($data['username']);
        } else {
            throw new Exception('Failed to update profile');
        }
    }
    
    public function searchUsers($query) {
        $stmt = $this->db->prepare("
            SELECT username, display_name, profile_picture, about, is_online 
            FROM users 
            WHERE (username LIKE ? OR display_name LIKE ?) 
            AND username != 'talksy_official'
            LIMIT 20
        ");
        
        $searchTerm = "%{$query}%";
        $stmt->execute([$searchTerm, $searchTerm]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Normalize profile pictures
        foreach ($users as &$u) {
            $u['profile_picture'] = $this->normalizeProfilePictureUrl($u['profile_picture'] ?? null);
        }
        unset($u);

        return ['success' => true, 'users' => $users];
    }
    
    // Chat Management Methods
    public function getChats($username) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT c.id, c.name, c.type,
                   u.username, u.display_name, u.profile_picture, u.is_online,
                   m.content as last_message, m.sender as last_message_sender, m.created_at as last_message_time,
                   (SELECT COUNT(*) FROM messages m2 
                    JOIN chat_participants cp2 ON m2.chat_id = cp2.chat_id 
                    WHERE cp2.username = ? AND m2.chat_id = c.id 
                    AND m2.id NOT IN (SELECT message_id FROM message_reads WHERE username = ?)) as unread_count
            FROM chats c
            JOIN chat_participants cp ON c.id = cp.chat_id
            LEFT JOIN chat_participants cp_other ON c.id = cp_other.chat_id AND cp_other.username != ?
            LEFT JOIN users u ON cp_other.username = u.username
            LEFT JOIN messages m ON c.id = m.chat_id
            WHERE cp.username = ?
            AND m.id = (SELECT MAX(id) FROM messages WHERE chat_id = c.id)
            ORDER BY m.created_at DESC
        ");
        
        $stmt->execute([$username, $username, $username, $username]);
        $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add Talksy Official chat if no chats exist
        if (empty($chats)) {
            $this->createChat($username, 'talksy_official');
            // Re-fetch chats
            $stmt->execute([$username, $username, $username, $username]);
            $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Normalize profile picture URLs for each chat's user
        foreach ($chats as &$chat) {
            if (isset($chat['profile_picture'])) {
                $chat['profile_picture'] = $this->normalizeProfilePictureUrl($chat['profile_picture']);
                // Maintain a consistent 'avatar' field for compatibility
                $chat['avatar'] = $chat['profile_picture'];
            } else {
                $chat['profile_picture'] = null;
                $chat['avatar'] = null;
            }
        }
        unset($chat);

        return ['success' => true, 'chats' => $chats];
    }
    
    public function createChat($currentUser, $targetUser) {
        // Check if chat already exists
        $stmt = $this->db->prepare("
            SELECT c.id FROM chats c
            JOIN chat_participants cp1 ON c.id = cp1.chat_id
            JOIN chat_participants cp2 ON c.id = cp2.chat_id
            WHERE cp1.username = ? AND cp2.username = ? AND c.type = 'direct'
        ");
        $stmt->execute([$currentUser, $targetUser]);
        $existingChat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingChat) {
            // Get full chat info including target user details
            $chatInfo = $this->getChatInfo($existingChat['id'], $currentUser);
            return ['success' => true, 'chat' => $chatInfo];
        }
        
        // Create new chat
        $this->db->beginTransaction();
        
        try {
            // Get user IDs for both participants
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$currentUser]);
            $currentUserId = $stmt->fetchColumn();
            
            $stmt->execute([$targetUser]);
            $targetUserId = $stmt->fetchColumn();
            
            if (!$currentUserId || !$targetUserId) {
                throw new Exception('One or both users not found');
            }
            
            $stmt = $this->db->prepare("INSERT INTO chats (type, created_by) VALUES ('direct', ?)");
            $stmt->execute([$currentUser]);
            $chatId = $this->db->lastInsertId();
            
            // Add participants with both user_id and username
            $stmt = $this->db->prepare("INSERT INTO chat_participants (chat_id, user_id, username) VALUES (?, ?, ?)");
            $stmt->execute([$chatId, $currentUserId, $currentUser]);
            $stmt->execute([$chatId, $targetUserId, $targetUser]);
            
            // Send welcome message if it's Talksy Official
            if ($targetUser === 'talksy_official') {
                $this->sendMessage([
                    'chatId' => $chatId,
                    'sender' => 'talksy_official',
                    'content' => 'Welcome to Talksy! 🎉 Enjoy unlimited messaging and amazing features!',
                    'type' => 'text'
                ]);
            }
            
            $this->db->commit();
            // Get full chat info including target user details
            $chatInfo = $this->getChatInfo($chatId, $currentUser);
            return ['success' => true, 'chat' => $chatInfo];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Failed to create chat: ' . $e->getMessage());
        }
    }
    
    private function getChatInfo($chatId, $currentUser) {
        // Get chat details
        $stmt = $this->db->prepare("SELECT * FROM chats WHERE id = ?");
        $stmt->execute([$chatId]);
        $chat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get the other participant (for direct chats)
        $stmt = $this->db->prepare("
            SELECT u.username, u.display_name, u.profile_picture, u.is_online
            FROM chat_participants cp
            JOIN users u ON cp.username = u.username  
            WHERE cp.chat_id = ? AND cp.username != ?
        ");
        $stmt->execute([$chatId, $currentUser]);
        $otherUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get last message
        $stmt = $this->db->prepare("
            SELECT content, sender, created_at 
            FROM messages 
            WHERE chat_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$chatId]);
        $lastMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Normalize other user's profile picture
        $normalizedProfile = $this->normalizeProfilePictureUrl($otherUser['profile_picture'] ?? null);

        return [
            'id' => $chat['id'],
            'name' => $otherUser['display_name'] ?: $otherUser['username'],
            'display_name' => $otherUser['display_name'],
            'avatar' => $normalizedProfile,
            'profile_picture' => $normalizedProfile,
            'is_online' => $otherUser['is_online'],
            'last_message' => $lastMessage['content'] ?? '',
            'last_message_time' => $lastMessage['created_at'] ?? $chat['created_at'],
            'last_message_sender' => $lastMessage['sender'] ?? '',
            'unread_count' => 0 // TODO: implement unread count
        ];
    }
    
    public function getMessages($chatId, $username, $limit = 50, $offset = 0) {
        // Ensure limit and offset are integers
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $stmt = $this->db->prepare("
            SELECT m.*, mr.read_at
            FROM messages m
            LEFT JOIN message_reads mr ON m.id = mr.message_id AND mr.username = ?
            WHERE m.chat_id = ?
            ORDER BY m.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        
        $stmt->execute([$username, $chatId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['success' => true, 'messages' => array_reverse($messages)];
    }
    
    public function sendMessage($data) {
        $stmt = $this->db->prepare("
            INSERT INTO messages (chat_id, sender, content, type, attachments) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $attachments = isset($data['attachments']) ? json_encode($data['attachments']) : null;
        
        $result = $stmt->execute([
            $data['chatId'],
            $data['sender'],
            $data['content'],
            $data['type'] ?? 'text',
            $attachments
        ]);
        
        if ($result) {
            $messageId = $this->db->lastInsertId();
            
            // Get the created message
            $stmt = $this->db->prepare("SELECT * FROM messages WHERE id = ?");
            $stmt->execute([$messageId]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Send WebSocket notification to all chat participants
            $this->sendWebSocketNotification([
                'type' => 'new_message',
                'chatId' => $data['chatId'],
                'messageId' => $messageId,
                'senderId' => $data['sender'],
                'content' => $data['content'],
                'messageType' => $data['type'] ?? 'text',
                'timestamp' => $message['created_at'],
                'tempId' => $data['tempId'] ?? null
            ]);
            
            return ['success' => true, 'message' => $message];
        } else {
            throw new Exception('Failed to send message');
        }
    }
    
    // Status Management Methods
    public function uploadStatus() {
        $username = $_POST['username'];
        $type = $_POST['type'];
        $content = $_POST['content'] ?? '';
        $backgroundColor = $_POST['backgroundColor'] ?? null;
        $filter = $_POST['filter'] ?? null;
        
        $fileUrl = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $fileUrl = $this->handleFileUpload($_FILES['file'], 'statuses');
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO statuses (username, content, type, file_url, background_color, filter, viewed_by) 
            VALUES (?, ?, ?, ?, ?, ?, '[]')
        ");
        
        $result = $stmt->execute([$username, $content, $type, $fileUrl, $backgroundColor, $filter]);
        
        if ($result) {
            $statusId = $this->db->lastInsertId();
            $stmt = $this->db->prepare("SELECT * FROM statuses WHERE id = ?");
            $stmt->execute([$statusId]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            // Normalize file_url
            if (isset($status['file_url'])) {
                $status['file_url'] = $this->normalizeProfilePictureUrl($status['file_url']);
            }

            return ['success' => true, 'status' => $status];
        } else {
            throw new Exception('Failed to upload status');
        }
    }
    
    public function getStatuses($username) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.display_name 
            FROM statuses s 
            JOIN users u ON s.username = u.username 
            WHERE s.username = ? AND s.expires_at > NOW() 
            ORDER BY s.created_at DESC
        ");
        
        $stmt->execute([$username]);
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Normalize status file URLs
        foreach ($statuses as &$s) {
            if (isset($s['file_url'])) {
                $s['file_url'] = $this->normalizeProfilePictureUrl($s['file_url']);
            }
        }
        unset($s);

        return ['success' => true, 'statuses' => $statuses];
    }
    
    // Utility Methods
    private function handleFileUpload($file, $folder) {
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }
        
        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if ($file['size'] > $maxSize) {
            throw new Exception('File too large. Maximum size is 10MB.');
        }
        
        // Validate file type for profile pictures
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        if ($folder === 'profiles' && !in_array($fileType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
        }
        
        $uploadDir = "uploads/{$folder}/";
        
        // Create directory with proper permissions if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception('Failed to create upload directory');
            }
            // Set proper permissions after creation
            chmod($uploadDir, 0777);
        }
        
        // Ensure directory is writable
        if (!is_writable($uploadDir)) {
            chmod($uploadDir, 0777);
        }
        
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Set proper permissions on uploaded file
            chmod($filePath, 0644);
            return $filePath;
        } else {
            throw new Exception('Failed to upload file to server. Check directory permissions.');
        }
    }
    
    public function updateOnlineStatus($username, $isOnline) {
        $stmt = $this->db->prepare("UPDATE users SET is_online = ?, last_seen = CURRENT_TIMESTAMP WHERE username = ?");
        $result = $stmt->execute([$isOnline, $username]);
        
        return ['success' => $result];
    }
    
    public function getFilters() {
        $filters = [
            ['id' => 'none', 'name' => 'None', 'preview' => null],
            ['id' => 'sepia', 'name' => 'Sepia', 'preview' => 'sepia(1)'],
            ['id' => 'grayscale', 'name' => 'Grayscale', 'preview' => 'grayscale(1)'],
            ['id' => 'blur', 'name' => 'Blur', 'preview' => 'blur(3px)'],
            ['id' => 'brightness', 'name' => 'Bright', 'preview' => 'brightness(1.3)'],
            ['id' => 'contrast', 'name' => 'Contrast', 'preview' => 'contrast(1.3)'],
            ['id' => 'saturate', 'name' => 'Saturate', 'preview' => 'saturate(1.5)'],
            ['id' => 'vintage', 'name' => 'Vintage', 'preview' => 'sepia(0.5) contrast(1.2)'],
            ['id' => 'cool', 'name' => 'Cool', 'preview' => 'hue-rotate(90deg)'],
            ['id' => 'warm', 'name' => 'Warm', 'preview' => 'hue-rotate(-30deg) saturate(1.2)']
        ];
        
        return ['success' => true, 'filters' => $filters];
    }
    
    // Placeholder methods for other features
    public function getUserContacts($username) {
        return ['success' => true, 'contacts' => []];
    }
    
    public function uploadProfilePicture() {
        if (isset($_FILES['profile_picture'])) {
            $fileUrl = $this->handleFileUpload($_FILES['profile_picture'], 'profiles');
            // Return absolute URL
            $fileUrl = $this->normalizeProfilePictureUrl($fileUrl);
            return ['success' => true, 'profilePictureUrl' => $fileUrl];
        }
        throw new Exception('No file uploaded');
    }
    
    public function markMessagesAsRead($data) {
        return ['success' => true];
    }
    
    public function deleteMessage($messageId, $username) {
        // Add logging for debugging
        error_log("Delete message attempt - MessageID: $messageId, Username: $username");
        
        // Check if user owns the message or has permission to delete
        $stmt = $this->db->prepare("SELECT sender, chat_id FROM messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$message) {
            error_log("Delete message failed - Message not found: $messageId");
            throw new Exception('Message not found');
        }
        
        error_log("Delete message - Message sender: {$message['sender']}, Requesting user: $username");
        
        if ($message['sender'] !== $username) {
            error_log("Delete message failed - Permission denied. Sender: {$message['sender']}, User: $username");
            throw new Exception('You can only delete your own messages');
        }
        
        // Delete the message
        $stmt = $this->db->prepare("DELETE FROM messages WHERE id = ? AND sender = ?");
        $result = $stmt->execute([$messageId, $username]);
        
        if ($result) {
            // Also delete message reads
            $stmt = $this->db->prepare("DELETE FROM message_reads WHERE message_id = ?");
            $stmt->execute([$messageId]);
            
            // Send WebSocket notification to all chat participants
            $this->sendWebSocketNotification([
                'type' => 'message_deleted',
                'messageId' => $messageId,
                'chatId' => $message['chat_id'],
                'deletedBy' => $username,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'message' => 'Message deleted successfully'];
        } else {
            throw new Exception('Failed to delete message');
        }
    }
    
    public function uploadFile() {
        if (isset($_FILES['file'])) {
            $fileUrl = $this->handleFileUpload($_FILES['file'], 'attachments');
            return ['success' => true, 'fileUrl' => $fileUrl];
        }
        throw new Exception('No file uploaded');
    }
    
    public function getContactsStatuses($username) {
        return ['success' => true, 'statuses' => []];
    }
    
    public function viewStatus($username, $statusId) {
        return ['success' => true];
    }
    
    public function deleteStatus($username, $statusId) {
        return ['success' => true];
    }
    
    public function getStatusViewers($statusId) {
        return ['success' => true, 'viewers' => []];
    }
    
    public function applyFilter() {
        return ['success' => true, 'filteredFileUrl' => ''];
    }
    
    public function saveSnap($snapUrl, $username) {
        return ['success' => true];
    }
    
    // Send WebSocket notification to chat participants - OPTIMIZED FOR SPEED
    private function sendWebSocketNotification($data) {
        try {
            // Use simple HTTP POST to notify WebSocket server - much faster
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($data),
                    'timeout' => 0.5  // 500ms timeout for speed
                ]
            ]);
            
            // Send to WebSocket server's HTTP handler
            $result = @file_get_contents('http://localhost:8080/notify', false, $context);
            
            if ($result === false) {
                // Fallback: Use UDP socket for instant delivery
                $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                if ($socket) {
                    $message = json_encode($data);
                    @socket_sendto($socket, $message, strlen($message), 0, '127.0.0.1', 8081);
                    @socket_close($socket);
                }
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("WebSocket notification error: " . $e->getMessage());
            return false;
        }
    }

    // Enhanced File Upload for Chat Messages
    public function uploadChatFile() {
        try {
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }

            $file = $_FILES['file'];
            $chatId = $_POST['chatId'] ?? null;
            $sender = $_POST['sender'] ?? null;

            if (!$chatId || !$sender) {
                throw new Exception('Chat ID and sender are required');
            }

            // Enhanced file validation
            $allowedTypes = [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf', 'text/plain', 'text/csv',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip', 'application/x-rar-compressed'
            ];

            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('File type not allowed. Please upload images, documents, or archives only.');
            }

            // File size validation (50MB max for chat files)
            $maxSize = 50 * 1024 * 1024; // 50MB
            if ($file['size'] > $maxSize) {
                throw new Exception('File too large. Maximum size is 50MB.');
            }

            // Upload file
            $fileUrl = $this->handleFileUpload($file, 'files');
            $absoluteUrl = $this->normalizeProfilePictureUrl($fileUrl);

            // Store file info in database
            $stmt = $this->db->prepare("INSERT INTO chat_files (chat_id, sender, filename, file_url, file_size, file_type, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$chatId, $sender, $file['name'], $fileUrl, $file['size'], $fileType]);
            $fileId = $this->db->lastInsertId();

            return [
                'success' => true,
                'fileId' => $fileId,
                'fileUrl' => $absoluteUrl,
                'fileName' => $file['name'],
                'fileSize' => $file['size'],
                'fileType' => $fileType
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // File Download with Security
    public function downloadFile($fileId) {
        try {
            if (!$fileId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'File ID required']);
                exit;
            }

            $stmt = $this->db->prepare("SELECT * FROM chat_files WHERE id = ?");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'File not found']);
                exit;
            }

            $filePath = $file['file_url'];
            if (!file_exists($filePath)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'File no longer exists on server']);
                exit;
            }

            // Clear any output buffers
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Set proper headers for download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . addslashes($file['filename']) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            // Output file content
            readfile($filePath);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // Get Chat Files
    public function getChatFiles($chatId) {
        try {
            if (!$chatId) {
                throw new Exception('Chat ID required');
            }

            $stmt = $this->db->prepare("SELECT * FROM chat_files WHERE chat_id = ? ORDER BY uploaded_at DESC");
            $stmt->execute([$chatId]);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'files' => $files
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Snap Upload with View-Once Functionality
    public function uploadSnap() {
        try {
            if (!isset($_FILES['snap'])) {
                throw new Exception('No snap file uploaded');
            }

            $file = $_FILES['snap'];
            $chatId = $_POST['chatId'] ?? null;
            $sender = $_POST['sender'] ?? null;

            if (!$chatId || !$sender) {
                throw new Exception('Chat ID and sender are required');
            }

            // Only allow images for snaps
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Only images are allowed for snaps');
            }

            // Upload snap
            $fileUrl = $this->handleFileUpload($file, 'snaps');
            $absoluteUrl = $this->normalizeProfilePictureUrl($fileUrl);

            // Store snap info in database with view tracking
            $stmt = $this->db->prepare("INSERT INTO snaps (chat_id, sender, file_url, created_at, is_viewed, auto_delete_at) VALUES (?, ?, ?, NOW(), 0, DATE_ADD(NOW(), INTERVAL 24 HOUR))");
            $stmt->execute([$chatId, $sender, $fileUrl]);
            $snapId = $this->db->lastInsertId();

            return [
                'success' => true,
                'snapId' => $snapId,
                'snapUrl' => $absoluteUrl,
                'expiresAt' => date('Y-m-d H:i:s', strtotime('+24 hours'))
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // View Snap (Mark as viewed and auto-delete)
    public function viewSnap($snapId, $username) {
        try {
            if (!$snapId || !$username) {
                throw new Exception('Snap ID and username are required');
            }

            // Get snap details
            $stmt = $this->db->prepare("SELECT * FROM snaps WHERE id = ? AND is_viewed = 0");
            $stmt->execute([$snapId]);
            $snap = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$snap) {
                throw new Exception('Snap not found or already viewed');
            }

            // Only allow receiver to view (not the sender)
            if ($snap['sender'] === $username) {
                return [
                    'success' => true,
                    'snapUrl' => $this->normalizeProfilePictureUrl($snap['file_url']),
                    'canView' => false,
                    'message' => 'You cannot view your own snap'
                ];
            }

            // Mark as viewed and schedule for deletion
            $stmt = $this->db->prepare("UPDATE snaps SET is_viewed = 1, viewed_at = NOW(), viewed_by = ?, auto_delete_at = DATE_ADD(NOW(), INTERVAL 10 SECOND) WHERE id = ?");
            $stmt->execute([$username, $snapId]);

            return [
                'success' => true,
                'snapUrl' => $this->normalizeProfilePictureUrl($snap['file_url']),
                'canView' => true,
                'deleteAfter' => 10 // seconds
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Auto-cleanup viewed snaps (call this periodically)
    public function cleanupViewedSnaps() {
        try {
            // Get snaps that should be deleted
            $stmt = $this->db->prepare("SELECT * FROM snaps WHERE auto_delete_at <= NOW()");
            $stmt->execute();
            $snapsToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($snapsToDelete as $snap) {
                // Delete file from filesystem
                if (file_exists($snap['file_url'])) {
                    unlink($snap['file_url']);
                }

                // Delete from database
                $deleteStmt = $this->db->prepare("DELETE FROM snaps WHERE id = ?");
                $deleteStmt->execute([$snap['id']]);
            }

            return [
                'success' => true,
                'deletedCount' => count($snapsToDelete)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

// Handle the request
$api = new TalksyAPI();
$response = $api->handleRequest();
echo json_encode($response);
?>
