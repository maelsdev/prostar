#!/bin/bash

# ProStar Travel - Скрипт автоматичного деплою
# Використання: ./deploy.sh "Опис змін"

set -e

# Кольори для виводу
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Параметри
COMMIT_MESSAGE="${1:-Оновлення проєкту}"
SERVER_HOST="prostar@prostartravel.com"
SERVER_PASSWORD="Dthjybrf777"
SERVER_PATH="/home/prostar/public_html"
LARAVEL_PATH="$SERVER_PATH/laravel"

echo -e "${BLUE}════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  ProStar Travel - Автоматичний деплой${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════${NC}"
echo ""

# Крок 1: Перевірка статусу Git
echo -e "${YELLOW}📋 Крок 1: Перевірка статусу Git...${NC}"
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${GREEN}✓ Є зміни для коміту${NC}"
    git status --short
else
    echo -e "${YELLOW}⚠️  Немає змін для коміту${NC}"
    read -p "Продовжити деплой? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi
echo ""

# Крок 2: Коміт змін
echo -e "${YELLOW}📝 Крок 2: Коміт змін...${NC}"
git add -A
git commit -m "$COMMIT_MESSAGE" || echo -e "${YELLOW}⚠️  Немає змін для коміту${NC}"
echo -e "${GREEN}✓ Зміни закомічено${NC}"
echo ""

# Крок 3: Синхронізація з віддаленим репозиторієм
echo -e "${YELLOW}🔄 Крок 3: Синхронізація з GitHub...${NC}"
if git pull --rebase origin main 2>&1 | grep -q "CONFLICT"; then
    echo -e "${RED}❌ Конфлікти злиття! Вирішіть конфлікти вручну.${NC}"
    exit 1
fi
git push origin main
echo -e "${GREEN}✓ Код відправлено на GitHub${NC}"
echo ""

# Крок 4: Деплой на сервер
echo -e "${YELLOW}🚀 Крок 4: Деплой на сервер...${NC}"
sshpass -p "$SERVER_PASSWORD" ssh -o StrictHostKeyChecking=no "$SERVER_HOST" << EOF
    cd $SERVER_PATH
    echo "Оновлення коду з GitHub..."
    git fetch origin
    git reset --hard origin/main
    
    cd $LARAVEL_PATH
    echo "Очищення кешів..."
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    php artisan route:clear
    
    echo "Оптимізація для production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    echo "Перевірка версії..."
    git log --oneline -1
EOF

echo -e "${GREEN}✓ Деплой завершено${NC}"
echo ""

# Крок 5: Перевірка синхронізації
echo -e "${YELLOW}✅ Крок 5: Перевірка синхронізації...${NC}"
LOCAL_COMMIT=$(git rev-parse --short HEAD)
SERVER_COMMIT=$(sshpass -p "$SERVER_PASSWORD" ssh -o StrictHostKeyChecking=no "$SERVER_HOST" "cd $SERVER_PATH && git rev-parse --short HEAD")

if [ "$LOCAL_COMMIT" = "$SERVER_COMMIT" ]; then
    echo -e "${GREEN}✓ Проєкти синхронізовані (коміт: $LOCAL_COMMIT)${NC}"
else
    echo -e "${RED}❌ Проєкти не синхронізовані!${NC}"
    echo "   Локально: $LOCAL_COMMIT"
    echo "   На сервері: $SERVER_COMMIT"
    exit 1
fi
echo ""

# Завершення
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Деплой успішно завершено!${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}📋 Наступні кроки:${NC}"
echo "1. Перевірте сайт: https://prostartravel.com"
echo "2. Перевірте адмін-панель: https://prostartravel.com/admin"
echo "3. Перевірте новий функціонал"
echo "4. Перевірте консоль браузера на помилки"
echo ""
