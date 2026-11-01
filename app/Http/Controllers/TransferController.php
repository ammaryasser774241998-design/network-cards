<?php

namespace App\Http\Controllers;

use App\Models\CardCategory;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\SystemSetting;
use App\Models\Transfer;
use App\Models\Voucher;
use App\Services\CardSaleService;
use App\Services\CustomerMessageDeliveryService;
use App\Services\DepositApprovalService;
use App\Services\PhoneExclusionService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransferController extends Controller
{
    // عرض صفحة البيع المباشر
    public function directSaleIndex()
    {
        // استدعاء 'vouchers' مباشرة دون استخدام as vouchers_count
        $categories = CardCategory::withCount(['vouchers' => function ($query) {
            $query->where('status', 'available');
        }])->get();

        return view('direct_sales.index', compact('categories'));
    }

    public function directSaleStore(
        Request $request,
        CardSaleService $saleService,
        CustomerMessageDeliveryService $customerMessages,
        PhoneExclusionService $phoneExclusions,
    ) {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'category_id' => [
                'required',
                Rule::exists('card_categories', 'id')
                    ->where(fn ($query) => TenantContext::scopeQuery($query, 'tenant_id')),
            ],
            'quantity' => 'required|integer|min:1|max:100',
        ], [
            'phone.required' => 'يرجى إدخال رقم هاتف الزبون.',
            'category_id.required' => 'يرجى اختيار الفئة.',
        ]);

        $quantity = (int) $validated['quantity'];
        $sale = DB::transaction(function () use ($validated, $quantity, $saleService, $phoneExclusions) {
            $category = CardCategory::whereKey($validated['category_id'])->lockForUpdate()->firstOrFail();
            $bonusQuantity = $quantity >= $category->bonus_min_quantity
                ? (int) $category->bonus_cards
                : 0;
            $totalVoucherQuantity = $quantity + $bonusQuantity;
            $vouchers = Voucher::with('category')
                ->where('category_id', $validated['category_id'])
                ->where('status', 'available')
                ->orderBy('id')
                ->limit($totalVoucherQuantity)
                ->lockForUpdate()
                ->get();

            if ($vouchers->count() !== $totalVoucherQuantity) {
                return ['status' => 'out_of_stock'];
            }

            $phone = $validated['phone'];
            if ($phoneExclusions->excludes($phone)) {
                return ['status' => 'delivery_disabled'];
            }
            $customer = Customer::where('phone_number', $phone)->lockForUpdate()->first();
            $isRegisteredCustomer = $customer !== null && ! $customer->is_guest;
            if ($customer && ($phoneExclusions->excludes($phone, $customer)
                || $customer->status !== 'active' || ! $customer->card_delivery_enabled)) {
                return ['status' => 'delivery_disabled'];
            }

            $unitPrice = $quantity >= $category->wholesale_min_quantity && $category->wholesale_price !== null
                ? (float) $category->wholesale_price
                : (float) $category->price;
            $priceInCents = (int) round($unitPrice * 100);
            $totalAmount = $priceInCents * $quantity / 100;
            $balanceInCents = $isRegisteredCustomer
                ? (int) round((float) $customer->balance * 100)
                : 0;
            $requiredInCents = $priceInCents * $quantity;
            $debtInCents = $isRegisteredCustomer
                ? (int) round((float) $customer->debt_balance * 100)
                : 0;
            $creditLimitInCents = ! $isRegisteredCustomer || $customer->debt_limit === null
                ? null
                : (int) round((float) $customer->debt_limit * 100);
            $shortfallInCents = max(0, $requiredInCents - $balanceInCents);
            if ($isRegisteredCustomer && $shortfallInCents > 0
                && (! $customer->credit_sales_enabled
                    || ! $customer->card_delivery_enabled
                    || $customer->status !== 'active'
                    || $creditLimitInCents === null
                    || $debtInCents + $shortfallInCents > $creditLimitInCents)) {
                return [
                    'status' => 'insufficient_balance',
                    'balance' => $customer->balance,
                    'required' => $totalAmount,
                    'phone' => $customer->phone_number,
                ];
            }

            if (! $isRegisteredCustomer) {
                $storedBudget = json_decode((string) SystemSetting::lock('current_budget', '0'), true);
                $currentBudget = json_last_error() === JSON_ERROR_NONE
                    ? (float) $storedBudget
                    : (float) SystemSetting::read('current_budget', 0);
                $budgetInCents = (int) round($currentBudget * 100);

                if ($budgetInCents < $requiredInCents) {
                    return [
                        'status' => 'insufficient_budget',
                        'budget' => $budgetInCents / 100,
                        'required' => $totalAmount,
                    ];
                }

                SystemSetting::write(
                    'current_budget',
                    number_format(($budgetInCents - $requiredInCents) / 100, 2, '.', '')
                );
            }

            if ($isRegisteredCustomer && $shortfallInCents > 0) {
                $customer->update([
                    'balance' => 0,
                    'debt_balance' => ($debtInCents + $shortfallInCents) / 100,
                ]);
                CustomerLedgerEntry::create([
                    'customer_id' => $customer->id,
                    'type' => 'charge',
                    'amount' => $shortfallInCents / 100,
                    'description' => 'بيع آجل لكروت الشبكة.',
                ]);
            } elseif ($isRegisteredCustomer) {
                $customer->decrement('balance', $totalAmount);
            }

            $saleCustomer = $isRegisteredCustomer ? $customer : null;
            $cardSales = $vouchers->values()->map(function (Voucher $voucher, int $index) use (
                $saleService,
                $saleCustomer,
                $phone,
                $quantity,
                $priceInCents,
            ) {
                $isBonus = $index >= $quantity;

                return $saleService->sell(
                    $voucher,
                    $saleCustomer,
                    $phone,
                    'direct',
                    saleAmount: $isBonus ? 0 : $priceInCents / 100,
                    sourceOverride: $isBonus ? 'bonus' : null,
                );
            });

            return [
                'status' => 'completed',
                'customer' => $saleCustomer,
                'cardSales' => $cardSales,
            ];
        });

        if ($sale['status'] === 'out_of_stock') {
            return back()->with('error', 'عفواً، لا توجد كروت متاحة حالياً في هذه الفئة.');
        }

        if ($sale['status'] === 'insufficient_balance') {
            $customerMessages->send(
                $sale['phone'],
                'نعتذر، تعذّر إتمام عملية شراء الكرت لأن رصيدك غير كافٍ. يرجى شحن رصيدك ثم إعادة المحاولة.'
            );

            return back()->with(
                'error',
                'رصيد العميل غير كافٍ. الرصيد الحالي: '.number_format((float) $sale['balance'], 2)
                    .'، والمطلوب: '.number_format((float) $sale['required'], 2).' ريال.'
            );
        }
        if ($sale['status'] === 'insufficient_budget') {
            return back()->with(
                'error',
                'ميزانية النظام غير كافية. الميزانية الحالية: '.number_format((float) $sale['budget'], 2)
                    .'، والمطلوب: '.number_format((float) $sale['required'], 2).' ريال.'
            );
        }
        if ($sale['status'] === 'delivery_disabled') {
            return back()->with('error', 'حساب العميل موقوف أو تم تعطيل إرسال الكروت إليه.');
        }

        $customer = $sale['customer'];
        $firstSale = $sale['cardSales']->first();

        return back()->with('last_transfer', [
            'customer_phone' => $customer?->phone_number ?? $validated['phone'],
            'customer_name' => $customer?->full_name ?? $validated['phone'],
            'cards' => $sale['cardSales']->map(fn ($cardSale) => [
                'pin_code' => $cardSale->pin_code,
                'serial_number' => $cardSale->serial_number,
            ])->all(),
            'category_name' => $firstSale->category_name,
            'price' => $sale['cardSales']->sum('amount'),
            'date' => $firstSale->sold_at->format('Y-m-d H:i'),
            'delivery_status' => 'attempted',
        ]);
    }

    // عرض قائمة التحويلات والإيداعات
    public function index()
    {
        $transfers = Transfer::with('customer')->latest()->paginate(25);

        return view('transfers.index', compact('transfers'));
    }

    public function destroy(Transfer $transfer)
    {
        DB::transaction(fn () => $transfer->delete());

        return back()->with('success', 'تم حذف سجل حركة التحويل فقط؛ لم يتغير رصيد العميل.');
    }

    public function registerDepositor(Request $request, Transfer $transfer)
    {
        if ($transfer->status !== 'pending_customer' || $transfer->customer_id !== null) {
            abort(409, 'تمت معالجة الإيداع أو ربطه بحساب مسبقاً.');
        }

        $rawPhone = $request->input('phone_number');
        $request->merge([
            'phone_number' => is_string($rawPhone) ? (string) preg_replace('/\D+/', '', $rawPhone) : '',
        ]);
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'amount' => 'nullable|numeric|min:0.01|max:9999999999.99',
        ]);

        app(DepositApprovalService::class)->approve(
            $transfer,
            $validated['full_name'],
            $validated['phone_number'],
            isset($validated['amount']) ? (float) $validated['amount'] : null,
        );

        return back()->with('success', 'تم إنشاء حساب العميل وربط الإيداع به وإضافة المبلغ إلى رصيده.');
    }

    public function skipCustomerLink(Transfer $transfer, DepositApprovalService $approvalService)
    {
        $approvalService->reject($transfer);

        return back()->with('success', 'تم حفظ الإيداع دون إنشاء حساب للعميل أو إضافة رصيد.');
    }

    // تسجيل تحويل جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bank_transactions', 'ref_number')
                    ->where(fn ($query) => TenantContext::scopeQuery($query, 'tenant_id')),
            ],
            'amount' => 'required|numeric|min:1',
            'bank_name' => 'required|string',
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')
                    ->where(fn ($query) => TenantContext::scopeQuery($query, 'tenant_id')),
            ],
        ]);

        Transfer::create([
            'ref_number' => $validated['reference_number'],
            'sender_name_raw' => $validated['bank_name'],
            'amount' => $validated['amount'],
            'raw_message' => 'إشعار تحويل مسجل يدوياً',
            'customer_id' => $validated['customer_id'],
            'status' => 'pending_customer',
        ]);

        return back()->with('success', 'تم إرسال إشعار التحويل بنجاح وهو قيد المراجعة.');
    }

    // قبول التحويل
    public function approve(Request $request, $id, DepositApprovalService $approvalService)
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01|max:9999999999.99',
        ]);

        $transfer = Transfer::findOrFail($id);
        $approvalService->approve(
            $transfer,
            amount: isset($validated['amount']) ? (float) $validated['amount'] : null,
        );

        return back()->with('success', 'تم قبول الإشعار وتغذية الرصيد بنجاح.');
    }

    // رفض التحويل
    public function reject($id, DepositApprovalService $approvalService)
    {
        $transfer = Transfer::findOrFail($id);
        $approvalService->reject($transfer);

        return back()->with('success', 'تم حفظ الإيداع دون تسجيل العميل أو إضافة المبلغ إلى رصيد.');
    }

    // سجل التحويلات والتقارير
    public function logIndex()
    {
        $transfers = Transfer::latest()->paginate(15);

        return view('transfers.index', compact('transfers'));
    }

    public function reportsIndex()
    {
        return redirect()->route('reports.transfers');
    }
}
