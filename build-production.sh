#!/bin/bash

# Script untuk build production package
# Usage: ./build-production.sh [nama-project]

PROJECT_NAME=${1:-"pos-system"}
DATE=$(date +%Y%m%d_%H%M%S)
ARCHIVE_NAME="${PROJECT_NAME}-production-${DATE}.zip"

echo "🚀 Building production package: $ARCHIVE_NAME"

# Step 1: Build frontend
echo "📦 Building frontend..."
cd frontend
npm run build:production
if [ $? -ne 0 ]; then
    echo "❌ Frontend build failed!"
    exit 1
fi

# Step 2: Copy to backend public
echo "📁 Copying build files..."
cp -r dist/* ../backend/public/
cd ..

# Step 3: Clean up
echo "🧹 Cleaning up..."
# Backup .env if exists
if [ -f "backend/.env" ]; then
    cp backend/.env backend/.env.backup
fi

# Remove development files
rm -rf frontend/node_modules/ 2>/dev/null
rm -rf backend/vendor/ 2>/dev/null
rm -rf backend/storage/logs/*.log 2>/dev/null
rm -rf backend/storage/framework/sessions/* 2>/dev/null
rm -rf backend/storage/framework/views/*.php 2>/dev/null
rm -f backend/.env 2>/dev/null

# Step 4: Create archive
echo "📦 Creating archive..."
zip -r "$ARCHIVE_NAME" \
  backend/ \
  frontend/ \
  README.md \
  DEPLOYMENT_GUIDE.md \
  SETUP_TOKO_BARU.md \
  -x \
  "backend/node_modules/*" \
  "frontend/node_modules/*" \
  "backend/vendor/*" \
  "backend/storage/logs/*" \
  "backend/storage/framework/sessions/*" \
  "backend/storage/framework/views/*" \
  "backend/.env" \
  "frontend/.env*" \
  "*.DS_Store" \
  "*/.*"

# Step 5: Restore .env if backed up
if [ -f "backend/.env.backup" ]; then
    mv backend/.env.backup backend/.env
fi

# Step 6: Show results
echo "✅ Production package created!"
echo "📁 File: $ARCHIVE_NAME"
echo "📊 Size: $(ls -lh $ARCHIVE_NAME | awk '{print $5}')"
echo ""
echo "🚀 Ready to deploy!"
echo "   1. Extract on server"
echo "   2. Run: composer install --no-dev"
echo "   3. Setup .env file"
echo "   4. Run: php artisan migrate"
echo "   5. Run: php artisan db:seed"
echo "   6. Run: php artisan storage:link"