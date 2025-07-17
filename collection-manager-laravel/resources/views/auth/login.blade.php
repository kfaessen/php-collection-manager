<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen - Collection Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .oauth-divider {
            text-align: center;
            margin: 2rem 0;
            position: relative;
        }
        .oauth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }
        .oauth-divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="login-card p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-collection display-1 text-primary"></i>
                            <h2 class="mt-3 mb-1">Collection Manager</h2>
                            <p class="text-muted">Beheer je collectie</p>
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

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mailadres</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="{{ old('email') }}" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Wachtwoord</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Inloggen
                                </button>
                            </div>
                        </form>

                        <!-- OAuth Login -->
                        @php
                            $oauthService = app(\App\Services\OAuthService::class);
                        @endphp
                        
                        @if($oauthService->isEnabled('google') || $oauthService->isEnabled('facebook'))
                            <div class="oauth-divider">
                                <span>of</span>
                            </div>

                            <div class="d-grid gap-2">
                                @if($oauthService->isEnabled('google'))
                                    <a href="{{ route('oauth.redirect', 'google') }}" class="btn btn-outline-danger">
                                        <i class="bi bi-google me-2"></i>
                                        Inloggen met Google
                                    </a>
                                @endif

                                @if($oauthService->isEnabled('facebook'))
                                    <a href="{{ route('oauth.redirect', 'facebook') }}" class="btn btn-outline-primary">
                                        <i class="bi bi-facebook me-2"></i>
                                        Inloggen met Facebook
                                    </a>
                                @endif
                            </div>
                        @endif

                        <div class="text-center mt-4">
                            <p class="mb-0">
                                Nog geen account? 
                                <a href="{{ route('register') }}" class="text-decoration-none">Registreer hier</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 