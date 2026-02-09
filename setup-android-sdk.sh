#!/bin/bash

echo "🔍 Checking Android SDK Setup..."
echo ""

# Check if Android Studio is installed
if [ -d "/Applications/Android Studio.app" ]; then
    echo "✅ Android Studio is installed"
else
    echo "❌ Android Studio is NOT installed"
    echo "   Please install from: https://developer.android.com/studio"
    exit 1
fi

# Check SDK location
SDK_DIR="$HOME/Library/Android/sdk"

if [ -d "$SDK_DIR" ]; then
    echo "✅ SDK directory exists: $SDK_DIR"
    
    # Check if SDK has required components
    if [ -d "$SDK_DIR/platforms/android-33" ]; then
        echo "✅ Android SDK Platform 33 is installed"
    else
        echo "⚠️  Android SDK Platform 33 is NOT installed"
        echo "   Please install via Android Studio:"
        echo "   Settings → Appearance & Behavior → System Settings → Android SDK"
        echo "   → SDK Platforms → Check 'Android 13.0 (Tiramisu)' → Apply"
    fi
    
    if [ -d "$SDK_DIR/build-tools" ]; then
        echo "✅ Build Tools are installed"
        ls -1 "$SDK_DIR/build-tools" | head -3
    else
        echo "⚠️  Build Tools are NOT installed"
        echo "   Please install via Android Studio SDK Manager"
    fi
else
    echo "❌ SDK directory does NOT exist: $SDK_DIR"
    echo ""
    echo "📝 To setup SDK:"
    echo "   1. Open Android Studio"
    echo "   2. If prompted, complete the setup wizard (it will download SDK)"
    echo "   3. Or go to: Settings → Appearance & Behavior → System Settings → Android SDK"
    echo "   4. Install:"
    echo "      - Android SDK Platform 33 (Android 13.0)"
    echo "      - Android SDK Build-Tools 33.0.0 or higher"
    echo "   5. Click 'Apply' and wait for download to complete"
    echo ""
    echo "   After setup, run this script again to verify"
    exit 1
fi

echo ""
echo "📍 SDK Location: $SDK_DIR"
echo ""

# Update local.properties
echo "📝 Updating local.properties..."
echo "sdk.dir=$SDK_DIR" > android-webview/local.properties
echo "✅ local.properties updated"

echo ""
echo "🎯 Next Steps:"
echo "   If all components are installed, run:"
echo "   ./build-apk.sh"
echo ""
