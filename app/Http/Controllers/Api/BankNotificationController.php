<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\SystemSetting;
use App\Services\DepositApprovalService;
use App\Services\CustomerMessageDeliveryService;
use App\Services\PaymentMessageParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BankNotificationController extends Controller
{
    public function handleSms(
        Request $request,
        PaymentMessageParser $parser,
        DepositApprovalService $depositApprovals,
    ): JsonResponse {
        $validated = $request->validate([
            'sender' => 'required|string|max:255',
            'raw_message' => 'required|string|max:10000',
            'received_at' => 'nullable|date',
            'customer_phone' => 'nullable|string|max:30',
            'reference' => 'nullable|string|max:255',
            'source_device' => 'nullable|string|max:100',
        ]);

        if (! $parser->matchesConfiguredSender($validated['sender'])) {
            return response()->json(['status' => 'ignored']);
        }

        $parsed = $parser->parse($validated['sender'], $validated['raw_message']);

        return $this->processPayment(
            request: $request,
            senderName: $validated['sender'],
            rawMessage: $validated['raw_message'],
            amount: $parsed['amount'] ?? null,
            phone: $validated['customer_phone'] ?? $parsed['customer_phone'] ?? null,
            customerName: $parsed['customer_name'] ?? null,
            walletName: $parsed['wallet_name'] ?? null,
            reference: $validated['reference'] ?? $parsed['reference'] ?? null,
            sourceDevice: $validated['source_device'] ?? null,
            depositApprovals: $depositApprovals,
        );
    }

    public function handleIncomingPayment(
        Request $request,
        DepositApprovalService $depositApprovals,
    ): JsonResponse
    {
        $validated = $request->validate([
            'sender_name' => 'nullable|string|max:255',
            'wallet_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:30',
            'customer_phone' => 'nullable|string|max:30',
            'customer_name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'raw_message' => 'required|string|max:10000',
            'ref_number' => 'nullable|string|max:255',
            'source_device' => 'nullable|string|max:100',
        ]);

        return $this->processPayment(
            request: $request,
            senderName: $validated['sender_name'] ?? null,
            rawMessage: $validated['raw_message'],
            amount: (float) $validated['amount'],
            phone: $validated['customer_phone'] ?? $validated['phone_number'] ?? null,
            customerName: $validated['customer_name'] ?? null,
            walletName: $validated['wallet_name'] ?? null,
            reference: $validated['ref_number'] ?? null,
            sourceDevice: $validated['source_device'] ?? null,
            depositApprovals: $depositApprovals,
        );
    }

    private function processPayment(
        Request $request,
        ?string $senderName,
        string $rawMessage,
        ?float $amount,
        ?string $phone,
        ?string $customerName,
        ?string $walletName,
        ?string $reference,
        ?string $sourceDevice,
        DepositApprovalService $depositApprovals,
    ): JsonResponse {
        $reference = trim((string) $reference) ?: null;
        $messageHash = hash('sha256', implode('|', [
            mb_strtolower(trim((string) $senderName)),
            trim($rawMessage),
            trim((string) $request->input('received_at', '')),
        ]));

        $existing = BankTransaction::where('message_hash', $messageHash)->first();
        if (! $existing && $reference) {
            $existing = BankTransaction::where('ref_number', $reference)->first();
        }
        if ($existing) {
            return response()->json([
                'status' => $existing->status,
                'transaction_id' => $existing->id,
                'duplicate' => true,
            ]);
        }

        $transaction = BankTransaction::create([
            'ref_number' => $reference,
            'message_hash' => $messageHash,
            'sender_name_raw' => $senderName,
            'sender_customer_name' => $customerName,
            'wallet_name' => $walletName,
            'sender_phone' => $phone,
            'amount' => $amount ?? 0,
            'raw_message' => $rawMessage,
            'status' => 'pending_customer',
            'source_device' => $sourceDevice,
        ]);

        $customer = $this->findCustomer($phone, $customerName);
        if ($customer && $amount && SystemSetting::read('automation_enabled', false)) {
            $credit = $depositApprovals->creditExistingCustomer($transaction, $customer, $amount);
            $this->notifyCreditedCustomer($credit['customer'], $credit['balance']);
            $this->notifyManagerOfCredit($transaction, $credit['customer'], $credit['amount'], $credit['balance']);

            return response()->json([
                'status' => 'completed',
                'transaction_id' => $transaction->id,
                'customer_id' => $customer->id,
                'balance' => $credit['balance'],
                'message' => 'تمت تغذية رصيد المشترك المسجل وإشعار العميل وصاحب الشبكة.',
            ]);
        }

        $processingNote = ! SystemSetting::read('automation_enabled', false)
            ? 'الأتمتة متوقفة؛ بانتظار اعتماد الإيداع من صاحب الشبكة.'
            : (! $amount
                ? 'تعذر استخراج مبلغ موثوق؛ بانتظار مراجعة واعتماد صاحب الشبكة.'
                : 'بانتظار اعتماد صاحب الشبكة قبل تسجيل العميل أو إضافة المبلغ إلى الرصيد.');
        $transaction->update([
            'customer_id' => $customer?->id,
            'processing_note' => $processingNote,
        ]);
        $this->notifyManager($transaction, $customer?->full_name ?? $customerName);

        return response()->json([
            'status' => 'pending_customer',
            'transaction_id' => $transaction->id,
            'message' => 'تم حفظ الإيداع؛ لن يضاف إلى رصيد العميل قبل اعتماد صاحب الشبكة.',
        ], 202);
    }

    private function notifyCreditedCustomer(
        Customer $customer,
        float $balance,
    ): void {
        $networkName = (string) SystemSetting::read('network_name', 'شبكتك');
        $message = "أهلاً بك في {$networkName}، لقد تم تغذية حسابك. رصيدك الحالي هو "
            .number_format($balance, 2, '.', ',').' YER.';
        app(CustomerMessageDeliveryService::class)->send($customer->phone_number, $message);
    }

    private function notifyManagerOfCredit(
        BankTransaction $transaction,
        Customer $customer,
        float $amount,
        float $balance,
    ): void {
        $managerPhone = trim((string) SystemSetting::read('manager_phone', ''));
        if ($managerPhone === '') {
            Log::warning('Registered customer deposit was credited, but no manager phone is configured.', [
                'transaction_id' => $transaction->id,
                'customer_id' => $customer->id,
            ]);

            return;
        }

        $message = 'تمت تغذية حساب المشترك المسجل '.$customer->full_name
            .' بمبلغ '.number_format($amount, 2, '.', ',')
            .' YER. الرصيد الإجمالي الحالي '
            .number_format($balance, 2, '.', ',').' YER.';
        app(CustomerMessageDeliveryService::class)->send($managerPhone, $message);
    }

    private function notifyManager(BankTransaction $transaction, ?string $customerName): void
    {
        $managerPhone = trim((string) SystemSetting::read('manager_phone', ''));
        if ($managerPhone === '') {
            Log::warning('Deposit awaits owner approval, but no manager phone is configured.', [
                'transaction_id' => $transaction->id,
            ]);

            return;
        }

        $name = trim((string) $customerName) ?: 'عميل غير معروف';
        $amount = (float) $transaction->amount > 0
            ? number_format((float) $transaction->amount, 2, '.', ',')
            : 'غير معروف';
        $approvalCommand = $transaction->sender_phone
            ? "نعم {$transaction->id}"
            : "نعم {$transaction->id} رقم_العميل";
        $message = "لقد تلقيت إيداعاً من {$name}، بقيمة {$amount} YER.\n"
            ."هل تريد حفظه كمشترك أساسي؟\n"
            ."للموافقة أرسل: {$approvalCommand}\n"
            ."لعدم تسجيله أو إضافة المبلغ إلى رصيد أرسل: لا {$transaction->id}";

        app(CustomerMessageDeliveryService::class)->send($managerPhone, $message);
    }

    private function findCustomer(?string $phone, ?string $name): ?Customer
    {
        if ($phone) {
            $normalized = preg_replace('/\D+/', '', $phone);
            $suffix = substr($normalized, -9);
            $customers = strlen($suffix) >= 7
                ? Customer::query()
                    ->where(fn ($query) => $query
                        ->where('phone_number', $phone)
                        ->orWhere('phone_number', 'LIKE', '%'.$suffix)
                        ->orWhereJsonContains('additional_phones', $normalized)
                        ->orWhereJsonContains('additional_phones', $suffix))
                    ->limit(2)
                    ->get()
                : collect();

            if ($customers->count() === 1) {
                return $customers->first();
            }
        }

        if ($name) {
            $customers = Customer::query()
                ->where(fn ($query) => $query
                    ->whereRaw('LOWER(full_name) = ?', [mb_strtolower(trim($name))])
                    ->orWhereRaw('LOWER(bank_account_name) = ?', [mb_strtolower(trim($name))]))
                ->limit(2)
                ->get();

            return $customers->count() === 1 ? $customers->first() : null;
        }

        return null;
    }
}
