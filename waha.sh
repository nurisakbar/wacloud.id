#!/bin/bash

# ============================================================================
# WAHA Management Script - All-in-One
# ============================================================================
# Script untuk mengelola semua operasi WAHA dalam satu file
# Usage: ./waha.sh [command] [options]
#
# Commands:
#   setup       - Setup environment (generate .env if not exists)
#   start       - Start WAHA container
#   stop        - Stop WAHA container
#   restart     - Restart WAHA container
#   status      - Check WAHA status
#   logs        - View WAHA logs (use -f to follow)
#   backup      - Backup WAHA sessions
#   restore     - Restore WAHA sessions from backup
#   pull        - Pull WAHA Plus image (with auto login/logout)
#   update      - Update WAHA to latest version
#   shell       - Open shell in WAHA container
#   help        - Show this help message
# ============================================================================

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Configuration
ENV_FILE="$SCRIPT_DIR/.env"
ENV_EXAMPLE="$SCRIPT_DIR/docker.env.example"
COMPOSE_FILE="$SCRIPT_DIR/docker-compose.yml"
SESSION_DIR="$SCRIPT_DIR/docker-data/waha-sessions"
BACKUP_DIR="$SCRIPT_DIR/backups"
CONTAINER_NAME="waha-plus"

# Docker compose command
if docker compose version &> /dev/null 2>&1; then
    DOCKER_COMPOSE="docker compose"
else
    DOCKER_COMPOSE="docker-compose"
fi

# ============================================================================
# Helper Functions
# ============================================================================

print_header() {
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

check_docker() {
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker first."
        exit 1
    fi
}

check_env() {
    if [ ! -f "$ENV_FILE" ]; then
        print_warning ".env file not found. Running setup..."
        setup_env
    fi
}

# ============================================================================
# Setup Functions
# ============================================================================

setup_env() {
    print_header "WAHA Environment Setup"
    
    if [ -f "$ENV_FILE" ]; then
        print_warning ".env file already exists!"
        read -p "Do you want to overwrite it? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_info "Setup cancelled."
            return
        fi
    fi
    
    # Create .env from template or generate defaults
    if [ -f "$ENV_EXAMPLE" ]; then
        cp "$ENV_EXAMPLE" "$ENV_FILE"
        print_success "Created .env from template"
    else
        # Generate default .env
        cat > "$ENV_FILE" <<EOF
# WAHA API Configuration
# Image: Using WAHA PLUS (paid version) - requires docker login
# For free version, change to: WAHA_IMAGE=devlikeapro/waha:latest
# IMPORTANT: Run 'docker login' before starting if using waha-plus
WAHA_IMAGE=devlikeapro/waha-plus:latest
WAHA_PORT=3000
WAHA_SWAGGER_USERNAME=admin
WAHA_SWAGGER_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
WAHA_DASHBOARD_USERNAME=admin
WAHA_DASHBOARD_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
WAHA_API_KEY=$(openssl rand -hex 16)
WAHA_LOG_LEVEL=info
WAHA_SWAGGER_ENABLED=true
WHATSAPP_DEFAULT_ENGINE=NOWEB

# Redis Configuration
REDIS_PORT=6379
REDIS_PASSWORD=

# Timezone
TZ=Asia/Jakarta
EOF
        print_success "Generated .env with secure random passwords"
    fi
    
    # Generate secure passwords if using template
    if grep -q "change_me" "$ENV_FILE" 2>/dev/null; then
        print_info "Generating secure passwords..."
        
        WAHA_SWAGGER_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        WAHA_DASHBOARD_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        WAHA_API_KEY=$(openssl rand -hex 16)
        
        sed -i.bak "s/WAHA_SWAGGER_PASSWORD=.*/WAHA_SWAGGER_PASSWORD=$WAHA_SWAGGER_PASS/" "$ENV_FILE"
        sed -i.bak "s/WAHA_DASHBOARD_PASSWORD=.*/WAHA_DASHBOARD_PASSWORD=$WAHA_DASHBOARD_PASS/" "$ENV_FILE"
        sed -i.bak "s/WAHA_API_KEY=.*/WAHA_API_KEY=$WAHA_API_KEY/" "$ENV_FILE"
        
        rm -f "$ENV_FILE.bak"
        print_success "Secure passwords generated"
    fi
    
    # Create necessary directories
    mkdir -p "$SESSION_DIR"
    mkdir -p "$BACKUP_DIR"
    
    # Check if using waha-plus and remind about docker login
    WAHA_IMAGE=$(grep WAHA_IMAGE "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "")
    if echo "$WAHA_IMAGE" | grep -q "waha-plus"; then
        print_warning "Using WAHA PLUS (paid version)"
        echo ""
        print_info "IMPORTANT: You need to login to Docker Hub first:"
        echo "  docker login"
        echo ""
        print_info "Then run: ./waha.sh start"
    else
        print_success "Setup completed!"
        echo ""
        print_info "Next steps:"
        echo "  1. Review $ENV_FILE and adjust if needed"
        echo "  2. Run: ./waha.sh start"
    fi
    echo ""
}

# ============================================================================
# Container Management Functions
# ============================================================================

cmd_start() {
    print_header "Starting WAHA"
    
    check_docker
    check_env
    
    # Check if using waha-plus and pull image if needed
    WAHA_IMAGE=$(grep WAHA_IMAGE "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "devlikeapro/waha-plus:latest")
    if echo "$WAHA_IMAGE" | grep -q "waha-plus"; then
        # Check if image exists locally
        if ! docker images --format "{{.Repository}}:{{.Tag}}" | grep -q "^${WAHA_IMAGE}$"; then
            print_warning "WAHA Plus image not found locally"
            echo ""
            print_info "Pulling WAHA Plus image with automatic login..."
            cmd_pull
            echo ""
        fi
    fi
    
    # Check if container already exists
    if docker ps -a | grep -q "$CONTAINER_NAME"; then
        if docker ps | grep -q "$CONTAINER_NAME"; then
            print_warning "WAHA is already running"
            return 0
        else
            print_info "Container exists but is stopped. Starting existing container..."
            docker start "$CONTAINER_NAME"
        fi
    else
        print_info "Starting WAHA container..."
        START_SUCCESS=false
        
        if ! $DOCKER_COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d waha 2>&1 | tee /tmp/waha-start.log; then
            # Check if error is about pull access denied
            if grep -q "pull access denied\|repository does not exist\|may require 'docker login'" /tmp/waha-start.log 2>/dev/null; then
                echo ""
                print_error "Docker image pull failed!"
                echo ""
                WAHA_IMAGE=$(grep WAHA_IMAGE "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "devlikeapro/waha-plus:latest")
                if echo "$WAHA_IMAGE" | grep -q "waha-plus"; then
                    print_warning "You're trying to use WAHA PLUS (paid version)"
                    echo ""
                    print_info "Attempting to pull with automatic login..."
                    cmd_pull
                    echo ""
                    print_info "Retrying container start..."
                    if $DOCKER_COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d waha 2>&1 | tee /tmp/waha-start-retry.log; then
                        START_SUCCESS=true
                    else
                        print_error "Still failed after pull. Check logs: ./waha.sh logs"
                        rm -f /tmp/waha-start.log /tmp/waha-start-retry.log
                        exit 1
                    fi
                    rm -f /tmp/waha-start.log /tmp/waha-start-retry.log
                else
                    print_info "Trying to pull image: $WAHA_IMAGE"
                    print_info "If this image doesn't exist, check:"
                    echo "  - Image name in .env file"
                    echo "  - Internet connection"
                    echo "  - Docker Hub access"
                    rm -f /tmp/waha-start.log
                    exit 1
                fi
            else
                print_error "Failed to start WAHA"
                print_info "Check logs: ./waha.sh logs"
                rm -f /tmp/waha-start.log
                exit 1
            fi
        else
            START_SUCCESS=true
            rm -f /tmp/waha-start.log
        fi
        
        if [ "$START_SUCCESS" = false ]; then
            print_error "Failed to start WAHA"
            print_info "Check logs: ./waha.sh logs"
            exit 1
        fi
    fi
    
    sleep 3
    
    if docker ps | grep -q "$CONTAINER_NAME"; then
        print_success "WAHA is running!"
        echo ""
        WAHA_PORT=$(grep WAHA_PORT "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "3000")
        print_info "📍 API URL: http://localhost:${WAHA_PORT}"
        print_info "📚 Swagger UI: http://localhost:${WAHA_PORT}/api-docs"
        echo ""
        print_info "View logs: ./waha.sh logs"
        print_info "Stop: ./waha.sh stop"
    else
        print_error "WAHA failed to start"
        print_info "Check logs: ./waha.sh logs"
        exit 1
    fi
}

cmd_stop() {
    print_header "Stopping WAHA"
    
    check_docker
    
    print_info "Stopping WAHA container..."
    $DOCKER_COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" stop waha
    
    print_success "WAHA stopped"
}

cmd_restart() {
    print_header "Restarting WAHA"
    
    check_docker
    check_env
    
    print_info "Restarting WAHA container..."
    $DOCKER_COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" restart waha
    
    sleep 2
    
    if docker ps | grep -q "$CONTAINER_NAME"; then
        print_success "WAHA restarted successfully!"
        WAHA_PORT=$(grep WAHA_PORT "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "3000")
        print_info "📍 API URL: http://localhost:${WAHA_PORT}"
    else
        print_error "WAHA failed to restart"
        print_info "Check logs: ./waha.sh logs"
        exit 1
    fi
}

cmd_recreate() {
    print_header "Recreating WAHA Container"
    
    check_docker
    check_env
    
    print_warning "This will stop and remove the container, then create a new one"
    read -p "Continue? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "Cancelled"
        return 0
    fi
    
    # Check if using waha-plus and pull image if needed
    WAHA_IMAGE=$(grep WAHA_IMAGE "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "devlikeapro/waha-plus:latest")
    if echo "$WAHA_IMAGE" | grep -q "waha-plus"; then
        # Check if image exists locally
        if ! docker images --format "{{.Repository}}:{{.Tag}}" | grep -q "^${WAHA_IMAGE}$"; then
            print_warning "WAHA Plus image not found locally"
            echo ""
            print_info "Pulling WAHA Plus image with automatic login..."
            cmd_pull
            echo ""
        fi
    fi
    
    print_info "Stopping and removing WAHA container..."
    $DOCKER_COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" stop waha 2>/dev/null
    $DOCKER_COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" rm -f waha 2>/dev/null
    
    # Also remove container manually if exists
    if docker ps -a | grep -q "$CONTAINER_NAME"; then
        docker rm -f "$CONTAINER_NAME" 2>/dev/null
    fi
    
    print_info "Creating new WAHA container..."
    $DOCKER_COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d waha
    
    sleep 3
    
    if docker ps | grep -q "$CONTAINER_NAME"; then
        print_success "WAHA container recreated successfully!"
        echo ""
        WAHA_PORT=$(grep WAHA_PORT "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "3000")
        print_info "📍 API URL: http://localhost:${WAHA_PORT}"
        print_info "📚 Swagger UI: http://localhost:${WAHA_PORT}/api-docs"
    else
        print_error "WAHA failed to recreate"
        print_info "Check logs: ./waha.sh logs"
        exit 1
    fi
}

cmd_status() {
    print_header "WAHA Status"
    
    if docker ps -a | grep -q "$CONTAINER_NAME"; then
        if docker ps | grep -q "$CONTAINER_NAME"; then
            print_success "Status: Running"
            echo ""
            echo "Container Info:"
            docker ps --filter "name=$CONTAINER_NAME" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
            echo ""
            
            # Health check
            WAHA_PORT=$(grep WAHA_PORT "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "3000")
            if curl -s "http://localhost:${WAHA_PORT}/api/health" > /dev/null 2>&1; then
                print_success "API is responding"
                print_info "📍 API URL: http://localhost:${WAHA_PORT}"
                print_info "📚 Swagger UI: http://localhost:${WAHA_PORT}/api-docs"
            else
                print_warning "API is not responding (container may still be starting)"
            fi
            
            # Session info
            if [ -d "$SESSION_DIR" ]; then
                SESSION_COUNT=$(find "$SESSION_DIR" -type f 2>/dev/null | wc -l | tr -d ' ')
                if [ "$SESSION_COUNT" -gt 0 ]; then
                    echo ""
                    print_info "Sessions: $SESSION_COUNT files"
                    SESSION_SIZE=$(du -sh "$SESSION_DIR" 2>/dev/null | cut -f1)
                    print_info "Size: $SESSION_SIZE"
                fi
            fi
        else
            print_warning "Status: Stopped"
            echo ""
            print_info "To start: ./waha.sh start"
        fi
    else
        print_error "Status: Container not found"
        echo ""
        print_info "To create and start: ./waha.sh start"
    fi
}

cmd_logs() {
    print_header "WAHA Logs"
    
    if [ "$1" == "-f" ] || [ "$1" == "--follow" ]; then
        print_info "Following logs (Press Ctrl+C to exit)..."
        docker logs -f "$CONTAINER_NAME"
    else
        docker logs --tail 100 "$CONTAINER_NAME"
    fi
}

# ============================================================================
# Backup & Restore Functions
# ============================================================================

cmd_backup() {
    print_header "WAHA Sessions Backup"
    
    BACKUP_DIR_CUSTOM=${1:-$BACKUP_DIR}
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_FILE="$BACKUP_DIR_CUSTOM/waha-sessions-$TIMESTAMP.tar.gz"
    
    if [ ! -d "$SESSION_DIR" ]; then
        print_error "Session directory not found: $SESSION_DIR"
        exit 1
    fi
    
    if [ -z "$(ls -A $SESSION_DIR 2>/dev/null)" ]; then
        print_warning "No sessions found to backup"
        return 0
    fi
    
    mkdir -p "$BACKUP_DIR_CUSTOM"
    
    print_info "Creating backup..."
    tar -czf "$BACKUP_FILE" -C "$(dirname $SESSION_DIR)" "$(basename $SESSION_DIR)" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        print_success "Backup created successfully!"
        echo "   File: $BACKUP_FILE"
        echo "   Size: $BACKUP_SIZE"
        
        echo ""
        print_info "Recent backups:"
        ls -lh "$BACKUP_DIR_CUSTOM"/waha-sessions-*.tar.gz 2>/dev/null | tail -5 | awk '{print "   "$9" ("$5")"}' || print_warning "No previous backups found"
    else
        print_error "Backup failed!"
        exit 1
    fi
}

cmd_restore() {
    print_header "WAHA Sessions Restore"
    
    BACKUP_FILE=$1
    
    if [ -z "$BACKUP_FILE" ]; then
        print_error "Backup file not specified"
        echo ""
        print_info "Usage: ./waha.sh restore <backup_file>"
        echo ""
        print_info "Available backups:"
        ls -lh "$BACKUP_DIR"/waha-sessions-*.tar.gz 2>/dev/null | awk '{print "   "$9" ("$5")"}' || print_warning "No backups found"
        exit 1
    fi
    
    if [ ! -f "$BACKUP_FILE" ]; then
        print_error "Backup file not found: $BACKUP_FILE"
        exit 1
    fi
    
    if docker ps | grep -q "$CONTAINER_NAME"; then
        print_warning "WAHA container is running"
        read -p "Do you want to stop it before restore? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            print_info "Stopping WAHA container..."
            $DOCKER_COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" stop waha
        fi
    fi
    
    mkdir -p "$SESSION_DIR"
    
    if [ -d "$SESSION_DIR" ] && [ -n "$(ls -A $SESSION_DIR 2>/dev/null)" ]; then
        CURRENT_BACKUP="$SESSION_DIR-backup-$(date +%Y%m%d_%H%M%S).tar.gz"
        print_info "Backing up current sessions..."
        tar -czf "$CURRENT_BACKUP" -C "$(dirname $SESSION_DIR)" "$(basename $SESSION_DIR)" 2>/dev/null
        print_success "Current sessions backed up to: $CURRENT_BACKUP"
    fi
    
    print_info "Restoring from backup..."
    tar -xzf "$BACKUP_FILE" -C "$(dirname $SESSION_DIR)" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        print_success "Restore completed successfully!"
        echo ""
        print_info "Next steps:"
        echo "  1. Start WAHA: ./waha.sh start"
        echo "  2. Check sessions: docker exec $CONTAINER_NAME ls -la /app/.sessions"
    else
        print_error "Restore failed!"
        exit 1
    fi
}

# ============================================================================
# Other Functions
# ============================================================================

cmd_pull() {
    print_header "Pulling WAHA Plus Image"
    
    check_docker
    
    # Load .env file if available
    if [ -f "$ENV_FILE" ]; then
        export $(grep -v '^#' "$ENV_FILE" | xargs)
    fi
    
    # Docker Hub credentials for WAHA Plus
    DOCKER_USERNAME="${WAHA_DOCKER_USERNAME:-devlikeapro}"
    DOCKER_PASSWORD="${WAHA_DOCKER_PASSWORD}"
    WAHA_IMAGE="${WAHA_IMAGE:-devlikeapro/waha-plus:latest}"
    
    print_info "Logging in to Docker Hub..."
    echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin
    
    if [ $? -ne 0 ]; then
        print_error "Docker login failed!"
        exit 1
    fi
    
    print_success "Logged in to Docker Hub"
    echo ""
    
    print_info "Pulling WAHA Plus image: $WAHA_IMAGE"
    docker pull --platform linux/amd64 "$WAHA_IMAGE"
    
    if [ $? -ne 0 ]; then
        print_error "Failed to pull WAHA Plus image!"
        docker logout
        exit 1
    fi
    
    print_success "Image pulled successfully!"
    echo ""
    
    print_info "Logging out from Docker Hub..."
    docker logout
    
    if [ $? -eq 0 ]; then
        print_success "Logged out from Docker Hub"
    else
        print_warning "Logout failed (non-critical)"
    fi
    
    echo ""
    print_info "Image ready: $WAHA_IMAGE"
    print_info "You can now start WAHA with: ./waha.sh start"
}

cmd_update() {
    print_header "Updating WAHA"
    
    check_docker
    check_env
    
    # Check if using waha-plus
    WAHA_IMAGE=$(grep WAHA_IMAGE "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "devlikeapro/waha-plus:latest")
    if echo "$WAHA_IMAGE" | grep -q "waha-plus"; then
        print_info "Using WAHA PLUS - pulling with automatic login..."
        cmd_pull
    else
        print_info "Pulling latest WAHA image..."
        $DOCKER_COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" pull waha
    fi
    
    print_info "Restarting with new image..."
    $DOCKER_COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d waha
    
    print_success "WAHA updated successfully!"
}

cmd_shell() {
    print_header "WAHA Shell"
    
    if ! docker ps | grep -q "$CONTAINER_NAME"; then
        print_error "WAHA container is not running"
        exit 1
    fi
    
    print_info "Opening shell in WAHA container..."
    docker exec -it "$CONTAINER_NAME" /bin/sh
}

cmd_help() {
    print_header "WAHA Management Script - Help"
    
    echo "Usage: ./waha.sh [command] [options]"
    echo ""
    echo "Commands:"
    echo "  setup              Setup environment (generate .env if not exists)"
    echo "  start              Start WAHA container"
    echo "  stop               Stop WAHA container"
    echo "  restart            Restart WAHA container"
    echo "  recreate           Stop, remove, and create new WAHA container"
    echo "  status             Check WAHA status and health"
    echo "  logs [-f]          View WAHA logs (use -f to follow)"
    echo "  backup [dir]       Backup WAHA sessions (default: ./backups)"
    echo "  restore <file>    Restore WAHA sessions from backup"
    echo "  pull               Pull WAHA Plus image (with auto login/logout)"
    echo "  update             Update WAHA to latest version"
    echo "  shell              Open shell in WAHA container"
    echo "  help               Show this help message"
    echo ""
    echo "Examples:"
    echo "  ./waha.sh setup              # First time setup"
    echo "  ./waha.sh start               # Start WAHA"
    echo "  ./waha.sh logs -f             # Follow logs"
    echo "  ./waha.sh backup              # Backup sessions"
    echo "  ./waha.sh restore ./backups/waha-sessions-20250127_120000.tar.gz"
    echo ""
}

# ============================================================================
# Main
# ============================================================================

COMMAND=${1:-help}

case "$COMMAND" in
    setup)
        setup_env
        ;;
    start)
        cmd_start
        ;;
    stop)
        cmd_stop
        ;;
    restart)
        cmd_restart
        ;;
    recreate)
        cmd_recreate
        ;;
    status)
        cmd_status
        ;;
    logs)
        cmd_logs "$2"
        ;;
    backup)
        cmd_backup "$2"
        ;;
    restore)
        cmd_restore "$2"
        ;;
    pull)
        cmd_pull
        ;;
    update)
        cmd_update
        ;;
    shell)
        cmd_shell
        ;;
    help|--help|-h)
        cmd_help
        ;;
    *)
        print_error "Unknown command: $COMMAND"
        echo ""
        cmd_help
        exit 1
        ;;
esac

