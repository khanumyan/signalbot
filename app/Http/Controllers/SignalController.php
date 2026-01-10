<?php

namespace App\Http\Controllers;

use App\Models\CryptoSignal;
use App\Models\Subscription;
use App\Models\Product;
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
        
        $user = auth()->user();
        $hasActiveSubscription = false;
        $hasFreeTrialUsed = false;
        
        if ($user) {
            // Проверяем наличие активной подписки для signals
            // product_id = 2 - полная подписка (для signals и strategy-settings)
            // product_id = 4 - free trial для signals
            // product_id = 5 - free trial для strategy-settings (также дает доступ к signals)
            // Если у пользователя есть активная подписка с product_id = 2, 4 или 5, он видит все сигналы
            $hasActiveSubscription = Subscription::where('user_id', $user->id)
                ->whereIn('product_id', [2, 4, 5])
                ->where('status', 'active')
                ->where('date_from', '<=', now())
                ->where('date_to', '>=', now())
                ->exists();
            
            // Проверяем, использовал ли пользователь free trial
            // Если была подписка с product_id = 4 (free trial для signals) или product_id = 5 (free trial для strategy-settings) в любом статусе,
            // то показываем только кнопку "Купить подписку"
            $hasFreeTrialUsed = Subscription::where('user_id', $user->id)
                ->whereIn('product_id', [4, 5])
                ->exists();
        }
        
        $query = CryptoSignal::query();
        
        // Apply date filter first
        $query = $this->applyDateFilter($query, $filter, $hasActiveSubscription);
        
        // Если нет активной подписки
        if (!$hasActiveSubscription) {
            // Для фильтров "week", "month", "all" показываем только сигналы со статусом (status IS NOT NULL)
            // Это позволяет пользователям видеть сигналы со статусами и мотивирует купить подписку
            if (in_array($filter, ['week', 'month', 'all'])) {
                $query->whereNotNull('status');
            }
            
            // Исключаем сигналы за сегодня и вчера для всех фильтров
            $yesterday = Carbon::now()->subDay()->startOfDay();
            $query->where(function($q) use ($yesterday) {
                $q->whereDate('signal_time', '<', $yesterday);
            });
        }
        
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
                        'status' => $signal->status,
                    ];
                }),
                'hasMore' => ($page * $perPage) < $total,
                'currentPage' => $page,
                'total' => $total,
                'hasActiveSubscription' => $hasActiveSubscription,
                'hasFreeTrialUsed' => $hasFreeTrialUsed,
            ]);
        }
        
        return view('signals.index', [
            'signals' => $signals,
            'filter' => $filter,
            'hasMore' => ($page * $perPage) < $total,
            'total' => $total,
            'hasActiveSubscription' => $hasActiveSubscription,
            'hasFreeTrialUsed' => $hasFreeTrialUsed,
        ]);
    }

    /**
     * Apply date filter to query
     */
    private function applyDateFilter($query, $filter, $hasActiveSubscription)
    {
        $now = Carbon::now();
        
        switch ($filter) {
            case 'today':
                if ($hasActiveSubscription) {
                    $query->whereDate('signal_time', $now->toDateString());
                } else {
                    // Для пользователей без подписки этот фильтр не покажет сигналы
                    $query->whereRaw('1 = 0'); // Пустой результат
                }
                break;
                
            case 'yesterday':
                if ($hasActiveSubscription) {
                    $query->whereDate('signal_time', $now->copy()->subDay()->toDateString());
                } else {
                    // Для пользователей без подписки этот фильтр не покажет сигналы
                    $query->whereRaw('1 = 0'); // Пустой результат
                }
                break;
                
            case 'week':
                $weekAgo = $now->copy()->subWeek()->startOfDay();
                $query->where('signal_time', '>=', $weekAgo);
                // Сегодня и вчера будут исключены после, если нет подписки
                break;
                
            case 'month':
                $monthAgo = $now->copy()->subMonth()->startOfDay();
                $query->where('signal_time', '>=', $monthAgo);
                // Сегодня и вчера будут исключены после, если нет подписки
                break;
                
            case 'all':
            default:
                // No filter, show all (сегодня и вчера будут исключены после, если нет подписки)
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

    /**
     * Start free trial subscription
     */
    public function startFreeTrial(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Необходима авторизация'
            ], 401);
        }

        // Проверяем, использовал ли пользователь уже free trial
        $hasFreeTrialUsed = Subscription::where('user_id', $user->id)
            ->where('product_id', 4)
            ->exists();

        if ($hasFreeTrialUsed) {
            return response()->json([
                'success' => false,
                'error' => 'Бесплатный пробный период уже был использован'
            ], 400);
        }

        try {
            // Получаем продукт free trial (product_id = 4)
            $product = Product::find(4);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'error' => 'Продукт для бесплатного пробного периода не найден'
                ], 404);
            }

            // Используем duration из продукта (в днях)
            $duration = $product->duration ?? 3; // По умолчанию 3 дня, если duration не указан
            
            // Создаем free trial подписку
            $dateFrom = Carbon::now();
            $dateTo = Carbon::now()->addDays($duration);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'product_id' => 4,
                'status' => 'active',
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Бесплатный пробный период активирован на ' . $duration . ' ' . ($duration == 1 ? 'день' : ($duration < 5 ? 'дня' : 'дней')) . '!',
                'subscription' => [
                    'id' => $subscription->id,
                    'date_from' => $subscription->date_from_formatted,
                    'date_to' => $subscription->date_to_formatted,
                    'duration' => $duration,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при создании подписки: ' . $e->getMessage()
            ], 500);
        }
    }
}

