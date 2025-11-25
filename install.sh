#!/bin/bash

# Скрипт встановлення ProStar Travel
# Використання: bash install.sh

set -e

# Кольори для виводу
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функція для виводу заголовка
print_header() {
    echo -e "${BLUE}"
    echo "════════════════════════════════════════════════════════"
    echo "  ProStar Travel - Встановлення"
    echo "════════════════════════════════════════════════════════"
    echo -e "${NC}"
}

# Функція для запиту вводу
ask_input() {
    local prompt=$1
    local default=$2
    local var_name=$3
    
    if [ -n "$default" ]; then
        read -p "$(echo -e "${YELLOW}$prompt${NC} [${GREEN}$default${NC}]: ")" input
        eval "$var_name=\"\${input:-$default}\""
    else
        read -p "$(echo -e "${YELLOW}$prompt${NC}: ")" input
        eval "$var_name=\"$input\""
    fi
}

# Функція для запиту пароля
ask_password() {
    local prompt=$1
    local default=$2
    local var_name=$3
    
    if [ -n "$default" ]; then
        read -sp "$(echo -e "${YELLOW}$prompt${NC} [${GREEN}****${NC}]: ")" input
        echo ""
        if [ -z "$input" ]; then
            eval "$var_name=\"$default\""
        else
            eval "$var_name=\"$input\""
        fi
    else
        read -sp "$(echo -e "${YELLOW}$prompt${NC}: ")" input
        echo ""
        eval "$var_name=\"$input\""
    fi
}

# Перевірка, що скрипт запущено з правильної директорії
if [ ! -f "index.php" ] || [ ! -d "laravel" ]; then
    echo -e "${RED}Помилка: Скрипт має бути запущений з кореня проекту!${NC}"
    echo "Поточна директорія: $(pwd)"
    echo "Переконайтеся, що ви в директорії з index.php та папкою laravel/"
    exit 1
fi

print_header

# Визначення поточної директорії
CURRENT_DIR=$(pwd)
echo -e "${GREEN}Поточна директорія: $CURRENT_DIR${NC}"
echo ""

# ============================================
# КРОК 1: Параметри бази даних
# ============================================
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}КРОК 1: Налаштування бази даних${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

DB_TYPE=""
while [ "$DB_TYPE" != "sqlite" ] && [ "$DB_TYPE" != "mysql" ]; do
    ask_input "Тип бази даних (sqlite/mysql)" "sqlite" DB_TYPE
    DB_TYPE=$(echo "$DB_TYPE" | tr '[:upper:]' '[:lower:]')
    if [ "$DB_TYPE" != "sqlite" ] && [ "$DB_TYPE" != "mysql" ]; then
        echo -e "${RED}Помилка: Виберіть 'sqlite' або 'mysql'${NC}"
    fi
done

if [ "$DB_TYPE" = "mysql" ]; then
    ask_input "MySQL Host" "localhost" DB_HOST
    ask_input "MySQL Port" "3306" DB_PORT
    ask_input "MySQL Database" "prostar_db" DB_DATABASE
    ask_input "MySQL Username" "prostar_db" DB_USERNAME
    ask_password "MySQL Password" "Dthjybrf777" DB_PASSWORD
else
    # SQLite
    DB_HOST=""
    DB_PORT=""
    DB_DATABASE="$CURRENT_DIR/laravel/database/database.sqlite"
    DB_USERNAME=""
    DB_PASSWORD=""
fi

echo -e "${GREEN}✓ Тип БД: $DB_TYPE${NC}"
if [ "$DB_TYPE" = "mysql" ]; then
    echo -e "${GREEN}✓ Host: $DB_HOST:$DB_PORT${NC}"
    echo -e "${GREEN}✓ Database: $DB_DATABASE${NC}"
fi
echo ""

# ============================================
# КРОК 2: Логін та пароль адміна
# ============================================
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}КРОК 2: Налаштування адміністратора${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

ADMIN_EMAIL=""
ADMIN_PASSWORD=""

ask_input "Email адміністратора" "maels@ukr.net" ADMIN_EMAIL
ask_password "Пароль адміністратора" "Dthjybrf777" ADMIN_PASSWORD

echo -e "${GREEN}✓ Email: $ADMIN_EMAIL${NC}"
echo ""

# ============================================
# КРОК 3: Встановлення
# ============================================
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}КРОК 3: Встановлення проекту${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

cd laravel

# Перевірка наявності composer
COMPOSER_CMD=""
if command -v composer &> /dev/null; then
    COMPOSER_CMD="composer"
elif [ -f "/usr/local/bin/composer" ] && [ -x "/usr/local/bin/composer" ]; then
    COMPOSER_CMD="/usr/local/bin/composer"
elif [ -f "/usr/bin/composer" ] && [ -x "/usr/bin/composer" ]; then
    COMPOSER_CMD="/usr/bin/composer"
elif [ -f "$HOME/composer.phar" ]; then
    COMPOSER_CMD="php $HOME/composer.phar"
elif [ -f "composer.phar" ]; then
    COMPOSER_CMD="php composer.phar"
else
    # Остання спроба - пошук composer в системі
    COMPOSER_PATH=$(which composer 2>/dev/null || find /usr -name composer -type f 2>/dev/null | head -1)
    if [ -n "$COMPOSER_PATH" ] && [ -x "$COMPOSER_PATH" ]; then
        COMPOSER_CMD="$COMPOSER_PATH"
    else
        echo -e "${RED}Помилка: Composer не знайдено!${NC}"
        echo "Спробуйте встановити Composer або вкажіть шлях до composer.phar"
        echo "Встановіть Composer: https://getcomposer.org/"
        exit 1
    fi
fi

echo -e "${GREEN}✓ Composer знайдено: $COMPOSER_CMD${NC}"

# Перевірка наявності PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}Помилка: PHP не встановлено!${NC}"
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
echo -e "${GREEN}✓ PHP версія: $PHP_VERSION${NC}"

# 1. Створення .env файлу
echo -e "${YELLOW}📝 Створення .env файлу...${NC}"
if [ -f ".env.example" ]; then
    cp .env.example .env
    echo -e "${GREEN}✓ .env створено з .env.example${NC}"
else
    # Створюємо базовий .env
    cat > .env << EOF
APP_NAME="ProStar Travel"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=

DB_CONNECTION=$DB_TYPE
EOF
    echo -e "${GREEN}✓ Базовий .env створено${NC}"
fi

# Перевірка, чи файл створено
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ Помилка: Не вдалося створити .env файл!${NC}"
    exit 1
fi

# 2. Налаштування .env
echo -e "${YELLOW}⚙️  Налаштування .env...${NC}"

# Визначаємо команду sed (для Linux та macOS)
if [[ "$OSTYPE" == "darwin"* ]]; then
    SED_CMD="sed -i ''"
else
    SED_CMD="sed -i"
fi

# Оновлення бази даних в .env ПЕРЕД генерацією ключа
if [ "$DB_TYPE" = "mysql" ]; then
    $SED_CMD "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|g" .env
    $SED_CMD "s|DB_HOST=.*|DB_HOST=$DB_HOST|g" .env
    $SED_CMD "s|DB_PORT=.*|DB_PORT=$DB_PORT|g" .env
    $SED_CMD "s|DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|g" .env
    $SED_CMD "s|DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|g" .env
    $SED_CMD "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|g" .env
    # Додаємо рядки, якщо їх немає
    grep -q "^DB_HOST=" .env || echo "DB_HOST=$DB_HOST" >> .env
    grep -q "^DB_PORT=" .env || echo "DB_PORT=$DB_PORT" >> .env
    grep -q "^DB_DATABASE=" .env || echo "DB_DATABASE=$DB_DATABASE" >> .env
    grep -q "^DB_USERNAME=" .env || echo "DB_USERNAME=$DB_USERNAME" >> .env
    grep -q "^DB_PASSWORD=" .env || echo "DB_PASSWORD=$DB_PASSWORD" >> .env
else
    # SQLite
    $SED_CMD "s|DB_CONNECTION=.*|DB_CONNECTION=sqlite|g" .env
    $SED_CMD "s|DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|g" .env
    grep -q "^DB_DATABASE=" .env || echo "DB_DATABASE=$DB_DATABASE" >> .env
    # Створюємо базу даних якщо не існує
    mkdir -p database
    if [ ! -f "database/database.sqlite" ]; then
        touch database/database.sqlite
        chmod 664 database/database.sqlite
    fi
fi

# Генерація APP_KEY (після налаштування БД)
echo -e "${YELLOW}🔑 Генерація APP_KEY...${NC}"
if php artisan key:generate --force 2>&1; then
    echo -e "${GREEN}✓ APP_KEY згенеровано${NC}"
else
    echo -e "${YELLOW}⚠️  Помилка генерації ключа, спробуємо вручну...${NC}"
    # Генеруємо ключ вручну
    APP_KEY=$(php artisan key:generate --show 2>/dev/null || php -r "echo 'base64:'.base64_encode(random_bytes(32));")
    $SED_CMD "s|APP_KEY=.*|APP_KEY=$APP_KEY|g" .env
    echo -e "${GREEN}✓ APP_KEY встановлено${NC}"
fi

echo -e "${GREEN}✓ .env файл налаштовано${NC}"

# 3. Встановлення Composer залежностей
echo -e "${YELLOW}📦 Встановлення Composer залежностей...${NC}"

# Перевірка наявності fileinfo розширення
if php -m | grep -q fileinfo; then
    echo -e "${GREEN}✓ PHP розширення fileinfo встановлено${NC}"
    $COMPOSER_CMD install --no-dev --optimize-autoloader --no-interaction
else
    echo -e "${RED}⚠️  УВАГА: PHP розширення fileinfo не знайдено!${NC}"
    echo -e "${YELLOW}   Це розширення обов'язкове для роботи Filament!${NC}"
    echo -e "${YELLOW}   Встановлюємо залежності з ігноруванням, але потрібно встановити fileinfo!${NC}"
    echo ""
    echo -e "${YELLOW}   Як встановити fileinfo:${NC}"
    echo -e "${YELLOW}   1. Зайдіть в cPanel -> Select PHP Version${NC}"
    echo -e "${YELLOW}   2. Знайдіть 'fileinfo' та увімкніть його${NC}"
    echo -e "${YELLOW}   3. Збережіть зміни та перезапустіть PHP-FPM${NC}"
    echo ""
    $COMPOSER_CMD install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-fileinfo || {
        echo -e "${YELLOW}⚠️  Спробуємо composer update...${NC}"
        $COMPOSER_CMD update --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-fileinfo
    }
fi
echo -e "${GREEN}✓ Composer залежності встановлено${NC}"

# 4. Налаштування прав доступу
echo -e "${YELLOW}🔐 Налаштування прав доступу...${NC}"
chmod -R 775 storage bootstrap/cache
if [ -f "database/database.sqlite" ]; then
    chmod 664 database/database.sqlite
fi
echo -e "${GREEN}✓ Права доступу налаштовано${NC}"

# 5. Створення символічного посилання для storage
echo -e "${YELLOW}🔗 Створення символічного посилання для storage...${NC}"

# Оскільки публічні файли в корені, а не в laravel/public,
# потрібно створити посилання в корені проекту
cd ..
if [ ! -L "storage" ] && [ ! -d "storage" ]; then
    ln -s laravel/storage/app/public storage
    echo -e "${GREEN}✓ Символічне посилання створено в корені: storage -> laravel/storage/app/public${NC}"
elif [ -L "storage" ]; then
    echo -e "${GREEN}✓ Символічне посилання вже існує${NC}"
else
    echo -e "${YELLOW}⚠️  Папка storage вже існує (не посилання)${NC}"
    echo -e "${YELLOW}   Видаляємо стару папку та створюємо посилання...${NC}"
    rm -rf storage
    ln -s laravel/storage/app/public storage
    echo -e "${GREEN}✓ Символічне посилання створено${NC}"
fi

# Також створюємо посилання в laravel/public для сумісності
cd laravel
if [ ! -L "public/storage" ] && [ ! -d "public/storage" ]; then
    php artisan storage:link || echo -e "${YELLOW}⚠️  Не вдалося створити посилання в laravel/public${NC}"
fi
cd ..

echo -e "${GREEN}✓ Символічні посилання налаштовано${NC}"

# 6. Запуск міграцій
echo -e "${YELLOW}🗄️  Запуск міграцій бази даних...${NC}"
cd laravel
php artisan migrate:fresh --force
echo -e "${GREEN}✓ Міграції виконано${NC}"

# 7. Імпорт даних з бази даних (якщо є)
echo -e "${YELLOW}📥 Пошук бази даних для імпорту...${NC}"

# Шукаємо базу даних в різних місцях
SQLITE_DB=""
if [ -f "../database.sqlite" ]; then
    SQLITE_DB="../database.sqlite"
elif [ -f "database/database.sqlite" ]; then
    SQLITE_DB="database/database.sqlite"
elif [ -f "../laravel/database/database.sqlite" ]; then
    SQLITE_DB="../laravel/database/database.sqlite"
fi

if [ -n "$SQLITE_DB" ]; then
    echo -e "${GREEN}✓ Знайдено базу даних: $SQLITE_DB${NC}"
    
    if [ "$DB_TYPE" = "sqlite" ]; then
        # Копіюємо базу даних
        cp "$SQLITE_DB" database/database.sqlite
        chmod 664 database/database.sqlite
        echo -e "${GREEN}✓ База даних скопійована${NC}"
    else
        # Для MySQL використовуємо скрипт імпорту
        if [ -f "../import_data.php" ]; then
            echo -e "${YELLOW}🔄 Імпорт даних з SQLite в MySQL...${NC}"
            
            # Копіюємо базу в корінь для скрипта імпорту (якщо її там немає)
            if [ "$SQLITE_DB" != "../database.sqlite" ]; then
                cp "$SQLITE_DB" ../database.sqlite
                echo -e "${GREEN}✓ База даних скопійована в корінь для імпорту${NC}"
            fi
            
            cd ..
            php import_data.php
            cd laravel
            echo -e "${GREEN}✓ Дані імпортовано в MySQL${NC}"
        else
            echo -e "${YELLOW}⚠️  Скрипт імпорту не знайдено, пропускаємо імпорт${NC}"
        fi
    fi
else
    echo -e "${YELLOW}ℹ️  База даних для імпорту не знайдена${NC}"
    echo "   Перевірені місця:"
    echo "   - ../database.sqlite"
    echo "   - database/database.sqlite"
    echo "   - ../laravel/database/database.sqlite"
fi

# 8. Створення адміністратора
echo -e "${YELLOW}👤 Створення адміністратора...${NC}"

# Створюємо тимчасовий seeder
cat > database/seeders/TempAdminSeeder.php << EOF
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TempAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Видаляємо старого адміна якщо існує
        User::where('email', '$ADMIN_EMAIL')->delete();
        
        // Створюємо нового адміна
        User::create([
            'name' => 'Admin',
            'email' => '$ADMIN_EMAIL',
            'password' => Hash::make('$ADMIN_PASSWORD'),
            'email_verified_at' => now(),
        ]);
    }
}
EOF

php artisan db:seed --class=TempAdminSeeder
rm -f database/seeders/TempAdminSeeder.php

echo -e "${GREEN}✓ Адміністратор створено${NC}"

# 9. Очищення та кешування
echo -e "${YELLOW}🧹 Очищення та кешування...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${GREEN}✓ Кеш оптимізовано${NC}"

cd ..

# ============================================
# ЗАВЕРШЕННЯ
# ============================================
echo ""
echo -e "${GREEN}"
echo "════════════════════════════════════════════════════════"
echo "✅ Встановлення успішно завершено!"
echo "════════════════════════════════════════════════════════"
echo -e "${NC}"
echo ""

echo -e "${YELLOW}📋 Наступні кроки:${NC}"
echo ""
echo "1. Document Root вже вказує на корінь проекту"
echo "   (файли index.php, css, js, images в корені)"
echo ""
echo "2. Налаштуйте APP_URL в файлі laravel/.env:"
echo "   APP_URL=http://ваш-домен.com"
echo "   або"
echo "   APP_URL=https://ваш-домен.com"
echo ""
echo "3. Переконайтеся, що PHP розширення fileinfo увімкнено"
echo "   (через cPanel -> Select PHP Version)"
echo ""
echo "4. Відкрийте адмін-панель:"
echo "   http://ваш-домен.com/admin"
echo ""
echo "5. Дані для входу:"
echo -e "   ${GREEN}Email:${NC} $ADMIN_EMAIL"
echo -e "   ${GREEN}Password:${NC} $ADMIN_PASSWORD"
echo ""
echo -e "${GREEN}🎉 Проект готовий до роботи!${NC}"
echo ""
