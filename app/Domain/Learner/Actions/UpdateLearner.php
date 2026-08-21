<?php

declare(strict_types=1);

namespace App\Domain\Learner\Actions;

use App\Domain\Learner\Models\Learner;
use App\Domain\Learner\Services\LearnerPhotoStorage;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class UpdateLearner
{
    public function __construct(
        private TenantContext $tenant,
        private AuditLogger $audit,
        private LearnerPhotoStorage $photos,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Learner $learner, array $attributes, User $actor, ?UploadedFile $photo, bool $removePhoto): Learner
    {
        $oldPath = $learner->photo_path;
        $newPath = null;
        $before = $learner->only(['language', 'initial_level']);

        try {
            $updated = DB::transaction(function () use ($learner, $attributes, $actor, $photo, $removePhoto, $before, &$newPath): Learner {
                if ($photo !== null) {
                    $newPath = $this->photos->store($learner, $photo);
                    $attributes['photo_path'] = $newPath;
                } elseif ($removePhoto) {
                    $attributes['photo_path'] = null;
                }

                $learner->update($attributes);
                $after = $learner->only(['language', 'initial_level']);
                $this->audit->record('learner.updated', $actor, $this->tenant->organization(), $learner, [
                    'changed_fields' => array_keys($learner->getChanges()),
                    'before' => $before,
                    'after' => $after,
                ]);

                return $learner->refresh();
            });
        } catch (Throwable $exception) {
            $this->photos->delete($newPath);
            throw $exception;
        }

        if (($photo !== null || $removePhoto) && $oldPath !== $updated->photo_path) {
            $this->photos->delete($oldPath);
        }

        return $updated;
    }
}
