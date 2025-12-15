<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'crm_table_id',
        'room_id',
        'name',
        'price_type',
        'rooms_count',
        'sort_order',
    ];

    protected $casts = [
        'rooms_count' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Отримати CRM таблицю
     */
    public function crmTable()
    {
        return $this->belongsTo(CrmTable::class);
    }

    /**
     * Отримати номер (як категорію)
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Отримати всі записи категорії
     */
    public function items()
    {
        return $this->hasMany(CrmItem::class, 'crm_category_id')->orderBy('sort_order');
    }
}
