<?php

namespace App\Models;

use App\Enums\Protocol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'port',
        'protocol',
        'country',
        'google_pass',
        'cloudflare_pass',
        'latency_ms',
        'last_checked_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'protocol' => Protocol::class,
            'google_pass' => 'boolean',
            'cloudflare_pass' => 'boolean',
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    /**
     * Convert an ISO alpha2 country code to its flag emoji.
     */
    public function getCountryFlagAttribute(): string
    {
        if (strlen($this->country) !== 2) {
            return '';
        }

        return mb_chr(0x1F1E6 + ord($this->country[0]) - 65)
             .mb_chr(0x1F1E6 + ord($this->country[1]) - 65);
    }

    /**
     * Number of latency bars (0-4) based on latency_ms.
     */
    public function getLatencyBarsAttribute(): int
    {
        return match (true) {
            $this->latency_ms === null => 0,
            $this->latency_ms < 200 => 4,
            $this->latency_ms < 400 => 3,
            $this->latency_ms < 600 => 2,
            default => 1,
        };
    }
}
