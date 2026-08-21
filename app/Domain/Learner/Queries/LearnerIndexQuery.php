<?php

declare(strict_types=1);

namespace App\Domain\Learner\Queries;

use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learner\Models\Learner;
use Illuminate\Database\Eloquent\Builder;

final class LearnerIndexQuery
{
    /** @param array<string, mixed> $filters
     * @return Builder<Learner>
     */
    public function build(array $filters): Builder
    {
        $query = Learner::query()->orderBy('last_name')->orderBy('first_name');
        $status = (string) ($filters['status'] ?? LearnerStatus::Active->value);

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if (! empty($filters['level'])) {
            $query->where('initial_level', $filters['level']);
        }
        if (! empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }
        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $like = '%'.$term.'%';
            $query->where(static function (Builder $nested) use ($like): void {
                $nested->whereRaw('first_name ILIKE ?', [$like])
                    ->orWhereRaw('last_name ILIKE ?', [$like])
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", [$like])
                    ->orWhere('phone', 'like', $like)
                    ->orWhereRaw('email ILIKE ?', [$like]);
            });
        }

        return $query;
    }
}
