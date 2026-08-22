<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Services;

use App\Domain\Learner\Enums\LearnerLevel;
use InvalidArgumentException;

final class PlacementScorer
{
    public function levelFor(float $percentage, string $version): LearnerLevel
    {
        if ($version !== (string) config('placement.scoring_version')) {
            throw new InvalidArgumentException('Unsupported placement scoring version.');
        }

        $result = LearnerLevel::A1;
        /** @var array<int|string, string> $thresholds */
        $thresholds = config('placement.thresholds', []);
        ksort($thresholds, SORT_NUMERIC);
        foreach ($thresholds as $minimum => $level) {
            if ($percentage >= (float) $minimum) {
                $result = LearnerLevel::from($level);
            }
        }

        return $result;
    }
}
