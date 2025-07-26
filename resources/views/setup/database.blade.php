<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Configuratie - Collection Manager</title>
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
        
        .progress-step.active:not(:last-child):after {
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
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
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
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <div class="logo">📚 Collection Manager</div>
            <h1 class="step-title">Database Configuratie</h1>
            <p class="step-subtitle">Configureer je database verbinding</p>
        </div>
        
        <div class="progress-bar">
            <div class="progress-step active">
                <div class="progress-circle">1</div>
                <div class="progress-label">Database</div>
            </div>
            <div class="progress-step">
                <div class="progress-circle">2</div>
                <div class="progress-label">Migraties</div>
            </div>
            <div class="progress-step">
                <div class="progress-circle">3</div>
                <div class="progress-label">Admin</div>
            </div>
            <div class="progress-step">
                <div class="progress-circle">4</div>
                <div class="progress-label">Klaar</div>
            </div>
        </div>
        
        <div id="alerts"></div>
        
        <div class="alert alert-info">
            <strong>💡 Informatie:</strong> De setup wizard zal automatisch alle benodigde database tabellen aanmaken als ze nog niet bestaan. 
            Dit omvat gebruikers, collecties, permissies en alle andere benodigde tabellen.
        </div>
        
        <form id="databaseForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Database Host</label>
                    <input type="text" name="host" class="form-input" value="127.0.0.1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Port</label>
                    <input type="number" name="port" class="form-input" value="3306" min="1" max="65535" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Database Naam</label>
                <input type="text" name="database" class="form-input" value="collection_manager" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Gebruikersnaam</label>
                    <input type="text" name="username" class="form-input" value="root" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Wachtwoord</label>
                    <input type="password" name="password" class="form-input" value="password">
                </div>
            </div>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Database verbinding testen...</p>
            </div>
            
            <div style="text-align: center;">
                <button type="button" class="btn btn-secondary" onclick="testConnection()">Test Verbinding</button>
                <button type="button" class="btn btn-success" onclick="saveDatabase()" id="saveBtn" disabled>Database Configureren & Migraties Uitvoeren</button>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="{{ route('setup.welcome') }}" class="btn btn-secondary">Terug</a>
            <a href="/server_diagnostics.php" class="btn btn-secondary" target="_blank">Server Diagnostiek</a>
        </div>
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
        
        async function testConnection() {
            const form = document.getElementById('databaseForm');
            const formData = new FormData(form);
            
            showLoading();
            
            try {
                const response = await fetch('{{ route("setup.test-database") }}', {
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
                    document.getElementById('saveBtn').disabled = false;
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('Er is een fout opgetreden bij het testen van de verbinding.', 'danger');
            } finally {
                hideLoading();
            }
        }
        
        async function saveDatabase() {
            const form = document.getElementById('databaseForm');
            const formData = new FormData(form);
            
            showLoading();
            document.getElementById('saveBtn').disabled = true;
            
            // Update loading message
            document.querySelector('#loading p').textContent = 'Database configureren en tabellen aanmaken...';
            
            try {
                const response = await fetch('{{ route("setup.save-database") }}', {
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
                    
                    // Show migration details if available
                    if (data.migration_output) {
                        console.log('Migration Output:', data.migration_output);
                    }
                    if (data.seeder_output) {
                        console.log('Seeder Output:', data.seeder_output);
                    }
                    
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    showAlert(data.message, 'danger');
                    
                    // Show debug info if available
                    if (data.debug_info) {
                        console.error('Debug Info:', data.debug_info);
                    }
                    
                    document.getElementById('saveBtn').disabled = false;
                }
            } catch (error) {
                showAlert('Er is een fout opgetreden bij het configureren van de database. Controleer de server logs voor meer details.', 'danger');
                console.error('Setup Error:', error);
                document.getElementById('saveBtn').disabled = false;
            } finally {
                hideLoading();
            }
        }
    </script>
</body>
</html> 