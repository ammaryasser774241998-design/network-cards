@extends('layouts.app')

@section('title', 'إعدادات النظام')

@section('content')
<div class="mx-auto max-w-5xl space-y-6" dir="rtl">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">إعدادات النظام والروبوت</h2>
        <p class="mt-1 text-sm text-slate-500">لا تُفعّل المعالجة الآلية قبل ضبط قواعد الرسائل والتحقق منها بالمحاكاة.</p>
    </div>

    <form action="{{ route('settings.update') }}" method="POST" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <label class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 p-4">
            <span>
                <span class="block font-bold text-slate-800">تشغيل الروبوت الآلي</span>
                <span class="text-xs text-slate-500">عند الإيقاف تُحفظ الحوالات الواردة للمراجعة دون صرف كروت.</span>
            </span>
            <input type="checkbox" name="automation_enabled" value="1" @checked($settings['automation_enabled']) class="h-5 w-5 accent-amber-500">
        </label>

        <div>
            <label for="current_budget" class="mb-1 block text-sm font-bold text-slate-700">ميزانية الإدارة / الرصيد المتاح (ر.ي)</label>
            <input id="current_budget" name="current_budget" type="number" min="0" max="999999999.99" step="0.01" value="{{ old('current_budget', number_format((float) $settings['current_budget'], 2, '.', '')) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            <p class="mt-1 text-xs text-slate-500">يُحدّث هذا الرصيد يدويًا ولا يتغير تلقائيًا مع تسجيل المبيعات أو الحوالات.</p>
            @error('current_budget')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="network_name" class="mb-1 block text-sm font-bold text-slate-700">اسم الشبكة في رسائل العملاء</label>
                <input id="network_name" name="network_name" required maxlength="100" value="{{ old('network_name', $settings['network_name']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">قالب رسالة تسليم الكرت</label>
                <input name="sms_template" required maxlength="1000" value="{{ old('sms_template', $settings['sms_template']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <p class="mt-1 text-xs text-slate-500">المتغيرات: {network}، {category}، {pin}، {serial}، {serial_suffix}، {phone}.</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">رقم هاتف الإدارة للتنبيهات</label>
                <input type="text" name="manager_phone" value="{{ old('manager_phone', $settings['manager_phone']) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
                <p class="mt-1 text-xs text-slate-500">لن تُرسل التنبيهات حتى تهيئة مزود رسائل في الخادم.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">شريحة SIM الافتراضية لتنبيهات النظام</label>
                <select name="sim_slot_default" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="0" @selected((int) old('sim_slot_default', $settings['sim_slot_default']) === 0)>الشريحة 1</option>
                    <option value="1" @selected((int) old('sim_slot_default', $settings['sim_slot_default']) === 1)>الشريحة 2</option>
                </select>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-bold text-slate-700">قواعد المحافظ والرسائل (JSON)</label>
            <p class="mb-2 text-xs text-slate-500">استخدم تعبيرات منتظمة بصيغة PCRE مع مجموعة التقاط أولى، وأحط كل نمط بفاصل مثل ~النمط~u. أي رسالة لا تطابق القواعد تُحال للمراجعة ولا تصرف كرتاً.</p>
            <textarea name="wallet_rules_json" rows="10" placeholder="[{&quot;name&quot;:&quot;اسم المحفظة&quot;,&quot;sender_pattern&quot;:&quot;~BANK~i&quot;,&quot;amount_pattern&quot;:&quot;~المبلغ:\\s*([0-9,]+)~u&quot;,&quot;reference_pattern&quot;:&quot;~رقم:\\s*([A-Z0-9-]+)~u&quot;,&quot;customer_phone_pattern&quot;:&quot;~هاتف:\\s*([0-9]+)~u&quot;}]" class="w-full rounded-lg border border-slate-300 p-3 font-mono text-xs" dir="ltr">{{ old('wallet_rules_json', json_encode($settings['wallet_rules'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
            <p class="mt-2 text-xs text-slate-500">الحقول: name, sender_pattern, amount_pattern، واختيارياً reference_pattern وcustomer_phone_pattern وcustomer_name_pattern.</p>
            @error('wallet_rules_json')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <button class="rounded-lg bg-amber-500 px-5 py-2 font-bold text-slate-950">حفظ الإعدادات</button>
    </form>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="font-bold text-slate-900">مختبر محاكاة قراءة الرسائل</h3>
        <p class="mb-4 mt-1 text-xs text-slate-500">المحاكاة لا تنشئ حوالة أو تبيع كرتاً ولا ترسل رسالة حقيقية.</p>
        <form action="{{ route('settings.simulate') }}" method="POST" class="space-y-3">
            @csrf
            <input name="sender" required placeholder="اسم مرسل الرسالة / المحفظة" value="{{ old('sender') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <textarea name="message" required rows="4" placeholder="نص رسالة الحوالة للاختبار" class="w-full rounded-lg border border-slate-300 p-3"></textarea>
            <button class="rounded-lg bg-slate-900 px-5 py-2 font-bold text-white">تحليل تجريبي</button>
        </form>

        @if(session('simulation'))
            @php($simulation = session('simulation'))
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                @if($simulation['parsed'])
                    <p>المحفظة: <strong>{{ $simulation['parsed']['wallet_name'] }}</strong></p>
                    <p>المبلغ المستخرج: <strong>{{ number_format($simulation['parsed']['amount'], 2) }} ر.ي</strong></p>
                    <p>الفئة: <strong>{{ $simulation['category'] ?? 'لا توجد فئة بهذا السعر' }}</strong></p>
                    <p>المخزون المتاح: <strong>{{ $simulation['available'] }}</strong></p>
                    <p>العميل المطابق: <strong>{{ $simulation['customer'] ?? 'غير معروف' }}</strong></p>
                @else
                    <p class="text-rose-700">لم تطابق الرسالة أياً من قواعد المحافظ المحفوظة.</p>
                @endif
                <p class="mt-2 font-bold">حالة الروبوت: {{ $simulation['automation_enabled'] ? 'مفعّل' : 'متوقف' }} — لم يحدث بيع أو إرسال فعلي.</p>
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="font-bold text-slate-900">تطبيق Android المرافق</h3>
        <p class="mt-2 text-sm text-slate-600">سجّل دخول التطبيق بحساب النظام واسمح باستقبال SMS البنكية والإشعارات. لا يحتاج التطبيق إلى صلاحية إرسال الرسائل؛ لم يتم إعداد مزود رسائل صادر حالياً.</p>
    </section>
</div>

<script>
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(button.dataset.passwordToggle);
            const showPassword = input.type === 'password';

            input.type = showPassword ? 'text' : 'password';
            button.textContent = showPassword ? 'إخفاء' : 'إظهار';
            button.setAttribute('aria-pressed', String(showPassword));
        });
    });
</script>
@endsection
