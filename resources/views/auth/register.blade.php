<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد - نظام إدارة كروت الشبكة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Cairo', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 30px 20px;
            overflow-x: hidden;
        }

        /* حاوية البطاقة بتأثير الزجاج والتوهج الذهبي */
        .auth-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(245, 166, 35, 0.25);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 20px rgba(245, 166, 35, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px 30px 30px 30px;
            position: relative;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 25px;
        }

        /* أيقونة تسجيل مستخدم جديد متوهجة */
        .user-icon-wrapper {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #f5a623 0%, #d97706 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 25px rgba(245, 166, 35, 0.4);
            margin-bottom: 15px;
            animation: pulse-glow 3s infinite alternate;
        }

        @keyframes pulse-glow {
            0% { box-shadow: 0 0 15px rgba(245, 166, 35, 0.3); }
            100% { box-shadow: 0 0 30px rgba(245, 166, 35, 0.6); }
        }

        .user-icon-wrapper svg {
            width: 36px;
            height: 36px;
            fill: #0f172a;
        }

        .auth-header h4 {
            font-weight: 800;
            color: #ffffff;
            font-size: 1.4rem;
            margin-bottom: 5px;
        }

        .auth-header p {
            color: #94a3b8;
            font-size: 0.88rem;
            margin: 0;
        }

        /* حقول الإدخال */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            color: #cbd5e1;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        .form-control {
            background-color: rgba(15, 23, 42, 0.8);
            border: 1px solid #334155;
            color: #ffffff;
            border-radius: 10px;
            padding: 10px 14px;
            height: auto;
            font-size: 0.92rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: rgba(15, 23, 42, 0.95);
            border-color: #f5a623;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(245, 166, 35, 0.2);
            outline: none;
        }

        /* زر إنشاء الحساب */
        .btn-gold {
            background: linear-gradient(135deg, #f5a623 0%, #d97706 100%);
            color: #0f172a;
            font-weight: 800;
            font-size: 0.98rem;
            border: none;
            border-radius: 10px;
            padding: 12px;
            width: 100%;
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(245, 166, 35, 0.3);
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 166, 35, 0.45);
            color: #000;
        }

        /* فاصل ورابط الانتقال لتسجيل الدخول */
        .login-box {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin-top: 22px;
            padding-top: 16px;
            text-align: center;
        }

        .login-box span {
            color: #94a3b8;
            font-size: 0.88rem;
        }

        .login-link {
            color: #f5a623;
            font-weight: 700;
            text-decoration: none;
            margin-right: 5px;
            transition: color 0.2s ease;
        }

        .login-link:hover {
            color: #fbbf24;
            text-decoration: underline;
        }

        /* تنبيه الأخطاء */
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 10px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <!-- الهيدر وأيقونة مستخدم جديد عصري -->
    <div class="auth-header">
        <div class="user-icon-wrapper">
            <svg viewBox="0 0 24 24">
                <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
        </div>
        <h4>إنشاء حساب جديد</h4>
        <p>أدخل بياناتك للانضمام إلى النظام</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 mb-3">
            <ul class="mb-0 pr-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- نموذج إنشاء الحساب -->
    <form action="{{ route('register') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>الاسم الكامل <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="أدخل اسمك الكامل" value="{{ old('name') }}" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label">البريد الإلكتروني أو رقم الهاتف<span class="text-danger">*</span></label>
            <input type="text" name="login" class="form-control" placeholder="أدخل البريد أو رقم الهاتف" value="{{ old('login') }}" required dir="ltr">
        </div>

        <div class="form-group">
            <label>كلمة المرور <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required dir="ltr">
        </div>

        <div class="form-group">
            <label>تأكيد كلمة المرور <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••" required dir="ltr">
        </div>

        <button type="submit" class="btn btn-gold">إنشاء الحساب ودخول النظام</button>
    </form>

    <!-- رابط الانتقال لصفحة تسجيل الدخول -->
    <div class="login-box">
        <span>لديك حساب بالفعل؟</span>
        <a href="{{ route('login') }}" class="login-link">تسجيل الدخول</a>
    </div>
</div>

</body>
</html>