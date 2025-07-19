# Unified Deployment Pipeline

## 🎯 Overzicht

De Collection Manager heeft nu één unified deployment pipeline die automatisch detecteert welke deployment methode gebruikt moet worden op basis van de beschikbare secrets.

## ✨ Features

### 🔄 Automatische Deployment Detectie
- **SSH Deployment**: Voor VPS/dedicated servers
- **FTP Deployment**: Voor shared hosting
- **Conditional Execution**: Alleen relevante deployment methode wordt uitgevoerd

### 🧪 Test Pipeline
- **MySQL Service**: Voor database tests
- **PHP 8.2**: Met alle benodigde extensies
- **Composer**: Dependency installatie
- **Laravel Tests**: Volledige test suite

### 🚀 Production Deployment
- **Code Optimization**: Config, route en view caching
- **Dependency Management**: Production dependencies
- **Permission Management**: Correcte bestandsrechten
- **Storage Link**: Symbolische links voor uploads

## 📋 Pipeline Structuur

### Test Job
```yaml
test:
  runs-on: ubuntu-latest
  services:
    mysql: # Test database
  steps:
    - Setup PHP 8.2
    - Install dependencies
    - Configure test database
    - Run tests
```

### Deploy Job
```yaml
deploy:
  needs: test
  runs-on: ubuntu-latest
  steps:
    - Setup PHP 8.2
    - Install dependencies
    - Optimize for production
    - Deploy via SSH (if SSH_HOST secret exists)
    - Deploy via FTP (if FTP_SERVER secret exists)
```

## 🔧 Deployment Methoden

### SSH Deployment (VPS/Dedicated)
**Secrets nodig:**
- `SSH_HOST`
- `SSH_USER`
- `SSH_PRIVATE_KEY`
- `DEPLOY_PATH`

**Voordelen:**
- Volledige server controle
- Git-based deployment
- Automatische dependency installatie
- Laravel artisan commando's

### FTP Deployment (Shared Hosting)
**Secrets nodig:**
- `FTP_SERVER`
- `FTP_USERNAME`
- `FTP_PASSWORD`
- `FTP_SERVER_DIR`

**Voordelen:**
- Werkt met alle hosting providers
- Geen SSH toegang nodig
- Eenvoudige configuratie
- Snelle deployment

## 📝 Secrets Configuratie

### Voor SSH Deployment
```yaml
SSH_HOST: your-server.com
SSH_USER: username
SSH_PRIVATE_KEY: |
  -----BEGIN OPENSSH PRIVATE KEY-----
  your-private-key-content
  -----END OPENSSH PRIVATE KEY-----
DEPLOY_PATH: /var/www/collection-manager
```

### Voor FTP Deployment
```yaml
FTP_SERVER: ftp.yourhosting.com
FTP_USERNAME: your-username
FTP_PASSWORD: your-password
FTP_SERVER_DIR: /public_html
```

### Voor Beide Methoden
```yaml
# Algemene secrets (optioneel)
APP_ENV: production
APP_DEBUG: false
```

## 🚀 Deployment Flow

### 1. Code Push
```bash
git push origin main
```

### 2. Pipeline Trigger
- GitHub Actions detecteert push naar main/master
- Pipeline start automatisch

### 3. Test Execution
- PHP 8.2 setup
- Composer install
- Database configuratie
- Test uitvoering

### 4. Deployment
- **Als SSH secrets bestaan**: SSH deployment
- **Als FTP secrets bestaan**: FTP deployment
- **Als beide bestaan**: Beide worden uitgevoerd

### 5. Post-Deployment
- Database setup via setup wizard
- Admin gebruiker aanmaken
- Applicatie testen

## 🔒 Veiligheid

### Secrets Management
- Alle credentials in GitHub Secrets
- Geen hardcoded waardes
- Versleutelde opslag
- Per-repository isolatie

### Deployment Veiligheid
- Alleen main/master branch
- Test vereist voor deployment
- Conditional execution
- Rollback mogelijkheden

## 📊 Voordelen

### Voor Ontwikkelaars
- **Eén pipeline**: Voor alle hosting providers
- **Automatische detectie**: Geen handmatige configuratie
- **Flexibiliteit**: SSH en FTP ondersteuning
- **Betrouwbaarheid**: Test vereist voor deployment

### Voor Gebruikers
- **Eenvoudige setup**: Upload en setup wizard
- **Betrouwbare deployment**: Geautomatiseerd proces
- **Flexibele hosting**: Werkt overal
- **Snelle updates**: Automatische deployment

## 🐛 Troubleshooting

### Pipeline Fails
1. **Check secrets**: Alle benodigde secrets ingesteld?
2. **Check permissions**: SSH key of FTP credentials correct?
3. **Check paths**: Deployment paths correct?
4. **Check logs**: GitHub Actions logs bekijken

### Deployment Issues
1. **SSH Issues**: SSH key en permissions controleren
2. **FTP Issues**: Credentials en server directory controleren
3. **Database Issues**: Setup wizard gebruiken
4. **Permission Issues**: Bestandsrechten controleren

## 🔮 Toekomstige Verbeteringen

- [ ] Multi-environment support (staging, production)
- [ ] Automatic rollback on failure
- [ ] Health checks na deployment
- [ ] Slack/Discord notifications
- [ ] Database backup voor deployment
- [ ] Blue-green deployment
- [ ] Canary deployments

## 📚 Gerelateerde Documentatie

- [Setup Wizard](./SETUP_WIZARD.md)
- [Pipeline Database Removal](./PIPELINE_DATABASE_REMOVAL.md)
- [Deployment Guide](./DEPLOYMENT_OVH.md) 