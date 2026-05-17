#!/bin/bash
set -e

cd /var/www/training-hub

echo "=== デプロイ開始 ==="

# メンテナンスモードON
php artisan down

# 最新コード取得
git pull origin main

# 依存関係更新
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# マイグレーション
php artisan migrate --force

# キャッシュ再構築
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Queue再起動
php artisan queue:restart

# メンテナンスモードOFF
php artisan up

echo "=== デプロイ完了 ==="
