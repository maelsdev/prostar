<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\Tour;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $page = Page::getBySlug('home');
        
        // Отримати всі тури, які ще не закінчилися, відсортовані за датою старту (максимум 10)
        // Завантажуємо зв'язок mainImage для оптимізації
        $tours = Tour::where('end_date', '>=', Carbon::today())
            ->with('mainImage')
            ->orderBy('start_date', 'asc')
            ->limit(10)
            ->get();
        
        return view('home', compact('page', 'tours'));
    }
}
