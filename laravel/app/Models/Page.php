<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'season',
        'h1',
        'description',
        'button_text',
        'button_action',
        'show_hotels_section',
        'show_activities_section',
        'show_about_section_after_tours',
        'show_contact_section',
    ];

    protected $casts = [
        'show_hotels_section' => 'boolean',
        'show_activities_section' => 'boolean',
        'show_about_section_after_tours' => 'boolean',
        'show_contact_section' => 'boolean',
    ];

    /**
     * Отримати сторінку за slug
     */
    public static function getBySlug(string $slug)
    {
        return static::where('slug', $slug)->first();
    }
}
