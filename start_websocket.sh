#!/bin/bash

# Start Talksy WebSocket Server
echo "Starting Talksy WebSocket Server..."

# Navigate to the project directory
cd "$(dirname "$0")"

# Fix file permissions first
echo "🔧 Fixing file permissions..."
./fix_permissions.sh

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install
fi

# Start the WebSocket server
php websocket_server.php
