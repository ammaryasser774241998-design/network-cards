@extends('layouts.app')

@section('content')
<div class="space-y-6 dir-rtl">

    <!-- Top Action Bar -->
    <div class="bg-slate-900 text-white p-4 rounded-2xl shadow-md flex flex-wrap justify-between items-center gap-4 border border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-amber-400 flex items-center gap-2">
                <i class="fa-solid fa-layer-group"></i> البيع المباشر وإدارة الفئات
            </h2>
            <p class="text-xs text-slate-400 mt-1">اختر الفئة للبيع المباشر، أو قم بإدارة وتحديث الفئات</p>
        </div>
        @include('partials.system-controls')
    </div>
          
    <!-- Cards Grid (Compact & Modern Style) -->
    <div>
        <h3 class="text-l font-bold text-amber-400 flex items-center gap-2">
            <span class="bg-slate-800 text-amber-400 px-3 py-1 rounded-full text-sm border border-amber-500/20"><p></p>عدد الفئات المتاحة</p></span>
            <span class="bg-slate-800 text-amber-400 text-xs px-2 py-0.5 rounded-full font-mono">{{ $categories->count() }}</span>
        </h3>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @forelse($categories as $category)
                <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-3 shadow-sm hover:shadow-md transition border border-slate-200 dark:border-slate-700 flex flex-col justify-between items-center text-center group">
                    
                    <!-- زر حذف الفئة -->
                    <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="absolute top-2 left-2 z-10" data-confirm-delete="هل أنت متأكد من حذف هذه الفئة؟ سيتم حذف جميع الكروت المرتبطة بها.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-6 h-6 rounded-full bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white transition flex items-center justify-center opacity-70 group-hover:opacity-100 shadow-sm" title="حذف الفئة">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </form>

                    <!-- Icon & Title -->
                    <div class="mt-1 mb-2">
                        <div class="w-10 h-10 mx-auto rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-500 flex items-center justify-center mb-1.5">
                            <i class="fa-solid fa-ticket text-lg"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300 block">فئة {{ $category->name }}</span>
                    </div>

                    <!-- Available Count & Price -->
                    <div class="my-1">
                        <div class="text-xl font-extrabold text-slate-900 dark:text-white font-mono leading-none">
                            {{ number_format($category->vouchers_count ?? 0) }}
                        </div>
                        <span class="text-[10px] text-gray-400 font-semibold block mt-1">
                            السعر: {{ number_format($category->price, 0) }} ر.ي
                        </span>
                    </div>

                    <!-- Buy Button -->
                    <form action="{{ route('direct_sales.store') }}" method="POST" class="w-full mt-2">
                        @csrf
                       <button type="button" 
        onclick="openSellModal('{{ $category->id }}', '{{ $category->name }}', '{{ $category->price }}')"
        class="w-full py-2 bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold rounded-xl text-xs flex items-center justify-center gap-1 transition">
    <i class="fa-solid fa-print"></i> بيع وطباعة
</button>
                    </form>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-slate-800 p-8 rounded-2xl text-center text-gray-400 border border-slate-200 dark:border-slate-700">
                    لا توجد فئات كروت حالياً. قم بإنشاء فئة جديدة للبدء.
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- عرض بيانات آخر تحويل أسفل بطاقات الفئات --}}
    @if(session('last_transfer'))
        @php $transfer = session('last_transfer'); @endphp
        <div class="mt-6 bg-slate-900 border border-emerald-500/50 rounded-2xl p-5 shadow-lg text-white">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                <h4 class="text-emerald-400 font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> تم تسجيل البيع ومحاولة إرسال رسالة الكرت
                </h4>
                <span class="text-xs text-slate-400">{{ $transfer['date'] }}</span>
            </div>
            <p class="mb-4 text-center text-xs text-amber-300">لا يوجد مزود رسائل صادر مهيأ حالياً، ولا يوجد تحويل احتياطي إلى SMS.</p>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-center">
                <div class="bg-slate-800/60 p-3 rounded-xl border border-slate-700/50">
                    <span class="block text-xs text-slate-400 mb-1">رقم هاتف العميل</span>
                    <span class="text-base font-bold text-amber-400 dir-ltr">{{ $transfer['customer_phone'] }}</span>
                </div>
                <div class="bg-slate-800/60 p-3 rounded-xl border border-slate-700/50">
                    <span class="block text-xs text-slate-400 mb-1">الفئة والسعر</span>
                    <span class="text-xs font-semibold text-slate-200">{{ $transfer['category_name'] }} ({{ $transfer['price'] }} ر.ي)</span>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($transfer['cards'] as $index => $card)
                    <div class="rounded-xl border border-slate-700 bg-slate-800/60 p-3 text-center">
                        <span class="mb-1 block text-xs text-slate-400">الكرت {{ $index + 1 }} — PIN</span>
                        <span class="select-all font-mono text-base font-bold text-emerald-400">{{ $card['pin_code'] }}</span>
                        <span class="mt-1 block font-mono text-xs text-slate-300">{{ $card['serial_number'] ?? 'غير متوفر' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- نافذة البيع وتحويل الكرت للعميل Modal --}}
    <div id="sellModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 text-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-slate-800/80 p-4 flex justify-between items-center border-b border-slate-700">
                <h3 class="font-bold text-sm text-amber-400 flex items-center gap-2">
                    <i class="fa-solid fa-cart-shopping"></i> بيع وتحويل كرت مباشر
                </h3>
                <button type="button" onclick="toggleModal('sellModal')" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

<form action="{{ route('direct_sales.transfer') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="category_id" id="modal_category_id">

                <!-- عرض الفئة المختارة -->
                <div class="bg-slate-800/50 p-3 rounded-xl border border-slate-700 text-center">
                    <span class="text-xs text-slate-400 block mb-1">نوع الكرت المطلوب:</span>
                    <span id="modal_category_name" class="font-bold text-amber-400 text-base"></span>
                    <span id="modal_category_price" class="text-xs text-emerald-400 block mt-0.5"></span>
                </div>

                <!-- إدخال رقم العميل -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-2">رقم هاتف العميل <span class="text-red-400">*</span></label>
                    <input type="text" name="phone" required placeholder="أدخل رقم الهاتف..." 
                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-400 transition dir-ltr text-center">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-2">الكمية</label>
                    <input type="number" name="quantity" min="1" max="100" value="1" required
                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-400 transition text-center">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" onclick="toggleModal('sellModal')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs">إلغاء</button>
                    <button type="submit" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold rounded-xl text-xs flex items-center gap-2">
                        <i class="fa-solid fa-check"></i> تأكيد البيع والتحويل
                    </button>
                </div>
            </form>
        </div>
    </div>
    


<!-- Modal 2: رفع ملف الكروت -->
<div id="importModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="bg-slate-900 text-white p-4 flex justify-between items-center border-b border-amber-500/20">
            <h3 class="font-bold text-sm text-amber-400 flex items-center gap-2">
                <i class="fa-solid fa-file-arrow-up"></i> رفع كروت من اليوزر مانيجر
            </h3>
            <button onclick="toggleModal('importModal')" class="text-gray-400 hover:text-white transition">&times;</button>
        </div>
        <form action="{{ route('vouchers.import') }}" method="POST" enctype="multipart/form-data" class="p-4 space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">الفئة المخصصة</label>
                <select name="category_id" required class="w-full p-2.5 bg-slate-50 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-xs dark:text-white">
                    <option value="" disabled selected>اختر الفئة...</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }} ({{ number_format($category->price, 0) }} YER)</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ملف الكروت (PDF أو Excel)</label>
                <input type="file" name="file" required accept=".pdf,.xlsx,.xls,.csv" class="w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-amber-400 hover:file:bg-slate-800 cursor-pointer border border-gray-300 dark:border-slate-700 rounded-xl p-1">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="toggleModal('importModal')" class="px-4 py-2 bg-gray-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold rounded-xl text-xs transition">إلغاء</button>
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-amber-400 font-bold rounded-xl text-xs transition border border-amber-500/30 shadow">رفع واستخراج</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }
</script>
<script>
function openSellModal(id, name, price) {
    document.getElementById('modal_category_id').value = id;
    document.getElementById('modal_category_name').innerText = name;
    document.getElementById('modal_category_price').innerText = 'السعر: ' + price + ' ر.ي';
    toggleModal('sellModal');
}
</script>
@endsection