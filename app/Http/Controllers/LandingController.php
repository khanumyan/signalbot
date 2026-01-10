<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Display landing page
     */
    public function index()
    {
        // If user is authenticated, redirect to dashboard
        if (auth()->check()) {
            return redirect()->route('home');
        }
        
        return view('landing');
    }
}

