<?php

namespace App\Http\Controllers;

use App\Models\UserWallet;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Display user profile page (Личный кабинет)
     */
    public function index()
    {
        $user = Auth::user();
        
        return view('profile.index', compact('user'));
    }

    /**
     * Get or create user wallet
     */
    public function getWallet()
    {
        $user = Auth::user();
        
        // Получаем или создаем кошелек
        $wallet = $user->wallet;
        
        if (!$wallet) {
            $wallet = UserWallet::create([
                'user_id' => $user->id,
                'amount' => 0.00,
                'currency' => 'USD',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'wallet' => [
                'amount' => $wallet->amount,
                'currency' => $wallet->currency,
            ]
        ]);
    }

    /**
     * Get referral link for user
     */
    public function getReferralLink()
    {
        $user = Auth::user();
        
        if (!$user->share_referal_code) {
            return response()->json([
                'success' => false,
                'message' => 'Реферальный код не найден'
            ], 404);
        }
        
        $appUrl = config('app.url');
        $referralLink = $appUrl . '?referrelCode=' . $user->share_referal_code;
        
        return response()->json([
            'success' => true,
            'referral_link' => $referralLink,
            'referral_code' => $user->share_referal_code,
        ]);
    }

    /**
     * Get user subscriptions
     */
    public function getSubscriptions()
    {
        $user = Auth::user();
        
        $subscriptions = Subscription::where('user_id', $user->id)
            ->with('product')
            ->orderBy('date_from', 'desc')
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'product_name' => $subscription->product ? $subscription->product->name : 'Неизвестный продукт',
                    'status' => $subscription->status,
                    'date_from' => $subscription->date_from->format('d.m.Y'),
                    'date_to' => $subscription->date_to->format('d.m.Y'),
                ];
            });
        
        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions,
        ]);
    }
}




