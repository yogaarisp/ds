@echo off
echo ========================================
echo Building Debug APK (Like Android Studio)
echo ========================================
echo.

REM Set environment variables
set ANDROID_HOME=C:\Users\Kee\AppData\Local\Android\Sdk
set JAVA_HOME=C:\Program Files\Microsoft\jdk-17.0.9.8-hotspot

echo Setting up environment...
echo.

echo Step 1: Building Debug APK...
call gradlew.bat assembleDebug
if errorlevel 1 (
    echo ERROR: Build failed!
    pause
    exit /b 1
)

echo.
echo Step 2: Copying APK to root folder...
set DEBUG_APK=app\build\outputs\apk\debug\app-debug.apk
set OUTPUT_APK=..\ds.apk

if not exist "%DEBUG_APK%" (
    echo ERROR: Debug APK not found!
    pause
    exit /b 1
)

copy /Y "%DEBUG_APK%" "%OUTPUT_APK%"

echo.
echo ========================================
echo BUILD SUCCESS!
echo ========================================
echo.
echo APK Location:
echo - Build folder: %DEBUG_APK%
echo - Root folder: %OUTPUT_APK%
echo.
echo APK Size:
dir "%OUTPUT_APK%" | findstr "ds.apk"
echo.
echo NOTE: Debug APK is signed with debug keystore automatically.
echo      You can install it directly on your device!
echo.
pause
