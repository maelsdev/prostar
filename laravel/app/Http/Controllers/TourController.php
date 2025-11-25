<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tour;

class TourController extends Controller
{
    public function show($slug)
    {
        $tour = Tour::with(['mainImage', 'images.mediaFile'])
            ->where('slug', $slug)
            ->firstOrFail();
        
        return view('tour', compact('tour'));
    }
}
