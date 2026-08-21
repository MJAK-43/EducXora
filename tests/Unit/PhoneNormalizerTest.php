<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Learner\Support\PhoneNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNormalizerTest extends TestCase
{
    #[DataProvider('phones')]
    public function test_it_normalizes_cameroon_phone_numbers(string $input, string $expected): void
    {
        self::assertSame($expected, PhoneNormalizer::normalize($input));
        self::assertTrue(PhoneNormalizer::isValid($input));
    }

    /** @return array<string, array{string, string}> */
    public static function phones(): array
    {
        return [
            'local mobile' => ['699 12 34 56', '+237699123456'],
            'international mobile' => ['+237 677 11 22 33', '+237677112233'],
            'international landline' => ['00237 222 33 44 55', '+237222334455'],
        ];
    }
}
