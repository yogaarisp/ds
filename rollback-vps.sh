#!/bin/bash

# ============================================
# Rollback Script untuk VPS Production
# ============================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
VPS_IP="your-vps-ip"  # GANTI dengan IP VPS Anda
VPS_USER="root"       # GANTI jika bukan root
VPS_PATH="/var/www/html/dimsumpos"

# Functions
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Check if VPS_IP is configured
if [ "$VPS_IP" = "your-vps-ip" ]; then
    print_error "Please configure VPS_IP in this script first!"
    echo "Edit rollback-vps.sh and change VPS_IP to your actual VPS IP address"
    exit 1
fi

# ============================================
# STEP 1: List Available Backups
# ============================================
print_header "STEP 1: Available Backups"

print_info "Fetching backup list from VPS..."

backups=$(ssh ${VPS_USER}@${VPS_IP} "ls -1t ${VPS_PATH}/backups/ 2>/dev/null || echo 'No backups found'")

if [ "$backups" = "No backups found" ]; then
    print_error "No backups found on VPS!"
    echo "Cannot rollback without a backup."
    exit 1
fi

echo -e "${GREEN}Available backups:${NC}"
echo "$backups" | nl

# ============================================
# STEP 2: Select Backup
# ============================================
print_header "STEP 2: Select Backup to Restore"

read -p "Enter backup number to restore (or 'q' to quit): " backup_num

if [ "$backup_num" = "q" ]; then
    print_info "Rollback cancelled"
    exit 0
fi

# Get selected backup name
selected_backup=$(echo "$backups" | sed -n "${backup_num}p")

if [ -z "$selected_backup" ]; then
    print_error "Invalid backup number!"
    exit 1
fi

print_warning "You are about to rollback to: $selected_backup"
read -p "Are you sure? This will replace current files! (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    print_info "Rollback cancelled"
    exit 0
fi

# ============================================
# STEP 3: Perform Rollback
# ============================================
print_header "STEP 3: Performing Rollback"

print_info "Connecting to VPS and rolling back..."

ssh ${VPS_USER}@${VPS_IP} << ENDSSH
set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

BACKUP_NAME="$selected_backup"
PROJECT_PATH="${VPS_PATH}"
BACKUP_PATH="\${PROJECT_PATH}/backups/\${BACKUP_NAME}"

echo -e "\${BLUE}Starting rollback on VPS...\${NC}"

# Check if backup exists
if [ ! -d "\$BACKUP_PATH" ]; then
    echo -e "\${RED}Backup not found: \$BACKUP_PATH\${NC}"
    exit 1
fi

# Navigate to project
cd \$PROJECT_PATH

# Enable maintenance mode
echo -e "\${YELLOW}Enabling maintenance mode...\${NC}"
cd backend
php artisan down --message="Rolling back to previous version..." || true
cd ..

# Create backup of current state (before rollback)
CURRENT_BACKUP="before-rollback-\$(date +%Y%m%d_%H%M%S)"
echo -e "\${YELLOW}Backing up current state: \$CURRENT_BACKUP\${NC}"
mkdir -p backups/\$CURRENT_BACKUP
cp -r backend backups/\$CURRENT_BACKUP/ 2>/dev/null || true
cp -r backend/public/assets backups/\$CURRENT_BACKUP/assets 2>/dev/null || true
cp backend/public/index.html backups/\$CURRENT_BACKUP/index.html 2>/dev/null || true

# Restore backend
echo -e "\${YELLOW}Restoring backend from backup...\${NC}"
if [ -d "\$BACKUP_PATH/backend" ]; then
    # Preserve .env
    cp backend/.env /tmp/.env.preserve
    
    # Remove current backend
    rm -rf backend
    
    # Restore from backup
    cp -r \$BACKUP_PATH/backend .
    
    # Restore .env
    cp /tmp/.env.preserve backend/.env
    rm /tmp/.env.preserve
    
    echo -e "\${GREEN}Backend restored\${NC}"
else
    echo -e "\${YELLOW}No backend in backup, skipping...\${NC}"
fi

# Restore frontend
echo -e "\${YELLOW}Restoring frontend from backup...\${NC}"
if [ -d "\$BACKUP_PATH/assets" ]; then
    rm -rf backend/public/assets
    cp -r \$BACKUP_PATH/assets backend/public/assets
    echo -e "\${GREEN}Frontend assets restored\${NC}"
fi

if [ -f "\$BACKUP_PATH/index.html" ]; then
    cp \$BACKUP_PATH/index.html backend/public/index.html
    echo -e "\${GREEN}Frontend index.html restored\${NC}"
fi

# Install composer dependencies
echo -e "\${YELLOW}Installing composer dependencies...\${NC}"
cd backend
composer install --no-dev --optimize-autoloader --no-interaction

# Clear caches
echo -e "\${YELLOW}Clearing caches...\${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize
echo -e "\${YELLOW}Optimizing...\${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
echo -e "\${YELLOW}Setting permissions...\${NC}"
cd ..
sudo chown -R www-data:www-data \$PROJECT_PATH
sudo chmod -R 755 \$PROJECT_PATH
sudo chmod -R 775 \$PROJECT_PATH/backend/storage
sudo chmod -R 775 \$PROJECT_PATH/backend/bootstrap/cache

# Ensure storage link
cd backend
php artisan storage:link 2>/dev/null || true

# Restart services
echo -e "\${YELLOW}Restarting services...\${NC}"
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

# Disable maintenance mode
echo -e "\${YELLOW}Disabling maintenance mode...\${NC}"
php artisan up

echo -e "\${GREEN}✓ Rollback completed successfully!\${NC}"

# Show status
echo -e "\n\${BLUE}Service Status:\${NC}"
sudo systemctl status nginx --no-pager -l | head -3
sudo systemctl status php8.2-fpm --no-pager -l | head -3

echo -e "\n\${BLUE}Recent Laravel Logs:\${NC}"
tail -10 storage/logs/laravel.log 2>/dev/null || echo "No logs yet"

ENDSSH

print_success "Rollback completed!"

# ============================================
# STEP 4: Verification
# ============================================
print_header "STEP 4: Verification"

print_info "Testing website..."
response=$(curl -s -o /dev/null -w "%{http_code}" http://${VPS_IP})

if [ "$response" = "200" ]; then
    print_success "Website is responding (HTTP $response)"
else
    print_warning "Website returned HTTP $response"
fi

# ============================================
# Done!
# ============================================
print_header "Rollback Complete!"

echo -e "${GREEN}✓ Rollback completed successfully!${NC}\n"
echo -e "${BLUE}Next steps:${NC}"
echo "1. Visit your website: http://${VPS_IP}"
echo "2. Test all features"
echo "3. Check for any errors"
echo "4. Monitor logs"
echo ""
echo -e "${YELLOW}To check logs on VPS:${NC}"
echo "ssh ${VPS_USER}@${VPS_IP}"
echo "tail -f ${VPS_PATH}/backend/storage/logs/laravel.log"
echo ""

print_success "Rollback successful! 🔄"
