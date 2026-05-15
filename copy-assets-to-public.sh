#!/bin/bash

echo "📁 Copying storage assets to public directory..."

# Create directories in public
mkdir -p backend/public/logos
mkdir -p backend/public/favicons
mkdir -p backend/public/products
mkdir -p backend/public/packages
mkdir -p backend/public/carousel

# Copy files from storage to public
echo "Copying logos..."
cp -r backend/storage/app/public/logos/* backend/public/logos/ 2>/dev/null || echo "No logos to copy"

echo "Copying favicons..."
cp -r backend/storage/app/public/favicons/* backend/public/favicons/ 2>/dev/null || echo "No favicons to copy"

echo "Copying product images..."
cp -r backend/storage/app/public/products/* backend/public/products/ 2>/dev/null || echo "No product images to copy"

echo "Copying package images..."
cp -r backend/storage/app/public/packages/* backend/public/packages/ 2>/dev/null || echo "No package images to copy"

echo "Copying carousel images..."
cp -r backend/storage/app/public/carousel/* backend/public/carousel/ 2>/dev/null || echo "No carousel images to copy"

# Set permissions
chmod -R 755 backend/public/logos
chmod -R 755 backend/public/favicons
chmod -R 755 backend/public/products
chmod -R 755 backend/public/packages
chmod -R 755 backend/public/carousel

echo "✅ Assets copied successfully!"
echo "🔍 Testing logo access..."
curl -I "https://ds.keetech.my.id/logos/tmNAETpu3MqFPBwzcedety7CPTePSx2MbiWHp52W.png"