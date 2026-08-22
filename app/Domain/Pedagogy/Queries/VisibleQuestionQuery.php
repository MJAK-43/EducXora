<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Queries;

use App\Domain\Pedagogy\Models\PlacementQuestion;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

final readonly class VisibleQuestionQuery
{
    public function __construct(private TenantContext $tenant) {}

    /** @return Builder<PlacementQuestion> */
    public function build(): Builder
    {
        return PlacementQuestion::query()->where(function (Builder $query): void {
            $query->whereNull('organization_id')->orWhere('organization_id', $this->tenant->id());
        });
    }
}
