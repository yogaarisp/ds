@echo off
echo ========================================
echo Building Release APK (Like Android Studio)
echo ========================================
echo.

REM Set environment variables
set ANDROID_HOME=C:\Users\Kee\AppData\Local\Android\Sdk
set JAVA_HOME=C:\Program Files\Microsoft\jdk-17.0.9.8-hotspot

echo Setting up environment...
echo.

echo Step 1: Building Release APK (Signed)...
call gradlew.bat assembleRelease
if errorlevel 1 (
    echo ERROR: Build failed!
    pause
    exit /b 1
)

echo.
echo Step 2: Copying APK to root folder...
set RELEASE_APK=app\build\outputs\apk\release\app-release.apk
set OUTPUT_APK=..\ds.apk

if not exist "%RELEASE_APK%" (
    echo ERROR: Release APK not found!
    echo Check if signing config is correct.
    pause
    exit /b 1
)

copy /Y "%RELEASE_APK%" "%OUTPUT_APK%"

echo.
echo ========================================
echo BUILD SUCCESS!
echo ========================================
echo.
echo APK Location:
echo - Build folder: %RELEASE_APK%
echo - Root folder: %OUTPUT_APK%
echo.
echo APK Size:
dir "%OUTPUT_APK%" | findstr "ds.apk"
echo.
echo NOTE: APK is signed with release keystore automatically.
echo      You can install it directly on your device!
echo.
pause
