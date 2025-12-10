# Список міграцій для застосування на продакшені

## Нові міграції (версія 1.1)

Ці міграції додають функціонал схеми готелю та калькулятора туру:

### Готелі та номери
1. `2025_11_25_184412_create_hotels_table.php` - Створення таблиці готелів
2. `2025_11_25_184413_create_rooms_table.php` - Створення таблиці номерів
3. `2025_11_25_184428_add_hotel_id_to_tours_table.php` - Додавання hotel_id до турів
4. `2025_11_25_190012_add_scheme_to_hotels_table.php` - Додавання схеми до готелів
5. `2025_11_25_191501_add_is_hostel_to_rooms_table.php` - Позначка хостелу
6. `2025_11_25_202549_add_quantity_to_rooms_table.php` - Кількість номерів

### Схема готелю
7. `2025_11_25_192252_create_hotel_scheme_categories_table.php` - Категорії схеми
8. `2025_11_25_192253_create_hotel_scheme_items_table.php` - Елементи схеми
9. `2025_11_25_195519_add_meals_to_hotel_scheme_categories_table.php` - Харчування в категоріях
10. `2025_11_25_214815_add_phone_and_hierarchy_to_hotel_scheme_items_table.php` - Телефон та ієрархія
11. `2025_11_25_224135_add_telegram_to_hotel_scheme_items_table.php` - Telegram
12. `2025_11_25_224448_add_advance_and_balance_to_hotel_scheme_items_table.php` - Аванс та залишок
13. `2025_11_25_225126_add_transfer_fields_to_hotel_scheme_items_table.php` - Трансфери
14. `2025_11_25_225802_add_info_to_hotel_scheme_items_table.php` - Інформація

### Калькулятор туру
15. `2025_11_25_230404_add_transfer_prices_to_tours_table.php` - Ціни трансферів
16. `2025_11_25_230849_add_room_prices_to_tours_table.php` - Ціни номерів (JSON)
17. `2025_11_25_232646_add_margin_to_tours_table.php` - Маржа

## Команда для застосування

```bash
cd laravel
php artisan migrate --force
```

## Перевірка після застосування

```bash
# Перевірте статус міграцій
php artisan migrate:status

# Перевірте структуру таблиць
php artisan tinker
# В tinker:
# Schema::hasTable('hotels')
# Schema::hasTable('rooms')
# Schema::hasTable('hotel_scheme_categories')
# Schema::hasTable('hotel_scheme_items')
# exit
```

## Відкат (якщо потрібно)

```bash
# Відкат останніх 17 міграцій
php artisan migrate:rollback --step=17
```


