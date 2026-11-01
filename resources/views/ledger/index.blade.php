@extends('layouts.app')

@section('title', 'الدفتر المحاسبي')

@section('content')
<div class="space-y-6" dir="rtl">
    <header>
        <h2 class="text-2xl font-bold text-slate-900">الدفتر المحاسبي للعملاء</h2>
        <p class="mt-1 text-sm text-slate-500">إدارة أرصدة الديون والدفعات وسقف المديونية وإعدادات البيع الآجل.</p>
    </header>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="mb-4 font-bold text-slate-900">إضافة قيد جديد</h3>
        <form method="POST" action="{{ route('ledger.entries.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            @csrf
            <select name="customer_id" required class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">اختر العميل</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->full_name }} — {{ $customer->phone_number }}</option>
                @endforeach
            </select>
            <select name="type" required class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="charge" @selected(old('type') === 'charge')>إضافة دين</option>
                <option value="payment" @selected(old('type') === 'payment')>تسجيل سداد</option>
            </select>
            <input name="amount" type="number" min="0.01" step="0.01" required value="{{ old('amount') }}" placeholder="المبلغ بالريال" class="rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            <input name="description" required maxlength="500" value="{{ old('description') }}" placeholder="البيان / سبب القيد" class="rounded-lg border border-slate-300 px-3 py-2">
            <div class="md:col-span-4">
                @error('amount')<p class="mb-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                <button class="rounded-lg bg-amber-500 px-5 py-2 font-bold text-slate-950 hover:bg-amber-600">حفظ القيد</button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">حسابات العملاء</h3>
            <form method="GET" action="{{ route('ledger.index') }}" class="flex gap-2">
                <input name="search" value="{{ request('search') }}" placeholder="ابحث بالاسم أو الهاتف" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white">بحث</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="p-3">العميل</th>
                        <th class="p-3">الهاتف</th>
                        <th class="p-3">أرقام إضافية</th>
                        <th class="p-3">الدين / السقف</th>
                        <th class="p-3">الحالة والخدمات</th>
                        <th class="p-3">إعداد الحساب</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr>
                            <td class="p-3 font-bold">{{ $customer->full_name }}</td>
                            <td class="p-3" dir="ltr">{{ $customer->phone_number }}</td>
                            <td class="p-3" dir="ltr">{{ implode(', ', $customer->additional_phones ?? []) ?: '-' }}</td>
                            <td class="p-3 font-bold">
                                {{ number_format((float) $customer->debt_balance, 2) }}
                                <span class="font-normal text-slate-500">/ {{ $customer->debt_limit === null ? 'غير محدد' : number_format((float) $customer->debt_limit, 2) }}</span>
                                <div class="text-xs font-normal text-slate-500">{{ $customer->ledger_entries_count }} قيد</div>
                            </td>
                            <td class="p-3 text-xs">
                                <div>{{ $customer->status === 'active' ? 'نشط' : 'موقوف' }}</div>
                                <div>{{ $customer->credit_sales_enabled ? 'البيع الآجل مفعّل' : 'البيع الآجل متوقف' }}</div>
                                <div>{{ $customer->card_delivery_enabled ? 'إرسال الكروت مسموح' : 'إرسال الكروت متوقف' }}</div>
                            </td>
                            <td class="min-w-72 p-3">
                                <details>
                                    <summary class="cursor-pointer font-bold text-amber-700">تعديل الحساب</summary>
                                    <form method="POST" action="{{ route('ledger.customers.update', $customer) }}" class="mt-3 space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <input name="full_name" required value="{{ $customer->full_name }}" placeholder="اسم العميل" class="w-full rounded border px-2 py-1">
                                        <input name="phone_number" required value="{{ $customer->phone_number }}" placeholder="الهاتف الأساسي" class="w-full rounded border px-2 py-1" dir="ltr">
                                        <input name="bank_account_name" value="{{ $customer->bank_account_name }}" placeholder="اسم الحساب" class="w-full rounded border px-2 py-1">
                                        <input name="bank_account_number" value="{{ $customer->bank_account_number }}" placeholder="رقم الحساب" class="w-full rounded border px-2 py-1" dir="ltr">
                                        @for($phoneIndex = 0; $phoneIndex < 3; $phoneIndex++)
                                            <input name="additional_phones[]" value="{{ $customer->additional_phones[$phoneIndex] ?? '' }}" placeholder="هاتف إضافي {{ $phoneIndex + 1 }}" class="w-full rounded border px-2 py-1" dir="ltr">
                                        @endfor
                                        <input name="debt_limit" type="number" min="0" step="0.01" value="{{ $customer->debt_limit }}" placeholder="سقف المديونية" class="w-full rounded border px-2 py-1" dir="ltr">
                                        <select name="status" class="w-full rounded border px-2 py-1">
                                            <option value="active" @selected($customer->status === 'active')>الحساب نشط</option>
                                            <option value="inactive" @selected($customer->status === 'inactive')>الحساب موقوف</option>
                                        </select>
                                        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="credit_sales_enabled" value="1" @checked($customer->credit_sales_enabled)> السماح بالبيع الآجل</label>
                                        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="card_delivery_enabled" value="1" @checked($customer->card_delivery_enabled)> السماح بإرسال الكروت</label>
                                        <button class="rounded bg-slate-800 px-3 py-1.5 text-xs font-bold text-white">حفظ إعدادات الحساب</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-slate-500">لا توجد حسابات مطابقة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t p-4">{{ $customers->links() }}</div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-5"><h3 class="font-bold text-slate-900">آخر قيود الدفتر</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr><th class="p-3">التاريخ</th><th class="p-3">العميل</th><th class="p-3">نوع القيد</th><th class="p-3">المبلغ</th><th class="p-3">البيان</th><th class="p-3">المستخدم</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $entry)
                        <tr>
                            <td class="p-3">{{ $entry->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3">{{ $entry->customer?->full_name ?? 'عميل محذوف' }}</td>
                            <td class="p-3">{{ $entry->type === 'charge' ? 'إضافة دين' : 'سداد' }}</td>
                            <td class="p-3 font-bold">{{ number_format((float) $entry->amount, 2) }} ر.ي</td>
                            <td class="p-3">{{ $entry->description }}</td>
                            <td class="p-3">{{ $entry->creator->name ?? 'النظام' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-slate-500">لا توجد قيود مسجلة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t p-4">{{ $entries->links() }}</div>
    </section>
</div>
@endsection
