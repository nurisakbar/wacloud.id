#!/bin/bash

# Script untuk menjalankan semua komponen (WAHA + Laravel)
# Usage: ./START_ALL.sh

echo "🚀 Starting All Services..."
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check Docker
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: Docker is not running. Please start Docker first.${NC}"
    exit 1
fi

# Navigate to project directory
cd "$(dirname "$0")"

# 1. Start WAHA
echo -e "${YELLOW}📦 Starting WAHA API...${NC}"
if docker compose version &> /dev/null; then
    docker compose up -d waha 2>&1 | grep -v "obsolete" || true
else
    docker-compose up -d waha 2>&1 | grep -v "obsolete" || true
fi

sleep 5

if docker ps | grep -q waha-api; then
    echo -e "${GREEN}✅ WAHA API is running${NC}"
else
    echo -e "${RED}⚠️  WAHA API may still be starting...${NC}"
fi

# 2. Check Laravel
echo ""
echo -e "${YELLOW}🔧 Checking Laravel setup...${NC}"
cd frontend

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${RED}❌ .env file not found. Please run setup first.${NC}"
    exit 1
fi

# Clear caches
/Applications/MAMP/bin/php/php8.3.14/bin/php artisan config:clear > /dev/null 2>&1
/Applications/MAMP/bin/php/php8.3.14/bin/php artisan cache:clear > /dev/null 2>&1

# Check database connection
echo -e "${YELLOW}📊 Checking database connection...${NC}"
if /Applications/MAMP/bin/php/php8.3.14/bin/php artisan migrate:status > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Database connection OK${NC}"
else
    echo -e "${RED}❌ Database connection failed. Check your .env configuration.${NC}"
    exit 1
fi

# 3. Build frontend assets
echo ""
echo -e "${YELLOW}🎨 Building frontend assets...${NC}"
if npm run build > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Frontend assets built${NC}"
else
    echo -e "${YELLOW}⚠️  Frontend build had warnings (may still work)${NC}"
fi

# 4. Start Laravel server
echo ""
echo -e "${YELLOW}🌐 Starting Laravel server...${NC}"

# Check if already running
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    echo -e "${YELLOW}⚠️  Port 8000 already in use. Server may already be running.${NC}"
else
    # Start in background
    nohup /Applications/MAMP/bin/php/php8.3.14/bin/php artisan serve --host=127.0.0.1 --port=8000 > /tmp/laravel-server.log 2>&1 &
    sleep 3
    
    if curl -s http://127.0.0.1:8000 > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Laravel server is running${NC}"
    else
        echo -e "${RED}❌ Laravel server failed to start. Check logs: /tmp/laravel-server.log${NC}"
    fi
fi

# Summary
echo ""
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ All Services Started!${NC}"
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo ""
echo "📍 Access Points:"
echo "   • Laravel App:  http://127.0.0.1:8000"
echo "   • WAHA API:     http://localhost:3000"
echo "   • Swagger UI:   http://localhost:3000/api-docs"
echo ""
echo "📋 Useful Commands:"
echo "   • View WAHA logs:    ./waha-logs.sh"
echo "   • Check WAHA status: ./waha-status.sh"
echo "   • Stop WAHA:         ./waha-stop.sh"
echo "   • Stop Laravel:      pkill -f 'artisan serve'"
echo ""
echo -e "${YELLOW}Note: Laravel server is running in background.${NC}"
echo -e "${YELLOW}To view Laravel logs: tail -f /tmp/laravel-server.log${NC}"

