<?php

namespace App\Http\Controllers;

use App\Models\UserStrategySetting;
use App\Models\Subscription;
use App\Models\Product;
use App\Services\CryptoAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class StrategySettingsController extends Controller
{
    /**
     * Get user identifier (session ID or user ID if authenticated)
     */
    private function getUserIdentifier(Request $request): string
    {
        // Use authenticated user ID if available
        if (auth()->check()) {
            return 'user_' . auth()->id();
        }
        
        // Otherwise use session ID
        return 'session_' . Session::getId();
    }

    /**
     * Display settings page
     */
    public function index(Request $request)
    {
        $userIdentifier = $this->getUserIdentifier($request);
        
        $user = auth()->user();
        $hasActiveSubscription = false;
        $hasFreeTrialUsed = false;
        
        if ($user) {
            // Проверяем наличие активной подписки для strategy-settings (product_id = 2 или 5)
            // product_id = 2 - полная подписка, product_id = 5 - free trial
            $hasActiveSubscription = Subscription::where('user_id', $user->id)
                ->whereIn('product_id', [2, 5])
                ->where('status', 'active')
                ->where('date_from', '<=', now())
                ->where('date_to', '>=', now())
                ->exists();
            
            // Проверяем, использовал ли пользователь free trial для strategy-settings (product_id = 5)
            $hasFreeTrialUsed = Subscription::where('user_id', $user->id)
                ->where('product_id', 5)
                ->exists();
        }
        
        $strategies = [
            'MTF' => 'Multi-TimeFrame Strategy',
            'EMA+RSI+MACD' => 'EMA + RSI + MACD Strategy',
            'Bollinger+RSI' => 'Bollinger Bands + RSI Strategy',
            'EMA+Stochastic' => 'EMA + Stochastic Strategy',
            'SuperTrend+VWAP' => 'SuperTrend + VWAP Strategy',
            'Ichimoku+RSI' => 'Ichimoku + RSI Strategy',
        ];

        $settings = [];
        foreach ($strategies as $strategyName => $strategyTitle) {
            $setting = UserStrategySetting::getOrCreate($strategyName, $userIdentifier);
            $settings[$strategyName] = [
                'id' => $setting->id,
                'title' => $strategyTitle,
                'is_active' => $setting->is_active,
                'parameters' => $setting->parameters,
                'defaults' => UserStrategySetting::getDefaultParameters($strategyName)
            ];
        }

        return view('strategy-settings.index', compact('settings', 'strategies', 'hasActiveSubscription', 'hasFreeTrialUsed'));
    }

    /**
     * Update strategy settings
     */
    public function update(Request $request, string $strategyName): JsonResponse
    {
        $userIdentifier = $this->getUserIdentifier($request);
        
        $request->validate([
            'is_active' => 'boolean',
            'parameters' => 'array'
        ]);

        $setting = UserStrategySetting::getOrCreate($strategyName, $userIdentifier);
        
        if ($request->has('is_active')) {
            $setting->is_active = $request->boolean('is_active');
        }
        
        if ($request->has('parameters')) {
            // Merge with existing parameters to preserve defaults
            $existingParams = $setting->parameters ?? [];
            $newParams = $request->input('parameters', []);
            $setting->parameters = array_merge($existingParams, $newParams);
        }
        
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Настройки успешно обновлены',
            'setting' => $setting
        ]);
    }

    /**
     * Reset strategy to defaults
     */
    public function reset(Request $request, string $strategyName): JsonResponse
    {
        $userIdentifier = $this->getUserIdentifier($request);
        
        $setting = UserStrategySetting::getOrCreate($strategyName, $userIdentifier);
        $setting->parameters = UserStrategySetting::getDefaultParameters($strategyName);
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Настройки сброшены к значениям по умолчанию',
            'setting' => $setting
        ]);
    }

    /**
     * Get settings for a specific strategy (API)
     */
    public function get(Request $request, string $strategyName): JsonResponse
    {
        $userIdentifier = $this->getUserIdentifier($request);
        $setting = UserStrategySetting::getOrCreate($strategyName, $userIdentifier);
        
        return response()->json([
            'success' => true,
            'setting' => $setting
        ]);
    }

    /**
     * Analyze cryptocurrency with selected strategy
     */
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'strategy' => 'required|string',
            'symbol' => 'required|string|max:10',
            'parameters' => 'array'
        ]);

        try {
            $strategy = $request->input('strategy');
            $symbol = strtoupper($request->input('symbol'));
            $params = $request->input('parameters', []);

            // Get user settings or use defaults
            $userIdentifier = $this->getUserIdentifier($request);
            $setting = UserStrategySetting::getOrCreate($strategy, $userIdentifier);
            $userParams = $setting->parameters ?? [];
            
            // Merge user params with provided params
            $finalParams = array_merge($userParams, $params);

            $analysisService = new CryptoAnalysisService();

            // Analyze based on strategy
            $result = match($strategy) {
                'MTF' => $analysisService->analyzeMTF($symbol, $finalParams),
                'EMA+RSI+MACD' => $analysisService->analyzeEmaRsiMacd($symbol, $finalParams),
                'Bollinger+RSI' => $analysisService->analyzeBollingerRsi($symbol, $finalParams),
                'EMA+Stochastic' => $analysisService->analyzeEmaStochastic($symbol, $finalParams),
                'SuperTrend+VWAP' => $analysisService->analyzeSuperTrendVwap($symbol, $finalParams),
                'Ichimoku+RSI' => $analysisService->analyzeIchimokuRsi($symbol, $finalParams),
                default => throw new \Exception("Стратегия {$strategy} пока не поддерживается для анализа")
            };

            return response()->json([
                'success' => true,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start free trial subscription for strategy-settings (product_id = 5)
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

        // Проверяем, использовал ли пользователь уже free trial для strategy-settings
        $hasFreeTrialUsed = Subscription::where('user_id', $user->id)
            ->where('product_id', 5)
            ->exists();

        if ($hasFreeTrialUsed) {
            return response()->json([
                'success' => false,
                'error' => 'Бесплатный пробный период уже был использован'
            ], 400);
        }

        try {
            // Получаем продукт free trial для strategy-settings (product_id = 5)
            $product = Product::find(5);
            
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
                'product_id' => 5,
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

    /**
     * Buy full subscription for strategy-settings (product_id = 2)
     */
    public function buySubscription(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Необходима авторизация'
            ], 401);
        }

        try {
            // Получаем продукт полной подписки (product_id = 2)
            $product = Product::find(2);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'error' => 'Продукт для полной подписки не найден'
                ], 404);
            }

            // Используем duration из продукта (в днях)
            $duration = $product->duration ?? 30; // По умолчанию 30 дней, если duration не указан
            
            // Создаем полную подписку
            $dateFrom = Carbon::now();
            $dateTo = Carbon::now()->addDays($duration);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'product_id' => 2,
                'status' => 'active',
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Подписка активирована на ' . $duration . ' ' . ($duration == 1 ? 'день' : ($duration < 5 ? 'дня' : 'дней')) . '!',
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
