<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة كروت الشبكة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --page-bg: #e9edf4;
            --content-bg: #edf2f8;
            --surface-bg: #fffdf7;
            --surface-muted: #e6edf5;
            --gold-border: #ded5bc;
            --navy-text: #17243a;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--page-bg);
        }

        body.bg-gray-100 {
            background-color: var(--page-bg) !important;
        }

        .bg-slate-50 {
            background-color: var(--content-bg) !important;
        }

        .bg-white {
            background-color: var(--surface-bg) !important;
        }

        .bg-slate-100,
        .bg-gray-50 {
            background-color: var(--surface-muted) !important;
        }

        .bg-gray-100 {
            background-color: var(--page-bg) !important;
        }

        .bg-gray-200 {
            background-color: #dbe3ed !important;
        }

        .robot-gold-glow {
            filter: drop-shadow(0 0 4px rgb(251 191 36 / 85%));
            animation: robot-gold-shine 2.2s ease-in-out infinite;
        }

        .robot-offline {
            filter: drop-shadow(0 0 5px rgb(244 63 94 / 80%));
        }

        @keyframes robot-gold-shine {
            0%, 100% { filter: drop-shadow(0 0 3px rgb(251 191 36 / 60%)); }
            50% { filter: drop-shadow(0 0 10px rgb(251 191 36 / 100%)); }
        }

        .border-slate-100,
        .border-slate-200,
        .border-gray-100 {
            border-color: var(--gold-border) !important;
        }

        .border-slate-300,
        .border-gray-300 {
            border-color: #c8d2df !important;
        }

        .text-slate-900 {
            color: var(--navy-text);
        }

        .shadow-sm {
            box-shadow: 0 2px 8px rgb(23 36 58 / 6%), 0 1px 2px rgb(23 36 58 / 4%);
        }

        main table {
            background-color: #0f172a;
            border-color: rgb(30 41 59 / 60%);
            color: #e2e8f0;
        }

        main table thead,
        main table thead tr,
        main table thead th {
            background-color: #020617 !important;
            border-color: rgb(245 158 11 / 20%) !important;
            color: #fbbf24 !important;
        }

        main table thead th {
            border-bottom: 1px solid rgb(245 158 11 / 20%) !important;
        }

        main .overflow-hidden:has(table),
        main .custom-card:has(table) {
            background-color: #0f172a !important;
            border-color: rgb(245 158 11 / 30%) !important;
        }

        main table tbody {
            color: #e2e8f0;
        }

        main table tbody tr,
        main table tbody tr:nth-child(even) {
            background-color: #0f172a !important;
            transition: background-color 150ms ease;
        }

        main table tbody tr:hover {
            background-color: rgb(30 41 59 / 50%) !important;
        }

        main table tbody td {
            background-color: transparent !important;
            border-color: rgb(30 41 59 / 60%) !important;
        }

        @media (max-width: 1023px) {
            #app-content {
                flex-direction: column;
            }

            #system-sidebar {
                width: 100%;
                flex: none;
                border-left: 0;
                border-bottom: 1px solid rgb(245 158 11 / 30%);
                padding: .5rem;
            }

            #system-sidebar > h2 {
                display: none;
            }

            #system-sidebar > nav {
                display: flex;
                gap: .35rem;
                overflow-x: auto;
                overscroll-behavior-x: contain;
                padding-bottom: .25rem;
                scrollbar-width: thin;
                white-space: nowrap;
            }

            #system-sidebar > nav > a {
                flex: 0 0 auto;
                padding: .5rem .65rem;
                font-size: .75rem;
            }

            #system-sidebar > form {
                display: flex;
                justify-content: flex-end;
                margin-top: .25rem;
                border-top: 0;
                padding-top: 0;
            }

            #system-sidebar > form > button {
                width: auto;
                padding: .4rem .65rem;
            }
        }

        @media (max-width: 767px) {
            body {
                overflow-x: hidden;
            }

            header > div {
                gap: .65rem;
                padding: .65rem .75rem .65rem 3.75rem;
            }

            header h1 {
                font-size: clamp(.85rem, 4vw, 1.1rem);
                line-height: 1.3;
            }

            header > div > div:first-child > div {
                width: 2.25rem;
                height: 2.25rem;
                flex: 0 0 auto;
                font-size: 1rem;
            }

            #header-controls {
                width: 100%;
                justify-content: space-between;
                gap: .5rem;
            }

            #header-controls > div,
            #header-controls > button {
                padding: .35rem .5rem;
                font-size: .75rem;
            }

            #header-controls button {
                min-height: 2rem;
                padding: .35rem .5rem;
                font-size: .7rem;
            }

            #header-controls > div {
                gap: .4rem;
            }

            #header-controls > div > i {
                font-size: .95rem;
            }

            main {
                min-width: 0;
                padding: .875rem;
                padding-bottom: calc(6rem + env(safe-area-inset-bottom));
            }

            main table {
                display: block;
                width: max-content;
                min-width: 100%;
                max-width: 100%;
                overflow-x: auto;
                overscroll-behavior-x: contain;
            }

            main table th,
            main table td {
                padding: .45rem .55rem !important;
                font-size: .75rem !important;
                line-height: 1.35;
            }

            main table th {
                white-space: nowrap;
            }

            main table button,
            main table .btn,
            main table a[class*="rounded"] {
                min-height: 2rem;
                padding: .35rem .5rem !important;
                font-size: .7rem !important;
                line-height: 1.25;
            }

            main button,
            main .btn {
                min-height: 2.25rem;
                padding: .4rem .65rem !important;
                font-size: .8rem !important;
                line-height: 1.3;
            }

            main input:not([type="checkbox"]):not([type="radio"]),
            main select,
            main textarea {
                min-height: 2.25rem;
                padding: .4rem .55rem !important;
                font-size: .8rem !important;
            }

            main input:not([type="checkbox"]):not([type="radio"]),
            main select,
            main textarea {
                max-width: 100%;
            }

            footer {
                padding-bottom: env(safe-area-inset-bottom);
            }
        }

        @media (max-width: 420px) {
            main {
                padding: .65rem;
                padding-bottom: calc(5.75rem + env(safe-area-inset-bottom));
            }

            main table th,
            main table td {
                padding: .35rem .45rem !important;
                font-size: .7rem !important;
            }

            main button,
            main .btn {
                min-height: 2rem;
                padding: .35rem .5rem !important;
                font-size: .75rem !important;
            }

            footer nav {
                gap: 0;
                padding-right: .25rem;
                padding-left: .25rem;
            }

            footer nav a {
                padding-right: .15rem;
                padding-left: .15rem;
                font-size: 9px;
            }
        }

    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <!-- Top Header -->
    <header class="sticky top-0 z-50 bg-slate-900 text-white shadow-md border-b border-amber-500/30">
        <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 pl-16">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="w-10 h-10 bg-amber-500 text-slate-900 rounded-full flex items-center justify-center font-bold text-xl">
                    <i class="fa-solid fa-wifi"></i>
                </div>
                <h1 class="text-xl font-bold tracking-wide">نظام إدارة كروت الشبكة</h1>
            </div>
        </div>
        <a href="{{ route('settings.index') }}" aria-label="الإعدادات والمحاكاة" title="الإعدادات والمحاكاة" class="absolute left-4 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-lg border border-amber-500/30 text-lg text-amber-400 transition hover:bg-amber-500 hover:text-slate-900 {{ request()->routeIs('settings.*') ? 'bg-amber-500 text-slate-900' : '' }}">
                <i class="fa-solid fa-gear" aria-hidden="true"></i>
        </a>
    </header>

    <div id="app-content" class="flex min-w-0 flex-1">
        @if(request()->routeIs('dashboard', 'account.*', 'settings.*', 'ledger.*', 'exclusions.*', 'messages.*', 'shifts.*', 'system-health.*', 'help.*'))
            <aside id="system-sidebar" class="w-52 shrink-0 border-l border-amber-500/30 bg-slate-900 p-3 text-slate-200">
                <h2 class="mb-3 px-3 text-sm font-bold text-amber-400">الإعدادات والمحاكاة</h2>
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-amber-500 hover:text-slate-900 {{ request()->routeIs('dashboard') ? 'bg-amber-500 font-bold text-slate-900' : 'text-slate-200' }}">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>لوحة التحكم</span>
                    </a>
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('sellers.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-amber-500 hover:text-slate-900 {{ request()->routeIs('sellers.*') ? 'bg-amber-500 font-bold text-slate-900' : 'text-slate-200' }}">
                            <i class="fa-solid fa-store"></i>
                            <span>إدارة البائعين</span>
                        </a>
                    @endif
                    <a href="{{ route('account.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-amber-500 hover:text-slate-900 {{ request()->routeIs('account.*') ? 'bg-amber-500 font-bold text-slate-900' : 'text-slate-200' }}">
                        <i class="fa-solid fa-user"></i>
                        <span>الحساب</span>
                    </a>
                    <a href="{{ route('ledger.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-amber-500 hover:text-slate-900 {{ request()->routeIs('ledger.*') ? 'bg-amber-500 font-bold text-slate-900' : 'text-slate-200' }}">
                        <i class="fa-solid fa-book-open"></i>
                        <span>الدفتر المحاسبي</span>
                    </a>
                    <a href="{{ route('exclusions.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-amber-500 hover:text-slate-900 {{ request()->routeIs('exclusions.*') ? 'bg-amber-500 font-bold text-slate-900' : 'text-slate-200' }}">
                        <i class="fa-solid fa-ban"></i>
                        <span>استثناءات الهواتف</span>
                    </a>
                    <a href="{{ route('messages.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-amber-500 hover:text-slate-900 {{ request()->routeIs('messages.*') ? 'bg-amber-500 font-bold text-slate-900' : 'text-slate-200' }}">
                        <i class="fa-solid fa-comment-sms"></i>
                        <span>بث رسائل SMS</span>
                    </a>
                    <a href="{{ route('shifts.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-amber-500 hover:text-slate-900 {{ request()->routeIs('shifts.*') ? 'bg-amber-500 font-bold text-slate-900' : 'text-slate-200' }}">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>سجل الورديات</span>
                    </a>
                    <a href="{{ route('system-health.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-amber-500 hover:text-slate-900 {{ request()->routeIs('system-health.*') ? 'bg-amber-500 font-bold text-slate-900' : 'text-slate-200' }}">
                        <i class="fa-solid fa-heart-pulse"></i>
                        <span>أداء النظام</span>
                    </a>
                    <a href="{{ route('help.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-amber-500 hover:text-slate-900 {{ request()->routeIs('help.*') ? 'bg-amber-500 font-bold text-slate-900' : 'text-slate-200' }}">
                        <i class="fa-solid fa-circle-question"></i>
                        <span>دليل المستخدم</span>
                    </a>
                </nav>
                <form action="{{ route('logout') }}" method="POST" class="mt-4 border-t border-slate-700 pt-3">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-rose-300 transition hover:bg-rose-500 hover:text-white" onclick="return confirm('هل ترغب في تسجيل الخروج؟')">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </aside>
        @endif

        <!-- Main Content Area -->
        <main class="min-w-0 flex-1 bg-slate-50 p-3 pb-24 sm:p-4 sm:pb-24 lg:p-6 lg:pb-24">
            @if(session('success') || session('error') || $errors->any())
                @php
                    $isSuccessfulNotification = session()->has('success');
                    $notificationMessage = session('success') ?? session('error');
                @endphp
                <div
                    id="operation-notification"
                    role="{{ $isSuccessfulNotification ? 'status' : 'alert' }}"
                    aria-live="{{ $isSuccessfulNotification ? 'polite' : 'assertive' }}"
                    class="fixed left-1/2 top-1/2 z-[100] w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-xl border border-amber-400/50 bg-slate-900 p-4 text-slate-100 shadow-2xl shadow-slate-950/40"
                >
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-amber-400/40 bg-amber-400/10 text-amber-300">
                            <i class="fa-solid {{ $isSuccessfulNotification ? 'fa-circle-check' : 'fa-circle-exclamation' }}" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-amber-300">
                                {{ $isSuccessfulNotification ? 'تمت العملية بنجاح' : 'تعذر إتمام العملية' }}
                            </p>
                            @if($notificationMessage)
                                <p class="mt-1 text-sm leading-6 text-slate-200">{{ $notificationMessage }}</p>
                            @endif
                            @if($errors->any())
                                <ul class="mt-1 list-inside list-disc text-sm leading-6 text-slate-200">
                                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            @endif
                        </div>
                        <button
                            type="button"
                            class="rounded p-1 text-slate-400 transition hover:bg-slate-800 hover:text-amber-300"
                            aria-label="إغلاق التنبيه"
                            onclick="document.getElementById('operation-notification').remove()"
                        >
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <footer class="fixed inset-x-0 bottom-0 z-50 border-t border-amber-500/30 bg-slate-900 text-slate-200 shadow-md">
        <nav aria-label="التنقل الرئيسي" class="mx-auto grid w-full max-w-7xl grid-cols-5 items-center gap-1 px-2 py-2 sm:px-4">
            <a href="{{ route('vouchers.index') }}" class="flex min-w-0 flex-col items-center justify-center gap-0.5 rounded-full px-1.5 py-1.5 text-center text-[10px] leading-tight transition hover:bg-gray-200 hover:text-slate-900 sm:text-[11px] {{ request()->routeIs('vouchers.*') ? 'bg-gray-200 font-bold text-slate-900' : '' }}">
                <i class="fa-solid fa-ticket text-sm" aria-hidden="true"></i>
                <span>إدارة الكروت والفئات</span>
            </a>
            <a href="{{ route('transfers.index') }}" class="flex min-w-0 flex-col items-center justify-center gap-0.5 rounded-full px-1.5 py-1.5 text-center text-[10px] leading-tight transition hover:bg-gray-200 hover:text-slate-900 sm:text-[11px] {{ request()->routeIs('transfers.*') ? 'bg-gray-200 font-bold text-slate-900' : '' }}">
                <i class="fa-solid fa-building-columns text-sm" aria-hidden="true"></i>
                <span>الإيداعات والتحويلات</span>
            </a>
            <a href="{{ route('direct_sales.index') }}" class="relative z-10 flex min-w-0 -translate-y-0.5 flex-col items-center justify-center gap-0.5 rounded-full border border-amber-400/70 bg-amber-500 px-1 py-1 text-center text-[9px] font-extrabold leading-tight text-slate-950 shadow-md shadow-amber-500/20 transition hover:-translate-y-1 hover:bg-amber-400 sm:text-[10px] {{ request()->routeIs('direct_sales.*') ? 'ring-1 ring-amber-300 ring-offset-1 ring-offset-slate-900' : '' }}">
                <i class="fa-solid fa-cash-register text-xs" aria-hidden="true"></i>
                <span>المبيعات المباشرة</span>
            </a>
            <a href="{{ route('customers.index') }}" class="flex min-w-0 flex-col items-center justify-center gap-0.5 rounded-full px-1.5 py-1.5 text-center text-[10px] leading-tight transition hover:bg-gray-200 hover:text-slate-900 sm:text-[11px] {{ request()->routeIs('customers.*') ? 'bg-gray-200 font-bold text-slate-900' : '' }}">
                <i class="fa-solid fa-users text-sm" aria-hidden="true"></i>
                <span>إدارة العملاء</span>
            </a>
            <a href="{{ route('reports.transfers') }}" class="flex min-w-0 flex-col items-center justify-center gap-0.5 rounded-full px-1.5 py-1.5 text-center text-[10px] leading-tight transition hover:bg-gray-200 hover:text-slate-900 sm:text-[11px] {{ request()->routeIs('reports.*') ? 'bg-gray-200 font-bold text-slate-900' : '' }}">
                <i class="fa-solid fa-chart-line text-sm" aria-hidden="true"></i>
                <span>التقارير المالية</span>
            </a>
        </nav>
    </footer>

    <div id="delete-confirmation-modal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-slate-950/75 p-4" role="alertdialog" aria-modal="true" aria-labelledby="delete-confirmation-title" aria-describedby="delete-confirmation-message">
        <div class="w-full max-w-md rounded-2xl border-2 border-amber-400/70 bg-slate-900 p-6 text-slate-100 shadow-2xl shadow-slate-950/60">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-amber-400/50 bg-amber-400/10 text-xl text-amber-300">
                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                </span>
                <h2 id="delete-confirmation-title" class="text-lg font-bold text-amber-300">تأكيد الحذف</h2>
            </div>
            <p id="delete-confirmation-message" class="text-sm leading-7 text-slate-200">هل أنت متأكد من حذف هذا السجل؟</p>
            <div class="mt-6 flex justify-end gap-3">
                <button id="delete-confirmation-cancel" type="button" class="rounded-lg border border-amber-400/50 px-5 py-2 font-bold text-amber-200 transition hover:bg-slate-800">
                    إلغاء
                </button>
                <button id="delete-confirmation-submit" type="button" class="rounded-lg border border-amber-300 bg-amber-500 px-5 py-2 font-bold text-slate-950 transition hover:bg-amber-400">
                    حذف
                </button>
            </div>
        </div>
    </div>

    <div id="automation-confirmation-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-950/75 p-4" role="alertdialog" aria-modal="true" aria-labelledby="automation-confirmation-title" aria-describedby="automation-confirmation-message">
        <div class="w-full max-w-md rounded-2xl border-2 border-amber-400/70 bg-slate-900 p-6 text-slate-100 shadow-2xl shadow-slate-950/60">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-amber-400/50 bg-amber-400/10 text-xl text-amber-300">
                    <i class="fa-solid fa-robot" aria-hidden="true"></i>
                </span>
                <h2 id="automation-confirmation-title" class="text-lg font-bold text-amber-300">تأكيد تشغيل الروبوت</h2>
            </div>
            <p id="automation-confirmation-message" class="text-sm leading-7 text-slate-200"></p>
            <div class="mt-6 flex justify-end gap-3">
                <button id="automation-confirmation-cancel" type="button" class="rounded-lg border border-amber-400/50 px-5 py-2 font-bold text-amber-200 transition hover:bg-slate-800">
                    إلغاء
                </button>
                <button id="automation-confirmation-submit" type="button" class="rounded-lg border border-amber-300 bg-amber-500 px-5 py-2 font-bold text-slate-950 transition hover:bg-amber-400">
                    تشغيل
                </button>
            </div>
        </div>
    </div>

    <script>
        const scrollPositionStorageKey = 'network-cards-system:scroll-position';
        const storedScrollPosition = sessionStorage.getItem(scrollPositionStorageKey);

        if (storedScrollPosition) {
            sessionStorage.removeItem(scrollPositionStorageKey);
            const scrollPosition = JSON.parse(storedScrollPosition);
            if (scrollPosition.path === window.location.pathname) {
                window.requestAnimationFrame(() => window.scrollTo(0, scrollPosition.top));
            }
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (form instanceof HTMLFormElement && form.hasAttribute('data-confirm-automation')) {
                if (confirmedAutomationForm === form) {
                    confirmedAutomationForm = null;
                } else {
                    event.preventDefault();
                    openAutomationConfirmation(form);
                    return;
                }
            }

            if (form instanceof HTMLFormElement
                && form.querySelector('input[name="_method"][value="DELETE"]')) {
                if (confirmedDeleteForm === form) {
                    confirmedDeleteForm = null;
                } else {
                    event.preventDefault();
                    openDeleteConfirmation(form, event.submitter);
                    return;
                }
            }

            if (form instanceof HTMLFormElement && form.target !== '_blank') {
                sessionStorage.setItem(scrollPositionStorageKey, JSON.stringify({
                    path: window.location.pathname,
                    top: window.scrollY,
                }));
            }
        }, true);

        const deleteConfirmationModal = document.getElementById('delete-confirmation-modal');
        const deleteConfirmationMessage = document.getElementById('delete-confirmation-message');
        const deleteConfirmationCancel = document.getElementById('delete-confirmation-cancel');
        const deleteConfirmationSubmit = document.getElementById('delete-confirmation-submit');
        let pendingDeleteForm = null;
        let pendingDeleteSubmitter = null;
        let confirmedDeleteForm = null;
        let pendingAutomationForm = null;
        let confirmedAutomationForm = null;

        const automationConfirmationModal = document.getElementById('automation-confirmation-modal');
        const automationConfirmationTitle = document.getElementById('automation-confirmation-title');
        const automationConfirmationMessage = document.getElementById('automation-confirmation-message');
        const automationConfirmationCancel = document.getElementById('automation-confirmation-cancel');
        const automationConfirmationSubmit = document.getElementById('automation-confirmation-submit');

        function closeDeleteConfirmation() {
            deleteConfirmationModal.classList.add('hidden');
            deleteConfirmationModal.classList.remove('flex');
            pendingDeleteForm = null;
            pendingDeleteSubmitter = null;
        }

        function openDeleteConfirmation(form, submitter) {
            pendingDeleteForm = form;
            pendingDeleteSubmitter = submitter;
            deleteConfirmationMessage.textContent = form.dataset.confirmDelete
                || 'هل أنت متأكد من حذف هذا السجل؟';
            deleteConfirmationModal.classList.remove('hidden');
            deleteConfirmationModal.classList.add('flex');
            deleteConfirmationCancel.focus();
        }

        function closeAutomationConfirmation() {
            automationConfirmationModal.classList.add('hidden');
            automationConfirmationModal.classList.remove('flex');
            pendingAutomationForm = null;
        }

        function openAutomationConfirmation(form) {
            pendingAutomationForm = form;
            const enabling = form.querySelector('input[name="enabled"]').value === '1';
            automationConfirmationTitle.textContent = enabling ? 'تأكيد تشغيل الروبوت' : 'تأكيد إيقاف الروبوت';
            automationConfirmationMessage.textContent = enabling
                ? 'هل تريد تشغيل الروبوت؟ سيقوم بالتقاط الحوالات وتوزيع الكروت للعملاء.'
                : 'هل تريد إيقاف الروبوت الآلي؟';
            automationConfirmationSubmit.textContent = enabling ? 'تشغيل' : 'إيقاف';
            automationConfirmationSubmit.dataset.enabled = enabling ? '1' : '0';
            automationConfirmationModal.classList.remove('hidden');
            automationConfirmationModal.classList.add('flex');
            automationConfirmationCancel.focus();
        }

        deleteConfirmationCancel.addEventListener('click', closeDeleteConfirmation);
        deleteConfirmationModal.addEventListener('click', function (event) {
            if (event.target === deleteConfirmationModal) closeDeleteConfirmation();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !deleteConfirmationModal.classList.contains('hidden')) {
                closeDeleteConfirmation();
            }
            if (event.key === 'Escape' && !automationConfirmationModal.classList.contains('hidden')) {
                closeAutomationConfirmation();
            }
        });
        deleteConfirmationSubmit.addEventListener('click', function () {
            if (!pendingDeleteForm) return;

            const form = pendingDeleteForm;
            const submitter = pendingDeleteSubmitter;
            closeDeleteConfirmation();
            confirmedDeleteForm = form;
            if (submitter && submitter.isConnected) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
        });
        automationConfirmationCancel.addEventListener('click', closeAutomationConfirmation);
        automationConfirmationModal.addEventListener('click', function (event) {
            if (event.target === automationConfirmationModal) closeAutomationConfirmation();
        });
        automationConfirmationSubmit.addEventListener('click', function () {
            if (!pendingAutomationForm) return;

            const form = pendingAutomationForm;
            form.querySelector('input[name="enabled"]').value = automationConfirmationSubmit.dataset.enabled;
            closeAutomationConfirmation();
            confirmedAutomationForm = form;
            form.requestSubmit();
        });
        const budgetToggle = document.getElementById('budget-toggle');
        if (budgetToggle) {
            budgetToggle.addEventListener('click', function () {
                const isVisible = this.getAttribute('aria-pressed') === 'true';
                this.setAttribute('aria-pressed', String(!isVisible));
                this.setAttribute('aria-label', isVisible ? 'إظهار مبلغ الميزانية' : 'إخفاء مبلغ الميزانية');
                document.getElementById('budget-value').textContent = isVisible
                    ? '****** YER'
                    : `${this.dataset.budget} YER`;
                document.getElementById('budget-visibility-icon').className = isVisible
                    ? 'fa-solid fa-eye'
                    : 'fa-solid fa-eye-slash';
            });
        }

        const makkahClock = document.getElementById('makkah-clock');
        if (makkahClock) {
            const serverTimestamp = Number(makkahClock.dataset.serverTimestamp) * 1000;
            const clockStartedAt = Date.now();
            const makkahTimeFormatter = new Intl.DateTimeFormat('en-GB', {
                timeZone: 'Asia/Riyadh',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hourCycle: 'h23',
            });

            const updateMakkahClock = () => {
                const currentTime = serverTimestamp + (Date.now() - clockStartedAt);
                makkahClock.textContent = makkahTimeFormatter.format(currentTime);
                makkahClock.dateTime = new Date(currentTime).toISOString();
            };

            updateMakkahClock();
            window.setInterval(updateMakkahClock, 1000);
        }

        const operationNotification = document.getElementById('operation-notification');
        if (operationNotification && operationNotification.getAttribute('role') === 'status') {
            window.setTimeout(() => operationNotification.remove(), 6000);
        }
    </script>

</body>
</html>