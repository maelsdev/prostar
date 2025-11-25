<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'scheme_image_id',
        'scheme_description',
    ];

    /**
     * Отримати всі номери готелю
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Отримати всі тури, що використовують цей готель
     */
    public function tours()
    {
        return $this->hasMany(Tour::class);
    }

    /**
     * Отримати зображення схеми готелю
     */
    public function schemeImage()
    {
        return $this->belongsTo(MediaFile::class, 'scheme_image_id');
    }

    /**
     * Отримати категорії схеми готелю
     */
    public function schemeCategories()
    {
        return $this->hasMany(HotelSchemeCategory::class);
    }
}
