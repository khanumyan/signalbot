<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Show the registration form
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.register');
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms_accepted' => 'required|accepted',
        ], [
            'phone.required' => 'Пожалуйста, введите номер телефона',
            'phone.regex' => 'Неверный формат номера телефона. Используйте формат: +1234567890',
            'phone.unique' => 'Пользователь с этим номером телефона уже зарегистрирован',
            'terms_accepted.required' => 'Необходимо ознакомиться с условиями использования',
            'terms_accepted.accepted' => 'Необходимо принять условия использования',
        ]);

        // Generate unique verification token (8 digits with 'w' prefix)
        do {
            $token = 'w' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        } while (User::where('verification_token', $token)->exists());

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'verification_token' => $token,
        ]);

        // Show verification page with bot link
        // Format: w48135207--w98429842 (static part--dynamic token)
        $staticToken = 'w48135207';
        $botUsername = $this->telegramService->getBotUsername();
        $telegramUrl = "https://t.me/{$botUsername}?start={$staticToken}--{$token}";

        return view('auth.register-verify', [
            'user' => $user,
            'telegram_url' => $telegramUrl,
            'bot_username' => $botUsername,
        ]);
    }

    /**
     * Check if user verification is complete (AJAX endpoint)
     */
    public function checkVerification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if ($user && $user->telegram_chat_id && !$user->verification_token) {
            // User is verified, login and redirect
            Auth::login($user, true);

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
