<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learner\Models\Learner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class LearnerPhotoController
{
    public function __invoke(Request $request, string $learnerUuid): BinaryFileResponse
    {
        $learner = Learner::query()->where('uuid', $learnerUuid)->firstOrFail();
        abort_unless($request->user()->can('view', $learner), 403);
        abort_unless($learner->photo_path && Storage::disk('local')->exists($learner->photo_path), 404);

        return response()->file(Storage::disk('local')->path($learner->photo_path), [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
