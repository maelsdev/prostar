<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_type',
        'bed_types',
        'rooms_count',
        'quantity',
        'meals',
        'is_hostel',
    ];

    protected $casts = [
        'bed_types' => 'array',
        'rooms_count' => 'integer',
        'quantity' => 'integer',
        'is_hostel' => 'boolean',
    ];

    /**
     * Отримати готель
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Отримати назву харчування
     */
    public function getMealsLabelAttribute(): string
    {
        return match($this->meals) {
            'breakfast' => 'Сніданки',
            'breakfast_dinner' => 'Сніданок + вечеря',
            'no_meals' => 'Без харчування',
            default => 'Без харчування',
        };
    }

    /**
     * Отримати кількість місць в одному номері
     * 1 односпальне ліжко = 1 місце
     * 1 двоспальне ліжко = 2 місця
     */
    public function getPlacesPerRoomAttribute(): int
    {
        $bedTypes = is_array($this->bed_types) ? $this->bed_types : json_decode($this->bed_types ?? '{}', true);
        
        if (!is_array($bedTypes)) {
            return 0;
        }
        
        $singleBeds = (int)($bedTypes['single'] ?? 0);
        $doubleBeds = (int)($bedTypes['double'] ?? 0);
        
        // 1 односпальне = 1 місце, 1 двоспальне = 2 місця
        return $singleBeds + ($doubleBeds * 2);
    }

    /**
     * Отримати загальну кількість місць (кількість місць в номері × кількість номерів)
     */
    public function getTotalPlacesAttribute(): int
    {
        $placesPerRoom = $this->places_per_room;
        $quantity = $this->quantity ?? 1;
        
        return $placesPerRoom * $quantity;
    }
}
