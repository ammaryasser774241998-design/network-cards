@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">لوحة التحكم</h2>
            <p class="text-sm text-slate-500">ملخص المبيعات ({{ $periodLabel }}) والمخزون والحوالات الواردة</p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex items-center gap-2 rounded-xl border border-amber-400/50 bg-slate-900 px-3 py-2 text-sm text-amber-300 shadow-sm" aria-label="التوقيت الحالي في مكة المكرمة">
                <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400" aria-hidden="true"></span>
                <span class="font-bold">مكة المكرمة</span>
                <time id="makkah-clock" data-server-timestamp="{{ now()->timestamp }}" datetime="{{ now()->toIso8601String() }}" dir="ltr">{{ now()->format('Y-m-d H:i:s') }}</time>
            </div>
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-end gap-2">
                <div>
                    <label for="period" class="mb-1 block text-xs font-medium text-slate-600">فترة المبيعات</label>
                    <select id="period" name="period" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="today" @selected($period === 'today')>اليوم</option>
                        <option value="yesterday" @selected($period === 'yesterday')>الأمس</option>
                        <option value="week" @selected($period === 'week')>هذا الأسبوع</option>
                        <option value="month" @selected($period === 'month')>هذا الشهر</option>
                        <option value="all" @selected($period === 'all')>إجمالي المبيعات</option>
                    </select>
                </div>
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-700">تطبيق</button>
            </form>
            <form method="POST" action="{{ route('dashboard.close-shift') }}" onsubmit="return confirm('سيُحفظ ملخص الوردية ويبدأ احتساب وردية جديدة دون حذف السجلات. هل تريد المتابعة؟')">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button class="rounded-lg bg-indigo-100 px-4 py-2 text-sm font-bold text-indigo-800 hover:bg-indigo-200">إقفال الوردية</button>
            </form>
        </div>
    </div>

    <section class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
        <div>
            <h3 class="font-bold text-indigo-950">الوردية الحالية</h3>
            <p class="text-xs text-indigo-800">بدأت {{ $shiftStartedAt->format('Y-m-d H:i') }} — المبيعات {{ number_format($currentShift['sales_amount'], 2) }} ر.ي ({{ number_format($currentShift['sales_count']) }} كرت) — {{ number_format($currentShift['transactions_count']) }} حوالة</p>
        </div>
        <a href="{{ route('shifts.index') }}" class="text-sm font-bold text-indigo-800 underline">سجل الورديات</a>
    </section>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">إجمالي المبيعات — {{ $periodLabel }}</p>
            <p class="mt-2 text-2xl font-extrabold text-emerald-700">{{ number_format($salesAmount, 2) }} ر.ي</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">الكروت المباعة — {{ $periodLabel }}</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ number_format($salesCount) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">الكروت المتاحة</p>
            <p class="mt-2 text-2xl font-extrabold text-amber-600">{{ number_format($availableStockCount) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">قيمة تكلفة المخزون المتاح</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ number_format($stockCostValue, 2) }} ر.ي</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">ميزانية الإدارة (تُحدّث يدويًا)</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ number_format($currentBudget, 2) }} ر.ي</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">إجمالي أرصدة العملاء</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ number_format($customerBalancesTotal, 2) }} ر.ي</p>
            <p class="mt-1 text-xs text-slate-400">الأرصدة المسجلة في حسابات العملاء</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-5 font-bold text-slate-900">تطور المبيعات خلال آخر 7 أيام</h3>
            @php($maxSales = max(1, $dailySales->max('amount')))
            <div class="flex h-56 items-end gap-3 border-b border-slate-200 pb-2">
                @foreach($dailySales as $day)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-xs text-slate-500">{{ number_format($day['amount'], 0) }}</span>
                        <div class="w-full rounded-t-lg bg-amber-400" style="height: {{ max(3, ($day['amount'] / $maxSales) * 75) }}%"></div>
                        <span class="text-xs text-slate-500">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-bold text-slate-900">مخزون الكروت حسب الفئة</h3>
                <a href="{{ route('vouchers.index') }}" class="text-sm font-bold text-amber-700">إدارة المخزون</a>
            </div>
            <div class="space-y-3">
                @forelse($categories as $category)
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                        <div>
                            <p class="font-bold text-slate-800">{{ $category->name }}</p>
                            <p class="text-xs text-slate-500">السعر {{ number_format($category->price, 2) }} ر.ي</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-sm font-bold {{ $category->available_vouchers_count <= $category->low_stock_threshold ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ number_format($category->available_vouchers_count) }} متاح
                        </span>
                    </div>
                @empty
                    <p class="py-8 text-center text-slate-500">لا توجد فئات مضافة.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">آخر الحوالات الواردة</h3>
            <a href="{{ route('transfers.index') }}" class="text-sm font-bold text-amber-700 hover:text-amber-800">إدارة الحوالات</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="p-3">التاريخ</th>
                        <th class="p-3">المحفظة</th>
                        <th class="p-3">العميل</th>
                        <th class="p-3">المبلغ</th>
                        <th class="p-3">رقم الحوالة</th>
                        <th class="p-3">الكرت</th>
                        <th class="p-3">الحالة</th>
                        <th class="p-3">الإجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($latestTransactions as $transaction)
                        <tr>
                            <td class="p-3">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3">{{ $transaction->wallet_name ?? '-' }}</td>
                            <td class="p-3">{{ $transaction->customer->full_name ?? $transaction->sender_name_raw ?? '-' }}<br><span class="text-xs text-slate-500">{{ $transaction->sender_phone ?? $transaction->customer->phone_number ?? '' }}</span></td>
                            <td class="p-3 font-bold">{{ number_format($transaction->amount, 2) }} ر.ي</td>
                            <td class="p-3 font-mono">
                                {{ $transaction->ref_number ?? '-' }}
                                <p class="mt-1 max-w-xs whitespace-normal font-sans text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($transaction->raw_message, 90) }}</p>
                            </td>
                            <td class="p-3 font-mono">{{ $transaction->voucher->pin_code ?? '-' }}</td>
                            <td class="p-3">
                                {{ match($transaction->status) { 'completed' => 'مكتملة', 'no_stock' => 'لا يوجد مخزون', 'failed' => 'فاشلة', default => 'بانتظار المراجعة' } }}
                                @if($transaction->processing_note)
                                    <p class="mt-1 text-xs text-slate-500">{{ $transaction->processing_note }}</p>
                                @endif
                            </td>
                            <td class="p-3">
                                <form action="{{ route('transfers.destroy', $transaction) }}" method="POST" data-confirm-delete="سيتم حذف سجل حركة التحويل فقط دون تغيير رصيد العميل. هل تريد المتابعة؟">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded border border-amber-500/40 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-500 hover:text-slate-950">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i> حذف
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-8 text-center text-slate-500">لا توجد حوالات مسجلة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
