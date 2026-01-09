<?php

namespace App\Http\Controllers;

use App\Models\CryptoSignal;
use App\Models\CryptoNews;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Display home page
     */
    public function index()
    {
        // Get latest 6 news
        $latestNews = CryptoNews::orderBy('pub_date', 'desc')
            ->limit(6)
            ->get();

        return view('welcome', compact('latestNews'));
    }

    /**
     * Get statistics for home page
     */
    public function stats(): JsonResponse
    {
        $total = CryptoSignal::count();
        $today = CryptoSignal::whereDate('signal_time', Carbon::today())->count();
        
        return response()->json([
            'success' => true,
            'total' => $total,
            'today' => $today
        ]);
    }
}
