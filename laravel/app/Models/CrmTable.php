<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'hotel_id',
    ];

    /**
     * Отримати тур
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Отримати готель
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Отримати категорії CRM
     */
    public function categories()
    {
        return $this->hasMany(CrmCategory::class)->orderBy('sort_order');
    }
}
