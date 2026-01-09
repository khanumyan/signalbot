<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Handle Telegram webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $update = $request->all();

            if (isset($update['message'])) {
                $message = $update['message'];
                $chatId = $message['chat']['id'];
                $text = $message['text'] ?? '';

                // Handle /start command with token
                if (str_starts_with($text, '/start')) {
                    $parts = explode(' ', $text, 2);
                    $token = $parts[1] ?? null;

                    if ($token) {
                        // Extract dynamic token from format: w48135207--w98429842
                        // Static part (w48135207) is ignored, we need the part after --
                        $tokenParts = explode('--', $token);
                        $dynamicToken = end($tokenParts); // Get last part after --
                        
                        // Handle user verification by token
                        $user = User::where('verification_token', $dynamicToken)
                            ->whereNull('telegram_chat_id')
                            ->first();

                        if ($user) {
                            // Update user with telegram_chat_id and clear token
                            $user->update([
                                'telegram_chat_id' => $chatId,
                                'verification_token' => null,
                            ]);
                            
                            Log::info("User verified via Telegram: user_id={$user->id}, chat_id={$chatId}");
                        }
                    }
                }
            }

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Setup bot menu with Web App button
     */
    public function setupMenu(): JsonResponse
    {
        try {
            $result = $this->telegramService->setupMenu([]);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Меню бота настроено успешно'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Не удалось настроить меню'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
