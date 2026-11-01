<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Query\Builder;

class TenantContext
{
    private static ?int $tenantId = null;

    public static function set(?int $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    public static function id(): ?int
    {
        $user = auth()->user();
        if ($user instanceof User) {
            return $user->tenant_id;
        }

        return self::$tenantId;
    }

    public static function scopeQuery(Builder $query, string $column = 'tenant_id'): Builder
    {
        $tenantId = static::id();
        if ($tenantId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($tenantId, $column) {
            $query->where($column, $tenantId);
            if (auth()->user()?->is_admin) {
                $query->orWhereNull($column);
            }
        });
    }
}
