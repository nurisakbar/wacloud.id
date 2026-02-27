#!/bin/bash

# ============================================================================
# WAHA Plus Image Pull Script
# ============================================================================
# Script untuk pull image WAHA Plus dengan login otomatis
# Usage: ./pull-waha-plus.sh
# ============================================================================

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

# Main execution
print_header "WAHA Plus Image Pull"

check_docker

# Load .env file
if [ -f .env ]; then
    # Load variables from .env
    # Only load lines that have = and don't start with #
    export $(grep -v '^#' .env | xargs)
fi

# Docker Hub credentials for WAHA Plus
DOCKER_USERNAME="${WAHA_DOCKER_USERNAME:-devlikeapro}"
DOCKER_PASSWORD="${WAHA_DOCKER_PASSWORD}"
WAHA_IMAGE="${WAHA_IMAGE:-devlikeapro/waha-plus:latest}"

print_info "Step 1/3: Logging in to Docker Hub..."
echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

if [ $? -ne 0 ]; then
    print_error "Docker login failed!"
    exit 1
fi

print_success "Logged in to Docker Hub as $DOCKER_USERNAME"
echo ""

print_info "Step 2/3: Pulling WAHA Plus image..."
print_info "Image: $WAHA_IMAGE"
docker pull --platform linux/amd64 "$WAHA_IMAGE"

if [ $? -ne 0 ]; then
    print_error "Failed to pull WAHA Plus image!"
    print_info "Logging out..."
    docker logout
    exit 1
fi

print_success "Image pulled successfully!"
echo ""

print_info "Step 3/3: Logging out from Docker Hub..."
docker logout

if [ $? -eq 0 ]; then
    print_success "Logged out from Docker Hub"
else
    print_warning "Logout failed (non-critical)"
fi

echo ""
print_success "All done! WAHA Plus image is ready."
echo ""
print_info "Image: $WAHA_IMAGE"
print_info "You can now start WAHA with: ./waha.sh start"
echo ""

