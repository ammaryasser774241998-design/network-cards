<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class CardSale extends Model
{
    use BelongsToTenant;

    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cost_amount' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class);
    }
}
