<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Voltooid - Collection Manager</title>
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
            text-align: center;
        }
        
        .header {
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .subtitle {
            color: #666;
            line-height: 1.6;
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
            background: #667eea;
            z-index: 1;
        }
        
        .progress-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #28a745;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            position: relative;
            z-index: 2;
        }
        
        .progress-label {
            font-size: 0.8rem;
            color: #666;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
            margin: 0.5rem;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .next-steps {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .next-steps h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .step-list {
            list-style: none;
        }
        
        .step-list li {
            padding: 0.5rem 0;
            color: #666;
            position: relative;
            padding-left: 1.5rem;
        }
        
        .step-list li:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <div class="logo">📚 Collection Manager</div>
            <div class="success-icon">🎉</div>
            <h1 class="title">Setup Voltooid!</h1>
            <p class="subtitle">
                Gefeliciteerd! Collection Manager is succesvol geïnstalleerd en klaar voor gebruik.
            </p>
        </div>
        
        <div class="progress-bar">
            <div class="progress-step">
                <div class="progress-circle">✓</div>
                <div class="progress-label">Database</div>
            </div>
            <div class="progress-step">
                <div class="progress-circle">✓</div>
                <div class="progress-label">Migraties</div>
            </div>
            <div class="progress-step">
                <div class="progress-circle">✓</div>
                <div class="progress-label">Admin</div>
            </div>
            <div class="progress-step">
                <div class="progress-circle">✓</div>
                <div class="progress-label">Klaar</div>
            </div>
        </div>
        
        <div class="next-steps">
            <h3>Volgende Stappen:</h3>
            <ul class="step-list">
                <li>Log in met je admin account</li>
                <li>Voeg je eerste collectie items toe</li>
                <li>Configureer gebruikers en rollen</li>
                <li>Test de barcode scanning functionaliteit</li>
                <li>Stel push notificaties in</li>
            </ul>
        </div>
        
        <div>
            <a href="{{ route('login') }}" class="btn btn-success">Inloggen</a>
            <a href="{{ route('admin.dashboard') }}" class="btn">Admin Dashboard</a>
            <a href="{{ route('collection.index') }}" class="btn btn-secondary">Collectie Bekijken</a>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e0e0e0;">
            <p style="color: #666; font-size: 0.9rem;">
                Heb je vragen of problemen? Raadpleeg de documentatie of neem contact op met de ontwikkelaar.
            </p>
        </div>
    </div>
</body>
</html> 