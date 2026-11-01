@extends('layouts.app')

@section('title', 'الحساب')

@section('content')
<div class="mx-auto max-w-3xl space-y-6" dir="rtl">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">الحساب</h2>
        <p class="mt-1 text-sm text-slate-500">عرض بيانات الحساب وتغيير كلمة المرور.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 font-bold text-slate-900">بيانات الحساب</h3>
        <dl class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-sm text-slate-500">اسم المستخدم</dt>
                <dd class="mt-1 font-bold text-slate-900">{{ $user->name }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-sm text-slate-500">رقم الهاتف</dt>
                <dd class="mt-1 font-bold text-slate-900" dir="ltr">{{ $user->phone_number ?: '—' }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2">
                <dt class="text-sm text-slate-500">البريد الإلكتروني</dt>
                <dd class="mt-1 font-bold text-slate-900">{{ $user->email ?: '—' }}</dd>
            </div>
        </dl>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="font-bold text-slate-900">ميزانية الإدارة</h3>
        <p class="mt-2 text-sm text-slate-600">الميزانية الحالية: <strong class="text-slate-900">{{ number_format($currentBudget, 2) }} ر.ي</strong></p>

        <div class="mt-5 grid gap-6 md:grid-cols-2">
            <form action="{{ route('account.budget.update') }}" method="POST" class="space-y-3 rounded-xl bg-slate-50 p-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" value="set">
                <h4 class="font-bold text-slate-800">تعديل مبلغ الميزانية</h4>
                <div>
                    <label for="new_budget" class="mb-1 block text-sm text-slate-700">المبلغ الجديد (ر.ي)</label>
                    <input id="new_budget" name="new_budget" type="number" min="0" max="999999999.99" step="0.01" required value="{{ old('new_budget', number_format($currentBudget, 2, '.', '')) }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2" dir="ltr">
                    @error('new_budget')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-700">حفظ الميزانية</button>
            </form>

            <form action="{{ route('account.budget.update') }}" method="POST" class="space-y-3 rounded-xl bg-slate-50 p-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" value="increase">
                <h4 class="font-bold text-slate-800">زيادة الميزانية</h4>
                <div>
                    <label for="increase_amount" class="mb-1 block text-sm text-slate-700">مبلغ الزيادة (ر.ي)</label>
                    <input id="increase_amount" name="increase_amount" type="number" min="0" max="999999999.99" step="0.01" required value="{{ old('increase_amount') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2" dir="ltr">
                    @error('increase_amount')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-slate-950 transition hover:bg-amber-400">إضافة إلى الميزانية</button>
            </form>
        </div>
        <p class="mt-3 text-xs text-slate-500">الحد الأعلى للميزانية 999,999,999.99 ر.ي.</p>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="font-bold text-slate-900">كلمة المرور</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            لا يمكن عرض كلمة المرور الحالية لأنها محفوظة بشكل مشفّر لحماية الحساب. يمكنك تعيين كلمة مرور جديدة من هنا.
        </p>

        <form action="{{ route('account.password.update') }}" method="POST" class="mt-5 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="current_password" class="mb-1 block text-sm font-bold text-slate-700">كلمة المرور الحالية</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
                @error('current_password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-bold text-slate-700">كلمة المرور الجديدة</label>
                <input id="password" name="password" type="password" required minlength="6" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
                @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-bold text-slate-700">تأكيد كلمة المرور الجديدة</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="6" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            </div>
            <button type="submit" class="rounded-lg bg-amber-500 px-5 py-2 font-bold text-slate-950 transition hover:bg-amber-400">تغيير كلمة المرور</button>
        </form>
    </section>
</div>
@endsection
