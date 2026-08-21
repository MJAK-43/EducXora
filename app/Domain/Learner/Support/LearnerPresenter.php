<?php

declare(strict_types=1);

namespace App\Domain\Learner\Support;

use App\Domain\Learner\Models\Learner;

final class LearnerPresenter
{
    /** @return array<string, mixed> */
    public static function summary(Learner $learner): array
    {
        return [
            'uuid' => $learner->uuid,
            'first_name' => $learner->first_name,
            'last_name' => $learner->last_name,
            'full_name' => $learner->fullName(),
            'phone' => $learner->phone,
            'email' => $learner->email,
            'language' => $learner->language->value,
            'language_label' => $learner->language->label(),
            'initial_level' => $learner->initial_level->value,
            'registered_on' => $learner->registered_on->format('Y-m-d'),
            'registered_on_label' => $learner->registered_on->format('d/m/Y'),
            'status' => $learner->status->value,
            'has_photo' => $learner->photo_path !== null,
        ];
    }

    /** @return array<string, mixed> */
    public static function detail(Learner $learner): array
    {
        return [
            ...self::summary($learner),
            'birth_date' => $learner->birth_date->format('Y-m-d'),
            'birth_date_label' => $learner->birth_date->format('d/m/Y'),
            'photo_url' => $learner->photo_path ? route('learners.photo', ['learnerUuid' => $learner->uuid], false) : null,
            'archived_at' => $learner->archived_at?->toIso8601String(),
        ];
    }
}
