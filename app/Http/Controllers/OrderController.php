<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display order page for subscription purchase
     */
    public function index(Request $request)
    {
        // Determine product_id based on source page
        // product_id = 2 for strategy-settings, product_id = 1 for signals (default)
        $fromStrategySettings = $request->get('from') === 'strategy-settings' || 
                               ($request->header('referer') && str_contains($request->header('referer'), '/strategy-settings'));
        
        $productId = $fromStrategySettings ? 2 : 1;
        
        // Get product by id
        $product = Product::find($productId);
        
        if (!$product) {
            $redirectRoute = $fromStrategySettings ? 'strategy-settings.index' : 'signals.index';
            return redirect()->route($redirectRoute)->with('error', 'Продукт не найден');
        }

        // Price is in kopecks, convert to dollars (divide by 100)
        $priceInKopecks = (int) $product->price;
        $priceInDollars = $priceInKopecks / 100;

        // Check if user came from signals page (only signals page shows excluded items)
        $fromSignals = $request->get('from') === 'signals' || 
                      (!$fromStrategySettings && $request->header('referer') && str_contains($request->header('referer'), '/signals'));

        return view('orders.index', [
            'product' => $product,
            'price' => $priceInDollars,
            'fromSignals' => $fromSignals ?? false,
            'fromStrategySettings' => $fromStrategySettings,
            'backRoute' => $fromStrategySettings ? 'strategy-settings.index' : 'signals.index',
        ]);
    }
}

