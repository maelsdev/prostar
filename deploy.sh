#!/bin/bash

# Скрипт для деплою на продакшн
# Використання: ./deploy.sh

set -e  # Зупинити виконання при помилці

echo "═══════════════════════════════════════════════════════════"
echo "  ProStar Travel - Скрипт деплою на продакшн"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Кольори для виводу
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функція для виводу повідомлень
info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Перевірка, що скрипт запущено з правильної директорії
if [ ! -d "laravel" ]; then
    error "Помилка: скрипт повинен бути запущений з кореня проекту"
    exit 1
fi

# Перехід в директорію Laravel
cd laravel

info "Перевірка середовища..."

# Перевірка наявності .env файлу
if [ ! -f ".env" ]; then
    error "Файл .env не знайдено! Створіть його на основі .env.example"
    exit 1
fi

# Перевірка типу бази даних
DB_CONNECTION=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)
if [ "$DB_CONNECTION" != "mysql" ]; then
    warn "Увага: DB_CONNECTION=$DB_CONNECTION (очікується mysql для продакшн)"
    read -p "Продовжити? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

info "Встановлення/оновлення Composer залежностей..."
composer install --no-dev --optimize-autoloader --no-interaction

info "Очищення кешів..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

info "Застосування міграцій..."
read -p "Застосувати міграції? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    info "Міграції застосовано успішно"
else
    warn "Міграції пропущено"
fi

info "Створення кешів для продакшн..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

info "Оптимізація автозавантаження..."
composer dump-autoload --optimize

info "Створення символічного посилання для storage..."
if [ ! -L "public/storage" ]; then
    php artisan storage:link
    info "Символічне посилання створено"
else
    info "Символічне посилання вже існує"
fi

info "Перевірка прав доступу..."
chmod -R 755 storage bootstrap/cache 2>/dev/null || warn "Не вдалося змінити права доступу (можливо потрібні права root)"

info "Перевірка статусу міграцій..."
php artisan migrate:status

echo ""
echo "═══════════════════════════════════════════════════════════"
info "Деплой завершено!"
echo "═══════════════════════════════════════════════════════════"
echo ""
warn "Не забудьте перевірити:"
echo "  1. Відкрити адмін-панель та перевірити функціонал"
echo "  2. Перевірити логи: tail -f storage/logs/laravel.log"
echo "  3. Перевірити, що всі міграції застосовано"
echo ""


