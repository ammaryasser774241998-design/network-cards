<div id="header-controls" class="flex flex-wrap items-center gap-3">
    @php
        $automationEnabled = (bool) \App\Models\SystemSetting::read('automation_enabled', false);
    @endphp
    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-amber-400/30 bg-slate-800 px-3 py-2 shadow-lg shadow-amber-500/5">
        <i class="fa-solid fa-robot text-xl {{ $automationEnabled ? 'robot-gold-glow text-amber-300' : 'robot-offline text-rose-500' }}" aria-hidden="true"></i>
        <form method="POST" action="{{ route('dashboard.automation') }}" data-confirm-automation>
            @csrf
            <input type="hidden" name="enabled" value="{{ $automationEnabled ? 0 : 1 }}">
            <button type="submit" class="rounded-lg border px-3 py-1.5 text-xs font-extrabold transition {{ $automationEnabled ? 'border-amber-300 bg-amber-400 text-slate-950 shadow-md shadow-amber-400/20 hover:bg-amber-300' : 'border-amber-400/50 text-amber-200 hover:bg-amber-400 hover:text-slate-950' }}">
                {{ $automationEnabled ? 'إيقاف' : 'تشغيل' }}
            </button>
        </form>
        <span class="flex items-center gap-1.5 text-[11px] {{ $automationEnabled ? 'text-emerald-300' : 'text-slate-400' }}">
            <span class="h-2 w-2 rounded-full {{ $automationEnabled ? 'animate-pulse bg-emerald-400' : 'bg-slate-500' }}" aria-hidden="true"></span>
            {{ $automationEnabled ? 'يعمل' : 'متوقف' }}
        </span>
    </div>
    <button id="budget-toggle" type="button" data-budget="{{ number_format((float) \App\Models\SystemSetting::read('current_budget', 0), 2) }}" aria-label="إظهار مبلغ الميزانية" aria-pressed="false" class="flex items-center gap-2 rounded-full border border-amber-500/20 bg-slate-800 px-3 py-1 text-sm text-amber-400 transition hover:bg-slate-700">
        <i id="budget-toggle-icon" class="fa-solid fa-wallet" aria-hidden="true"></i>
        <span>الميزانية:</span>
        <span id="budget-value" dir="ltr">****** YER</span>
        <i id="budget-visibility-icon" class="fa-solid fa-eye" aria-hidden="true"></i>
    </button>
</div>
