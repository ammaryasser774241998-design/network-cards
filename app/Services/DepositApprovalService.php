<?php

namespace App\Services;

use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class DepositApprovalService
{
    public function __construct(private CustomerMessageDeliveryService $customerMessages) {}

    public function creditExistingCustomer(
        BankTransaction $deposit,
        Customer $customer,
        float $amount,
    ): array {
        return DB::transaction(function () use ($deposit, $customer, $amount) {
            $deposit = BankTransaction::whereKey($deposit->id)->lockForUpdate()->firstOrFail();
            if ($deposit->status !== 'pending_customer') {
                abort(409, 'تمت معالجة هذا الإيداع مسبقاً.');
            }

            $lockedCustomer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $amount = round($amount, 2);
            if ($amount <= 0) {
                abort(422, 'لا يمكن إضافة إيداع بمبلغ غير صالح إلى رصيد العميل.');
            }

            $newBalance = round((float) $lockedCustomer->balance + $amount, 2);
            $lockedCustomer->update(['balance' => $newBalance]);
            $deposit->update([
                'amount' => $amount,
                'customer_id' => $lockedCustomer->id,
                'status' => 'completed',
                'processed_at' => now(),
                'processing_note' => 'تمت تغذية رصيد المشترك المسجل تلقائياً وإشعار العميل وصاحب الشبكة.',
            ]);

            return [
                'customer' => $lockedCustomer,
                'amount' => $amount,
                'balance' => $newBalance,
            ];
        });
    }

    public function approve(
        BankTransaction|Transfer $deposit,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?float $amount = null,
    ): array {
        return DB::transaction(function () use ($deposit, $customerName, $customerPhone, $amount) {
            $deposit = BankTransaction::whereKey($deposit->id)->lockForUpdate()->firstOrFail();
            if ($deposit->status !== 'pending_customer') {
                abort(409, 'تمت معالجة هذا الإيداع مسبقاً.');
            }

            $amount = round($amount ?? (float) $deposit->amount, 2);
            if ($amount <= 0) {
                abort(422, 'لا يمكن اعتماد الإيداع قبل التحقق من المبلغ.');
            }

            $customer = $deposit->customer_id
                ? Customer::whereKey($deposit->customer_id)->lockForUpdate()->firstOrFail()
                : $this->findCustomer($customerPhone ?? $deposit->sender_phone);

            $created = false;
            if (! $customer) {
                $name = trim((string) ($customerName ?? $deposit->sender_customer_name));
                $phone = $this->normalizePhone($customerPhone ?? $deposit->sender_phone);

                if ($name === '' || $phone === '') {
                    return ['status' => 'needs_customer_details'];
                }

                $customer = Customer::create([
                    'full_name' => $name,
                    'phone_number' => $phone,
                    'bank_account_name' => $name,
                    'balance' => 0,
                    'status' => 'active',
                    'card_delivery_enabled' => true,
                    'is_guest' => false,
                ]);
                $created = true;
            }

            $newBalance = round((float) $customer->balance + $amount, 2);
            $customer->update(['balance' => $newBalance]);
            $deposit->update([
                'amount' => $amount,
                'customer_id' => $customer->id,
                'status' => 'completed',
                'processed_at' => now(),
                'processing_note' => $created
                    ? 'تمت الموافقة على الإيداع وإنشاء حساب المشترك وإضافة المبلغ إلى رصيده.'
                    : 'تمت الموافقة على الإيداع وإضافة المبلغ إلى رصيد المشترك.',
            ]);
            $this->customerMessages->send(
                $customer->phone_number,
                'تم قبول إيداعك بقيمة '.number_format($amount, 2, '.', ',')
                    .' YER. رصيدك الحالي '.number_format($newBalance, 2, '.', ',')
                    .' YER. لشراء كرت أرسل: كرت ابو [الفئة].'
            );

            return [
                'status' => 'completed',
                'customer' => $customer,
                'created' => $created,
                'balance' => $newBalance,
                'amount' => $amount,
            ];
        });
    }

    public function reject(BankTransaction|Transfer $deposit): void
    {
        DB::transaction(function () use ($deposit) {
            $deposit = BankTransaction::whereKey($deposit->id)->lockForUpdate()->firstOrFail();
            if ($deposit->status !== 'pending_customer') {
                abort(409, 'تمت معالجة هذا الإيداع مسبقاً.');
            }
            $deposit->update([
                'status' => 'completed',
                'processed_at' => now(),
                'processing_note' => 'تم حفظ الإيداع دون تسجيل العميل أو إضافة المبلغ إلى رصيد أي حساب.',
            ]);
        });
    }

    private function findCustomer(?string $phone): ?Customer
    {
        $normalized = $this->normalizePhone($phone);
        if (strlen($normalized) < 7) {
            return null;
        }

        $suffix = substr($normalized, -9);
        $matches = Customer::query()
            ->where(fn ($query) => $query
                ->where('phone_number', $normalized)
                ->orWhere('phone_number', 'LIKE', '%'.$suffix)
                ->orWhereJsonContains('additional_phones', $normalized)
                ->orWhereJsonContains('additional_phones', $suffix))
            ->limit(2)
            ->lockForUpdate()
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function normalizePhone(?string $phone): string
    {
        return (string) preg_replace('/\D+/', '', (string) $phone);
    }
}
