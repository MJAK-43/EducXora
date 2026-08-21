<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $context = app(TenantContext::class);

            if (! $context->resolved()) {
                throw new LogicException('A tenant context is required to create '.get_class($model).'.');
            }

            if ($model->getAttribute('organization_id') !== null
                && (int) $model->getAttribute('organization_id') !== $context->id()) {
                throw new LogicException('Cross-tenant model creation is forbidden.');
            }

            $model->setAttribute('organization_id', $context->id());
        });
    }
}
