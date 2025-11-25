<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelSchemeCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_id',
        'name',
        'price_type',
        'meals',
        'rooms_count',
    ];

    protected $casts = [
        'rooms_count' => 'integer',
    ];

    /**
     * Отримати готель
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
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
        return $this->hasMany(HotelSchemeItem::class, 'category_id')->orderBy('sort_order');
    }
}
