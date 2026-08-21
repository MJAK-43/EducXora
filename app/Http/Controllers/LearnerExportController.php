<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learner\Models\Learner;
use App\Domain\Learner\Queries\LearnerIndexQuery;
use App\Http\Requests\Learners\IndexLearnerRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class LearnerExportController
{
    public function __construct(private LearnerIndexQuery $query) {}

    public function __invoke(IndexLearnerRequest $request): StreamedResponse
    {
        abort_unless($request->user()->can('export', Learner::class), 403);
        $learners = $this->query->build($request->validated())->cursor();

        return response()->streamDownload(function () use ($learners): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Prénom', 'Nom', 'Naissance', 'Téléphone', 'E-mail', 'Langue', 'Niveau', 'Inscription', 'Statut'], ';', '"', '\\', "\r\n");
            foreach ($learners as $learner) {
                fputcsv($output, [
                    $learner->first_name, $learner->last_name, $learner->birth_date->format('Y-m-d'),
                    $learner->phone, $learner->email, $learner->language->label(),
                    $learner->initial_level->value, $learner->registered_on->format('Y-m-d'),
                    $learner->status->value,
                ], ';', '"', '\\', "\r\n");
            }
            fclose($output);
        }, 'apprenants-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
