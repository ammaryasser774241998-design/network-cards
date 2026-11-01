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
    Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->string('full_name');
        $table->string('phone_number')->unique();
        $table->string('bank_account_name');
        $table->string('bank_account_number')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->decimal('balance', 10, 2)->default(0);
        $table->string('status')->default('active');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
        $table->dropColumn(['balance', 'status']);
    }
};
