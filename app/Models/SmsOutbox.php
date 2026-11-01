<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class SmsOutbox extends Model
{
    use BelongsToTenant;

    protected $table = 'sms_outbox';

    protected $fillable = [
        'card_sale_id',
        'phone_number',
        'message',
        'sim_slot',
        'status',
        'attempt_count',
        'external_id',
        'error_message',
        'claimed_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(CardSale::class, 'card_sale_id');
    }
}
