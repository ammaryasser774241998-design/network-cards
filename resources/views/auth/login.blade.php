<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام إدارة كروت الشبكة</title>
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
            padding: 20px;
            overflow-x: hidden;
        }

        /* حاوية البطاقة بتأثير الزجاج والتوهج الذهبي */
        .login-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(245, 166, 35, 0.25);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 20px rgba(245, 166, 35, 0.1);
            width: 100%;
            max-width: 430px;
            padding: 40px 30px 30px 30px;
            position: relative;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        /* شارة أصلية لأيقونة الواي فاي عصري */
        .wifi-icon-wrapper {
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

        .wifi-icon-wrapper svg {
            width: 38px;
            height: 38px;
            fill: #0f172a;
        }

        .login-header h4 {
            font-weight: 800;
            color: #ffffff;
            font-size: 1.4rem;
            margin-bottom: 5px;
        }

        .login-header p {
            color: #94a3b8;
            font-size: 0.88rem;
            margin: 0;
        }

        /* حقول الإدخال */
        .form-group label {
            color: #cbd5e1;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            background-color: rgba(15, 23, 42, 0.8);
            border: 1px solid #334155;
            color: #ffffff;
            border-radius: 10px;
            padding: 12px 15px;
            height: auto;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: rgba(15, 23, 42, 0.95);
            border-color: #f5a623;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(245, 166, 35, 0.2);
            outline: none;
        }

        /* زر الدخول */
        .btn-gold {
            background: linear-gradient(135deg, #f5a623 0%, #d97706 100%);
            color: #0f172a;
            font-weight: 800;
            font-size: 1rem;
            border: none;
            border-radius: 10px;
            padding: 12px;
            width: 100%;
            margin-top: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(245, 166, 35, 0.3);
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 166, 35, 0.45);
            color: #000;
        }

        /* زر ورابط إنشاء حساب جديد - منظم وممركز أسفل الكارت */
        .register-box {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin-top: 25px;
            padding-top: 18px;
            text-align: center;
        }

        .register-box span {
            color: #94a3b8;
            font-size: 0.88rem;
        }

        .register-link {
            color: #f5a623;
            font-weight: 700;
            text-decoration: none;
            margin-right: 5px;
            transition: color 0.2s ease;
        }

        .register-link:hover {
            color: #fbbf24;
            text-decoration: underline;
        }

        /* مربعات التنبيه */
        .alert {
            border-radius: 10px;
            font-size: 0.85rem;
            border: none;
        }
    </style>
</head>
<body>

<div class="login-card">
    <!-- الهيدر وأيقونة الواي فاي العصرية -->
    <div class="login-header">
        <div class="wifi-icon-wrapper">
            <svg viewBox="0 0 24 24">
                <path d="M12 3C7.03 3 2.5 5.25 0 8.78l2.25 2.25C4.21 8.21 7.88 6.25 12 6.25c4.12 0 7.79 1.96 9.75 4.78L24 8.78C21.5 5.25 16.97 3 12 3zm0 6c-3.31 0-6.31 1.39-8.48 3.63L6 15.13C7.54 13.59 9.66 12.63 12 12.63s4.46.96 6 2.5l2.48-2.5C18.31 10.39 15.31 9 12 9zm0 6c-1.66 0-3.16.69-4.24 1.81L12 21.19l4.24-4.38C15.16 15.69 13.66 15 12 15z"/>
            </svg>
        </div>
        <h4>نظام إدارة كروت الشبكة</h4>
        <p>سجّل دخولك للمتابعة والتحكم</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success text-center py-2 mb-3">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger text-center py-2 mb-3">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- نموذج تسجيل الدخول -->
    <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="form-group">
    <label>البريد الإلكتروني أو رقم الهاتف <span class="text-danger">*</span></label>
    <input type="text" name="login" class="form-control" placeholder="أدخل البريد أو رقم الهاتف" value="{{ old('login') }}" required autofocus dir="ltr">
</div>

        <div class="form-group">
            <label>كلمة المرور</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required dir="ltr">
        </div>

        <div class="form-group d-flex justify-content-between align-items-center mb-2">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                <label class="custom-control-label small text-muted cursor-pointer" for="remember">تذكرني</label>
            </div>
        </div>

        <button type="submit" class="btn btn-gold">تسجيل الدخول</button>
    </form>

    <!-- قسم إنشاء حساب جديد (تم إعادة تموضعه بداخل الكارت بشكل ممركّز) -->
    <div class="register-box">
        <span>ليس لديك حساب؟</span>
        <a href="{{ route('register') }}" class="register-link">إنشاء حساب جديد</a>
    </div>
</div>

</body>
</html>