<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('status')
                ->constrained('customers')
                ->nullOnDelete();
            $table->timestamp('sold_at')->nullable()->after('customer_id');
        });

        DB::table('vouchers')
            ->where('status', 'sold')
            ->whereNull('sold_at')
            ->update(['sold_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn('sold_at');
        });
    }
};
