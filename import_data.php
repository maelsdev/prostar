<?php

/**
 * Скрипт для імпорту даних з SQLite бази даних в MySQL
 * 
 * Використання: php import_data.php
 * 
 * Скрипт автоматично:
 * - Підключається до SQLite бази даних (database.sqlite)
 * - Підключається до MySQL через Laravel .env
 * - Імпортує всі дані з усіх таблиць (крім migrations та users)
 * - Пропускає записи, які вже існують
 */

require __DIR__ . '/laravel/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Завантажуємо Laravel
$app = require_once __DIR__ . '/laravel/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Шлях до SQLite бази даних (перевіряємо кілька можливих місць)
$possiblePaths = [
    __DIR__ . '/database.sqlite',
    __DIR__ . '/laravel/database/database.sqlite',
    __DIR__ . '/../database.sqlite',
    __DIR__ . '/../laravel/database/database.sqlite',
];

$sqlitePath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        // Перевіряємо, чи є в базі дані (не тільки системні таблиці)
        try {
            $testDb = new PDO("sqlite:$path");
            $tables = $testDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT IN ('migrations', 'users', 'password_reset_tokens', 'failed_jobs', 'personal_access_tokens')")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($tables)) {
                $sqlitePath = $path;
                break;
            }
        } catch (Exception $e) {
            // Продовжуємо пошук
        }
    }
}

if (!$sqlitePath) {
    echo "❌ Помилка: Файл database.sqlite з даними не знайдено!\n";
    echo "Перевірені шляхи:\n";
    foreach ($possiblePaths as $path) {
        $exists = file_exists($path) ? "✅ існує" : "❌ не існує";
        echo "  - $path ($exists)\n";
    }
    exit(1);
}

echo "📁 Використовується база: $sqlitePath\n";

echo "════════════════════════════════════════════════════════\n";
echo "  Імпорт даних з SQLite в MySQL\n";
echo "════════════════════════════════════════════════════════\n\n";

// Підключення до SQLite
try {
    $sqlite = new PDO("sqlite:$sqlitePath");
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Підключено до SQLite бази даних\n";
} catch (PDOException $e) {
    echo "❌ Помилка підключення до SQLite: " . $e->getMessage() . "\n";
    exit(1);
}

// Перевірка підключення до MySQL
try {
    DB::connection()->getPdo();
    echo "✅ Підключено до MySQL бази даних\n";
    echo "   База: " . config('database.connections.mysql.database') . "\n\n";
} catch (Exception $e) {
    echo "❌ Помилка підключення до MySQL: " . $e->getMessage() . "\n";
    echo "Перевірте налаштування в laravel/.env\n";
    exit(1);
}

// Отримуємо список таблиць з SQLite
$tables = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

// Таблиці, які пропускаємо
$skipTables = ['migrations', 'users'];

echo "📋 Знайдено таблиць: " . count($tables) . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$totalImported = 0;
$totalSkipped = 0;

foreach ($tables as $table) {
    // Пропускаємо системні таблиці
    if (in_array($table, $skipTables)) {
        echo "⏭️  Пропущено таблицю: $table\n";
        continue;
    }
    
    // Перевіряємо, чи існує таблиця в MySQL
    if (!Schema::hasTable($table)) {
        echo "⚠️  Таблиця $table не існує в MySQL, пропускаємо\n";
        continue;
    }
    
    echo "📥 Імпорт таблиці: $table\n";
    
    try {
        // Отримуємо всі дані з SQLite таблиці
        $rows = $sqlite->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            echo "   ℹ️  Таблиця порожня\n\n";
            continue;
        }
        
        $imported = 0;
        $skipped = 0;
        
        // Отримуємо первинний ключ таблиці
        $primaryKey = null;
        try {
            $tableInfo = $sqlite->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tableInfo as $column) {
                if ($column['pk'] == 1) {
                    $primaryKey = $column['name'];
                    break;
                }
            }
        } catch (Exception $e) {
            // Якщо не вдалося визначити первинний ключ, продовжуємо
        }
        
        // Імпортуємо дані
        foreach ($rows as $row) {
            try {
                // Перевіряємо, чи запис вже існує (якщо є первинний ключ)
                if ($primaryKey && isset($row[$primaryKey])) {
                    $exists = DB::table($table)->where($primaryKey, $row[$primaryKey])->exists();
                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                }
                
                // Конвертуємо значення NULL
                foreach ($row as $key => $value) {
                    if ($value === null) {
                        $row[$key] = null;
                    }
                }
                
                // Вставляємо запис
                DB::table($table)->insert($row);
                $imported++;
            } catch (Exception $e) {
                // Якщо помилка через дублікат, пропускаємо
                if (strpos($e->getMessage(), 'Duplicate entry') !== false || 
                    strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                    $skipped++;
                } else {
                    echo "   ⚠️  Помилка при імпорті запису: " . $e->getMessage() . "\n";
                    $skipped++;
                }
            }
        }
        
        echo "   ✅ Імпортовано: $imported записів\n";
        if ($skipped > 0) {
            echo "   ⏭️  Пропущено: $skipped записів (вже існують)\n";
        }
        
        $totalImported += $imported;
        $totalSkipped += $skipped;
        
    } catch (Exception $e) {
        echo "   ❌ Помилка: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Імпорт завершено!\n";
echo "   📊 Всього імпортовано: $totalImported записів\n";
if ($totalSkipped > 0) {
    echo "   ⏭️  Всього пропущено: $totalSkipped записів\n";
}
echo "════════════════════════════════════════════════════════\n";
