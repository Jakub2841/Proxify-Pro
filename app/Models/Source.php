<?php

namespace App\Models;

use App\Enums\Protocol;
use App\Enums\SourceParserType;
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
        'parser_type',
        'parser_config',
        'default_protocol',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_enabled' => 'boolean',
            'last_scraped_at' => 'datetime',
            'proxy_count' => 'integer',
            'parser_type' => SourceParserType::class,
            'parser_config' => 'array',
            'default_protocol' => Protocol::class,
        ];
    }
}
