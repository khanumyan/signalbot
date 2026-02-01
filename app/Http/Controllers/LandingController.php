<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Display landing page
     */
    public function index(Request $request)
    {
        // If user is authenticated, redirect to dashboard
        if (auth()->check()) {
            return redirect()->route('home');
        }

        // Get referral code from query parameter
        $referrelCode = $request->query('referrelCode');
        
        return view('landing', compact('referrelCode'));
    }
}

