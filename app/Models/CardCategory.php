<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class CardCategory extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'name',
        'price',
        'cost_price',
        'low_stock_threshold',
        'description',
        'wholesale_price',
        'wholesale_min_quantity',
        'bonus_min_quantity',
        'bonus_cards',
    ];

    // علاقة الفئة بالكروت
    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'category_id');
    }
}
