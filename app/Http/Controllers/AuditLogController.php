<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Audit\Queries\AuditLogIndexQuery;
use App\Http\Requests\AuditLogIndexRequest;
use App\Models\AuditLog;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AuditLogController
{
    public function __construct(private AuditLogIndexQuery $logs, private TenantContext $tenant) {}

    public function __invoke(AuditLogIndexRequest $request): Response
    {
        $filters = $request->validated();
        $timezone = $this->tenant->organization()->timezone ?: 'Africa/Douala';
        $logs = $this->logs->execute($filters);
        $logs->getCollection()->transform(fn (AuditLog $log): array => [
            'id' => $log->getKey(),
            'action' => $log->action,
            'actor' => $log->actor_id ? $log->actor->only(['name', 'email']) : null,
            'resourceType' => class_basename((string) $log->resource_type),
            'resourceId' => $log->resource_id,
            'occurredAt' => CarbonImmutable::parse((string) $log->created_at)->setTimezone($timezone)->format('d/m/Y H:i'),
        ]);

        return Inertia::render('Organization/Audit/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'action' => (string) ($filters['action'] ?? ''),
                'from' => (string) ($filters['from'] ?? ''),
                'to' => (string) ($filters['to'] ?? ''),
            ],
        ]);
    }
}
