<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Transfer extends Model
{
    use BelongsToTenant, HasFactory;

    // ربط النموذج بجدول قاعدة البيانات الفعلي
    protected $table = 'bank_transactions';

    protected $fillable = [
        'ref_number',
        'sender_name_raw',
        'sender_customer_name',
        'wallet_name',
        'sender_phone',
        'amount',
        'raw_message',
        'customer_id',
        'voucher_id',
        'source_device',
        'status',
        'processed_at',
        'processing_note',
        'message_hash',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
