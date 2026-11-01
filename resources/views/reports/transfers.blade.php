@extends('layouts.app')

@section('title', 'تقارير المبيعات والعمليات المالية')

@section('content')
<div class="space-y-5">
    <section class="bg-slate-900 border border-amber-500/30 p-5 rounded-xl shadow-xl">
        <h2 class="text-base font-bold text-amber-400 mb-4 flex items-center gap-2">
            <span>تقارير المبيعات والعمليات المالية</span>
        </h2>

        <form method="GET" action="{{ route('reports.transfers') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 items-end">
            <div class="min-w-0">
                <label for="from_date" class="block text-xs font-medium text-slate-300 mb-1">من تاريخ</label>
                <input id="from_date" type="date" name="from_date" value="{{ request('from_date') }}" class="w-full min-w-0 bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
            </div>

            <div class="min-w-0">
                <label for="to_date" class="block text-xs font-medium text-slate-300 mb-1">إلى تاريخ</label>
                <input id="to_date" type="date" name="to_date" value="{{ request('to_date') }}" class="w-full min-w-0 bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
            </div>

            <div class="min-w-0">
                <label for="type" class="block text-xs font-medium text-slate-300 mb-1">نوع الحركة</label>
                <select id="type" name="type" class="w-full min-w-0 bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                    <option value="">جميع الحركات</option>
                    <option value="sales" {{ request('type') == 'sales' ? 'selected' : '' }}>مبيعات</option>
                    <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>إيداع / تحويل</option>
                </select>
            </div>

            <div class="min-w-0">
                <label for="phone" class="block text-xs font-medium text-slate-300 mb-1">بحث باسم العميل أو رقم الهاتف</label>
                <input id="phone" type="search" name="phone" value="{{ request('phone') }}" placeholder="أدخل اسم العميل أو رقم الهاتف" class="w-full min-w-0 bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 min-w-0">
                <button type="submit" class="w-full min-h-10 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-2 px-4 rounded-lg text-sm transition-all shadow-md shadow-amber-500/20">
                    عرض التقرير
                </button>
                <button type="submit" name="print" value="1" formtarget="_blank" class="w-full min-h-10 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-2 px-4 rounded-lg text-sm transition-all shadow-md shadow-amber-500/20">
                    طباعة التقرير (PDF)
                </button>
            </div>
        </form>
    </section>

    {{-- 2. شريط ملخص إحصائيات وتفاصيل مبيعات الكروت (الموقع الجديد أعلى الجدول) --}}
    <div class="bg-slate-900 border border-amber-500/30 rounded-xl p-4 shadow-xl">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 text-center divide-x divide-x-reverse divide-slate-800">
            
            {{-- إجمالي المبالغ --}}
            <div class="px-2">
                <p class="text-xs text-slate-400 font-medium mb-1">إجمالي المبالغ</p>
                <p class="text-lg md:text-xl font-bold text-slate-100 tracking-wide">
                    {{ number_format($totalAmount ?? 0, 2) }} <span class="text-xs text-amber-400 font-normal">ر.ي</span>
                </p>
            </div>

            {{-- صافي الربح --}}
            <div class="px-2">
                <p class="text-xs text-slate-400 font-medium mb-1">صافي الربح</p>
                <p class="text-lg md:text-xl font-bold text-emerald-400 tracking-wide">
                    {{ number_format($netProfit ?? 0, 2) }} <span class="text-xs text-emerald-500 font-normal">ر.ي</span>
                </p>
            </div>

            <div class="px-2">
                <p class="text-xs text-slate-400 font-medium mb-1">تكلفة الكروت المباعة</p>
                <p class="text-lg md:text-xl font-bold text-slate-100 tracking-wide">
                    {{ number_format($totalCost ?? 0, 2) }} <span class="text-xs text-amber-400 font-normal">ر.ي</span>
                </p>
            </div>

            {{-- الكروت المباعة --}}
            <div class="px-2">
                <p class="text-xs text-slate-400 font-medium mb-1">الكروت المباعة</p>
                <p class="text-lg md:text-xl font-bold text-slate-100 tracking-wide">
                    {{ $soldCardsCount ?? 0 }} <span class="text-xs text-amber-400 font-normal">كرت</span>
                </p>
            </div>

            <div class="px-2">
                <p class="text-xs text-slate-400 font-medium mb-1">المخزون المتبقي</p>
                <p class="text-lg md:text-xl font-bold text-slate-100 tracking-wide">
                    {{ number_format($availableCardsCount ?? 0) }} <span class="text-xs text-amber-400 font-normal">كرت</span>
                </p>
                <p class="text-xs text-slate-500">{{ number_format($remainingStockValue ?? 0, 2) }} ر.ي تكلفة</p>
            </div>

            {{-- مرفوض / يدوي --}}
            <div class="px-2">
                <p class="text-xs text-slate-400 font-medium mb-1">مرفوض/يدوي</p>
                <p class="text-lg md:text-xl font-bold text-amber-500 tracking-wide">
                    {{ $rejectedCount ?? 0 }}
                </p>
            </div>

        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-amber-500/30 bg-slate-900 p-4 shadow-xl">
            <p class="text-xs text-slate-300">عملاء مسجلون في الفترة</p>
            <p class="mt-1 text-lg font-bold text-amber-400">{{ number_format($newCustomersCount) }}</p>
        </div>
        <div class="rounded-xl border border-amber-500/30 bg-slate-900 p-4 shadow-xl">
            <p class="text-xs text-slate-300">الفئة الأعلى مبيعًا</p>
            <p class="mt-1 font-bold text-amber-400">{{ $bestSellingCategory->category_name ?? 'لا توجد مبيعات' }}</p>
            @if($bestSellingCategory)<p class="text-xs text-slate-300">{{ number_format((float) $bestSellingCategory->total_amount, 2) }} ر.ي · {{ number_format($bestSellingCategory->cards_count) }} كرت</p>@endif
        </div>
        <div class="rounded-xl border border-amber-500/30 bg-slate-900 p-4 shadow-xl">
            <p class="text-xs text-slate-300">الفئة الأقل مبيعًا</p>
            <p class="mt-1 font-bold text-amber-400">{{ $leastSellingCategory->category_name ?? 'لا توجد مبيعات' }}</p>
            @if($leastSellingCategory)<p class="text-xs text-slate-300">{{ number_format((float) $leastSellingCategory->total_amount, 2) }} ر.ي · {{ number_format($leastSellingCategory->cards_count) }} كرت</p>@endif
        </div>
    </div>

    {{-- 3. جدول نتائج العمليات باللون الكحلي والذهبي --}}
    <div class="bg-slate-900 border border-amber-500/30 rounded-xl overflow-hidden shadow-xl">
        <div class="p-4 bg-slate-800/80 border-b border-amber-500/20 flex justify-between items-center">
            <h3 class="text-sm font-bold text-amber-400">نتائج العمليات المالية</h3>
        </div>

        <div id="report-customer-match" class="hidden border-b border-amber-500/20 bg-slate-800/80 px-4 py-3 text-sm text-amber-200" aria-live="polite"></div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-950 text-amber-400 font-semibold border-b border-amber-500/20">
                    <tr>
                        <th class="p-3.5">#</th>
                        <th class="p-3.5">التاريخ</th>
                        <th class="p-3.5">نوع الحركة</th>
                        <th class="p-3.5">المبلغ</th>
                        <th class="p-3.5">البيان / التفاصيل</th>
                        <th class="p-3.5 text-center">الحالة</th>
                        <th class="p-3.5 text-center">حذف الحركة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-200">
                    @forelse($transfers ?? [] as $transfer)
                        <tr
                            class="report-operation-row hover:bg-slate-800/50 transition-colors"
                            data-customer-name="{{ $transfer->customer_name ?? 'عميل غير معروف' }}"
                            data-customer-phone="{{ $transfer->phone ?? '' }}"
                        >
                            <td class="p-3.5 text-slate-400">{{ $loop->iteration }}</td>
                            <td class="p-3.5 text-slate-300">{{ \Illuminate\Support\Carbon::parse($transfer->operation_at)->format('Y-m-d H:i') }}</td>
                            <td class="p-3.5 font-medium text-amber-300">{{ $transfer->type === 'sales' ? 'بيع كرت ابو' : 'حوالة واردة' }}</td>
                            <td class="p-3.5 font-bold text-slate-100">{{ number_format($transfer->amount, 2) }} ر.ي</td>
                            <td class="p-3.5 text-slate-400">
                                <span class="report-search-highlight-target">{{ $transfer->customer_name ?? 'عميل غير معروف' }}</span>
                                @if($transfer->phone)<span dir="ltr">(<span class="report-search-highlight-target">{{ $transfer->phone }}</span>)</span>@endif
                                — {{ $transfer->description }}
                                @if($transfer->pin_code) — PIN: {{ $transfer->pin_code }}@endif
                                @if($transfer->wallet_name) — {{ $transfer->wallet_name }}@endif
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="px-2.5 py-1 text-xs rounded-md {{ $transfer->status === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-300 border border-amber-500/30' }}">
                                        {{ match($transfer->status) { 'completed' => 'مكتمل', 'no_stock' => 'لا يوجد مخزون', 'failed' => 'فاشل', default => 'بانتظار المراجعة' } }}
                                    </span>
                                    @if($transfer->type === 'sales')
                                        <form action="{{ route('sales.resend', $transfer->id) }}" method="POST">
                                            @csrf
                                            <button class="text-xs font-bold text-amber-300 hover:text-amber-100">إعادة إرسال الكرت</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3.5 text-center">
                                <form action="{{ $transfer->type === 'sales' ? route('sales.destroy', $transfer->id) : route('transfers.destroy', $transfer->id) }}" method="POST" data-confirm-delete="سيتم حذف سجل الحركة فقط دون تغيير الرصيد أو المخزون. هل تريد المتابعة؟">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-amber-500/40 px-3 py-1.5 text-xs font-bold text-amber-300 transition hover:bg-amber-500 hover:text-slate-950" aria-label="حذف حركة {{ $transfer->type === 'sales' ? 'البيع' : 'التحويل' }}">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        <span>حذف</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td id="report-server-no-results" colspan="7" class="p-8 text-center text-slate-400">
                                لا توجد عمليات مالية مسجلة في هذه الفترة.
                            </td>
                        </tr>
                    @endforelse
                    <tr id="report-live-no-results" class="hidden">
                        <td colspan="7" class="p-8 text-center text-slate-400">
                            لا توجد عمليات مطابقة للبحث في الصفحة الحالية.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-800 p-4">{{ $transfers->links() }}</div>
    </div>
</div>

<style>
    .report-search-highlight {
        background-color: #fef08a;
        color: #854d0e;
        font-weight: 700;
        border-radius: 3px;
        padding: 0 2px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('phone');
    if (!searchInput) return;

    const matchSummary = document.getElementById('report-customer-match');
    const operationRows = document.querySelectorAll('.report-operation-row');
    const targets = document.querySelectorAll('.report-search-highlight-target');
    const originals = Array.from(targets, target => target.textContent);
    const noResultsRow = document.getElementById('report-live-no-results');
    const serverNoResults = document.getElementById('report-server-no-results');

    function searchRanges(original, terms, phoneSearch) {
        const ranges = [];
        const lowerText = original.toLocaleLowerCase();

        terms.forEach(term => {
            let start = 0;
            let matchIndex;
            while ((matchIndex = lowerText.indexOf(term, start)) !== -1) {
                ranges.push([matchIndex, matchIndex + term.length]);
                start = matchIndex + term.length;
            }
        });

        if (phoneSearch) {
            const normalizedPhone = original.replace(/\D/g, '');
            const phoneStart = normalizedPhone.indexOf(phoneSearch);
            if (phoneStart !== -1) {
                let digitIndex = 0;
                let rangeStart = null;
                let previous = null;

                for (let characterIndex = 0; characterIndex < original.length; characterIndex++) {
                    if (/\d/.test(original[characterIndex])) {
                        if (digitIndex >= phoneStart && digitIndex < phoneStart + phoneSearch.length) {
                            if (rangeStart === null || characterIndex > previous + 1) {
                                if (rangeStart !== null) ranges.push([rangeStart, previous + 1]);
                                rangeStart = characterIndex;
                            }
                            previous = characterIndex;
                        }
                        digitIndex++;
                    }
                }
                if (rangeStart !== null) ranges.push([rangeStart, previous + 1]);
            }
        }

        return ranges
            .sort((first, second) => first[0] - second[0] || first[1] - second[1])
            .reduce((merged, range) => {
                const previous = merged[merged.length - 1];
                if (previous && range[0] <= previous[1]) {
                    previous[1] = Math.max(previous[1], range[1]);
                } else {
                    merged.push([...range]);
                }
                return merged;
            }, []);
    }

    function appendHighlightedText(container, value, terms, phoneSearch) {
        const ranges = searchRanges(value, terms, phoneSearch);
        container.replaceChildren();
        let textOffset = 0;
        ranges.forEach(([rangeStart, rangeEnd]) => {
            container.append(document.createTextNode(value.slice(textOffset, rangeStart)));
            const mark = document.createElement('mark');
            mark.className = 'report-search-highlight';
            mark.textContent = value.slice(rangeStart, rangeEnd);
            container.append(mark);
            textOffset = rangeEnd;
        });
        container.append(document.createTextNode(value.slice(textOffset)));
    }

    function applySearchHighlight() {
        const searchValue = searchInput.value.trim();
        const terms = searchValue.toLocaleLowerCase().split(/\s+/).filter(Boolean);
        const phoneSearch = /^[+\d\s().-]+$/.test(searchValue)
            ? searchValue.replace(/\D/g, '')
            : '';
        const matchedCustomers = new Map();
        let visibleRows = 0;

        operationRows.forEach(row => {
            const name = row.dataset.customerName || '';
            const phone = row.dataset.customerPhone || '';
            const searchableValues = [name, phone];
            const isMatch = terms.length === 0 || (
                terms.every(term => searchableValues.some(value => value.toLocaleLowerCase().includes(term)))
                || (phoneSearch !== '' && phone.replace(/\D/g, '').includes(phoneSearch))
            );

            row.style.display = isMatch ? '' : 'none';
            if (isMatch) {
                visibleRows++;
                if (searchValue) matchedCustomers.set(`${name}|${phone}`, { name, phone });
            }
        });

        if (noResultsRow) {
            noResultsRow.style.display = searchValue && visibleRows === 0 ? '' : 'none';
        }
        if (serverNoResults) {
            serverNoResults.style.display = searchValue ? 'none' : '';
        }

        if (matchSummary) {
            matchSummary.replaceChildren();
            if (!searchValue) {
                matchSummary.classList.add('hidden');
            } else {
                matchSummary.classList.remove('hidden');
                matchSummary.append(document.createTextNode('العميل أو الرقم المطابق في النتائج: '));
                if (matchedCustomers.size === 0) {
                    matchSummary.append(document.createTextNode('لا توجد نتائج مطابقة في الصفحة الحالية.'));
                } else {
                    Array.from(matchedCustomers.values()).forEach(({ name, phone }, index) => {
                        if (index > 0) matchSummary.append(document.createTextNode('، '));
                        const customerElement = document.createElement('strong');
                        customerElement.className = 'text-amber-400';
                        appendHighlightedText(customerElement, name, terms, phoneSearch);
                        matchSummary.append(customerElement);
                        if (phone) {
                            matchSummary.append(document.createTextNode(' — '));
                            const phoneElement = document.createElement('span');
                            phoneElement.dir = 'ltr';
                            appendHighlightedText(phoneElement, phone, terms, phoneSearch);
                            matchSummary.append(phoneElement);
                        }
                    });
                }
            }
        }

        targets.forEach((target, index) => {
            appendHighlightedText(target, originals[index], terms, phoneSearch);
        });
    }

    searchInput.addEventListener('input', applySearchHighlight);
    applySearchHighlight();
});
</script>
@endsection