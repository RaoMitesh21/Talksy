#!/bin/bash

# Talksy Setup Script
echo "🚀 Welcome to Talksy Setup!"
echo "================================"

# Check if we're in the right directory
if [ ! -f "README.md" ]; then
    echo "❌ Please run this script from the Talksy root directory"
    exit 1
fi

echo "📦 Installing Frontend Dependencies..."
cd frontend
npm install

echo "🔧 Starting Backend Server..."
cd ..
php -S localhost:8001 -t public &
BACKEND_PID=$!

echo "⏳ Waiting for backend to start..."
sleep 3

echo "🗄️  Setting up database..."
curl -s http://localhost:8001/setup_database.php > /dev/null

echo "🎯 Starting Frontend Development Server..."
cd frontend
npm start &
FRONTEND_PID=$!

echo "✅ Setup Complete!"
echo ""
echo "🌐 Your Talksy application is now running:"
echo "   Frontend: http://localhost:3000"
echo "   Backend:  http://localhost:8001"
echo ""
echo "📱 To stop the servers, press Ctrl+C"
echo ""

# Wait for user to stop servers
trap "echo '🛑 Stopping servers...'; kill $BACKEND_PID $FRONTEND_PID 2>/dev/null; exit" INT
wait
