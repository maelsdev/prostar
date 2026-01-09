<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourOrganizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'name',
        'phone',
        'telegram_username',
        'sort_order',
    ];

    /**
     * Отримати тур
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
