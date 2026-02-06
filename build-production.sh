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

# Step 3: Clean up (Moved to Step 4 within temp dir to preserve dev environment)
# We no longer delete node_modules or vendor from the source directory.


# Step 4: Create single-folder archive structure
echo "📦 Preparing archive structure..."
TEMP_DIR="build_temp"
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR/$PROJECT_NAME"

# Copy backend content (base application) to the single folder
# Note: Frontend assets are already in backend/public from Step 2
cp -r backend/* "$TEMP_DIR/$PROJECT_NAME/"
cp backend/.env.example "$TEMP_DIR/$PROJECT_NAME/" 2>/dev/null

# Copy documentation to the same single folder
cp README.md "$TEMP_DIR/$PROJECT_NAME/" 2>/dev/null
cp DEPLOYMENT_GUIDE.md "$TEMP_DIR/$PROJECT_NAME/" 2>/dev/null
cp SETUP_TOKO_BARU.md "$TEMP_DIR/$PROJECT_NAME/" 2>/dev/null

echo "📦 Zipping..."
cd "$TEMP_DIR"
zip -r "../$ARCHIVE_NAME" "$PROJECT_NAME" \
  -x "*/node_modules/*" \
  "*/vendor/*" \
  "*/storage/logs/*" \
  "*/storage/framework/sessions/*" \
  "*/storage/framework/views/*" \
  "*/.env" \
  "*/.DS_Store" \
  "*/.git/*" \
  "*/.gitignore"

cd ..
rm -rf "$TEMP_DIR"

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