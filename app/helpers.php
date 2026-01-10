<?php

if (!function_exists('formatCryptoPrice')) {
    /**
     * Форматирует цену криптовалюты с правильным количеством знаков после запятой
     * Удаляет лишние нули, но сохраняет все значащие цифры (до 8 знаков)
     */
    function formatCryptoPrice($price, $decimals = 8): string
    {
        if ($price === null || $price === '') {
            return '0';
        }
        
        // Форматируем с максимальной точностью
        $formatted = number_format((float)$price, $decimals, '.', ' ');
        
        // Удаляем лишние нули после запятой
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');
        
        // Если после удаления нулей ничего не осталось, возвращаем 0
        if ($formatted === '' || $formatted === ' ') {
            return '0';
        }
        
        return $formatted;
    }
}

