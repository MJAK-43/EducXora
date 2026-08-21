<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Enums\CourseSessionStatus;
use App\Domain\Scheduling\Models\CourseSession;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

final readonly class CancelCourseSession
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant, private Clock $clock) {}

    public function execute(CourseSession $session, User $actor): CourseSession
    {
        if ($session->isCancelled()) {
            return $session;
        }

        return DB::transaction(function () use ($session, $actor): CourseSession {
            $session->update(['status' => CourseSessionStatus::Cancelled, 'cancelled_at' => $this->clock->now(), 'cancelled_by' => $actor->getKey()]);
            $this->audit->record('lesson.cancelled', $actor, $this->tenant->organization(), $session);

            return $session->refresh();
        });
    }
}
