@extends('layouts.app')

@section('content')


<div class="space-y-6">
  <div class="bg-slate-900 text-white p-4 rounded-2xl shadow-md flex flex-wrap justify-between items-center gap-4 border border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-amber-400 flex items-center gap-2">
                <i class="fa-solid fa-building-columns text-lg"></i>
                <span>الإيداعات والتحويلات</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">تُراجع الإيداعات قبل إنشاء حساب أو إضافة رصيد؛ وافق فقط بعد التحقق من اسم العميل ورقمه ومبلغ الإيداع.</p>
        </div>
                </div>


    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-slate-900 text-white text-sm">
                    <th class="p-4">رقم الإشعار</th>
                    <th class="p-4">اسم العميل</th>
                    <th class="p-4">البنك / المحفظة</th>
                    <th class="p-4">المبلغ</th>
                    <th class="p-4">الحالة</th>
                    <th class="p-4">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($transfers ?? [] as $transfer)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-4 font-mono font-bold">{{ $transfer->ref_number ?? '-' }}</td>
                        <td class="p-4 font-medium">
                            {{ $transfer->customer->full_name ?? $transfer->sender_customer_name ?? 'عميل غير مربوط' }}
                            @if($transfer->sender_phone)
                                <div class="text-xs text-slate-500" dir="ltr">{{ $transfer->sender_phone }}</div>
                            @endif
                        </td>
                        <td class="p-4">{{ $transfer->wallet_name ?? $transfer->sender_name_raw ?? '-' }}</td>
                        <td class="p-4 font-bold text-green-600">{{ number_format($transfer->amount, 2) }} YER</td>
                        <td class="p-4">
                            @if($transfer->status == 'completed')
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold">مكتمل</span>
                            @elseif($transfer->status == 'failed')
                                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold">مرفوض</span>
                            @elseif($transfer->status == 'no_stock')
                                <span class="bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-xs font-bold">لا يوجد مخزون</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-bold">قيد الانتظار</span>
                            @endif
                        </td>
                        <td class="p-4">
                            @if($transfer->status === 'pending_customer' && ! $transfer->customer_id)
                                <div class="min-w-64 space-y-3">
                                    <p class="text-xs font-bold text-amber-700">
                                        إيداع جديد من عميل غير مربوط.                                         هل تريد تسجيل العميل كمشترك وإضافة الإيداع إلى رصيده؟
                                    </p>
                                    <form action="{{ route('transfers.register-customer', $transfer) }}" method="POST" class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                                        @csrf
                                        <label class="block text-xs font-medium text-slate-600">
                                            اسم العميل
                                            <input name="full_name" value="{{ old('full_name', $transfer->sender_customer_name) }}" required maxlength="255" class="mt-1 w-full rounded border-slate-300 text-sm">
                                        </label>
                                        <label class="block text-xs font-medium text-slate-600">
                                            رقم الهاتف
                                            <input name="phone_number" value="{{ old('phone_number', $transfer->sender_phone) }}" required maxlength="20" inputmode="tel" dir="ltr" class="mt-1 w-full rounded border-slate-300 text-sm">
                                        </label>
                                        <label class="block text-xs font-medium text-slate-600">
                                            مبلغ الإيداع المؤكد
                                            <input name="amount" value="{{ old('amount', $transfer->amount > 0 ? $transfer->amount : '') }}" required type="number" min="0.01" step="0.01" class="mt-1 w-full rounded border-slate-300 text-sm">
                                        </label>
                                        <button class="w-full rounded bg-green-600 px-3 py-2 text-xs font-bold text-white hover:bg-green-700">
                                            تسجيل المشترك وإضافة الرصيد
                                        </button>
                                    </form>
                                    <form action="{{ route('transfers.skip-customer', $transfer) }}" method="POST" onsubmit="return confirm('سيتم حفظ الإيداع دون تسجيل العميل أو إضافة رصيد. هل تريد المتابعة؟')">
                                        @csrf
                                        <button class="w-full rounded border border-slate-300 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100">
                                            لا، احفظ الإيداع دون تسجيل العميل أو إضافة رصيد
                                        </button>
                                    </form>
                                    @if((float) $transfer->amount <= 0)
                                        <p class="text-xs font-medium text-rose-600">تعذر استخراج المبلغ من الرسالة؛ تحقق منه ثم سجّله لإكمال ربط الإيداع.</p>
                                    @endif
                                    <details class="text-xs text-slate-500">
                                        <summary class="cursor-pointer">عرض نص إشعار البنك</summary>
                                        <p class="mt-1 whitespace-pre-wrap">{{ $transfer->raw_message }}</p>
                                    </details>
                                </div>
                            @elseif($transfer->status == 'pending_customer')
                                <div class="flex gap-2">
                                <form action="{{ route('transfers.approve', $transfer->id) }}" method="POST" class="space-y-2">
                                    @csrf
                                    <input name="amount" type="number" min="0.01" step="0.01" value="{{ $transfer->amount > 0 ? $transfer->amount : '' }}" required class="w-32 rounded border-slate-300 text-xs" aria-label="مبلغ الإيداع المؤكد">
                                    <button class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded font-bold">موافقة وإضافة الرصيد</button>
                                </form>
                                <form action="{{ route('transfers.reject', $transfer->id) }}" method="POST">
                                    @csrf
                                    <button class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1.5 rounded font-bold">لا، لا تسجل العميل</button>
                                </form>
                                </div>
                            @elseif($transfer->processing_note)
                                <span class="text-xs text-slate-500">{{ $transfer->processing_note }}</span>
                            @endif
                            <form action="{{ route('transfers.destroy', $transfer) }}" method="POST" class="mt-2" data-confirm-delete="سيتم حذف سجل حركة التحويل فقط دون تغيير رصيد العميل. هل تريد المتابعة؟">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded border border-amber-500/40 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-500 hover:text-slate-950">
                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i> حذف الحركة
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-gray-500">لا توجد عمليات تحويل مسجلة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t p-4">{{ $transfers->links() }}</div>
    </div>
</div>
@endsection