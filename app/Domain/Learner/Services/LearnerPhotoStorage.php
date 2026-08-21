<?php

declare(strict_types=1);

namespace App\Domain\Learner\Services;

use App\Domain\Learner\Models\Learner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class LearnerPhotoStorage
{
    public function store(Learner $learner, UploadedFile $photo): string
    {
        $extension = strtolower($photo->extension() ?: 'bin');
        $path = $photo->storeAs(
            'learners/'.$learner->organization->uuid.'/'.$learner->uuid,
            bin2hex(random_bytes(20)).'.'.$extension,
            'local',
        );

        if (! is_string($path)) {
            throw new RuntimeException('La photo n’a pas pu être enregistrée.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('local')->delete($path);
        }
    }
}
