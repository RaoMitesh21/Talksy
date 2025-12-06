<?php
/**
 * Simple Professional Directory Browser
 * Automatically detects current directory and displays professionally
 */

// Get current directory path
$currentDir = dirname($_SERVER['SCRIPT_NAME']);
$fullPath = $_SERVER['DOCUMENT_ROOT'] . $currentDir;

// Configuration
$title = "Mitesh Development Server";
$showHidden = true;
$excludeFiles = array('.', '..', 'index.php', 'autoindex.php', 'simple_index.php', '.htaccess', '.DS_Store');

// Function to format file size
function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0 B';
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

// Function to get file icon
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $icons = array(
        'folder' => '📁', 'php' => '🐘', 'html' => '🌐', 'css' => '🎨', 'js' => '⚡',
        'json' => '📄', 'xml' => '📄', 'txt' => '��', 'md' => '📖', 'pdf' => '📕',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'svg' => '🖼️',
        'mp3' => '🎵', 'mp4' => '🎬', 'zip' => '📦', 'sql' => '🗃️', 'log' => '📋'
    );
    
    return isset($icons[$ext]) ? $icons[$ext] : '📄';
}

// Get directory contents
$files = array();
$directories = array();

if (is_dir($fullPath)) {
    $items = scandir($fullPath);
    foreach ($items as $item) {
        if (in_array($item, $excludeFiles)) continue;
        if (!$showHidden && substr($item, 0, 1) === '.') continue;
        
        $itemPath = $fullPath . '/' . $item;
        $itemInfo = array(
            'name' => $item,
            'size' => is_file($itemPath) ? filesize($itemPath) : 0,
            'modified' => filemtime($itemPath),
            'is_dir' => is_dir($itemPath),
            'icon' => is_dir($itemPath) ? getFileIcon('folder') : getFileIcon($item)
        );
        
        if ($itemInfo['is_dir']) {
            $directories[] = $itemInfo;
        } else {
            $files[] = $itemInfo;
        }
    }
}

usort($directories, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
usort($files, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
$allItems = array_merge($directories, $files);

// Build breadcrumb
$pathParts = array_filter(explode('/', $currentDir));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title . ' - ' . (end($pathParts) ?: 'Root')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; color: #333;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            border-radius: 15px; padding: 30px; margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .header h1 {
            font-size: 2.5rem; font-weight: 700; color: #2d3748; margin-bottom: 10px;
            display: flex; align-items: center; gap: 15px;
        }
        .header p { color: #718096; font-size: 1.1rem; font-weight: 400; }
        .breadcrumb-nav {
            display: flex; align-items: center; gap: 8px; margin: 15px 0; flex-wrap: wrap;
        }
        .breadcrumb-item {
            color: #667eea; text-decoration: none; font-weight: 500;
            padding: 4px 8px; border-radius: 6px; transition: all 0.3s ease;
        }
        .breadcrumb-item:hover { background: rgba(102, 126, 234, 0.1); }
        .breadcrumb-separator { color: #a0aec0; margin: 0 2px; }
        .stats { display: flex; gap: 30px; margin-top: 20px; }
        .stat { display: flex; align-items: center; gap: 8px; color: #4a5568; font-weight: 500; }
        .file-browser {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            border-radius: 15px; overflow: hidden; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .search-box { padding: 20px; border-bottom: 1px solid rgba(0, 0, 0, 0.1); }
        .search-input {
            width: 100%; padding: 12px 20px; border: 2px solid #e2e8f0;
            border-radius: 10px; font-size: 16px; font-family: inherit; transition: all 0.3s ease;
        }
        .search-input:focus {
            outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .file-list { list-style: none; }
        .file-item {
            display: flex; align-items: center; padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05); transition: all 0.3s ease; cursor: pointer;
        }
        .file-item:hover { background: rgba(102, 126, 234, 0.05); transform: translateX(5px); }
        .file-item:last-child { border-bottom: none; }
        .file-icon { font-size: 1.5rem; margin-right: 15px; width: 30px; text-align: center; }
        .file-info { flex: 1; display: flex; justify-content: space-between; align-items: center; }
        .file-name { font-weight: 500; color: #2d3748; font-size: 1rem; }
        .file-details { display: flex; gap: 20px; color: #718096; font-size: 0.9rem; }
        .file-size { min-width: 80px; text-align: right; }
        .file-date { min-width: 120px; text-align: right; }
        .folder-item { background: rgba(102, 126, 234, 0.02); }
        .folder-item .file-name { color: #667eea; font-weight: 600; }
        .empty-state { text-align: center; padding: 60px 20px; color: #718096; }
        .empty-state i { font-size: 4rem; margin-bottom: 20px; opacity: 0.5; }
        .footer { text-align: center; margin-top: 30px; color: rgba(255, 255, 255, 0.8); font-size: 0.9rem; }
        .action-buttons { display: flex; gap: 10px; margin-top: 15px; }
        .btn {
            padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-family: inherit;
            font-size: 0.9rem; font-weight: 500; transition: all 0.3s ease; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a67d8; transform: translateY(-1px); }
        .btn-secondary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .btn-secondary:hover { background: rgba(102, 126, 234, 0.2); }
        @media (max-width: 768px) {
            .container { padding: 10px; } .header h1 { font-size: 2rem; }
            .stats { flex-direction: column; gap: 10px; }
            .file-details { flex-direction: column; gap: 5px; align-items: flex-end; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-server"></i><?php echo htmlspecialchars($title); ?></h1>
            <p>Professional localhost directory browser</p>
            
            <nav class="breadcrumb-nav">
                <i class="fas fa-home"></i>
                <a href="/" class="breadcrumb-item">Home</a>
                <?php 
                $currentPath = '';
                foreach ($pathParts as $part): 
                    $currentPath .= '/' . $part;
                ?>
                    <span class="breadcrumb-separator">></span>
                    <a href="<?php echo htmlspecialchars($currentPath); ?>/" class="breadcrumb-item">
                        <?php echo htmlspecialchars($part); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            
            <div class="stats">
                <div class="stat"><i class="fas fa-folder"></i><span><?php echo count($directories); ?> folders</span></div>
                <div class="stat"><i class="fas fa-file"></i><span><?php echo count($files); ?> files</span></div>
                <div class="stat"><i class="fas fa-clock"></i><span>Last updated: <?php echo date('M j, Y H:i'); ?></span></div>
            </div>
            
            <div class="action-buttons">
                <a href="/dashboard/" class="btn btn-primary"><i class="fas fa-tachometer-alt"></i>XAMPP Dashboard</a>
                <a href="/phpmyadmin/" class="btn btn-secondary"><i class="fas fa-database"></i>phpMyAdmin</a>
            </div>
        </div>

        <div class="file-browser">
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search files and folders..." id="searchInput">
            </div>

            <?php if (empty($allItems)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No files found</h3>
                    <p>This directory appears to be empty.</p>
                </div>
            <?php else: ?>
                <ul class="file-list">
                    <?php foreach ($allItems as $item): ?>
                        <li class="file-item <?php echo $item['is_dir'] ? 'folder-item' : ''; ?>" 
                            onclick="<?php echo $item['is_dir'] ? "window.location.href='" . htmlspecialchars($item['name']) . "/'" : "window.open('" . htmlspecialchars($item['name']) . "', '_blank')"; ?>">
                            <span class="file-icon"><?php echo $item['icon']; ?></span>
                            <div class="file-info">
                                <span class="file-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <div class="file-details">
                                    <span class="file-size"><?php echo $item['is_dir'] ? '—' : formatBytes($item['size']); ?></span>
                                    <span class="file-date"><?php echo date('M j, Y H:i', $item['modified']); ?></span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>Powered by Professional Localhost Browser • <?php echo date('Y'); ?></p>
        </div>
    </div>

    <script>
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.file-item').forEach(function(item) {
                const fileName = item.querySelector('.file-name').textContent.toLowerCase();
                item.style.display = fileName.includes(searchTerm) ? 'flex' : 'none';
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.file-item').forEach(function(item, index) {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(function() {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === '/') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
        });
    </script>
</body>
</html>
