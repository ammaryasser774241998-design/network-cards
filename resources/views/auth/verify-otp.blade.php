<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد رقم الهاتف - نظام إدارة كروت الشبكة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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

        .auth-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(245, 166, 35, 0.25);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 20px rgba(245, 166, 35, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px 30px 30px;
            position: relative;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 25px;
        }

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

        .user-icon-wrapper i {
            color: #0f172a;
            font-size: 32px;
        }

        .auth-header h4 {
            color: #ffffff;
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .auth-header p {
            color: #94a3b8;
            font-size: 0.88rem;
            margin: 0;
        }

        .form-label {
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.88rem;
            margin-bottom: 6px;
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

        .otp-control {
            letter-spacing: 4px;
            text-align: center;
        }

        .form-control:focus {
            background-color: rgba(15, 23, 42, 0.95);
            border-color: #f5a623;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(245, 166, 35, 0.2);
            outline: none;
        }

        .btn-gold {
            background: linear-gradient(135deg, #f5a623 0%, #d97706 100%);
            color: #0f172a;
            font-weight: 800;
            font-size: 0.98rem;
            border: none;
            border-radius: 10px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(245, 166, 35, 0.3);
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 166, 35, 0.45);
            color: #000;
        }

        .link-gold {
            color: #f5a623;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.2s ease;
        }

        .link-gold:hover {
            color: #fbbf24;
            text-decoration: underline;
        }

        .resend-box {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin-top: 22px;
            padding-top: 16px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.88rem;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 10px;
            font-size: 0.85rem;
        }

        .alert-success-custom {
            background: rgba(245, 166, 35, 0.12);
            border: 1px solid rgba(245, 166, 35, 0.3);
            color: #fcd34d;
            border-radius: 10px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="auth-header">
        <div class="user-icon-wrapper">
            <i class="bi bi-chat-dots"></i>
        </div>
        <h4>تأكيد رقم الهاتف</h4>
        <p>أدخل رمز التحقق إذا وصلك عبر قناة الرسائل</p>
    </div>

    @if (session('error'))
        <div class="alert alert-error text-center py-2 mb-3">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success-custom text-center py-2 mb-3">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('verify.otp') }}" method="POST">
        @csrf
        <div class="form-group mb-3">
            <label class="form-label d-block text-center">أدخل رمز التحقق (4 أرقام)</label>
            <input type="text" name="otp" class="form-control otp-control" placeholder="••••" maxlength="6" required autofocus dir="ltr">
        </div>

        <button type="submit" class="btn btn-gold w-100 mt-2">تأكيد الحساب</button>
    </form>

    <div class="resend-box">
        <span>لم يصلك الرمز؟</span>
        <form action="{{ route('resend.otp') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link link-gold p-0 ms-1 border-0 bg-transparent align-baseline">إعادة الإرسال</button>
        </form>
    </div>
</div>

</body>
</html>