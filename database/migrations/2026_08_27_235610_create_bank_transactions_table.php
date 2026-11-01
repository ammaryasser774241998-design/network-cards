<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('bank_transactions', function (Blueprint $table) {
        $table->id();
        $table->string('ref_number')->nullable()->unique();
        $table->string('sender_name_raw')->nullable();
        $table->decimal('amount', 10, 2);
        $table->text('raw_message');
        $table->enum('status', ['pending_customer', 'completed', 'failed', 'no_stock'])->default('pending_customer');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
