<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Generates unique 3-5 digit numeric public IDs.
 * Uses a gap-friendly approach so we never run out of space.
 */
class ShortIdService
{
    public function __construct()
    {
        //
    }

    /**
     * Generate a unique short_id (3-5 digits).
     * Strategy: try 1000-9999 first (4 digits), then 100-999 (3 digits),
     * then 10000-99999 (5 digits). Picks a random unused number.
     */
    public function generate(): string
    {
        $ranges = [
            [100, 999],     // 3 digits
            [1000, 9999],   // 4 digits
            [10000, 99999], // 5 digits
        ];

        foreach ($ranges as [$min, $max]) {
            $attempts = 0;
            while ($attempts < 100) {
                $candidate = (string) random_int($min, $max);
                if (!User::where('short_id', $candidate)->exists()) {
                    return $candidate;
                }
                $attempts++;
            }
        }

        // Fallback: sequential
        $last = User::max('short_id') ?? 0;
        $next = max(100, (int)$last + 1);
        while (User::where('short_id', (string)$next)->exists() && $next < 99999) {
            $next++;
        }

        return (string)$next;
    }

    public function validate(string $id): bool
    {
        return preg_match('/^[1-9]\d{2,4}$/', $id) === 1;
    }

    public function resolve(string $id): ?User
    {
        if (!$this->validate($id)) {
            return null;
        }

        return User::where('short_id', $id)->first();
    }
}