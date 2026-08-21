<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Services;

use App\Domain\Scheduling\Enums\CourseSessionStatus;
use App\Domain\Scheduling\Models\CourseSession;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

final class ScheduleConflictGuard
{
    public function ensureAvailable(int $groupId, int $teacherMembershipId, ?string $room, CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?int $exceptId = null): void
    {
        $base = CourseSession::query()->where('status', CourseSessionStatus::Scheduled)
            ->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt);
        if ($exceptId !== null) {
            $base->whereKeyNot($exceptId);
        }
        if ((clone $base)->where('group_id', $groupId)->exists()) {
            throw ValidationException::withMessages(['starts_at' => 'Ce groupe possède déjà une séance sur ce créneau.']);
        }
        if ((clone $base)->where('teacher_membership_id', $teacherMembershipId)->exists()) {
            throw ValidationException::withMessages(['teacher_membership_uuid' => 'Cet enseignant possède déjà une séance sur ce créneau.']);
        }
        if ($room !== null && (clone $base)->whereRaw('lower(room) = lower(?)', [$room])->exists()) {
            throw ValidationException::withMessages(['room' => 'Cette salle est déjà occupée sur ce créneau.']);
        }
    }

    public function rethrowDatabaseConflict(QueryException $exception): never
    {
        if (($exception->errorInfo[0] ?? null) === '23P01') {
            throw ValidationException::withMessages(['starts_at' => 'Ce créneau entre en conflit avec une autre séance.']);
        }

        throw $exception;
    }
}
