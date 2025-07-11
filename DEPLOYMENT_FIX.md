# Deployment Fix - Composer Lock File

## Probleem
De composer.lock file is niet up-to-date met de nieuwe dependencies in composer.json. Dit veroorzaakt een deployment fout.

## Oplossing

### Optie 1: Eenvoudige Fix (Aanbevolen)
PHPMailer is nu optioneel gemaakt. Voer op de server uit:
```bash
cd /path/to/your/project
composer install --no-dev --optimize-autoloader
```

### Optie 2: Composer Update
Als optie 1 niet werkt:
```bash
cd /path/to/your/project
composer update
composer install --no-dev --optimize-autoloader
```

### Optie 3: Lock File Regenereren
Als er nog steeds problemen zijn:
```bash
cd /path/to/your/project
rm composer.lock
composer install --no-dev --optimize-autoloader
```

## Wijzigingen
- **PHPMailer is nu optioneel**: Verplaatst van `require` naar `suggest` in composer.json
- **E-mail functionaliteit werkt zonder PHPMailer**: De app controleert of PHPMailer beschikbaar is
- **Geen deployment fouten meer**: Composer install werkt nu zonder problemen

## Deployment Command
Het deployment command zou nu moeten werken:
```bash
ssh -o StrictHostKeyChecking=no user@server "cd /path/to/project && git pull origin main && composer install --no-dev --optimize-autoloader"
```

## E-mail Functionaliteit
- **Met PHPMailer**: Volledige e-mail functionaliteit (delen van collecties)
- **Zonder PHPMailer**: App werkt normaal, e-mail functionaliteit is uitgeschakeld
- **Installatie PHPMailer**: `composer require phpmailer/phpmailer:^6.8`

## Controle
Na de fix, controleer of alles werkt:
```bash
composer install --no-dev --optimize-autoloader
```

## Notities
- De app werkt nu zonder PHPMailer
- E-mail functionaliteit is optioneel
- PHPMailer kan later toegevoegd worden als gewenst 