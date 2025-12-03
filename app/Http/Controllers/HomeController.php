<?php

namespace App\Http\Controllers;

use App\Models\CryptoSignal;
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
        return view('welcome');
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
