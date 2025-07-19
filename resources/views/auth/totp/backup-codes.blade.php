<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Codes - Collection Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .backup-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .backup-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .backup-codes {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            font-family: monospace;
            font-size: 1.1rem;
            line-height: 2;
        }
        .backup-code {
            display: inline-block;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="backup-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="backup-card p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-key-fill display-1 text-primary"></i>
                            <h2 class="mt-3 mb-1">Backup Codes</h2>
                            <p class="text-muted">Bewaar deze codes op een veilige plek</p>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Belangrijk:</strong> Deze backup codes kunnen gebruikt worden om in te loggen als je je authenticator app verliest. Bewaar ze op een veilige plek!
                        </div>

                        @if(session('success'))
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="backup-codes">
                            @foreach($user->totp_backup_codes as $code)
                                <span class="backup-code">{{ $code }}</span>
                            @endforeach
                        </div>

                        <div class="alert alert-info mt-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Gebruik:</strong> Voer een van deze codes in op de login pagina als je geen toegang hebt tot je authenticator app.
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <form method="POST" action="{{ route('totp.regenerate-backup-codes') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Weet je zeker dat je nieuwe backup codes wilt genereren? De oude codes worden ongeldig.')">
                                    <i class="bi bi-arrow-clockwise me-2"></i>
                                    Nieuwe Backup Codes Genereren
                                </button>
                            </form>
                            
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