<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class SystemSetting extends Model
{
    protected $table = 'tenant_settings';

    protected $fillable = ['tenant_id', 'key', 'value'];

    public static function read(string $key, mixed $default = null): mixed
    {
        $tenantId = TenantContext::id();
        $row = $tenantId === null
            ? null
            : DB::table('tenant_settings')->where('tenant_id', $tenantId)->where('key', $key)->first();
        $table = $row ? 'tenant_settings' : null;

        if (! $row && $tenantId === null) {
            $row = DB::table('system_settings')->where('key', $key)->first();
            $table = $row ? 'system_settings' : null;
        }

        if (! $row && auth()->user()?->is_admin) {
            $row = DB::table('system_settings')->where('key', $key)->first();
            $table = $row ? 'system_settings' : null;
        }

        if (! $row) {
            return $default;
        }

        $value = $row->value;
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    public static function write(string $key, mixed $value): void
    {
        $encoded = is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR);
        $tenantId = TenantContext::id();

        if ($tenantId === null) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $encoded, 'updated_at' => now(), 'created_at' => now()]
            );

            return;
        }

        DB::table('tenant_settings')->updateOrInsert(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $encoded, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public static function lock(string $key, mixed $default = null): mixed
    {
        $tenantId = TenantContext::id();
        if ($tenantId === null) {
            throw new LogicException('Tenant context is required to lock a setting.');
        }

        $query = DB::table('tenant_settings')
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->lockForUpdate();
        $row = $query->first();

        if (! $row) {
            if ($default === null) {
                return null;
            }
            static::write($key, $default);
            $row = DB::table('tenant_settings')
                ->where('tenant_id', $tenantId)
                ->where('key', $key)
                ->lockForUpdate()
                ->first();
        }

        return $row?->value;
    }
}
