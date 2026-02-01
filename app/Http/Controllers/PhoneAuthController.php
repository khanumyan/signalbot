<?php

namespace App\Http\Controllers;

use App\Models\PhoneVerification;
use App\Models\User;
use App\Models\UserWallet;
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
    public function showPhoneForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        
        // Save referral code to session if provided
        if ($request->has('referrelCode') && !empty($request->referrelCode)) {
            session(['referrelCode' => $request->referrelCode]);
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
            // Check if user already exists
            $user = User::where('phone', $verification->phone)->first();
            $isNewUser = !$user;

            if (!$user) {
                // Generate unique referral code
                do {
                    $referralCode = $this->generateReferralCode();
                } while (User::where('share_referal_code', $referralCode)->exists());

                // Find referrer by referral code if provided in session
                $whoReferred = null;
                $referrelCode = session('referrelCode');
                if ($referrelCode) {
                    $referrer = User::where('share_referal_code', $referrelCode)->first();
                    if ($referrer) {
                        $whoReferred = $referrer->id;
                    }
                    // Clear referral code from session after use
                    session()->forget('referrelCode');
                }

                // Create new user
                $user = User::create([
                    'phone' => $verification->phone,
                    'name' => 'User ' . substr($verification->phone, -4),
                    'email' => 'user_' . $verification->phone . '@telegram.local',
                    'password' => bcrypt(str()->random(32)), // Random password, not used for Telegram auth
                    'telegram_chat_id' => $verification->telegram_chat_id,
                    'share_referal_code' => $referralCode,
                    'who_referred' => $whoReferred,
                ]);

                // Create wallet for new user
                UserWallet::create([
                    'user_id' => $user->id,
                    'amount' => 0.00,
                    'currency' => 'USD',
                ]);
            } else {
                // Update telegram_chat_id if changed
                if ($user->telegram_chat_id !== $verification->telegram_chat_id) {
                    $user->update(['telegram_chat_id' => $verification->telegram_chat_id]);
                }
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

    /**
     * Generate unique referral code (12 characters: uppercase letters and numbers)
     * Format: WRDCe48DRvce (mixed case letters and numbers)
     */
    private function generateReferralCode(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $code = '';
        $length = 12;
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
}
