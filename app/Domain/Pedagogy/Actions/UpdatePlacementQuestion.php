<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Actions;

use App\Domain\Pedagogy\Models\PlacementQuestion;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdatePlacementQuestion
{
    public function __construct(private TenantContext $tenant, private AuditLogger $audit) {}

    /** @param array<string, mixed> $data */
    public function execute(PlacementQuestion $question, array $data, User $actor): PlacementQuestion
    {
        return DB::transaction(function () use ($question, $data, $actor): PlacementQuestion {
            $locked = PlacementQuestion::query()->lockForUpdate()->findOrFail($question->getKey());
            if ($locked->isSystem() || (int) $locked->organization_id !== $this->tenant->id()) {
                throw ValidationException::withMessages(['question' => 'Une question système est en lecture seule.']);
            }
            $before = $locked->only(['level', 'prompt', 'choices', 'correct_choice']);
            $locked->update($data);
            $this->audit->record('placement_question.updated', $actor, $this->tenant->organization(), $locked, [
                'question_uuid' => $locked->uuid,
                'before' => $before,
                'after' => $locked->only(['level', 'prompt', 'choices', 'correct_choice']),
            ]);

            return $locked;
        });
    }
}
