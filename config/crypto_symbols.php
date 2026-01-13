<?php

// Функция для получения списка токенов с Binance API
// Используем простое файловое кеширование, так как фасады недоступны в конфигах
function getCryptoSymbolsFromBinance(): array
{
    $cacheFile = __DIR__ . '/../storage/framework/cache/crypto_symbols_cache.json';
    $cacheTime = 3600; // 1 час
    
    // Проверяем кеш
    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        if (isset($cacheData['timestamp']) && isset($cacheData['symbols'])) {
            if (time() - $cacheData['timestamp'] < $cacheTime) {
                return $cacheData['symbols'];
            }
        }
    }
    
    try {
        // Делаем HTTP запрос к Binance API
        $url = 'https://fapi.binance.com/fapi/v1/exchangeInfo';
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => 'User-Agent: PHP'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            // Если запрос не удался, возвращаем пустой массив или данные из кеша (если есть)
            if (file_exists($cacheFile)) {
                $cacheData = json_decode(file_get_contents($cacheFile), true);
                if (isset($cacheData['symbols'])) {
                    return $cacheData['symbols'];
                }
            }
            return [];
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['symbols']) || !is_array($data['symbols'])) {
            // Если формат неверный, возвращаем кеш или пустой массив
            if (file_exists($cacheFile)) {
                $cacheData = json_decode(file_get_contents($cacheFile), true);
                if (isset($cacheData['symbols'])) {
                    return $cacheData['symbols'];
                }
            }
            return [];
        }
        
        // Фильтруем символы: TRADING, PERPETUAL, USDT
        $symbols = [];
        foreach ($data['symbols'] as $symbol) {
            if (
                isset($symbol['status']) && $symbol['status'] === 'TRADING' &&
                isset($symbol['contractType']) && $symbol['contractType'] === 'PERPETUAL' &&
                isset($symbol['quoteAsset']) && $symbol['quoteAsset'] === 'USDT' &&
                isset($symbol['baseAsset'])
            ) {
                $symbols[] = $symbol['baseAsset'];
            }
        }
        
        // Убираем дубликаты и сортируем
        $symbols = array_unique($symbols);
        sort($symbols);
        $symbols = array_values($symbols);
        
        // Исключаем BTC и YFI из массива
        $excludedSymbols = ['BTC', 'YFI'];
        $symbols = array_filter($symbols, function($symbol) use ($excludedSymbols) {
            return !in_array($symbol, $excludedSymbols);
        });
        $symbols = array_values($symbols); // Переиндексируем массив
        
        // Сохраняем в кеш
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, json_encode([
            'timestamp' => time(),
            'symbols' => $symbols
        ]));
        
        return $symbols;
        
    } catch (\Exception $e) {
        // При ошибке возвращаем кеш или пустой массив
        if (file_exists($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            if (isset($cacheData['symbols'])) {
                return $cacheData['symbols'];
            }
        }
        return [];
    }
}

return getCryptoSymbolsFromBinance();
