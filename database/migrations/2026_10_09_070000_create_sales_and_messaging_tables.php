<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_categories', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0)->after('price');
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('cost_price');
            $table->boolean('low_stock_notified')->default(false)->after('low_stock_threshold');
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('message_hash', 64)->nullable()->unique()->after('ref_number');
            $table->string('wallet_name')->nullable()->after('sender_name_raw');
            $table->string('sender_phone', 30)->nullable()->after('wallet_name');
            $table->foreignId('customer_id')->nullable()->after('sender_phone')->constrained()->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->string('source_device')->nullable()->after('voucher_id');
            $table->timestamp('processed_at')->nullable()->after('status');
            $table->text('processing_note')->nullable()->after('processed_at');
        });

        Schema::create('card_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_phone', 30);
            $table->string('category_name');
            $table->string('pin_code');
            $table->string('serial_number')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('cost_amount', 10, 2)->default(0);
            $table->string('source')->default('direct');
            $table->string('wallet_name')->nullable();
            $table->timestamp('sold_at');
            $table->timestamps();

            $table->index(['sold_at', 'source']);
        });

        Schema::create('sms_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone_number', 30);
            $table->text('message');
            $table->unsignedTinyInteger('sim_slot')->nullable();
            $table->enum('status', ['pending', 'sending', 'sent', 'failed'])->default('pending');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->string('external_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('sms_outbox');
        Schema::dropIfExists('card_sales');

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropUnique(['message_hash']);
            $table->dropColumn([
                'message_hash',
                'wallet_name',
                'sender_phone',
                'source_device',
                'processed_at',
                'processing_note',
            ]);
        });

        Schema::table('card_categories', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'low_stock_threshold', 'low_stock_notified']);
        });
    }
};
