#!/bin/bash

# ============================================
# Deploy Script untuk VPS Production
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
PROJECT_NAME="dimsumpos"

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
    echo "Edit deploy-to-vps.sh and change VPS_IP to your actual VPS IP address"
    exit 1
fi

# ============================================
# STEP 1: Pre-deployment Checks
# ============================================
print_header "STEP 1: Pre-deployment Checks"

# Check if git is clean
if [[ -n $(git status -s) ]]; then
    print_warning "You have uncommitted changes!"
    read -p "Do you want to commit them now? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git add -A
        read -p "Enter commit message: " commit_msg
        git commit -m "$commit_msg"
        git push origin main
        print_success "Changes committed and pushed"
    else
        print_error "Please commit your changes first"
        exit 1
    fi
else
    print_success "Git repository is clean"
fi

# Check if on main branch
current_branch=$(git branch --show-current)
if [ "$current_branch" != "main" ]; then
    print_warning "You are on branch '$current_branch', not 'main'"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# ============================================
# STEP 2: Build Frontend
# ============================================
print_header "STEP 2: Building Frontend"

cd frontend

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    print_info "Installing npm dependencies..."
    npm install
fi

print_info "Building frontend for production..."
npm run build

if [ ! -d "dist" ]; then
    print_error "Build failed! dist folder not found"
    exit 1
fi

print_success "Frontend built successfully"
cd ..

# ============================================
# STEP 3: Create Archives
# ============================================
print_header "STEP 3: Creating Archives"

# Clean old archives
rm -f frontend-build.tar.gz backend-files.tar.gz

# Create frontend archive
print_info "Compressing frontend build..."
tar -czf frontend-build.tar.gz -C frontend/dist .
print_success "Frontend archive created: frontend-build.tar.gz"

# Create backend archive (exclude vendor, node_modules, logs, .env)
print_info "Compressing backend files..."
tar -czf backend-files.tar.gz \
  --exclude='backend/vendor' \
  --exclude='backend/node_modules' \
  --exclude='backend/storage/logs/*' \
  --exclude='backend/storage/framework/cache/*' \
  --exclude='backend/storage/framework/sessions/*' \
  --exclude='backend/storage/framework/views/*' \
  --exclude='backend/.env' \
  backend/
print_success "Backend archive created: backend-files.tar.gz"

# ============================================
# STEP 4: Upload to VPS
# ============================================
print_header "STEP 4: Uploading to VPS"

print_info "Uploading frontend build..."
scp frontend-build.tar.gz ${VPS_USER}@${VPS_IP}:/tmp/
print_success "Frontend uploaded"

print_info "Uploading backend files..."
scp backend-files.tar.gz ${VPS_USER}@${VPS_IP}:/tmp/
print_success "Backend uploaded"

# ============================================
# STEP 5: Deploy on VPS
# ============================================
print_header "STEP 5: Deploying on VPS"

print_info "Connecting to VPS and deploying..."

ssh ${VPS_USER}@${VPS_IP} << 'ENDSSH'
set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Starting deployment on VPS...${NC}"

# Navigate to project
cd /var/www/html/dimsumpos

# Enable maintenance mode
echo -e "${YELLOW}Enabling maintenance mode...${NC}"
cd backend
php artisan down --message="Updating system, please wait..." || true
cd ..

# Backup current version
BACKUP_DIR="backup-$(date +%Y%m%d_%H%M%S)"
echo -e "${YELLOW}Creating backup: $BACKUP_DIR${NC}"
mkdir -p backups/$BACKUP_DIR
cp -r backend backups/$BACKUP_DIR/ 2>/dev/null || true
cp -r backend/public/assets backups/$BACKUP_DIR/assets 2>/dev/null || true
cp backend/public/index.html backups/$BACKUP_DIR/index.html 2>/dev/null || true

# Extract backend
echo -e "${YELLOW}Extracting backend files...${NC}"
tar -xzf /tmp/backend-files.tar.gz -C /var/www/html/dimsumpos/

# Install composer dependencies
echo -e "${YELLOW}Installing composer dependencies...${NC}"
cd backend
composer install --no-dev --optimize-autoloader --no-interaction

# Run migrations
echo -e "${YELLOW}Running database migrations...${NC}"
php artisan migrate --force

# Clear and cache
echo -e "${YELLOW}Clearing caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo -e "${YELLOW}Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Extract frontend
echo -e "${YELLOW}Extracting frontend build...${NC}"
cd ..
rm -rf backend/public/assets 2>/dev/null || true
rm -f backend/public/index.html 2>/dev/null || true
tar -xzf /tmp/frontend-build.tar.gz -C backend/public/

# Fix permissions
echo -e "${YELLOW}Setting permissions...${NC}"
sudo chown -R www-data:www-data /var/www/html/dimsumpos
sudo chmod -R 755 /var/www/html/dimsumpos
sudo chmod -R 775 /var/www/html/dimsumpos/backend/storage
sudo chmod -R 775 /var/www/html/dimsumpos/backend/bootstrap/cache

# Ensure storage link exists
cd backend
php artisan storage:link 2>/dev/null || true

# Restart services
echo -e "${YELLOW}Restarting services...${NC}"
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

# Disable maintenance mode
echo -e "${YELLOW}Disabling maintenance mode...${NC}"
php artisan up

# Clean up
echo -e "${YELLOW}Cleaning up...${NC}"
rm -f /tmp/frontend-build.tar.gz
rm -f /tmp/backend-files.tar.gz

echo -e "${GREEN}✓ Deployment completed successfully!${NC}"

# Show status
echo -e "\n${BLUE}Service Status:${NC}"
sudo systemctl status nginx --no-pager -l | head -3
sudo systemctl status php8.2-fpm --no-pager -l | head -3

echo -e "\n${BLUE}Recent Laravel Logs:${NC}"
tail -10 storage/logs/laravel.log 2>/dev/null || echo "No logs yet"

ENDSSH

print_success "Deployment completed!"

# ============================================
# STEP 6: Cleanup Local Files
# ============================================
print_header "STEP 6: Cleanup"

print_info "Removing local archives..."
rm -f frontend-build.tar.gz backend-files.tar.gz
print_success "Cleanup completed"

# ============================================
# STEP 7: Verification
# ============================================
print_header "STEP 7: Verification"

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
print_header "Deployment Complete!"

echo -e "${GREEN}✓ All steps completed successfully!${NC}\n"
echo -e "${BLUE}Next steps:${NC}"
echo "1. Visit your website: http://${VPS_IP}"
echo "2. Test all features"
echo "3. Check for any errors"
echo "4. Monitor logs for 24 hours"
echo ""
echo -e "${YELLOW}To check logs on VPS:${NC}"
echo "ssh ${VPS_USER}@${VPS_IP}"
echo "tail -f ${VPS_PATH}/backend/storage/logs/laravel.log"
echo ""
echo -e "${YELLOW}To rollback if needed:${NC}"
echo "ssh ${VPS_USER}@${VPS_IP}"
echo "cd ${VPS_PATH}"
echo "ls -la backups/"
echo "# Then restore from backup folder"
echo ""

print_success "Happy deploying! 🚀"
