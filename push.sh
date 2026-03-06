#!/bin/bash
read -p "Masukkan pesan commit: " msg
git add .
git commit -m "$msg"
git push origin main
echo "Selesai. Tekan Enter untuk menutup."
read
