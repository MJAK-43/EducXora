<?php

declare(strict_types=1);

namespace App\Domain\Learner\Support;

final class PhoneNormalizer
{
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';

        if (str_starts_with($digits, '00237')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '237')) {
            $digits = substr($digits, 3);
        }

        return '+237'.$digits;
    }

    public static function isValid(string $phone): bool
    {
        return preg_match('/^\+237[26]\d{8}$/', self::normalize($phone)) === 1;
    }
}
