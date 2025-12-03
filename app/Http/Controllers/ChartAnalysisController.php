<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChartAnalysisController extends Controller
{
    /**
     * Display chart analysis page
     */
    public function index()
    {
        return view('chart-analysis.index');
    }

    /**
     * Analyze uploaded chart image
     */
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
        ]);

        try {
            $image = $request->file('image');
            $imageBase64 = base64_encode(file_get_contents($image->getRealPath()));
            $mimeType = $image->getMimeType();

            // Call Anthropic Claude API
            $apiKey = env('ANTHROPIC_API_KEY');
            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'error' => 'API ключ не настроен'
                ], 500);
            }

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json'
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 2000,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mimeType,
                                    'data' => $imageBase64
                                ]
                            ],
                            [
                                'type' => 'text',
                                'text' => 'Проанализируй криптовалютный график. Ответь ТОЛЬКО JSON: {"crypto":"пара","current_price":"цена","signal":"LONG/SHORT","signal_strength":"1-10","entry_price":"вход","stop_loss":"SL","take_profit_1":"TP1","take_profit_2":"TP2","take_profit_3":"TP3","leverage":"плечо","technical_analysis":"анализ","why_enter":"обоснование"}'
                            ]
                        ]
                    ]
                ]
            ]);

            if (!$response->successful()) {
                Log::error('Anthropic API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Ошибка API: ' . $response->status()
                ], 500);
            }

            $data = $response->json();
            $text = $data['content'][0]['text'] ?? '';

            // Clean JSON response
            $text = preg_replace('/```json\n?/', '', $text);
            $text = preg_replace('/```\n?/', '', $text);
            $text = trim($text);

            // Extract JSON
            preg_match('/\{[\s\S]*\}/', $text, $matches);
            if (empty($matches)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Не удалось извлечь JSON из ответа'
                ], 500);
            }

            $analysis = json_decode($matches[0], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ошибка парсинга JSON: ' . json_last_error_msg()
                ], 500);
            }

            return response()->json([
                'success' => true,
                'analysis' => $analysis
            ]);

        } catch (\Exception $e) {
            Log::error('Chart analysis error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Ошибка анализа: ' . $e->getMessage()
            ], 500);
        }
    }
}
