<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Voucher extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'category_id',
        'pin_code',
        'serial_number',
        'status',
        'customer_id',
        'sold_at',
    ];

    public function category()
    {
        return $this->belongsTo(CardCategory::class, 'category_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
