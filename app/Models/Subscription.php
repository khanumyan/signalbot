<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'status',
        'date_from',
        'date_to',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    /**
     * Отношение к модели Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Отношение к модели User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить дату начала в формате d-m-Y
     */
    public function getDateFromFormattedAttribute(): string
    {
        return $this->date_from ? $this->date_from->format('d-m-Y') : '';
    }

    /**
     * Получить дату окончания в формате d-m-Y
     */
    public function getDateToFormattedAttribute(): string
    {
        return $this->date_to ? $this->date_to->format('d-m-Y') : '';
    }

    /**
     * Установить дату начала из формата d-m-Y
     */
    public function setDateFromAttribute($value): void
    {
        if ($value instanceof Carbon) {
            $this->attributes['date_from'] = $value->format('Y-m-d');
            return;
        }
        
        if (is_string($value) && strpos($value, '-') !== false) {
            $parts = explode('-', $value);
            if (count($parts) === 3) {
                // Проверяем формат: если первый элемент больше 12, то это d-m-Y
                if (strlen($parts[0]) <= 2 && strlen($parts[1]) <= 2 && strlen($parts[2]) === 4) {
                    try {
                        $date = Carbon::createFromFormat('d-m-Y', $value);
                        $this->attributes['date_from'] = $date->format('Y-m-d');
                        return;
                    } catch (\Exception $e) {
                        // Если не удалось распарсить как d-m-Y, пробуем стандартный формат
                    }
                }
            }
        }
        
        $this->attributes['date_from'] = $value;
    }

    /**
     * Установить дату окончания из формата d-m-Y
     */
    public function setDateToAttribute($value): void
    {
        if ($value instanceof Carbon) {
            $this->attributes['date_to'] = $value->format('Y-m-d');
            return;
        }
        
        if (is_string($value) && strpos($value, '-') !== false) {
            $parts = explode('-', $value);
            if (count($parts) === 3) {
                // Проверяем формат: если первый элемент больше 12, то это d-m-Y
                if (strlen($parts[0]) <= 2 && strlen($parts[1]) <= 2 && strlen($parts[2]) === 4) {
                    try {
                        $date = Carbon::createFromFormat('d-m-Y', $value);
                        $this->attributes['date_to'] = $date->format('Y-m-d');
                        return;
                    } catch (\Exception $e) {
                        // Если не удалось распарсить как d-m-Y, пробуем стандартный формат
                    }
                }
            }
        }
        
        $this->attributes['date_to'] = $value;
    }

    /**
     * Проверить, активна ли подписка
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->date_from <= now() && 
               $this->date_to >= now();
    }

    /**
     * Проверить, истекла ли подписка
     */
    public function isExpired(): bool
    {
        return $this->date_to < now();
    }
}
