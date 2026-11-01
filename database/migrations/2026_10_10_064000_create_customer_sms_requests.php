<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('sender_customer_name')->nullable()->after('sender_name_raw');
        });

        Schema::create('customer_sms_requests', function (Blueprint $table) {
            $table->id();
            $table->string('message_hash', 64)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_phone', 30);
            $table->text('message');
            $table->string('status')->default('processing');
            $table->text('response')->nullable();
            $table->foreignId('card_sale_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_sms_requests');

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn('sender_customer_name');
        });
    }
};
