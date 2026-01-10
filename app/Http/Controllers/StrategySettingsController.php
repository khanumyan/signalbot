<?php

namespace App\Http\Controllers;

use App\Models\UserStrategySetting;
use App\Services\CryptoAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

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

        return view('strategy-settings.index', compact('settings', 'strategies'));
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
}
