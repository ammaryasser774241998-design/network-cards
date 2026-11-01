<?php

namespace App\Services;

use App\Models\BankTransaction;
use App\Models\CardSale;
use App\Models\Customer;
use App\Models\SystemSetting;
use App\Models\Voucher;

class CardSaleService
{
    public function __construct(
        private InventoryAlertService $inventoryAlerts,
        private CustomerMessageDeliveryService $customerMessages,
    ) {}

    public function sell(
        Voucher $voucher,
        ?Customer $customer,
        string $phone,
        string $source,
        ?BankTransaction $bankTransaction = null,
        ?string $walletName = null,
        bool $sendCustomerMessage = true,
        ?float $saleAmount = null,
        ?string $sourceOverride = null,
        ?float $remainingBalance = null,
    ): CardSale {
        $soldAt = now();
        $category = $voucher->category;

        $voucher->update([
            'status' => 'sold',
            'customer_id' => $customer?->id,
            'sold_at' => $soldAt,
        ]);

        $sale = CardSale::create([
            'voucher_id' => $voucher->id,
            'customer_id' => $customer?->id,
            'bank_transaction_id' => $bankTransaction?->id,
            'customer_phone' => $phone,
            'category_name' => $category->name,
            'pin_code' => $voucher->pin_code,
            'serial_number' => $voucher->serial_number,
            'amount' => $saleAmount ?? $category->price,
            'cost_amount' => $category->cost_price ?? 0,
            'source' => $sourceOverride ?? $source,
            'wallet_name' => $walletName,
            'sold_at' => $soldAt,
        ]);

        if ($sendCustomerMessage) {
            $this->customerMessages->send(
                $phone,
                $remainingBalance === null
                    ? $this->messageForSale($sale)
                    : $this->messageForBalancePurchase($sale, $remainingBalance),
            );
        }

        if ($bankTransaction) {
            $bankTransaction->update([
                'customer_id' => $customer?->id,
                'voucher_id' => $voucher->id,
                'status' => 'completed',
                'processed_at' => $soldAt,
                'processing_note' => null,
            ]);
        }

        $this->inventoryAlerts->check($category);

        return $sale;
    }

    public function messageForSale(CardSale $sale): string
    {
        $template = (string) SystemSetting::read(
            'sms_template',
            'تم الشراء من {network}. الفئة: {category}. الرمز: {pin}{serial_suffix}'
        );

        return strtr($template, [
            '{network}' => (string) SystemSetting::read('network_name', 'شبكتك'),
            '{category}' => $sale->category_name,
            '{pin}' => $sale->pin_code,
            '{serial}' => $sale->serial_number ?? '',
            '{serial_suffix}' => $sale->serial_number ? '. الرقم التسلسلي: '.$sale->serial_number : '',
            '{phone}' => $sale->customer_phone,
        ]);
    }

    public function messageForBalancePurchase(CardSale $sale, float $remainingBalance): string
    {
        $message = trim($this->messageForSale($sale));
        $requiredDetails = [];
        $networkName = (string) SystemSetting::read('network_name', 'شبكتك');

        if (! str_contains($message, $networkName)) {
            $requiredDetails[] = 'اسم الشبكة: '.$networkName;
        }
        if (! str_contains($message, $sale->category_name)) {
            $requiredDetails[] = 'الفئة: '.$sale->category_name;
        }
        if (! str_contains($message, $sale->pin_code)) {
            $requiredDetails[] = 'رمز الكرت: '.$sale->pin_code;
        }

        return $message.($requiredDetails ? "\n".implode("\n", $requiredDetails) : '')
            ."\nتم خصم ".number_format((float) $sale->amount, 2, '.', ',')
            .' YER. رصيدك الحالي هو '.number_format($remainingBalance, 2, '.', ',').' YER.';
    }

}
