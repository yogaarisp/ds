#!/bin/bash

echo "🏗️  Building Dimsum Sarang POS APK..."
echo ""

# Set Java 17
export JAVA_HOME=$(/usr/libexec/java_home -v 17)
echo "☕ Using Java: $JAVA_HOME"
java -version
echo ""

# Navigate to android directory
cd android-webview

# Clean previous builds
echo "🧹 Cleaning previous builds..."
./gradlew clean

# Build release APK
echo "📦 Building release APK..."
./gradlew assembleRelease

# Check if build was successful
if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Build successful!"
    
    # Find the APK
    APK_PATH="app/build/outputs/apk/release/app-release.apk"
    
    if [ -f "$APK_PATH" ]; then
        # Copy to root directory with version name
        cp "$APK_PATH" "../DimsumSarangPOS-v1.0.9-release.apk"
        
        echo "📱 APK created: DimsumSarangPOS-v1.0.9-release.apk"
        ls -lh "../DimsumSarangPOS-v1.0.9-release.apk"
        
        echo ""
        echo "✅ APK siap diinstall!"
        echo "📍 Location: $(pwd)/../DimsumSarangPOS-v1.0.9-release.apk"
    else
        echo "❌ APK not found at expected location"
        echo "Looking for APK files..."
        find app/build/outputs/apk -name "*.apk"
    fi
else
    echo "❌ Build failed"
    exit 1
fi
