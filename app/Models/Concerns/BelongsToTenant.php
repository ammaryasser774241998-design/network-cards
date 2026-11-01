<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use LogicException;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = TenantContext::id();
            if ($tenantId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where(function (Builder $query) use ($tenantId) {
                $query->where($query->getModel()->qualifyColumn('tenant_id'), $tenantId);
                if (auth()->user()?->is_admin) {
                    $query->orWhereNull($query->getModel()->qualifyColumn('tenant_id'));
                }
            });
        });

        static::creating(function (Model $model) {
            $tenantId = TenantContext::id();
            if (auth()->check() && $tenantId === null) {
                throw new LogicException('Authenticated accounts must belong to a tenant.');
            }

            $model->setAttribute('tenant_id', $tenantId);
        });

        static::updating(function (Model $model) {
            if ($model->isDirty('tenant_id')) {
                Log::warning('Rejected attempt to change a record tenant.', [
                    'model' => $model::class,
                    'record_id' => $model->getKey(),
                ]);
                throw new LogicException('A record cannot be moved between tenants.');
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
