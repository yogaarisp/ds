#!/bin/bash

echo "🔧 Fixing Storage Link for Production..."

# Remove existing storage link if exists
if [ -L "backend/public/storage" ]; then
    echo "Removing existing storage link..."
    rm backend/public/storage
fi

# Create new storage link
echo "Creating new storage link..."
cd backend
php artisan storage:link

# Check if link was created successfully
if [ -L "public/storage" ]; then
    echo "✅ Storage link created successfully!"
    ls -la public/storage
else
    echo "❌ Failed to create storage link"
    echo "Manual fix needed on server:"
    echo "1. SSH to server"
    echo "2. cd /path/to/your/project/backend"
    echo "3. php artisan storage:link"
    echo "4. chmod -R 755 storage/"
    echo "5. chmod -R 755 public/storage"
fi

echo "🔍 Testing logo access..."
curl -I "https://ds.keetech.my.id/storage/logos/tmNAETpu3MqFPBwzcedety7CPTePSx2MbiWHp52W.png"

echo "Done!"