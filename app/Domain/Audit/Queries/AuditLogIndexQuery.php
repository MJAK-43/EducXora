<?php

declare(strict_types=1);

namespace App\Domain\Audit\Queries;

use App\Models\AuditLog;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class AuditLogIndexQuery
{
    public function __construct(private TenantContext $tenant) {}

    /** @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function execute(array $filters): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('actor:id,name,email')
            ->where('organization_id', $this->tenant->id())
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $pattern = '%'.$search.'%';
                    $nested->where('action', 'like', $pattern)
                        ->orWhere('resource_type', 'like', $pattern)
                        ->orWhereHas('actor', fn (Builder $actor) => $actor->where('name', 'like', $pattern));
                });
            })
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();
    }
}
