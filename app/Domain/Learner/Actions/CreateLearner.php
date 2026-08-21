<?php

declare(strict_types=1);

namespace App\Domain\Learner\Actions;

use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learner\Services\LearnerPhotoStorage;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateLearner
{
    public function __construct(
        private TenantContext $tenant,
        private AuditLogger $audit,
        private LearnerPhotoStorage $photos,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes, User $actor, ?UploadedFile $photo = null): Learner
    {
        $storedPath = null;

        try {
            return DB::transaction(function () use ($attributes, $actor, $photo, &$storedPath): Learner {
                $learner = Learner::query()->create([
                    ...$attributes,
                    'registered_on' => now($this->tenant->organization()->timezone)->toDateString(),
                    'status' => LearnerStatus::Active,
                    'created_by' => $actor->getKey(),
                ]);

                if ($photo !== null) {
                    $storedPath = $this->photos->store($learner, $photo);
                    $learner->update(['photo_path' => $storedPath]);
                }

                $this->audit->record('learner.created', $actor, $this->tenant->organization(), $learner, [
                    'initial_level' => $learner->initial_level->value,
                    'language' => $learner->language->value,
                ]);

                return $learner->refresh();
            });
        } catch (Throwable $exception) {
            $this->photos->delete($storedPath);
            throw $exception;
        }
    }
}
