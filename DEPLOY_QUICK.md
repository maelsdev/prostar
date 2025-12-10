# 🚀 Швидка інструкція деплою

## Перед деплоєм
```bash
# 1. Створіть резервну копію БД на сервері
mysqldump -u [USER] -p[PASS] [DB_NAME] > backup_$(date +%Y%m%d_%H%M%S).sql
```

## Деплой через Git
```bash
# На сервері
cd /path/to/project
git pull origin main
cd laravel
./deploy.sh  # або виконайте команди вручну нижче
```

## Деплой вручну
```bash
cd laravel

# 1. Оновлення залежностей
composer install --no-dev --optimize-autoloader

# 2. Очищення кешів
php artisan optimize:clear

# 3. Застосування міграцій
php artisan migrate --force

# 4. Створення кешів
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Storage link
php artisan storage:link
```

## Перевірка
- ✅ Адмін-панель: `/admin`
- ✅ Логи: `tail -f storage/logs/laravel.log`
- ✅ Міграції: `php artisan migrate:status`

## Відкат
```bash
# Відкат міграцій
php artisan migrate:rollback --step=1

# Відкат коду
git checkout [PREVIOUS_COMMIT]
```

**Детальна інструкція:** див. `DEPLOY.md`


