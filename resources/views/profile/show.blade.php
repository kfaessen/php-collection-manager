<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiel - Collection Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .profile-container {
            min-height: 100vh;
            padding: 2rem 0;
        }
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="profile-card p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-person-circle display-1 text-primary"></i>
                            <h2 class="mt-3 mb-1">Mijn Profiel</h2>
                            <p class="text-muted">Beheer je account instellingen</p>
                        </div>

                        @if(session('success'))
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                {{ session('success') }}
                            </div>
                        @endif

                        @if(session('info'))
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                {{ session('info') }}
                            </div>
                        @endif

                        <!-- Profile Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-person me-2"></i>Profiel Informatie</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('profile.update') }}">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Naam</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="{{ old('name', $user->name) }}" required>
                                            @error('name')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">E-mailadres</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="{{ old('email', $user->email) }}" required>
                                            @error('email')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Profiel Bijwerken
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-lock me-2"></i>Wachtwoord Wijzigen</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('profile.password') }}">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="current_password" class="form-label">Huidig Wachtwoord</label>
                                            <input type="password" class="form-control" id="current_password" 
                                                   name="current_password" required>
                                            @error('current_password')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="password" class="form-label">Nieuw Wachtwoord</label>
                                            <input type="password" class="form-control" id="password" 
                                                   name="password" required>
                                            @error('password')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="password_confirmation" class="form-label">Bevestig Wachtwoord</label>
                                            <input type="password" class="form-control" id="password_confirmation" 
                                                   name="password_confirmation" required>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-key me-2"></i>
                                        Wachtwoord Wijzigen
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Two-Factor Authentication -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Twee-factor Authenticatie</h5>
                            </div>
                            <div class="card-body">
                                @if($user->totp_enabled)
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <strong>TOTP is ingeschakeld</strong> - Je account is beveiligd met twee-factor authenticatie.
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <a href="{{ route('totp.backup-codes') }}" class="btn btn-info">
                                                <i class="bi bi-key-fill me-2"></i>
                                                Backup Codes Bekijken
                                            </a>
                                        </div>
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disableTOTPModal">
                                                <i class="bi bi-shield-x me-2"></i>
                                                TOTP Uitschakelen
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>TOTP is niet ingeschakeld</strong> - Beveilig je account met twee-factor authenticatie.
                                    </div>
                                    
                                    <a href="{{ route('totp.setup') }}" class="btn btn-success">
                                        <i class="bi bi-shield-check me-2"></i>
                                        TOTP Inschakelen
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Account Informatie</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Account aangemaakt:</strong> {{ $user->created_at->format('d-m-Y H:i') }}</p>
                                        <p><strong>Laatste login:</strong> {{ $user->last_login ? $user->last_login->format('d-m-Y H:i') : 'Nog niet ingelogd' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Rollen:</strong> 
                                            @foreach($user->roles as $role)
                                                <span class="badge bg-primary">{{ $role->display_name }}</span>
                                            @endforeach
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

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

    <!-- Disable TOTP Modal -->
    <div class="modal fade" id="disableTOTPModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">TOTP Uitschakelen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('profile.disable-totp') }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Waarschuwing:</strong> Door TOTP uit te schakelen wordt je account minder veilig.
                        </div>
                        <div class="mb-3">
                            <label for="totp_code" class="form-label">Authenticatie Code</label>
                            <input type="text" class="form-control" id="totp_code" name="totp_code" 
                                   placeholder="123456" maxlength="6" required>
                            @error('totp_code')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" class="btn btn-danger">TOTP Uitschakelen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 