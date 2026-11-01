<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('sales_count');
            $table->decimal('sales_amount', 14, 2);
            $table->decimal('sales_cost', 14, 2);
            $table->unsignedInteger('transactions_count');
            $table->decimal('transactions_amount', 14, 2);
            $table->timestamps();

            $table->index('closed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_shifts');
    }
};
