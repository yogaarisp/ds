<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - {{ $storeName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f8fafc;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            padding: 45px 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
            letter-spacing: -0.01em;
        }
        .message {
            font-size: 16px;
            color: #475569;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .button-container {
            text-align: center;
            margin: 40px 0;
        }
        .button {
            display: inline-block;
            padding: 18px 48px;
            background: #f97316;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 10px 15px -3px rgba(249, 115, 22, 0.4);
            transition: all 0.2s;
            border: none;
        }
        .info-card {
            background: #f1f5f9;
            padding: 24px;
            border-radius: 20px;
            margin: 30px 0;
            border: 1px solid #e2e8f0;
        }
        .info-card-title {
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            display: block;
        }
        .info-card-text {
            font-size: 14px;
            color: #475569;
            margin: 0;
        }
        .footer {
            padding: 40px;
            text-align: center;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
        }
        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #94a3b8;
        }
        .footer-logo {
            font-weight: 800;
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 15px !important;
        }
        .raw-link {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #e2e8f0;
            text-align: left;
        }
        .link-box {
            background: #f8fafc;
            padding: 12px;
            border-radius: 10px;
            margin-top: 10px;
            word-break: break-all;
            color: #f97316;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>🔐 Reset Password</h1>
                <p>{{ $storeName }}</p>
            </div>
            
            <div class="content">
                <div class="greeting">
                    Halo, {{ explode(' ', trim($customer->name))[0] }}! 👋
                </div>
                
                <div class="message">
                    Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda di <strong>{{ $storeName }}</strong>. Gunakan tombol di bawah ini untuk membuat password yang baru:
                </div>
                
                <div class="button-container">
                    <a href="{{ $resetUrl }}" class="button">Atur Ulang Password</a>
                </div>
                
                <div class="info-card">
                    <span class="info-card-title">PENTING</span>
                    <p class="info-card-text">Link ini hanya berlaku selama <strong>60 menit</strong>. Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan pesan ini.</p>
                </div>
                
                <div class="raw-link">
                    Jika tombol di atas tidak bisa diklik, silakan salin link di bawah ini ke browser Anda:
                    <div class="link-box">
                        {{ $resetUrl }}
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p class="footer-logo">{{ $storeName }}</p>
                <p>© {{ date('Y') }} {{ $storeName }}. All rights reserved.</p>
                <p>Email ini dikirim secara otomatis oleh sistem.</p>
            </div>
        </div>
    </div>
</body>
</html>
