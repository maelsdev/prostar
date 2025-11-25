<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'media_file_id',
        'sort_order',
    ];

    /**
     * Отримати тур
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Отримати файл зображення
     */
    public function mediaFile()
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }
}
