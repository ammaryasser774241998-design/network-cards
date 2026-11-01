<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_categories', function (Blueprint $table) {
            $table->decimal('wholesale_price', 10, 2)->nullable();
            $table->unsignedSmallInteger('wholesale_min_quantity')->default(2);
            $table->unsignedSmallInteger('bonus_min_quantity')->default(1);
            $table->unsignedSmallInteger('bonus_cards')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('card_categories', function (Blueprint $table) {
            $table->dropColumn([
                'wholesale_price',
                'wholesale_min_quantity',
                'bonus_min_quantity',
                'bonus_cards',
            ]);
        });
    }
};
