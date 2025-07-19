<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Gebruiker - Collection Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .setup-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 600px;
            width: 90%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .step-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .step-subtitle {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .progress-bar {
            display: flex;
            margin-bottom: 2rem;
        }
        
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .progress-step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .progress-step.active:not(:last-child):after,
        .progress-step.completed:not(:last-child):after {
            background: #667eea;
        }
        
        .progress-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            position: relative;
            z-index: 2;
        }
        
        .progress-step.active .progress-circle {
            background: #667eea;
        }
        
        .progress-step.completed .progress-circle {
            background: #28a745;
        }
        
        .progress-label {
            font-size: 0.8rem;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
            margin-right: 1rem;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin: 1rem 0;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-box h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .info-box p {
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <div class="logo">📚 Collection Manager</div>
            <h1 class="step-title">Admin Gebruiker Aanmaken</h1>
            <p class="step-subtitle">Maak je eerste admin gebruiker aan</p>
        </div>
        
        <div class="progress-bar">
            <div class="progress-step completed">
                <div class="progress-circle">✓</div>
                <div class="progress-label">Database</div>
            </div>
            <div class="progress-step completed">
                <div class="progress-circle">✓</div>
                <div class="progress-label">Migraties</div>
            </div>
            <div class="progress-step active">
                <div class="progress-circle">3</div>
                <div class="progress-label">Admin</div>
            </div>
            <div class="progress-step">
                <div class="progress-circle">4</div>
                <div class="progress-label">Klaar</div>
            </div>
        </div>
        
        <div class="info-box">
            <h3>Admin Gebruiker</h3>
            <p>Deze gebruiker krijgt volledige toegang tot de applicatie en kan andere gebruikers, rollen en permissies beheren. Zorg ervoor dat je een sterk wachtwoord gebruikt.</p>
        </div>
        
        <div id="alerts"></div>
        
        <form id="adminForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Naam</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Gebruikersnaam</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">E-mail Adres</label>
                <input type="email" name="email" class="form-input" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Wachtwoord</label>
                    <input type="password" name="password" class="form-input" minlength="8" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Wachtwoord Bevestigen</label>
                    <input type="password" name="password_confirmation" class="form-input" minlength="8" required>
                </div>
            </div>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Admin gebruiker aanmaken...</p>
            </div>
            
            <div style="text-align: center;">
                <button type="button" class="btn btn-success" onclick="createAdmin()">Admin Gebruiker Aanmaken</button>
            </div>
        </form>
    </div>
    
    <script>
        function showAlert(message, type = 'info') {
            const alerts = document.getElementById('alerts');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alerts.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }
        
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
        
        async function createAdmin() {
            const form = document.getElementById('adminForm');
            const formData = new FormData(form);
            
            // Validate password confirmation
            const password = formData.get('password');
            const passwordConfirmation = formData.get('password_confirmation');
            
            if (password !== passwordConfirmation) {
                showAlert('Wachtwoorden komen niet overeen.', 'danger');
                return;
            }
            
            showLoading();
            
            try {
                const response = await fetch('{{ route("setup.create-admin") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(Object.fromEntries(formData)),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('Er is een fout opgetreden bij het aanmaken van de admin gebruiker.', 'danger');
            } finally {
                hideLoading();
            }
        }
    </script>
</body>
</html> 