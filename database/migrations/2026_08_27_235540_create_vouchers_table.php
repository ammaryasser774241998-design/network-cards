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
    Schema::create('vouchers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('category_id')->constrained('card_categories')->onDelete('cascade');
        $table->string('pin_code');
        $table->string('serial_number')->nullable();
        $table->enum('status', ['available', 'sold'])->default('available');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
