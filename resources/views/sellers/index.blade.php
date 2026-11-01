@extends('layouts.app')

@section('title', 'إدارة البائعين')

@section('content')
<div class="mx-auto max-w-6xl space-y-6" dir="rtl">
    <header>
        <h2 class="text-2xl font-bold text-slate-900">حسابات البائعين</h2>
        <p class="mt-1 text-sm text-slate-600">لكل بائع مساحة مستقلة تشمل مخزونه وعملاءه ومبيعاته وإعداداته.</p>
    </header>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 font-bold text-slate-900">إنشاء حساب بائع</h3>
        <form method="POST" action="{{ route('sellers.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <label class="text-sm font-semibold text-slate-700">اسم البائع
                <input name="name" required maxlength="255" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </label>
            <label class="text-sm font-semibold text-slate-700">البريد الإلكتروني (اسم الدخول)
                <input name="email" required type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            </label>
            <label class="text-sm font-semibold text-slate-700">رقم الهاتف
                <input name="phone_number" required value="{{ old('phone_number') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            </label>
            <label class="text-sm font-semibold text-slate-700">كلمة المرور (8 أحرف على الأقل)
                <input name="password" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            </label>
            <label class="text-sm font-semibold text-slate-700">تأكيد كلمة المرور
                <input name="password_confirmation" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            </label>
            <div class="flex items-end">
                <button class="rounded-lg bg-amber-500 px-5 py-2 font-bold text-slate-950 hover:bg-amber-400">إنشاء مساحة البائع</button>
            </div>
        </form>
        @if($errors->any())
            <ul class="mt-3 list-inside list-disc text-sm text-rose-600">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <h3 class="p-5 font-bold text-slate-900">البائعون المسجلون</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead><tr><th class="p-3">البائع</th><th class="p-3">البريد</th><th class="p-3">الهاتف</th><th class="p-3">الحالة</th><th class="p-3">الإجراء</th></tr></thead>
                <tbody>
                    @forelse($sellers as $seller)
                        <tr class="border-t border-slate-200">
                            <td class="p-3">{{ $seller->name }}</td>
                            <td class="p-3" dir="ltr">{{ $seller->email }}</td>
                            <td class="p-3" dir="ltr">{{ $seller->phone_number }}</td>
                            <td class="p-3">{{ $seller->is_active ? 'فعال' : 'موقوف' }}</td>
                            <td class="p-3">
                                <form method="POST" action="{{ route('sellers.status', $seller) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $seller->is_active ? 0 : 1 }}">
                                    <button class="rounded-lg border border-amber-500 px-3 py-1 font-bold text-amber-700">
                                        {{ $seller->is_active ? 'إيقاف الدخول' : 'تفعيل الدخول' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-5 text-center text-slate-500">لا يوجد بائعون حتى الآن.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
