# 本番デプロイガイド

Training Hub の本番環境構築手順です。

## 構成概要

```
┌─────────────────────────────────────────┐
│  サーバー (Ubuntu 22.04+)               │
│                                         │
│  ┌───────────┐    ┌──────────────────┐  │
│  │  Nginx    │───▶│  PHP-FPM 8.4     │  │
│  │  :443/:80 │    │  Laravel App     │  │
│  └───────────┘    └──────────────────┘  │
│                                         │
│  ┌───────────┐    ┌──────────────────┐  │
│  │  MySQL 8  │    │  Redis           │  │
│  │  :3306    │    │  :6379           │  │
│  └───────────┘    └──────────────────┘  │
│                                         │
│  ┌───────────┐    ┌──────────────────┐  │
│  │  Ollama   │    │  Supervisor      │  │
│  │  :11434   │    │  (queue worker)  │  │
│  └───────────┘    └──────────────────┘  │
└─────────────────────────────────────────┘
```

## 前提条件

- Ubuntu 22.04 以上（または同等のLinux）
- 最低スペック: 2 vCPU / 4GB RAM / 20GB SSD
- AI要約を使う場合: 4 vCPU / 8GB RAM 以上推奨（Ollama 用）
- ドメインとSSL証明書（Let's Encrypt 推奨）

## 1. サーバーセットアップ

```bash
# パッケージ更新
sudo apt update && sudo apt upgrade -y

# PHP 8.4 インストール
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.4-fpm php8.4-mysql php8.4-redis php8.4-xml \
    php8.4-curl php8.4-mbstring php8.4-zip php8.4-bcmath php8.4-intl

# MySQL 8
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Redis
sudo apt install -y redis-server

# Nginx
sudo apt install -y nginx

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Supervisor（Queue Worker用）
sudo apt install -y supervisor
```

## 2. MySQL設定

```bash
sudo mysql -e "
CREATE DATABASE training_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'training_hub'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON training_hub.* TO 'training_hub'@'localhost';
FLUSH PRIVILEGES;
"
```

## 3. アプリケーションデプロイ

```bash
# アプリ配置
sudo mkdir -p /var/www/training-hub
sudo chown $USER:www-data /var/www/training-hub
cd /var/www/training-hub

git clone https://github.com/irochi1184/training-hub.git .
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 環境設定
cp .env.example .env
php artisan key:generate
```

`.env` を編集:

```env
APP_NAME="Training Hub"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=training_hub
DB_USERNAME=training_hub
DB_PASSWORD=YOUR_STRONG_PASSWORD

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1

OLLAMA_HOST=http://127.0.0.1:11434
OLLAMA_MODEL=gemma2
```

```bash
# マイグレーション
php artisan migrate --force

# 初回のみシード（デモデータ不要なら省略）
php artisan db:seed --force

# キャッシュ最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# パーミッション
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 4. Nginx設定

`/etc/nginx/sites-available/training-hub`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    root /var/www/training-hub/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/training-hub /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 5. SSL証明書（Let's Encrypt）

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

## 6. Queue Worker（Supervisor）

`/etc/supervisor/conf.d/training-hub-worker.conf`:

```ini
[program:training-hub-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/training-hub/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopastype=TERM
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/training-hub/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start training-hub-worker:*
```

## 7. スケジューラ（Cron）

```bash
sudo crontab -u www-data -e
```

以下を追加:

```
* * * * * cd /var/www/training-hub && php artisan schedule:run >> /dev/null 2>&1
```

これにより以下が自動実行されます:
- 毎日 06:00 — `risk:detect`（要注意者検知）
- 毎週月曜 06:00 — `summaries:generate-weekly`（AI要約生成）

## 8. Ollama（AI要約用、オプション）

```bash
# インストール
curl -fsSL https://ollama.com/install.sh | sh

# モデルダウンロード
ollama pull gemma2

# systemd で常時起動
sudo systemctl enable ollama
sudo systemctl start ollama
```

## 9. デプロイ更新スクリプト

`/var/www/training-hub/deploy/update.sh`:

```bash
#!/bin/bash
set -e

cd /var/www/training-hub

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

echo "デプロイ完了"
```

```bash
chmod +x /var/www/training-hub/deploy/update.sh
```

## 10. バックアップ

```bash
# MySQL日次バックアップ（cron設定）
0 2 * * * mysqldump -u training_hub -p'PASSWORD' training_hub | gzip > /var/backups/training-hub-$(date +\%Y\%m\%d).sql.gz

# 7日以上古いバックアップを削除
0 3 * * * find /var/backups/ -name "training-hub-*.sql.gz" -mtime +7 -delete
```

## トラブルシューティング

| 症状 | 対処 |
|------|------|
| 500エラー | `storage/logs/laravel.log` を確認。パーミッション再設定 |
| Queue が動かない | `sudo supervisorctl status` で確認。ログは `storage/logs/worker.log` |
| AI要約が生成されない | `ollama list` でモデル確認。`curl http://localhost:11434/api/tags` で接続確認 |
| Slack通知が届かない | 管理画面で Webhook URL が設定されているか確認。`storage/logs/laravel.log` でエラー確認 |
