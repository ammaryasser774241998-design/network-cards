@extends('layouts.app')

@section('title', 'سجل الورديات')

@section('content')
<div class="space-y-6" dir="rtl">
    <header>
        <h2 class="text-2xl font-bold text-slate-900">سجل الورديات المقفلة</h2>
        <p class="mt-1 text-sm text-slate-500">ملخصات محفوظة للورديات؛ إقفال الوردية لا يحذف المبيعات أو الحوالات من السجل المركزي.</p>
    </header>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="p-3">رقم الوردية</th><th class="p-3">من</th><th class="p-3">إلى</th>
                        <th class="p-3">المبيعات</th><th class="p-3">عدد الكروت</th>
                        <th class="p-3">تكلفة الكروت</th><th class="p-3">الحوالات المكتملة</th><th class="p-3">أُقفلت بواسطة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($shifts as $shift)
                        <tr>
                            <td class="p-3 font-bold">#{{ $shift->id }}</td>
                            <td class="p-3">{{ $shift->opened_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3">{{ $shift->closed_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3">{{ number_format((float) $shift->sales_amount, 2) }} ر.ي</td>
                            <td class="p-3">{{ number_format($shift->sales_count) }}</td>
                            <td class="p-3">{{ number_format((float) $shift->sales_cost, 2) }} ر.ي</td>
                            <td class="p-3">{{ number_format((float) $shift->transactions_amount, 2) }} ر.ي ({{ number_format($shift->transactions_count) }} إشعار)</td>
                            <td class="p-3">{{ $shift->closer->name ?? 'النظام' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-6 text-center text-slate-500">لا توجد ورديات مقفلة بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t p-4">{{ $shifts->links() }}</div>
    </section>
</div>
@endsection
