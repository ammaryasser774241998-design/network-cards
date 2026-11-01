<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class WorkShift extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'closed_by',
        'opened_at',
        'closed_at',
        'sales_count',
        'sales_amount',
        'sales_cost',
        'transactions_count',
        'transactions_amount',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'sales_amount' => 'decimal:2',
            'sales_cost' => 'decimal:2',
            'transactions_amount' => 'decimal:2',
        ];
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
