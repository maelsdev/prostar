<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'telegram_username',
        'sort_order',
    ];

    /**
     * Отримати тури організатора
     */
    public function tours()
    {
        return $this->belongsToMany(Tour::class, 'organizer_tour')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
