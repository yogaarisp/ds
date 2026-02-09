#!/bin/bash

# Script untuk sign APK Android
# Pastikan jarsigner tersedia (dari JDK)

APK_UNSIGNED="android-webview/app/build/outputs/apk/release/app-release-unsigned.apk"
APK_SIGNED="DimsumSarangPOS-v1.0.8-release.apk"
KEYSTORE="android-webview/keystore/release-new.keystore"
KEY_ALIAS="dimsumpos"
STORE_PASS="dimsum2026"
KEY_PASS="dimsum2026"

echo "🔐 Signing APK..."
echo "Input: $APK_UNSIGNED"
echo "Output: $APK_SIGNED"

# Check if unsigned APK exists
if [ ! -f "$APK_UNSIGNED" ]; then
    echo "❌ Error: Unsigned APK not found at $APK_UNSIGNED"
    echo "Please build the APK first with: cd android-webview && ./gradlew assembleRelease"
    exit 1
fi

# Check if keystore exists
if [ ! -f "$KEYSTORE" ]; then
    echo "❌ Error: Keystore not found at $KEYSTORE"
    exit 1
fi

# Copy unsigned APK to signed location
cp "$APK_UNSIGNED" "$APK_SIGNED"

# Sign the APK
jarsigner -verbose \
    -sigalg SHA256withRSA \
    -digestalg SHA-256 \
    -keystore "$KEYSTORE" \
    -storepass "$STORE_PASS" \
    -keypass "$KEY_PASS" \
    "$APK_SIGNED" \
    "$KEY_ALIAS"

if [ $? -eq 0 ]; then
    echo "✅ APK signed successfully!"
    echo "📦 Output: $APK_SIGNED"
    
    # Verify the signature
    echo ""
    echo "🔍 Verifying signature..."
    jarsigner -verify -verbose -certs "$APK_SIGNED"
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ Signature verified!"
        echo "📱 APK siap diinstall: $APK_SIGNED"
        ls -lh "$APK_SIGNED"
    else
        echo "❌ Signature verification failed"
        exit 1
    fi
else
    echo "❌ Failed to sign APK"
    exit 1
fi
