#!/bin/bash

echo "🚀 Deploying Logo Fix to Production..."

# 1. Copy logo to public directory (if not already done)
if [ ! -f "backend/public/logo.png" ]; then
    echo "Copying logo to public directory..."
    cp backend/storage/app/public/logos/tmNAETpu3MqFPBwzcedety7CPTePSx2MbiWHp52W.png backend/public/logo.png
    echo "✅ Logo copied"
else
    echo "✅ Logo already exists in public directory"
fi

# 2. Build frontend
echo "Building frontend..."
cd frontend
npm run build
cd ..

# 3. Test logo accessibility
echo "🔍 Testing logo accessibility..."
curl -I "https://ds.keetech.my.id/logo.png"

echo ""
echo "🎉 Deploy completed!"
echo ""
echo "Next steps:"
echo "1. Upload backend/public/logo.png to your server's public directory"
echo "2. Upload the updated backend/app/Http/Controllers/Api/SettingController.php"
echo "3. Upload the updated frontend/src/stores/settings.js"
echo "4. Upload the built frontend files to your server"
echo ""
echo "Test URLs:"
echo "- Logo: https://ds.keetech.my.id/logo.png"
echo "- Login: https://ds.keetech.my.id/login"
echo "- Customer: https://ds.keetech.my.id"