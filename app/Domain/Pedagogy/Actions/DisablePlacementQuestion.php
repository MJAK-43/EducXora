<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Actions;

use App\Domain\Pedagogy\Enums\QuestionStatus;
use App\Domain\Pedagogy\Models\PlacementQuestion;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DisablePlacementQuestion
{
    public function __construct(private TenantContext $tenant, private AuditLogger $audit, private Clock $clock) {}

    public function execute(PlacementQuestion $question, User $actor): PlacementQuestion
    {
        return DB::transaction(function () use ($question, $actor): PlacementQuestion {
            $locked = PlacementQuestion::query()->lockForUpdate()->findOrFail($question->getKey());
            if ($locked->isSystem() || (int) $locked->organization_id !== $this->tenant->id()) {
                throw ValidationException::withMessages(['question' => 'Une question système est en lecture seule.']);
            }
            if ($locked->status === QuestionStatus::Inactive) {
                return $locked;
            }
            $locked->update(['status' => QuestionStatus::Inactive, 'disabled_at' => $this->clock->now(), 'disabled_by' => $actor->getKey()]);
            $this->audit->record('placement_question.disabled', $actor, $this->tenant->organization(), $locked, ['question_uuid' => $locked->uuid]);

            return $locked;
        });
    }
}
