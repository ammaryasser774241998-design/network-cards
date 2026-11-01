@extends('layouts.app')

@section('title', 'دليل استخدام النظام')

@section('content')
<div class="mx-auto max-w-5xl space-y-6" dir="rtl">
    <header class="rounded-2xl border border-amber-400/20 bg-gradient-to-l from-slate-900 via-slate-800 to-slate-700 p-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-400 text-xl text-slate-950 shadow-md shadow-amber-950/20">
                <i class="fa-solid fa-book-open" aria-hidden="true"></i>
            </span>
            <div>
                <h2 class="text-2xl font-bold">دليل استخدام نظام الكروت</h2>
                <p class="mt-1 text-sm text-slate-300">شرح موجز لمسارات العمل المتاحة في إصدار النظام الحالي.</p>
            </div>
        </div>
    </header>

    <section class="group rounded-2xl border border-slate-200 border-r-4 border-r-amber-400 bg-gradient-to-l from-white to-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/10">
        <h3 class="mb-2 flex items-center gap-2 font-bold text-slate-900"><span class="text-sm text-amber-600">01</span><span>لوحة التحكم والروبوت</span></h3>
        <p class="text-sm leading-7 text-slate-700">استخدم زر الروبوت في صفحة البيع المباشر لتفعيل معالجة الحوالات التي تصل من تطبيق Android بعد إعداد قواعد المحافظ. لا يبدأ الزر مراقبة رسائل الهاتف وحده؛ يجب تشغيل التطبيق المرافق وتسجيل دخوله ومنحه الأذونات اللازمة. تظهر ميزانية الإدارة بجواره ويمكن إظهار قيمتها أو إخفاؤها. من لوحة التحكم اختر فترة المبيعات، وزر إقفال الوردية يحفظ ملخصًا في سجل الورديات دون حذف السجلات الأصلية.</p>
    </section>

    <section class="group rounded-2xl border border-slate-200 border-r-4 border-r-amber-400 bg-gradient-to-l from-white to-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/10">
        <h3 class="mb-2 flex items-center gap-2 font-bold text-slate-900"><span class="text-sm text-amber-600">02</span><span>المخزون والمبيعات</span></h3>
        <p class="text-sm leading-7 text-slate-700">من إدارة الكروت والفئات أنشئ فئة وحدد السعر والتكلفة وحد تنبيه المخزون. أضف الكروت منفردة أو استورد ملف Excel أو TXT أو PDF أو الصق الأكواد. يمكن ضبط سعر الجملة والحد الأدنى له وعدد الكروت المجانية الإضافية لكل فئة؛ يجب توفر كامل الكمية المطلوبة والمجانية حتى تتم عملية البيع.</p>
    </section>

    <section class="group rounded-2xl border border-slate-200 border-r-4 border-r-amber-400 bg-gradient-to-l from-white to-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/10">
        <h3 class="mb-2 flex items-center gap-2 font-bold text-slate-900"><span class="text-sm text-amber-600">03</span><span>الحوالات والمحافظ</span></h3>
        <p class="text-sm leading-7 text-slate-700">أضف قواعد المحافظ من الإعدادات بصيغة JSON واختبرها بمختبر المحاكاة قبل تفعيل الأتمتة. تُضاف إيداعات العملاء المسجلين إلى رصيدهم ولا يُصرف الكرت تلقائيًا عند وصول الإيداع. إذا وصل إيداع من رقم غير مربوط، افتح صفحة الإيداعات والتحويلات: راجع اسم العميل ورقمه ونص الإشعار، ثم أنشئ حسابًا واربط الإيداع به أو احفظه دون ربط. عند إنشاء الحساب يُضاف مبلغ الإيداع إلى رصيد العميل. تقارير الحوالات تسمح بالتصفية بالتاريخ والنوع ورقم الهاتف، وطباعة كامل النتائج إلى PDF عبر نافذة الطباعة.</p>
    </section>

    <section class="group rounded-2xl border border-slate-200 border-r-4 border-r-amber-400 bg-gradient-to-l from-white to-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/10">
        <h3 class="mb-2 flex items-center gap-2 font-bold text-slate-900"><span class="text-sm text-amber-600">04</span><span>العملاء والدفتر المحاسبي</span></h3>
        <p class="text-sm leading-7 text-slate-700">أضف العملاء يدويًا أو استورد جهات الاتصال من التطبيق المرافق. بعد تفعيل الأتمتة والسماح بإرسال الكروت للحساب، يستطيع العميل طلب رصيد مثل «كرت ابو 2000» برسالة SMS من رقمه المسجل؛ يتحقق النظام من الرصيد والمخزون، ويخصم قيمة الفئة مرة واحدة، ثم يضع الكرت في قائمة رسائل SMS. إذا لم يكفِ الرصيد لإتمام طلب العميل أو البيع المباشر إليه، لا يصدر الكرت وتُرسل إليه رسالة اعتذار تطلب شحن رصيده وإعادة المحاولة. من الدفتر المحاسبي اضبط رقم الهاتف والأرقام الإضافية وسقف الدين وإذن البيع الآجل وإرسال الكروت. إضافة قيد ترفع الدين، والسداد يخفضه ولا يسمح النظام بتجاوز الرصيد أو سقف المديونية.</p>
    </section>

    <section class="group rounded-2xl border border-slate-200 border-r-4 border-r-amber-400 bg-gradient-to-l from-white to-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/10">
        <h3 class="mb-2 flex items-center gap-2 font-bold text-slate-900"><span class="text-sm text-amber-600">05</span><span>الرسائل والاستثناءات</span></h3>
        <p class="text-sm leading-7 text-slate-700">قوالب SMS تدعم المتغيرات {network} و{category} و{pin} و{serial} و{serial_suffix} و{phone}. رسائل البث تدخل قائمة الانتظار للعملاء النشطين، مع تخطي الأرقام المستثناة. تظل حوالة الرقم المستثنى محفوظة للمراجعة ولا يصرف النظام كرتًا آليًا له.</p>
    </section>

    <section class="rounded-2xl border border-amber-300/60 bg-gradient-to-l from-amber-50 to-amber-100/70 p-5 shadow-sm">
        <h3 class="mb-2 flex items-center gap-2 font-bold text-slate-900"><span class="text-sm text-amber-700">06</span><span>ميزات تتطلب إعدادًا أو تطويرًا تكميليًا</span></h3>
        <p class="text-sm leading-7 text-slate-800">ميزانية الإدارة تُحدّث يدويًا ولا تُحتسب من قيود الدخل والمصروفات. لا يتضمن النظام حتى الآن مزامنة Google Drive أو استعادة نسخة احتياطية، وإدارة عروض عامة مؤقتة، ومساعدًا صوتيًا، أو سجل تشخيص مستمرًا على الهاتف. توافر SMS وجهات الاتصال والبصمة والعمل في الخلفية يعتمد على إصدار Android وأذونات الجهاز؛ افحصها من إعدادات الهاتف والتطبيق قبل التشغيل.</p>
    </section>

    <section class="group rounded-2xl border border-slate-200 border-r-4 border-r-amber-400 bg-gradient-to-l from-white to-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/10">
        <h3 class="mb-2 flex items-center gap-2 font-bold text-slate-900"><span class="text-sm text-amber-600">07</span><span>حسابات البائعين وفصل البيانات</span></h3>
        <p class="text-sm leading-7 text-slate-700">مدير المنصة ينشئ حساب البائع من «إدارة البائعين» ويزوّده بالبريد وكلمة المرور؛ يسجّل البائع دخوله من صفحة الدخول المعتادة. لكل حساب مساحة مستقلة تشمل الكروت والفئات والعملاء والمبيعات والتحويلات والرسائل والورديات والإعدادات، ولا يستطيع البائع الاطلاع على بيانات الحسابات الأخرى. يمكن لمدير المنصة إيقاف حساب بائع أو إعادة تفعيله، ولا يؤدي ذلك إلى حذف بياناته.</p>
    </section>
</div>
@endsection
