<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\CustomerMessageDeliveryService;

class AuthController extends Controller
{
    // ------------------------------------
    // 1. تسجيل الدخول (Login)
    // ------------------------------------
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
{
    // 1. التحقق من صحة المدخلات
    $request->validate([
        'login'    => 'required|string',
        'password' => 'required|string',
    ], [
        'login.required'    => 'يرجى إدخال البريد الإلكتروني أو رقم الهاتف',
        'password.required' => 'يرجى إدخال كلمة المرور',
    ]);

    // 2. تحديد نوع المدخل (بريد إلكتروني أو رقم هاتف)
    $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

    // 3. تجهيز بيانات الاعتماد
    $credentials = [
        $loginType => $request->login,
        'password' => $request->password,
        'is_active' => true,
    ];

    // 4. محاولة تسجيل الدخول
    if (Auth::attempt($credentials, $request->has('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended(route('direct_sales.index'));
    }

    // 5. في حال فشل تسجيل الدخول
    return back()->withErrors([
        'login' => 'بيانات الاعتماد التابعة لهذه البيانات غير مسجلة لدينا.',
    ])->onlyInput('login');
}

    // ------------------------------------
    // 2. إنشاء حساب جديد (Register)
    // ------------------------------------
    public function showRegisterForm()
    {
        return view('auth.register');
    }

   // ==================================================
    // 1. إنشاء حساب جديد وإرسال الرمز (Register)
    // ==================================================
    public function register(Request $request)
    {
        if (User::exists()) {
            return back()->withErrors([
                'login' => 'التسجيل الذاتي مغلق بعد إنشاء حساب المدير الأول.',
            ])->withInput();
        }

        // 1. التحقق من صحة المدخلات
        $request->validate([
            'name'     => 'required|string|max:255',
            'login'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required'      => 'يرجى إدخال الاسم الكامل',
            'login.required'     => 'يرجى إدخال البريد الإلكتروني أو رقم الهاتف',
            'password.required'  => 'يرجى إدخال كلمة المرور',
            'password.min'       => 'كلمة المرور يجب أن لا تقل عن 6 أحرف',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
        ]);

        // 2. تحديد نوع المدخل (بريد إلكتروني أم رقم هاتف)
        $isEmail = filter_var($request->login, FILTER_VALIDATE_EMAIL);
        $field   = $isEmail ? 'email' : 'phone_number';

        // 3. التحقق من عدم تكرار الحساب
        if (\App\Models\User::where($field, $request->login)->exists()) {
            $msg = $isEmail ? 'البريد الإلكتروني مستخدم بالفعل' : 'رقم الهاتف مستخدم بالفعل';
            return back()->withErrors(['login' => $msg])->withInput();
        }

        // 4. إنشاء حساب المستخدم الجديد
        $user = \App\Models\User::create([
            'name'     => $request->name,
            $field     => $request->login,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'is_admin' => true,
        ]);

        // 5. إذا كان التسجيل برقم هاتف، قم بتوليد رمز التحقق
        if (!$isEmail) {
            $otp = rand(1000, 9999);

            // حفظ بيانات المستخدم مؤقتاً في الجلسة لاستخدامها في صفحة التحقق
            session([
                'otp_user_id' => $user->id,
                'otp_code'    => $otp,
                'otp_phone'   => $request->login,
            ]);

            $sent = app(CustomerMessageDeliveryService::class)->send($request->login, "كود التحقق الخاص بك هو: {$otp}");

            return redirect()->route('show.otp')->with(
                $sent ? 'success' : 'error',
                $sent ? 'تم إرسال رمز التحقق إلى هاتفك.' : 'تعذر إرسال رمز التحقق؛ تحقق من تهيئة مزود الرسائل.'
            );
        }

        \Illuminate\Support\Facades\Auth::login($user);
        return redirect()->intended(route('direct_sales.index'));
    }

           // ==================================================
    // ==================================================
      // --------------------------------------------------
    // عرض صفحة إدخال رمز التحقق
    // --------------------------------------------------
    public function showOtpForm()
    {
        if (!session()->has('otp_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.verify-otp');
    }

    // --------------------------------------------------
    // التحقق من صحة رمز OTP
    // --------------------------------------------------
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric',
        ], [
            'otp.required' => 'يرجى إدخال رمز التحقق',
            'otp.numeric'  => 'رمز التحقق يجب أن يكون أرقاماً فقط',
        ]);

        if ($request->otp == session('otp_code')) {
            $user = \App\Models\User::find(session('otp_user_id'));

            if ($user) {
                \Illuminate\Support\Facades\Auth::login($user);
                session()->forget(['otp_user_id', 'otp_code', 'otp_phone']);
                return redirect()->intended(route('direct_sales.index'));
            }
        }

        return back()->with('error', 'كود التحقق غير صحيح، يرجى المحاولة مرة أخرى');
    }

    // ==================================================
    // 2. إعادة إرسال كود التحقق (Resend OTP)
    // ==================================================
    public function resendOtp()
    {
        // التحقق من وجود الجلسة المؤقتة الخاصة بالرقم
        if (!session()->has('otp_user_id') || !session()->has('otp_phone')) {
            return redirect()->route('login');
        }

        // توليد كود جديد وتحديثه في الجلسة
        $otp = rand(1000, 9999);
        session(['otp_code' => $otp]);

        // إرسال الكود الجديد
        $sent = app(CustomerMessageDeliveryService::class)->send(
            session('otp_phone'),
            "كود التحقق الخاص بك هو: {$otp}",
        );

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? 'تم إرسال رمز التحقق إلى هاتفك.' : 'تعذر إرسال رمز التحقق؛ تحقق من تهيئة مزود الرسائل.'
        );
    }
    
    // ------------------------------------
    // 3. تسجيل الخروج (Logout)
    // ------------------------------------
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'تم تسجيل الخروج بنجاح');
    }
    
}





    