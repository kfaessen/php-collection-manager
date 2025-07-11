<?php
require_once '../includes/functions.php';
Authentication::requireLogin();

$userId = Authentication::getCurrentUserId();
$user = Authentication::getCurrentUser();
$feedback = '';
$error = '';

// Formulierverwerking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_totp'])) {
        $result = Authentication::enableTOTP($userId);
        if ($result['success']) {
            $feedback = 'TOTP geconfigureerd. Scan de QR code met je authenticator app.';
            $secret = $result['secret'];
            $backupCodes = $result['backup_codes'];
            $qrUrl = $result['qr_url'];
        } else {
            $error = $result['message'];
        }
    }
    
    if (isset($_POST['verify_totp'])) {
        $code = Utils::sanitize($_POST['totp_code']);
        $result = Authentication::verifyAndEnableTOTP($userId, $code);
        if ($result['success']) {
            $feedback = 'TOTP succesvol ingeschakeld!';
            header('Refresh: 2; URL=totp-setup.php');
        } else {
            $error = $result['message'];
        }
    }
    
    if (isset($_POST['disable_totp'])) {
        $result = Authentication::disableTOTP($userId);
        if ($result['success']) {
            $feedback = 'TOTP succesvol uitgeschakeld!';
            header('Refresh: 2; URL=totp-setup.php');
        } else {
            $error = $result['message'];
        }
    }
    
    if (isset($_POST['generate_backup_codes'])) {
        $result = Authentication::generateNewBackupCodes($userId);
        if ($result['success']) {
            $feedback = 'Nieuwe backup codes gegenereerd.';
            $backupCodes = $result['backup_codes'];
        } else {
            $error = $result['message'];
        }
    }
}

// Haal huidige gebruiker opnieuw op voor TOTP status
$user = Authentication::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twee-factor authenticatie - Collectiebeheer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <a href="index.php" class="btn btn-outline-primary mb-3"><i class="bi bi-arrow-left"></i> Terug naar overzicht</a>
        <h2><i class="bi bi-shield-lock"></i> Twee-factor authenticatie</h2>
        
        <?php if ($feedback): ?>
            <div class="alert alert-success mt-3"><?= htmlspecialchars($feedback) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <?php if (!$user['totp_enabled']): ?>
                    <!-- TOTP niet ingeschakeld -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-shield-plus"></i> Twee-factor authenticatie inschakelen</h5>
                        </div>
                        <div class="card-body">
                            <p>Twee-factor authenticatie voegt een extra beveiligingslaag toe aan je account. 
                            Na het inschakelen moet je bij elke login een code invoeren die wordt gegenereerd door een authenticator app.</p>
                            
                            <div class="alert alert-info">
                                <strong>Benodigd:</strong> Een authenticator app zoals Google Authenticator, Authy, of Microsoft Authenticator.
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="enable_totp" class="btn btn-primary">
                                    <i class="bi bi-shield-check"></i> TOTP inschakelen
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <?php if (isset($secret) && isset($qrUrl)): ?>
                        <!-- TOTP setup stap 2 -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5><i class="bi bi-qr-code"></i> QR Code scannen</h5>
                            </div>
                            <div class="card-body">
                                <p>Scan deze QR code met je authenticator app:</p>
                                
                                <div class="text-center mb-3">
                                    <div id="qrcode"></div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <strong>Handmatige invoer:</strong> Als de QR code niet werkt, voer dan handmatig in:<br>
                                    <code><?= htmlspecialchars($secret) ?></code>
                                </div>
                                
                                <p>Voer de 6-cijferige code in die wordt getoond door je authenticator app:</p>
                                
                                <form method="POST" class="row g-3">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="totp_code" 
                                               placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" name="verify_totp" class="btn btn-success">
                                            <i class="bi bi-check-circle"></i> Verifiëren en inschakelen
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Backup codes -->
                        <?php if (isset($backupCodes)): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5><i class="bi bi-key"></i> Backup codes</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <strong>Belangrijk:</strong> Bewaar deze backup codes op een veilige plek. 
                                        Je kunt ze gebruiken om in te loggen als je telefoon niet beschikbaar is.
                                    </div>
                                    
                                    <div class="row">
                                        <?php foreach ($backupCodes as $code): ?>
                                            <div class="col-md-3 col-sm-4 col-6 mb-2">
                                                <code class="d-block p-2 bg-light text-center"><?= htmlspecialchars($code) ?></code>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <small class="text-muted">
                                        Elke backup code kan maar één keer gebruikt worden.
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- TOTP ingeschakeld -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-shield-check"></i> Twee-factor authenticatie ingeschakeld</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> Twee-factor authenticatie is actief voor je account.
                            </div>
                            
                            <p>Je moet nu bij elke login een code invoeren die wordt gegenereerd door je authenticator app.</p>
                            
                            <form method="POST" onsubmit="return confirm('Weet je zeker dat je twee-factor authenticatie wilt uitschakelen?')">
                                <button type="submit" name="disable_totp" class="btn btn-danger">
                                    <i class="bi bi-shield-x"></i> TOTP uitschakelen
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Backup codes beheer -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="bi bi-key"></i> Backup codes beheren</h5>
                        </div>
                        <div class="card-body">
                            <p>Backup codes kunnen gebruikt worden om in te loggen als je telefoon niet beschikbaar is.</p>
                            
                            <form method="POST" onsubmit="return confirm('Weet je zeker dat je nieuwe backup codes wilt genereren? De oude codes worden ongeldig.')">
                                <button type="submit" name="generate_backup_codes" class="btn btn-warning">
                                    <i class="bi bi-arrow-clockwise"></i> Nieuwe backup codes genereren
                                </button>
                            </form>
                            
                            <?php if (isset($backupCodes)): ?>
                                <div class="mt-3">
                                    <div class="alert alert-warning">
                                        <strong>Nieuwe backup codes:</strong> Bewaar deze op een veilige plek.
                                    </div>
                                    
                                    <div class="row">
                                        <?php foreach ($backupCodes as $code): ?>
                                            <div class="col-md-3 col-sm-4 col-6 mb-2">
                                                <code class="d-block p-2 bg-light text-center"><?= htmlspecialchars($code) ?></code>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-info-circle"></i> Informatie</h5>
                    </div>
                    <div class="card-body">
                        <h6>Wat is twee-factor authenticatie?</h6>
                        <p>Twee-factor authenticatie (2FA) voegt een extra beveiligingslaag toe aan je account. 
                        Naast je wachtwoord moet je ook een tijdelijke code invoeren die wordt gegenereerd door een app op je telefoon.</p>
                        
                        <h6>Hoe werkt het?</h6>
                        <ul>
                            <li>Scan de QR code met je authenticator app</li>
                            <li>De app genereert elke 30 seconden een nieuwe 6-cijferige code</li>
                            <li>Voer deze code in bij het inloggen</li>
                            <li>Bewaar je backup codes voor noodgevallen</li>
                        </ul>
                        
                        <h6>Aanbevolen apps:</h6>
                        <ul>
                            <li>Google Authenticator</li>
                            <li>Authy</li>
                            <li>Microsoft Authenticator</li>
                            <li>1Password</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
    
    <?php if (isset($qrUrl)): ?>
    <script>
        // Generate QR code
        QRCode.toCanvas(document.getElementById('qrcode'), '<?= $qrUrl ?>', function (error) {
            if (error) console.error(error);
        });
    </script>
    <?php endif; ?>
</body>
</html> 