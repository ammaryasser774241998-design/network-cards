<?php

namespace App\Services;

use App\Models\CardCategory;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class InventoryAlertService
{
    public function check(CardCategory $category): void
    {
        $available = $category->vouchers()->where('status', 'available')->count();
        if ($available > $category->low_stock_threshold) {
            if ($category->low_stock_notified) {
                $category->update(['low_stock_notified' => false]);
            }

            return;
        }

        $managerPhone = SystemSetting::read('manager_phone');
        if (! $managerPhone || $category->low_stock_notified) {
            return;
        }

        if (! app(CustomerMessageDeliveryService::class)->send(
            $managerPhone,
            "تنبيه مخزون: تبقى {$available} كرت من فئة {$category->name}.",
        )) {
            Log::error('Unable to deliver low-stock notification.', ['category_id' => $category->id]);
        }

        $category->update(['low_stock_notified' => true]);
    }
}
