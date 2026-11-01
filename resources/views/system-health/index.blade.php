@extends('layouts.app')

@section('title', 'أداء النظام والروبوت')

@section('content')
<div class="space-y-6" dir="rtl">
    <header>
        <h2 class="text-2xl font-bold text-slate-900">أداء النظام والروبوت</h2>
        <p class="mt-1 text-sm text-slate-500">ملخص آخر 30 يومًا (من {{ $since->format('Y-m-d') }}). الحالات قيد المراجعة مستبعدة من نسبة نجاح المعالجة.</p>
    </header>

    <section class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">نسبة نجاح الحوالات المعالجة</p>
            <p class="mt-2 text-2xl font-extrabold text-emerald-700">{{ $successRate === null ? '—' : number_format($successRate, 1).'%' }}</p>
        </div>
        @foreach(['completed' => ['حوالات مكتملة', 'text-emerald-700'], 'pending_customer' => ['قيد المراجعة', 'text-amber-700'], 'failed' => ['حوالات فاشلة', 'text-rose-700'], 'no_stock' => ['لا يوجد مخزون', 'text-orange-700']] as $status => [$label, $color])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-extrabold {{ $color }}">{{ number_format((int) ($transactionCounts[$status] ?? 0)) }}</p>
            </div>
        @endforeach
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="mb-4 font-bold text-slate-900">حالة قائمة رسائل SMS</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach(['pending' => 'بانتظار الإرسال', 'sending' => 'قيد الإرسال', 'sent' => 'أُرسلت بنجاح', 'failed' => 'فشل الإرسال'] as $status => $label)
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">{{ $label }}</p>
                    <p class="text-lg font-bold text-slate-900">{{ number_format((int) ($smsCounts[$status] ?? 0)) }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-slate-500">الإرسال الفعلي يعتمد على اتصال تطبيق Android المرافق بالخادم ومنح أذونات SMS وإتاحة الشريحة المحددة.</p>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b p-5"><h3 class="font-bold text-slate-900">أحدث الحوالات الفاشلة أو التي لم يتوفر لها مخزون</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr><th class="p-3">التاريخ</th><th class="p-3">المرسل / العميل</th><th class="p-3">المحفظة</th><th class="p-3">المبلغ</th><th class="p-3">الحالة والتفاصيل</th><th class="p-3">الإجراء</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentFailures as $transaction)
                        <tr>
                            <td class="p-3">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3">{{ $transaction->customer->full_name ?? $transaction->sender_name_raw ?? '-' }}<br><span dir="ltr" class="text-xs text-slate-500">{{ $transaction->sender_phone }}</span></td>
                            <td class="p-3">{{ $transaction->wallet_name ?? '-' }}</td>
                            <td class="p-3 font-bold">{{ number_format((float) $transaction->amount, 2) }} ر.ي</td>
                            <td class="p-3">{{ $transaction->status === 'no_stock' ? 'لا يوجد مخزون' : 'فشل' }}<br><span class="text-xs text-slate-500">{{ $transaction->processing_note ?? '-' }}</span></td>
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
                        <tr><td colspan="6" class="p-6 text-center text-slate-500">لا توجد حالات فشل أو نقص مخزون.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
