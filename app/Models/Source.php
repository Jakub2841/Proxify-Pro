<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'is_active',
        'is_enabled',
        'last_scraped_at',
        'proxy_count',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_enabled' => 'boolean',
            'last_scraped_at' => 'datetime',
            'proxy_count' => 'integer',
        ];
    }
}
