<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelSchemeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'parent_id',
        'place_number',
        'is_parent',
        'room_number',
        'meals',
        'price',
        'first_name',
        'last_name',
        'phone',
        'telegram',
        'advance',
        'balance',
        'has_transfer_there',
        'has_transfer_back',
        'info',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'advance' => 'decimal:2',
        'balance' => 'decimal:2',
        'sort_order' => 'integer',
        'place_number' => 'integer',
        'is_parent' => 'boolean',
        'has_transfer_there' => 'boolean',
        'has_transfer_back' => 'boolean',
    ];

    /**
     * Отримати категорію
     */
    public function category()
    {
        return $this->belongsTo(HotelSchemeCategory::class, 'category_id');
    }

    /**
     * Отримати батьківський номер
     */
    public function parent()
    {
        return $this->belongsTo(HotelSchemeItem::class, 'parent_id');
    }

    /**
     * Отримати дочірні місця (якщо це батьківський номер)
     */
    public function places()
    {
        return $this->hasMany(HotelSchemeItem::class, 'parent_id')->orderBy('place_number');
    }

    /**
     * Отримати назву харчування
     */
    public function getMealsLabelAttribute(): string
    {
        return match ($this->meals) {
            'breakfast' => 'Сніданки',
            'breakfast_dinner' => 'Сніданок + вечеря',
            'no_meals' => 'Без харчування',
            default => 'Без харчування',
        };
    }
}
