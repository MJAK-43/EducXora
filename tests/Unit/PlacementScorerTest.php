<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Pedagogy\Services\PlacementScorer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PlacementScorerTest extends TestCase
{
    /** @return array<string, array{float, LearnerLevel}> */
    public static function boundaries(): array
    {
        return [
            'A1 floor' => [0, LearnerLevel::A1],
            'before A2' => [34.99, LearnerLevel::A1],
            'A2' => [35, LearnerLevel::A2],
            'B1' => [50, LearnerLevel::B1],
            'B2' => [65, LearnerLevel::B2],
            'C1' => [80, LearnerLevel::C1],
            'before C2' => [89.99, LearnerLevel::C1],
            'C2' => [90, LearnerLevel::C2],
            'perfect' => [100, LearnerLevel::C2],
        ];
    }

    #[DataProvider('boundaries')]
    public function test_versioned_threshold_boundaries(float $percentage, LearnerLevel $expected): void
    {
        self::assertSame($expected, (new PlacementScorer)->levelFor($percentage, 'v1'));
    }
}
