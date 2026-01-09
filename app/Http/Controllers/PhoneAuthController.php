<?php

namespace App\Http\Controllers;

use App\Models\PhoneVerification;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PhoneAuthController extends Controller
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Show phone verification form
     */
    public function showPhoneForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        
        $botUsername = $this->telegramService->getBotUsername();
        
        return view('auth.phone', compact('botUsername'));
    }

    /**
     * Handle phone submission and create verification record
     */
    public function submitPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
        ], [
            'phone.required' => 'Пожалуйста, введите номер телефона',
            'phone.regex' => 'Неверный формат номера телефона. Используйте формат: +1234567890',
        ]);

        $phone = $request->phone;

        // Check if user with this phone already exists
        $existingUser = User::where('phone', $phone)->first();
        if ($existingUser) {
            throw ValidationException::withMessages([
                'phone' => 'Пользователь с этим номером телефона уже зарегистрирован',
            ]);
        }

        // Generate verification record
        $verification = PhoneVerification::generate($phone);

        $botUsername = $this->telegramService->getBotUsername();
        $telegramUrl = "https://t.me/{$botUsername}?start={$verification->token}";

        return view('auth.phone-verify', [
            'phone' => $phone,
            'verification_code' => $verification->verification_code,
            'token' => $verification->token,
            'telegram_url' => $telegramUrl,
            'bot_username' => $botUsername,
        ]);
    }

    /**
     * Check verification status (AJAX endpoint)
     */
    public function checkVerification(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $verification = PhoneVerification::where('token', $request->token)
            ->where('verified', true)
            ->first();

        if ($verification) {
            // Create or update user
            $user = User::firstOrCreate(
                ['phone' => $verification->phone],
                [
                    'name' => 'User ' . substr($verification->phone, -4),
                    'email' => 'user_' . $verification->phone . '@telegram.local',
                    'password' => bcrypt(str()->random(32)), // Random password, not used for Telegram auth
                    'telegram_chat_id' => $verification->telegram_chat_id,
                ]
            );

            // Update telegram_chat_id if changed
            if ($user->telegram_chat_id !== $verification->telegram_chat_id) {
                $user->update(['telegram_chat_id' => $verification->telegram_chat_id]);
            }

            // Login user
            Auth::login($user, true);

            // Delete verification record
            $verification->delete();

            return response()->json([
                'verified' => true,
                'redirect' => route('home'),
            ]);
        }

        return response()->json([
            'verified' => false,
        ]);
    }
}
