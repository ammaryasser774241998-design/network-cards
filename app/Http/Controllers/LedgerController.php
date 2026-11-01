<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);
        $search = $validated['search'] ?? null;

        $customers = Customer::query()
            ->withCount('ledgerEntries')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('phone_number', 'like', '%'.$search.'%')
                    ->orWhere('bank_account_name', 'like', '%'.$search.'%')
                    ->orWhere('bank_account_number', 'like', '%'.$search.'%');
            }))
            ->orderBy('full_name')
            ->paginate(15, ['*'], 'accounts_page')
            ->withQueryString();

        $entries = CustomerLedgerEntry::with(['customer', 'creator'])
            ->latest()
            ->paginate(20, ['*'], 'entries_page')
            ->withQueryString();

        return view('ledger.index', compact('customers', 'entries'));
    }

    public function updateAccount(Request $request, Customer $customer)
    {
        $rawPhone = $request->input('phone_number');
        $request->merge([
            'phone_number' => is_string($rawPhone) ? (string) preg_replace('/\D+/', '', $rawPhone) : '',
        ]);
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:30', Rule::unique('customers', 'phone_number')->ignore($customer->id)],
            'full_name' => 'required|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'additional_phones' => 'nullable|array|max:5',
            'additional_phones.*' => 'nullable|string|max:30',
            'debt_limit' => 'nullable|numeric|min:0|max:9999999999.99',
            'credit_sales_enabled' => 'nullable|boolean',
            'card_delivery_enabled' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $phones = collect($validated['additional_phones'] ?? [])
            ->map(fn (string $phone) => preg_replace('/\D+/', '', $phone))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $primaryPhone = preg_replace('/\D+/', '', $validated['phone_number']);
        if (in_array($primaryPhone, $phones, true)) {
            return back()->withErrors([
                'additional_phones' => 'لا يمكن تكرار الرقم الأساسي ضمن الأرقام الإضافية.',
            ])->withInput();
        }

        if ($request->boolean('credit_sales_enabled') && (float) ($validated['debt_limit'] ?? 0) <= 0) {
            return back()->withErrors([
                'debt_limit' => 'حدد سقف مديونية أكبر من صفر قبل تفعيل البيع الآجل.',
            ])->withInput();
        }

        $customer->update([
            'phone_number' => $primaryPhone,
            'full_name' => $validated['full_name'],
            'bank_account_name' => $validated['bank_account_name'] ?: $validated['full_name'],
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'additional_phones' => $phones,
            'debt_limit' => $validated['debt_limit'] ?? null,
            'credit_sales_enabled' => $request->boolean('credit_sales_enabled'),
            'card_delivery_enabled' => $request->boolean('card_delivery_enabled'),
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'تم تحديث حساب العميل وحدود المديونية.');
    }

    public function storeEntry(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')
                    ->where(fn ($query) => TenantContext::scopeQuery($query, 'tenant_id')),
            ],
            'type' => 'required|in:charge,payment',
            'amount' => 'required|numeric|min:0.01|max:9999999999.99',
            'description' => 'required|string|max:500',
        ]);

        $result = DB::transaction(function () use ($request, $validated) {
            $customer = Customer::whereKey($validated['customer_id'])->lockForUpdate()->firstOrFail();
            $amount = round((float) $validated['amount'], 2);
            $debt = round((float) $customer->debt_balance, 2);

            if ($validated['type'] === 'payment' && $amount > $debt) {
                return ['error' => 'لا يمكن تسجيل سداد يتجاوز الدين الحالي للعميل.'];
            }

            if ($validated['type'] === 'charge' && $customer->debt_limit !== null
                && $debt + $amount > (float) $customer->debt_limit) {
                return ['error' => 'القيد يتجاوز سقف المديونية المحدد لهذا العميل.'];
            }

            $customer->update([
                'debt_balance' => $validated['type'] === 'charge' ? $debt + $amount : $debt - $amount,
            ]);
            CustomerLedgerEntry::create([
                'customer_id' => $customer->id,
                'created_by' => $request->user()->id,
                'type' => $validated['type'],
                'amount' => $amount,
                'description' => $validated['description'],
            ]);

            return ['success' => true];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['amount' => $result['error']])->withInput();
        }

        return back()->with('success', 'تم تسجيل القيد وتحديث رصيد الدين.');
    }
}
