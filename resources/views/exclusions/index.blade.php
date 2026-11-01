@extends('layouts.app')

@section('title', 'قائمة استثناءات أرقام الهواتف')

@section('content')
<div class="mx-auto max-w-5xl space-y-6" dir="rtl">
    <header>
        <h2 class="text-2xl font-bold text-slate-900">قائمة استثناءات أرقام الهواتف</h2>
        <p class="mt-1 text-sm text-slate-600">لن تُصرف كروت تلقائيًا لهذه الأرقام. تبقى إشعارات التحويل محفوظة في سجل الحوالات للمراجعة.</p>
    </header>

    <form method="POST" action="{{ route('exclusions.store') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-3">
        @csrf
        <div>
            <label for="phone_number" class="mb-1 block text-sm font-bold">رقم الهاتف</label>
            <input id="phone_number" name="phone_number" required maxlength="30" value="{{ old('phone_number') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            @error('phone_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="reason" class="mb-1 block text-sm font-bold">سبب الاستثناء (اختياري)</label>
            <input id="reason" name="reason" maxlength="255" value="{{ old('reason') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div class="flex items-end">
            <button class="w-full rounded-lg bg-amber-500 px-4 py-2 font-bold text-slate-950 hover:bg-amber-600">إضافة الرقم</button>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr><th class="p-3">رقم الهاتف</th><th class="p-3">السبب</th><th class="p-3">تاريخ الإضافة</th><th class="p-3">إجراء</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($exclusions as $exclusion)
                        <tr>
                            <td class="p-3 font-mono" dir="ltr">{{ $exclusion->phone_number }}</td>
                            <td class="p-3">{{ $exclusion->reason ?: '-' }}</td>
                            <td class="p-3">{{ $exclusion->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3">
                                <form method="POST" action="{{ route('exclusions.destroy', $exclusion) }}" data-confirm-delete="إزالة هذا الرقم من قائمة الاستثناءات؟">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700">إزالة</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-slate-500">لا توجد أرقام مستثناة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t p-4">{{ $exclusions->links() }}</div>
    </section>
</div>
@endsection
