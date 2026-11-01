<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->json('additional_phones')->nullable();
            $table->decimal('debt_balance', 12, 2)->default(0);
            $table->decimal('debt_limit', 12, 2)->nullable();
            $table->boolean('credit_sales_enabled')->default(false);
            $table->boolean('card_delivery_enabled')->default(true);
        });

        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['charge', 'payment']);
            $table->decimal('amount', 12, 2);
            $table->string('description', 500);
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_ledger_entries');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'additional_phones',
                'debt_balance',
                'debt_limit',
                'credit_sales_enabled',
                'card_delivery_enabled',
            ]);
        });
    }
};
