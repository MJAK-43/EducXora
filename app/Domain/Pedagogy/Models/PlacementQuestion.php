<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Models;

use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Pedagogy\Enums\QuestionSource;
use App\Domain\Pedagogy\Enums\QuestionStatus;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\PlacementQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** @property int $id
 * @property string $uuid
 * @property int|null $organization_id
 * @property QuestionSource $source
 * @property LearnerLanguage $language
 * @property LearnerLevel $level
 * @property string $prompt
 * @property array{A: string, B: string, C: string, D: string} $choices
 * @property string $correct_choice
 * @property QuestionStatus $status
 * @property CarbonImmutable|null $disabled_at
 */
#[Fillable(['organization_id', 'source', 'language', 'level', 'prompt', 'choices', 'correct_choice', 'status', 'created_by', 'disabled_at', 'disabled_by'])]
final class PlacementQuestion extends Model
{
    /** @use HasFactory<PlacementQuestionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(fn (PlacementQuestion $question) => $question->uuid ??= (string) Str::uuid7());
    }

    protected static function newFactory(): PlacementQuestionFactory
    {
        return PlacementQuestionFactory::new();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSystem(): bool
    {
        return $this->source === QuestionSource::System;
    }

    public function isActive(): bool
    {
        return $this->status === QuestionStatus::Active;
    }

    protected function casts(): array
    {
        return [
            'source' => QuestionSource::class,
            'language' => LearnerLanguage::class,
            'level' => LearnerLevel::class,
            'choices' => 'array',
            'status' => QuestionStatus::class,
            'disabled_at' => 'immutable_datetime',
        ];
    }
}
