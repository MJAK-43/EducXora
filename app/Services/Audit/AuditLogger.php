<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AuditLogger
{
    /** @param array<string, mixed> $metadata */
    public function record(
        string $action,
        ?User $actor = null,
        ?Organization $organization = null,
        ?Model $resource = null,
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'uuid' => (string) Str::uuid7(),
            'organization_id' => $organization?->getKey(),
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'resource_type' => $resource ? $resource::class : null,
            'resource_id' => $resource?->getKey(),
            'metadata' => $this->sanitize($metadata),
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), ['password', 'password_confirmation', 'token', 'token_hash'], true)) {
                unset($values[$key]);
            } elseif (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }
}
