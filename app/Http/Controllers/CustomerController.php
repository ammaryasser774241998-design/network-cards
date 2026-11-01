<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\Customer;
use App\Services\CustomerMessageDeliveryService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        $query = Customer::query()->where('is_guest', false)->withCount('sales')->latest();
        if (! empty($validated['search'])) {
            $term = $validated['search'];
            $phone = $this->normalizePhone($term);
            $query->where(function ($customers) use ($term, $phone) {
                $customers->where('full_name', 'LIKE', '%'.$term.'%')
                    ->orWhere('phone_number', 'LIKE', '%'.$term.'%')
                    ->orWhere('bank_account_name', 'LIKE', '%'.$term.'%')
                    ->orWhere('bank_account_number', 'LIKE', '%'.$term.'%');
                if ($phone !== '') {
                    $customers->orWhereJsonContains('additional_phones', $phone);
                }
            });
        }

        $customers = $query->paginate(25)->withQueryString();
        $totalCustomers = Customer::where('is_guest', false)->count();

        return view('customers.index', compact('customers', 'totalCustomers'));
    }

    public function store(Request $request)
    {
        $request->merge(['phone_number' => $this->normalizePhone($request->input('phone_number', ''))]);
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'phone_number')
                    ->where(fn ($query) => TenantContext::scopeQuery($query, 'tenant_id')),
            ],
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'balance' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['balance'] = $validated['balance'] ?? 0;
        $validated['bank_account_name'] = ($validated['bank_account_name'] ?? null) ?: $validated['full_name'];
        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'تمت إضافة العميل بنجاح.');
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'nullable|required_without:contacts|file|mimes:csv,txt|max:10240',
            'contacts' => 'nullable|required_without:file|string|max:1000000',
        ]);

        $text = isset($validated['contacts'])
            ? $validated['contacts']
            : file_get_contents($request->file('file')->getRealPath());

        $created = 0;
        $updated = 0;
        DB::transaction(function () use ($text, &$created, &$updated) {
            foreach (preg_split('/\R/u', $text) as $index => $line) {
                $columns = str_getcsv(trim($line));
                $name = trim($columns[0] ?? '');
                $phone = (string) preg_replace('/\D+/', '', trim($columns[1] ?? ''));
                $accountName = trim($columns[2] ?? '');

                if ($index === 0 && in_array(mb_strtolower($name), ['name', 'full_name', 'الاسم'], true)) {
                    continue;
                }
                if ($name === '' || $phone === '') {
                    continue;
                }

                $customer = Customer::updateOrCreate(
                    ['phone_number' => $phone],
                    [
                        'full_name' => $name,
                        'bank_account_name' => $accountName ?: $name,
                        'status' => 'active',
                        'is_guest' => false,
                    ]
                );
                $customer->wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        if ($created + $updated === 0) {
            return back()->with('error', 'لم يتم استيراد جهات اتصال. يجب أن يحتوي كل سطر على الاسم ورقم الهاتف.');
        }

        return back()->with('success', "تمت مزامنة جهات الاتصال: {$created} جديد، {$updated} محدّث.");
    }

    public function update(Request $request, Customer $customer)
    {
        $request->merge(['phone_number' => $this->normalizePhone($request->input('phone_number', ''))]);
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'phone_number')
                    ->ignore($customer->id)
                    ->where(fn ($query) => TenantContext::scopeQuery($query, 'tenant_id')),
            ],
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'balance' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['bank_account_name'] = ($validated['bank_account_name'] ?? null) ?: $validated['full_name'];
        $validated['is_guest'] = false;
        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'تم تحديث بيانات العميل بنجاح.');
    }

    public function destroy(Customer $customer)
    {
        DB::transaction(fn () => $customer->delete());

        return redirect()->route('customers.index')
            ->with('success', 'تم حذف العميل مع الاحتفاظ بسجلاته المالية السابقة.');
    }

    public function recharge(Request $request, int $id, CustomerMessageDeliveryService $customerMessages)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
        ]);

        DB::transaction(function () use ($id, $validated, $customerMessages) {
            $customer = Customer::whereKey($id)->lockForUpdate()->firstOrFail();
            $customer->increment('balance', $validated['amount']);

            BankTransaction::create([
                'sender_name_raw' => $customer->full_name,
                'wallet_name' => 'شحن يدوي',
                'sender_phone' => $customer->phone_number,
                'customer_id' => $customer->id,
                'amount' => $validated['amount'],
                'raw_message' => 'تمت تغذية رصيد العميل يدوياً من لوحة الإدارة.',
                'status' => 'completed',
                'processed_at' => now(),
                'processing_note' => 'شحن رصيد يدوي.',
            ]);

            $customerMessages->send(
                $customer->phone_number,
                "تمت إضافة {$validated['amount']} ريال إلى رصيدك."
            );
        });

        return redirect()->route('customers.index')->with('success', 'تمت تغذية رصيد العميل وتسجيل العملية.');
    }

    private function normalizePhone(mixed $phone): string
    {
        if (! is_string($phone)) {
            return '';
        }

        return (string) preg_replace('/\D+/', '', $phone);
    }
}
