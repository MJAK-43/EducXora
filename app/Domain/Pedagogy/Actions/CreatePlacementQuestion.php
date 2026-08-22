<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Actions;

use App\Domain\Pedagogy\Enums\QuestionSource;
use App\Domain\Pedagogy\Enums\QuestionStatus;
use App\Domain\Pedagogy\Models\PlacementQuestion;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

final readonly class CreatePlacementQuestion
{
    public function __construct(private TenantContext $tenant, private AuditLogger $audit) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, User $actor): PlacementQuestion
    {
        return DB::transaction(function () use ($data, $actor): PlacementQuestion {
            $question = PlacementQuestion::query()->create([
                ...$data,
                'organization_id' => $this->tenant->id(),
                'source' => QuestionSource::Organization,
                'status' => QuestionStatus::Active,
                'created_by' => $actor->getKey(),
            ]);
            $this->audit->record('placement_question.created', $actor, $this->tenant->organization(), $question, [
                'question_uuid' => $question->uuid,
                'level' => $question->level->value,
            ]);

            return $question;
        });
    }
}
