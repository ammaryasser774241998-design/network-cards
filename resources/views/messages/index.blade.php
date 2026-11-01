@extends('layouts.app')

@section('title', 'البث الإعلاني وقائمة الرسائل')

@section('content')
<div class="mx-auto max-w-6xl space-y-6" dir="rtl">
    <header>
        <h2 class="text-2xl font-bold text-slate-900">البث الإعلاني ورسائل العملاء</h2>
        <p class="mt-1 text-sm text-slate-600">إرسال الإعلانات متوقف حتى تهيئة مزود رسائل في الخادم. لن تُرسل رسائل SMS كبديل. تُستبعد جهات البيع غير المسجلة والأرقام غير الصالحة والمكررة والمستثناة.</p>
    </header>

    <form method="POST" action="{{ route('messages.broadcast') }}" onsubmit="return confirm('سيتم محاولة إرسال رسالة لكل عميل مسجل ونشط لديه رقم صالح وغير مستثنى. هل تريد المتابعة؟')" class="space-y-3 rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
        @csrf
        <label for="message" class="block font-bold text-slate-800">نص الإعلان أو العرض</label>
        <textarea id="message" name="message" required maxlength="1000" rows="4" class="w-full rounded-lg border border-slate-300 p-3">{{ old('message') }}</textarea>
        @error('message')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="confirmation" value="1" required>
            أؤكد محاولة إرسال هذه الرسالة إلى جميع العملاء المسجلين والنشطين ذوي الأرقام الصالحة غير المستثناة.
        </label>
        <button class="rounded-lg bg-amber-500 px-5 py-2 font-bold text-slate-950 hover:bg-amber-600">إرسال الإعلان</button>
    </form>

    <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach(['pending' => 'بانتظار الإرسال', 'sending' => 'قيد الإرسال', 'sent' => 'أُرسلت', 'failed' => 'فشلت'] as $status => $label)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900">{{ number_format((int) ($queueCounts[$status] ?? 0)) }}</p>
            </div>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b p-5"><h3 class="font-bold text-slate-900">رسائل SMS القديمة غير المرسلة (للمراجعة أو الحذف فقط)</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr><th class="p-3">التاريخ</th><th class="p-3">الهاتف</th><th class="p-3">الرسالة</th><th class="p-3">الحالة</th><th class="p-3">المحاولات</th><th class="p-3 text-center">إجراء</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentMessages as $message)
                        <tr>
                            <td class="p-3">{{ $message->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3 font-mono" dir="ltr">{{ $message->phone_number }}</td>
                            <td class="max-w-lg whitespace-normal p-3">{{ \Illuminate\Support\Str::limit($message->message, 140) }}</td>
                            <td class="p-3">{{ match($message->status) { 'sent' => 'أُرسلت', 'failed' => 'فشلت', 'sending' => 'قيد الإرسال', default => 'بانتظار الإرسال' } }}</td>
                            <td class="p-3">{{ $message->attempt_count }}</td>
                            <td class="p-3 text-center">
                                <form method="POST" action="{{ route('messages.destroy', $message) }}" data-confirm-delete="هل تريد حذف هذه الرسالة القديمة من قائمة SMS؟">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-amber-500/40 px-3 py-1.5 text-xs font-bold text-amber-300 transition hover:bg-amber-500 hover:text-slate-950" aria-label="حذف الرسالة إلى {{ $message->phone_number }}">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        <span>حذف</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-slate-500">لا توجد رسائل في القائمة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t p-4">{{ $recentMessages->links() }}</div>
    </section>
</div>
@endsection
