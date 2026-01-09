<?php

namespace App\Http\Controllers;

use App\Models\CryptoNews;
use Illuminate\Http\Request;

class CryptoNewsController extends Controller
{
    /**
     * Display crypto news page
     */
    public function index()
    {
        $news = CryptoNews::orderBy('pub_date', 'desc')
            ->paginate(20);

        return view('crypto-news.index', compact('news'));
    }

    /**
     * Display single news article
     */
    public function show($id)
    {
        $news = CryptoNews::findOrFail($id);
        
        // Get related news (same source or same coins)
        $relatedNews = CryptoNews::where('id', '!=', $id)
            ->where(function($query) use ($news) {
                if ($news->source_id) {
                    $query->where('source_id', $news->source_id);
                }
                if ($news->coin && is_array($news->coin) && count($news->coin) > 0) {
                    foreach ($news->coin as $coin) {
                        $query->orWhereJsonContains('coin', $coin);
                    }
                }
            })
            ->orderBy('pub_date', 'desc')
            ->limit(4)
            ->get();

        return view('crypto-news.show', compact('news', 'relatedNews'));
    }
}
