<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Support;

use App\Domain\Scheduling\Models\CourseSession;

final class CourseSessionPresenter
{
    /** @return array<string, mixed> */
    public static function present(CourseSession $session, string $timezone): array
    {
        $startsAt = $session->starts_at->setTimezone($timezone);
        $endsAt = $session->ends_at->setTimezone($timezone);

        return [
            'uuid' => $session->uuid,
            'group_uuid' => $session->group->uuid,
            'group_name' => $session->group->name,
            'teacher_membership_uuid' => $session->teacherMembership->uuid,
            'teacher_name' => $session->teacherMembership->user->name,
            'room' => $session->room,
            'date' => $startsAt->format('Y-m-d'),
            'date_label' => $startsAt->format('d/m/Y'),
            'start_time' => $startsAt->format('H:i'),
            'end_time' => $endsAt->format('H:i'),
            'status' => $session->status->value,
        ];
    }
}
