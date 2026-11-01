<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class BankTransaction extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'ref_number',
        'sender_name_raw',
        'sender_customer_name',
        'wallet_name',
        'sender_phone',
        'amount',
        'raw_message',
        'status',
        'message_hash',
        'customer_id',
        'voucher_id',
        'source_device',
        'processed_at',
        'processing_note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
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
}
