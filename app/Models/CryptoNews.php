<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CryptoNews extends Model
{
    protected $fillable = [
        'article_id',
        'title',
        'description',
        'link',
        'pub_date',
        'creator',
        'coin',
        'image_url',
        'source_name',
        'source_id',
        'keywords',
        'content',
        'language',
        'sent_to_telegram',
    ];

    protected $casts = [
        'pub_date' => 'datetime',
        'creator' => 'array',
        'coin' => 'array',
        'keywords' => 'array',
        'sent_to_telegram' => 'boolean',
    ];
}
