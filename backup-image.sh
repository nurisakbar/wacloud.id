#!/bin/bash

# ============================================================================
# WAHA Image Backup Script
# ============================================================================
# Script untuk menyimpan (save) Docker image WAHA Plus ke dalam file .tar
# sehingga bisa digunakan kembali meskipun tidak ada koneksi internet
# atau akses ke Docker Hub (offline load).
# ============================================================================

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

IMAGE_NAME="devlikeapro/waha-plus:latest"
BACKUP_FILE="waha-plus-image.tar.gz"

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}WAHA Image Backup (Image to File)${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Check if image exists
if ! docker image inspect "$IMAGE_NAME" > /dev/null 2>&1; then
    echo -e "${RED}❌ Image $IMAGE_NAME tidak ditemukan di sistem ini.${NC}"
    echo -e "${YELLOW}Silakan jalankan ./pull-waha-plus.sh terlebih dahulu.${NC}"
    exit 1
fi

echo -e "${BLUE}ℹ️  Sedang menyimpan image $IMAGE_NAME ke $BACKUP_FILE...${NC}"
echo -e "${YELLOW}Proses ini mungkin memakan waktu beberapa menit (Ukuran ~700MB - 1GB)...${NC}"

# Save and compress image
docker save "$IMAGE_NAME" | gzip > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ Backup image berhasil dibuat: $BACKUP_FILE${NC}"
    echo -e "${GREEN}📦 Ukuran file: $(du -sh $BACKUP_FILE | cut -f1)${NC}"
    echo ""
    echo -e "${BLUE}ℹ️  CARA MENGGUNAKAN (RESTORE):${NC}"
    echo -e "Untuk memuat kembali image ini di mesin lain atau saat offline, gunakan perintah:"
    echo -e "${YELLOW}docker load -i $BACKUP_FILE${NC}"
    echo ""
    echo -e "${BLUE}⚠️  PENTING:${NC}"
    echo -e "File ini hanya berisi 'Software/Mesin' WAHA-nya saja."
    echo -e "Data sesi (WhatsApp login) ada di folder: ${YELLOW}docker-data/${NC}"
    echo -e "Pastikan Anda juga mem-backup folder tersebut secara rutin."
else
    echo -e "${RED}❌ Gagal membuat backup image.${NC}"
    exit 1
fi
