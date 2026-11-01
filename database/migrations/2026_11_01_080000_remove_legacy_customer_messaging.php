<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['system_settings', 'tenant_settings'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)
                ->where(function ($query) {
                    $query->where('key', 'like', 'green_api%')
                        ->orWhereIn('key', [
                            'customer_message_profile',
                            'customer_message_channel',
                            'customer_message_templates',
                        ]);
                })
                ->delete();
        }

        Schema::dropIfExists('customer_sms_requests');
    }

    public function down(): void
    {
        Schema::create('customer_sms_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('message_hash', 64);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_phone', 30);
            $table->text('message');
            $table->string('status')->default('processing');
            $table->text('response')->nullable();
            $table->foreignId('card_sale_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'message_hash']);
        });
    }
};
