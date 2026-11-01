<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->where('full_name', 'like', 'زبون %')
            ->whereColumn('bank_account_name', 'full_name')
            ->orderBy('id')
            ->chunkById(500, function ($customers): void {
                foreach ($customers as $customer) {
                    if ($customer->full_name === 'زبون '.$customer->phone_number) {
                        DB::table('customers')
                            ->where('id', $customer->id)
                            ->update(['is_guest' => true]);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('customers')
            ->where('full_name', 'like', 'زبون %')
            ->whereColumn('bank_account_name', 'full_name')
            ->orderBy('id')
            ->chunkById(500, function ($customers): void {
                foreach ($customers as $customer) {
                    if ($customer->full_name === 'زبون '.$customer->phone_number) {
                        DB::table('customers')
                            ->where('id', $customer->id)
                            ->update(['is_guest' => false]);
                    }
                }
            });
    }
};
