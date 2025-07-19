<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOTP Setup - Collection Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .setup-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .setup-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .qr-code {
            text-align: center;
            margin: 2rem 0;
        }
        .secret-key {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="setup-card p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock display-1 text-primary"></i>
                            <h2 class="mt-3 mb-1">Twee-factor Authenticatie</h2>
                            <p class="text-muted">Beveilig je account met TOTP</p>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Stap 1:</strong> Scan de QR-code met je authenticator app (Google Authenticator, Authy, etc.)
                        </div>

                        <div class="qr-code">
                            {!! $qrCode !!}
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><strong>Handmatige setup (als QR-code niet werkt):</strong></label>
                            <div class="secret-key">
                                <strong>Account:</strong> {{ $user->email }}<br>
                                <strong>Secret:</strong> {{ $user->totp_secret }}
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Stap 2:</strong> Voer de 6-cijferige code in van je authenticator app om TOTP te activeren
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('totp.enable') }}">
                            @csrf
                            
                            <div class="mb-4">
                                <label for="code" class="form-label">Authenticatie Code</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-key"></i>
                                    </span>
                                    <input type="text" class="form-control" id="code" name="code" 
                                           placeholder="123456" maxlength="6" required autofocus>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    TOTP Activeren
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Terug naar Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 