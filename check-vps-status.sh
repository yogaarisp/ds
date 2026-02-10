#!/bin/bash

# ============================================
# VPS Status Check Script
# ============================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
VPS_IP="your-vps-ip"  # GANTI dengan IP VPS Anda
VPS_USER="root"       # GANTI jika bukan root
VPS_PATH="/var/www/html/dimsumpos"

# Functions
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Check if VPS_IP is configured
if [ "$VPS_IP" = "your-vps-ip" ]; then
    print_error "Please configure VPS_IP in this script first!"
    exit 1
fi

print_header "VPS Status Check"

# Check website response
print_info "Checking website response..."
response=$(curl -s -o /dev/null -w "%{http_code}" http://${VPS_IP})
if [ "$response" = "200" ]; then
    print_success "Website is UP (HTTP $response)"
else
    print_error "Website returned HTTP $response"
fi

# Connect to VPS and get detailed status
print_info "Connecting to VPS for detailed status..."

ssh ${VPS_USER}@${VPS_IP} << 'ENDSSH'
# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "\n${BLUE}=== System Resources ===${NC}"
echo -e "${YELLOW}Disk Usage:${NC}"
df -h | grep -E '^/dev/|Filesystem'

echo -e "\n${YELLOW}Memory Usage:${NC}"
free -h

echo -e "\n${YELLOW}CPU Load:${NC}"
uptime

echo -e "\n${BLUE}=== Services Status ===${NC}"
echo -e "${YELLOW}Nginx:${NC}"
sudo systemctl status nginx --no-pager -l | head -5

echo -e "\n${YELLOW}PHP-FPM:${NC}"
sudo systemctl status php8.2-fpm --no-pager -l | head -5

echo -e "\n${YELLOW}MySQL:${NC}"
sudo systemctl status mysql --no-pager -l | head -5

echo -e "\n${BLUE}=== Application Status ===${NC}"
cd /var/www/html/dimsumpos/backend

echo -e "${YELLOW}Laravel Version:${NC}"
php artisan --version

echo -e "\n${YELLOW}Maintenance Mode:${NC}"
if [ -f storage/framework/down ]; then
    echo -e "${RED}ENABLED${NC}"
else
    echo -e "${GREEN}DISABLED${NC}"
fi

echo -e "\n${YELLOW}Recent Migrations:${NC}"
php artisan migrate:status | tail -5

echo -e "\n${BLUE}=== Recent Logs ===${NC}"
echo -e "${YELLOW}Laravel Errors (last 10 lines):${NC}"
tail -10 storage/logs/laravel.log 2>/dev/null || echo "No logs yet"

echo -e "\n${YELLOW}Nginx Errors (last 5 lines):${NC}"
sudo tail -5 /var/log/nginx/error.log 2>/dev/null || echo "No errors"

echo -e "\n${BLUE}=== Database Status ===${NC}"
echo -e "${YELLOW}Database Connection:${NC}"
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected successfully';" 2>&1 | grep -E 'Connected|Error' || echo "Connection test failed"

echo -e "\n${BLUE}=== File Permissions ===${NC}"
echo -e "${YELLOW}Storage Directory:${NC}"
ls -ld storage/

echo -e "\n${YELLOW}Storage/app/public:${NC}"
ls -ld storage/app/public/

echo -e "\n${YELLOW}Public/storage symlink:${NC}"
ls -l public/storage 2>/dev/null || echo "Symlink not found"

echo -e "\n${BLUE}=== Available Backups ===${NC}"
ls -lht /var/www/html/dimsumpos/backups/ 2>/dev/null | head -6 || echo "No backups found"

ENDSSH

print_header "Status Check Complete"
echo -e "${GREEN}Check completed!${NC}\n"
