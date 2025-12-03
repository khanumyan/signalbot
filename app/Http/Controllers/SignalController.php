<?php

namespace App\Http\Controllers;

use App\Models\CryptoSignal;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class SignalController extends Controller
{
    /**
     * Display a listing of signals with filters
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $page = $request->get('page', 1);
        $perPage = 50;
        
        $query = CryptoSignal::query();
        
        // Apply date filter
        $query = $this->applyDateFilter($query, $filter);
        
        // Get total count for the filter
        $total = $query->count();
        
        // Get signals for current page
        $signals = $query->orderBy('signal_time', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        
        // If AJAX request or wants JSON, return JSON
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'signals' => $signals->map(function($signal) {
                    return [
                        'id' => $signal->id,
                        'symbol' => $signal->symbol,
                        'type' => $signal->type,
                        'strength' => $signal->strength,
                        'price' => $signal->price,
                        'strategy' => $signal->strategy,
                        'signal_time' => $signal->signal_time->toIso8601String(),
                    ];
                }),
                'hasMore' => ($page * $perPage) < $total,
                'currentPage' => $page,
                'total' => $total
            ]);
        }
        
        return view('signals.index', [
            'signals' => $signals,
            'filter' => $filter,
            'hasMore' => ($page * $perPage) < $total,
            'total' => $total
        ]);
    }

    /**
     * Apply date filter to query
     */
    private function applyDateFilter($query, $filter)
    {
        $now = Carbon::now();
        
        switch ($filter) {
            case 'today':
                $query->whereDate('signal_time', $now->toDateString());
                break;
                
            case 'yesterday':
                $query->whereDate('signal_time', $now->copy()->subDay()->toDateString());
                break;
                
            case 'week':
                $query->where('signal_time', '>=', $now->copy()->subWeek()->startOfDay());
                break;
                
            case 'month':
                $query->where('signal_time', '>=', $now->copy()->subMonth()->startOfDay());
                break;
                
            case 'all':
            default:
                // No filter, show all
                break;
        }
        
        return $query;
    }

    /**
     * Display the specified signal with TradingView chart
     */
    public function show($id)
    {
        $signal = CryptoSignal::findOrFail($id);
        
        // Convert symbol to TradingView format (e.g., BTC -> BINANCE:BTCUSDT)
        $tradingViewSymbol = 'BINANCE:' . $signal->symbol . 'USDT';
        
        return view('signals.show', compact('signal', 'tradingViewSymbol'));
    }
}

