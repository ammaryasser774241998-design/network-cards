<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Customer extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'full_name',
        'phone_number',
        'balance',
        'bank_account_name',
        'bank_account_number',
        'status',
        'is_guest',
        'additional_phones',
        'debt_balance',
        'debt_limit',
        'credit_sales_enabled',
        'card_delivery_enabled',
    ];

    protected function casts(): array
    {
        return [
            'additional_phones' => 'array',
            'debt_balance' => 'decimal:2',
            'debt_limit' => 'decimal:2',
            'credit_sales_enabled' => 'boolean',
            'card_delivery_enabled' => 'boolean',
            'is_guest' => 'boolean',
        ];
    }

    public function sales()
    {
        return $this->hasMany(CardSale::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(CustomerLedgerEntry::class);
    }
}
