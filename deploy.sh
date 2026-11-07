#!/bin/bash
# Dijalankan DI SERVER (bukan di laptop), setelah clone pertama lewat cPanel Git.
# Pakai: bash ~/sahih-v3/deploy.sh
set -euo pipefail

APP_DIR="$HOME/sahih-v3"
PHP_BIN="${PHP_BIN:-php}"   # cPanel: /opt/alt/php84/usr/bin/php atau ea-php84

cd "$APP_DIR"

echo "==> Tarik kode terbaru"
git pull --ff-only

# --no-scripts: cPanel mematikan proc_open, sehingga Composer gagal saat
# menjalankan post-autoload-dump. package:discover dipanggil manual di bawah.
echo "==> Pasang dependensi produksi"
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

echo "==> Daftarkan paket"
"$PHP_BIN" artisan package:discover --ansi

echo "==> Migrasi database"
"$PHP_BIN" artisan migrate --force

echo "==> Bersihkan dan bangun cache"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

echo "==> Pastikan storage tertaut"
[ -L public/storage ] || ln -s ../storage/app/public public/storage
chmod -R 775 storage bootstrap/cache

echo "==> Selesai. Cek https://sahihofficial.id/up"
