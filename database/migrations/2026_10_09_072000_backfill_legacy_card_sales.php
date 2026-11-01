<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacySales = DB::table('vouchers')
            ->leftJoin('card_categories', 'vouchers.category_id', '=', 'card_categories.id')
            ->leftJoin('customers', 'vouchers.customer_id', '=', 'customers.id')
            ->where('vouchers.status', 'sold')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('card_sales')
                    ->whereColumn('card_sales.voucher_id', 'vouchers.id');
            })
            ->selectRaw(
                "vouchers.id, vouchers.customer_id, NULL, COALESCE(customers.phone_number, ''), COALESCE(card_categories.name, 'فئة محذوفة'), vouchers.pin_code, vouchers.serial_number, COALESCE(card_categories.price, 0), COALESCE(card_categories.cost_price, 0), 'legacy', NULL, COALESCE(vouchers.sold_at, vouchers.created_at), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP"
            );

        DB::table('card_sales')->insertUsing([
            'voucher_id',
            'customer_id',
            'bank_transaction_id',
            'customer_phone',
            'category_name',
            'pin_code',
            'serial_number',
            'amount',
            'cost_amount',
            'source',
            'wallet_name',
            'sold_at',
            'created_at',
            'updated_at',
        ], $legacySales);
    }

    public function down(): void
    {
        DB::table('card_sales')->where('source', 'legacy')->delete();
    }
};
