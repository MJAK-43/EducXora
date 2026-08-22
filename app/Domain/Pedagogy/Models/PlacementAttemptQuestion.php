<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Models;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property int $attempt_id
 * @property int $source_question_id
 * @property int $position
 * @property string $prompt_snapshot
 * @property array{A: string, B: string, C: string, D: string} $choices_snapshot
 * @property string $correct_choice_snapshot
 * @property LearnerLevel $level_snapshot
 * @property string|null $selected_choice
 * @property bool|null $is_correct
 * @property CarbonImmutable|null $answered_at
 */
#[Fillable([
    'attempt_id', 'source_question_id', 'position', 'prompt_snapshot',
    'choices_snapshot', 'correct_choice_snapshot', 'level_snapshot',
    'selected_choice', 'is_correct', 'answered_by', 'answered_at',
])]
final class PlacementAttemptQuestion extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        self::creating(fn (PlacementAttemptQuestion $question) => $question->uuid ??= (string) Str::uuid7());
    }

    /** @return BelongsTo<PlacementAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PlacementAttempt::class, 'attempt_id');
    }

    /** @return BelongsTo<PlacementQuestion, $this> */
    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(PlacementQuestion::class, 'source_question_id');
    }

    /** @return BelongsTo<User, $this> */
    public function answerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'choices_snapshot' => 'array',
            'level_snapshot' => LearnerLevel::class,
            'is_correct' => 'boolean',
            'answered_at' => 'immutable_datetime',
        ];
    }
}
