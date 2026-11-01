<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tenantTables = [
        'card_categories',
        'vouchers',
        'customers',
        'bank_transactions',
        'card_sales',
        'sms_outbox',
        'phone_exclusions',
        'customer_ledger_entries',
        'work_shifts',
        'customer_sms_requests',
    ];

    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('account_type')->default('seller');
        });

        foreach ($this->tenantTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
                $table->index('tenant_id');
            });
        }

        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
        });

        $users = DB::table('users')->orderBy('id')->get();
        $tenantIds = [];
        foreach ($users as $index => $user) {
            $tenantIds[$user->id] = DB::table('tenants')->insertGetId([
                'name' => $user->name.' — شبكة البائع',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('users')->where('id', $user->id)->update([
                'tenant_id' => $tenantIds[$user->id],
                'account_type' => (bool) ($user->is_admin ?? false) || $index === 0 ? 'admin' : 'seller',
            ]);
        }

        $platformTenantId = null;
        if ($users->isNotEmpty()) {
            $platformOwner = $users->firstWhere('is_admin', true) ?? $users->first();
            $platformTenantId = $tenantIds[$platformOwner->id];

            foreach ($this->tenantTables as $tableName) {
                DB::table($tableName)->whereNull('tenant_id')->update(['tenant_id' => $platformTenantId]);
            }

            foreach (DB::table('system_settings')->get() as $setting) {
                DB::table('tenant_settings')->insert([
                    'tenant_id' => $platformTenantId,
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ]);
            }
        }

        $this->scopeUniqueIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
        foreach (array_reverse($this->tenantTables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('account_type');
        });
        Schema::dropIfExists('tenants');
    }

    private function scopeUniqueIndexes(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['phone_number']);
            $table->unique(['tenant_id', 'phone_number']);
        });
        Schema::table('phone_exclusions', function (Blueprint $table) {
            $table->dropUnique(['phone_number']);
            $table->unique(['tenant_id', 'phone_number']);
        });
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropUnique(['ref_number']);
            $table->dropUnique(['message_hash']);
            $table->unique(['tenant_id', 'ref_number']);
            $table->unique(['tenant_id', 'message_hash']);
        });
        Schema::table('customer_sms_requests', function (Blueprint $table) {
            $table->dropUnique(['message_hash']);
            $table->unique(['tenant_id', 'message_hash']);
        });
    }
};
