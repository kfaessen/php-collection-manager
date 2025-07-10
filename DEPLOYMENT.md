# 🚀 Deployment Handleiding - Git Hosting Control Panel

Deze handleiding beschrijft hoe je de PHP Collectiebeheer-app eenvoudig uitrolt naar OVH (of vergelijkbare) hosting via het Git control panel van je provider. Je hebt geen GitHub Actions of CI/CD pipelines meer nodig: alles verloopt via branches en webhooks.

## 📋 Vereisten

- GitHub repository (publiek of privé)
- Hosting met Git deployment support (zoals OVH)
- Domein/subdomeinen voor elke omgeving

## 🌳 Branches & Omgevingen

Voor elke omgeving gebruik je een aparte branch en een aparte directory op de server:

| Branch | Omgeving      | Directory op server      |
|--------|--------------|-------------------------|
| dev    | Development  | /var/www/dev            |
| tst    | Test         | /var/www/tst            |
| acc    | Acceptatie   | /var/www/acc            |
| main   | Productie    | /var/www/prd            |

## 🛠️ Deployment instellen via het control panel

1. **Log in op je hosting control panel**
2. Ga naar het onderdeel voor Git deployment.
3. Maak voor elke omgeving een aparte deployment-configuratie aan:
   - **Repository URL:** jouw GitHub repository (HTTPS of SSH)
   - **Branch:** dev, tst, acc of main
   - **Directory:** de juiste map per omgeving (zie tabel hierboven)
4. Sla de configuratie op.

## 🔔 Webhook instellen in GitHub

1. In het control panel krijg je per deployment een unieke webhook-URL.
2. Ga in GitHub naar je repository → **Settings** → **Webhooks** → **Add webhook**.
3. Plak de webhook-URL van je hosting.
4. Selecteer 'Just the push event'.
5. Sla op.

Herhaal dit voor elke branch/omgeving.

## 🗂️ .env per omgeving

Elke omgeving heeft een eigen `.env`-bestand in de juiste directory op de server. Gebruik het meegeleverde `.env.template` als basis.

Voorbeeld voor development (`/var/www/dev/.env`):

```env
DB_HOST=localhost
DB_USER=collectie_user
DB_PASS=sterk_wachtwoord
DB_NAME=collectie_manager
DB_PREFIX=dev_
APP_ENV=dev
APP_DEBUG=true
APP_URL=https://dev.uwdomein.nl
```

Pas voor elke omgeving de variabelen aan (vooral `DB_PREFIX`, `APP_ENV`, `APP_URL`).

## 🚀 Deployment flow

- **Push naar branch** → Hosting haalt automatisch de laatste code op en zet deze in de juiste directory.
- **Webhook** zorgt dat de deployment direct na een push start.
- **.env** zorgt voor de juiste configuratie per omgeving.

## 🧹 Geen CI/CD pipelines meer nodig

- Je hebt geen GitHub Actions, SSH keys of GitHub Secrets meer nodig voor deployment.
- Alle deployment gebeurt via het hosting control panel en Git push.

## 🛠️ Veelvoorkomende handelingen

- **Nieuwe feature testen?**
  - Maak een feature branch van `dev`, merge na testen in `dev` en push.
- **Release naar productie?**
  - Merge `acc` of `tst` naar `main` en push.
- **Rollback?**
  - Gebruik de backup/restore functionaliteit van je hosting (indien aanwezig) of herstel een vorige commit.

## 📞 Support

- Problemen met deployment? Check de logs in je hosting control panel.
- Problemen met de app? Controleer het `.env`-bestand en de bestandspermissies.

---

**Let op:** commit nooit je `.env`-bestanden in git! Gebruik altijd een `.env.template` als voorbeeld. 