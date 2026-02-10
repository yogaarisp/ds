#!/bin/bash

# ============================================
# Prepare Files for Manual Upload
# ============================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

# ============================================
# STEP 1: Check Git Status
# ============================================
print_header "STEP 1: Checking Git Status"

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
        print_warning "Continuing with uncommitted changes..."
    fi
else
    print_success "Git repository is clean"
fi

# ============================================
# STEP 2: Build Frontend
# ============================================
print_header "STEP 2: Building Frontend"

cd frontend

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
# STEP 3: Create Upload Folder
# ============================================
print_header "STEP 3: Preparing Upload Folder"

# Create upload directory
UPLOAD_DIR="upload-to-vps"
rm -rf $UPLOAD_DIR
mkdir -p $UPLOAD_DIR

print_info "Creating upload folder: $UPLOAD_DIR"

# ============================================
# STEP 4: Copy Backend Files
# ============================================
print_header "STEP 4: Copying Backend Files"

print_info "Copying backend files (excluding vendor, node_modules, logs, .env)..."

# Create backend structure
mkdir -p $UPLOAD_DIR/backend

# Copy important directories
cp -r backend/app $UPLOAD_DIR/backend/
cp -r backend/bootstrap $UPLOAD_DIR/backend/
cp -r backend/config $UPLOAD_DIR/backend/
cp -r backend/database $UPLOAD_DIR/backend/
cp -r backend/resources $UPLOAD_DIR/backend/
cp -r backend/routes $UPLOAD_DIR/backend/

# Copy public (without old assets)
mkdir -p $UPLOAD_DIR/backend/public
cp backend/public/.htaccess $UPLOAD_DIR/backend/public/ 2>/dev/null || true
cp backend/public/index.php $UPLOAD_DIR/backend/public/ 2>/dev/null || true
cp backend/public/robots.txt $UPLOAD_DIR/backend/public/ 2>/dev/null || true

# Copy storage structure (without logs)
mkdir -p $UPLOAD_DIR/backend/storage/app/public
mkdir -p $UPLOAD_DIR/backend/storage/framework/cache
mkdir -p $UPLOAD_DIR/backend/storage/framework/sessions
mkdir -p $UPLOAD_DIR/backend/storage/framework/views
mkdir -p $UPLOAD_DIR/backend/storage/logs
touch $UPLOAD_DIR/backend/storage/logs/.gitkeep

# Copy root files
cp backend/artisan $UPLOAD_DIR/backend/
cp backend/composer.json $UPLOAD_DIR/backend/
cp backend/composer.lock $UPLOAD_DIR/backend/

# Copy .env.example as reference
cp backend/.env.example $UPLOAD_DIR/backend/.env.example

print_success "Backend files copied"

# ============================================
# STEP 5: Copy Frontend Build
# ============================================
print_header "STEP 5: Copying Frontend Build"

print_info "Copying frontend build to backend/public..."

# Copy all frontend build files to backend/public
cp -r frontend/dist/* $UPLOAD_DIR/backend/public/

print_success "Frontend build copied"

# ============================================
# STEP 6: Create ZIP Archives
# ============================================
print_header "STEP 6: Creating ZIP Archives"

print_info "Creating backend.zip..."
cd $UPLOAD_DIR
zip -r ../backend-upload.zip backend/ -q
cd ..
print_success "Created: backend-upload.zip"

print_info "Creating frontend-build.zip (from dist)..."
cd frontend/dist
zip -r ../../frontend-build-upload.zip . -q
cd ../..
print_success "Created: frontend-build-upload.zip"

# ============================================
# STEP 7: Create Instructions File
# ============================================
print_header "STEP 7: Creating Instructions"

cat > $UPLOAD_DIR/UPLOAD_INSTRUCTIONS.txt << 'EOF'
╔════════════════════════════════════════════════════════════════╗
║           UPLOAD INSTRUCTIONS - READ CAREFULLY                 ║
╚════════════════════════════════════════════════════════════════╝

📦 FILES PREPARED FOR UPLOAD
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

You have 2 options:

OPTION 1: Upload Folder (Recommended for FileZilla/WinSCP)
   → Upload entire "backend" folder

OPTION 2: Upload ZIP (Recommended for cPanel/Plesk)
   → Upload "backend-upload.zip" (in parent folder)
   → Extract on server

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 STEP-BY-STEP UPLOAD GUIDE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 1: BACKUP CURRENT FILES ON SERVER
   ⚠️  IMPORTANT: Backup before upload!
   
   Via FileZilla/WinSCP:
   - Navigate to: /var/www/html/dimsumpos/
   - Right-click "backend" folder → Rename → "backend-backup-20260210"
   
   Via cPanel File Manager:
   - Navigate to: public_html/dimsumpos/
   - Right-click "backend" → Rename → "backend-backup-20260210"

STEP 2: UPLOAD BACKEND FILES
   
   Via FileZilla/WinSCP:
   - Drag & drop "backend" folder to: /var/www/html/dimsumpos/
   - Wait for upload to complete
   
   Via cPanel File Manager:
   - Click "Upload" button
   - Upload "backend-upload.zip" (from parent folder)
   - Right-click ZIP → Extract
   - Delete ZIP after extraction

STEP 3: RESTORE .ENV FILE
   ⚠️  IMPORTANT: Don't overwrite .env!
   
   - Copy .env from backup folder:
     /var/www/html/dimsumpos/backend-backup-20260210/.env
   - Paste to:
     /var/www/html/dimsumpos/backend/.env

STEP 4: SET PERMISSIONS
   
   Via FileZilla/WinSCP:
   - Right-click "backend/storage" → File Permissions → 775
   - ✅ Check "Recurse into subdirectories"
   - Right-click "backend/bootstrap/cache" → File Permissions → 775
   
   Via cPanel File Manager:
   - Right-click "backend/storage" → Change Permissions → 775
   - ✅ Check "Recurse into subdirectories"
   - Right-click "backend/bootstrap/cache" → Change Permissions → 775

STEP 5: RUN COMMANDS ON SERVER
   ⚠️  REQUIRED: Must run these commands!
   
   Via SSH:
   ```
   ssh root@your-vps-ip
   cd /var/www/html/dimsumpos/backend
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan storage:link
   sudo systemctl restart php8.2-fpm nginx
   ```
   
   Via cPanel Terminal:
   - Open "Terminal" in cPanel
   - Run commands above (without sudo)
   
   Via Hosting Support:
   - Email support and ask them to run commands above

STEP 6: VERIFY DEPLOYMENT
   
   ✅ Check website: http://your-domain.com
   ✅ Test login
   ✅ Test checkout
   ✅ Check images loading
   ✅ Check for errors

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📁 FOLDER STRUCTURE ON SERVER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/var/www/html/dimsumpos/
├── backend/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   │   ├── assets/        ← Frontend build
│   │   ├── index.html     ← Frontend build
│   │   └── storage/       ← Symlink
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/            ← Will be created by composer
│   ├── .env               ← MUST restore from backup!
│   ├── artisan
│   ├── composer.json
│   └── composer.lock
└── backend-backup-20260210/  ← Your backup

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⚠️  IMPORTANT NOTES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. DO NOT upload "vendor" folder (will be created by composer)
2. DO NOT overwrite ".env" file (restore from backup)
3. DO NOT skip "composer install" (required!)
4. DO NOT skip "php artisan migrate" (required!)
5. DO set correct permissions (775 for storage)
6. DO restart PHP-FPM and Nginx after upload

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🆘 TROUBLESHOOTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Problem: Website shows 500 error
Solution: Check Laravel logs at backend/storage/logs/laravel.log

Problem: Images not loading
Solution: Run "php artisan storage:link" and check permissions

Problem: Composer install fails
Solution: Check PHP version (need 8.1+) and memory limit

Problem: Permission denied
Solution: Set folder permissions to 775 for storage and cache

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📞 NEED HELP?
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Read full guide: VPS_DEPLOY_MANUAL_UPLOAD.md

Good luck! 🚀

EOF

print_success "Instructions created: $UPLOAD_DIR/UPLOAD_INSTRUCTIONS.txt"

# ============================================
# STEP 8: Create Summary
# ============================================
print_header "Preparation Complete!"

echo -e "${GREEN}✓ All files prepared for manual upload!${NC}\n"

echo -e "${BLUE}Files created:${NC}"
echo "  📁 $UPLOAD_DIR/backend/          - Backend files (ready to upload)"
echo "  📦 backend-upload.zip            - Backend ZIP (alternative)"
echo "  📦 frontend-build-upload.zip     - Frontend build ZIP"
echo "  📄 $UPLOAD_DIR/UPLOAD_INSTRUCTIONS.txt - Step-by-step guide"
echo ""

echo -e "${YELLOW}Next steps:${NC}"
echo "1. Read: $UPLOAD_DIR/UPLOAD_INSTRUCTIONS.txt"
echo "2. Choose upload method:"
echo "   - FileZilla/WinSCP: Upload 'backend' folder"
echo "   - cPanel/Plesk: Upload 'backend-upload.zip' and extract"
echo "3. Follow instructions in UPLOAD_INSTRUCTIONS.txt"
echo "4. Don't forget to run commands on server!"
echo ""

echo -e "${GREEN}Upload folder location:${NC}"
echo "  $(pwd)/$UPLOAD_DIR"
echo ""

echo -e "${BLUE}File sizes:${NC}"
du -sh $UPLOAD_DIR/backend 2>/dev/null || echo "  Backend folder: N/A"
du -sh backend-upload.zip 2>/dev/null || echo "  backend-upload.zip: N/A"
du -sh frontend-build-upload.zip 2>/dev/null || echo "  frontend-build-upload.zip: N/A"
echo ""

print_success "Ready to upload! 🚀"
