<?php

namespace App\Models;

use App\Enums\AnonymityLevel;
use App\Enums\Protocol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'port',
        'protocol',
        'anonymity',
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
            'anonymity' => AnonymityLevel::class,
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

    /**
     * Full connection URI for HTTP proxy routing.
     */
    public function connectionUri(): string
    {
        return "{$this->protocol->value}://{$this->address}:{$this->port}";
    }

    /**
     * Apply dashboard filter criteria to the query.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeStale(Builder $query, int $minutes = 30): Builder
    {
        return $query->whereNull('last_checked_at')
            ->orWhere('last_checked_at', '<', now()->subMinutes($minutes));
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        if (! empty($filters['protocols'] ?? [])) {
            $query->whereIn('protocol', $filters['protocols']);
        }

        if (! empty($filters['anonymity'] ?? [])) {
            $query->whereIn('anonymity', $filters['anonymity']);
        }

        if (! empty($filters['checks'] ?? [])) {
            foreach ($filters['checks'] as $check) {
                match ($check) {
                    'google' => $query->where('google_pass', true),
                    'cloudflare' => $query->where('cloudflare_pass', true),
                    default => null,
                };
            }
        }

        if (! empty($filters['country'] ?? '')) {
            $query->where('country', $filters['country']);
        }

        if (! empty($filters['active_only'] ?? false)) {
            $query->where('is_active', true);
        }

        if (! empty($filters['search'] ?? '')) {
            $query->where('address', 'like', '%'.$filters['search'].'%');
        }

        return $query;
    }
}
