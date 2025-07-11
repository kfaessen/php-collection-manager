# 🚀 Deployment Handleiding - GitHub Actions + SSH + Environments

Deze handleiding beschrijft hoe je de PHP Collectiebeheer-app veilig en professioneel uitrolt naar je server(s) via GitHub Actions, met gebruik van environments en SSH.

## 📋 Vereisten

- GitHub repository (publiek of privé)
- Server(s) met SSH-toegang (bijv. OVH, TransIP, eigen VPS)
- Domein/subdomeinen voor elke omgeving
- Toegang tot GitHub repository settings

## 🌳 Branches & Environments

Voor elke omgeving gebruik je een aparte branch én een aparte GitHub environment:

| Branch | Environment   | Server directory         |
|--------|--------------|-------------------------|
| dev    | development  | /var/www/dev            |
| tst    | test         | /var/www/tst            |
| acc    | acceptance   | /var/www/acc            |
| main   | production   | /var/www/prd            |

## 🛠️ Deployment via GitHub Actions

1. **Elke push naar een branch triggert een workflow.**
2. **De workflow logt in via SSH op de juiste server en directory.**
3. **De code wordt uitgerold, afhankelijk van de environment.**
4. **Secrets (zoals SSH keys) worden veilig beheerd in GitHub.**

## 🔑 SSH Key Setup

1. **Genereer een SSH keypair op je eigen machine:**
   ```bash
   ssh-keygen -t ed25519 -C "github-actions@uwdomein.nl" -f ~/.ssh/github_actions
   # Of gebruik -t rsa -b 4096 voor bredere compatibiliteit
   ```
2. **Voeg de public key toe aan de `~/.ssh/authorized_keys` van de juiste gebruiker op je server(s):**
   ```bash
   cat ~/.ssh/github_actions.pub | ssh gebruiker@server 'cat >> ~/.ssh/authorized_keys'
   ```
3. **Voeg de private key toe als GitHub Secret:**
   - Ga naar je repository → Settings → Secrets and variables → Actions
   - Voeg toe als `SSH_PRIVATE_KEY`
4. **Voeg eventueel extra secrets toe:**
   - `SSH_USER` (gebruikersnaam op de server)
   - `SSH_HOST` (serveradres per environment)
   - `DEPLOY_PATH` (directory per environment)

## 🗂️ GitHub Environments instellen

1. Ga naar je repository → Settings → Environments
2. Maak een environment aan voor elke omgeving: `development`, `test`, `acceptance`, `production`
3. Koppel per environment de juiste secrets (zie hierboven)
4. (Optioneel) Stel deployment approvals in voor productie

## 📝 Voorbeeld workflow (.github/workflows/deploy.yml)

```yaml
name: Deploy via SSH

on:
  push:
    branches:
      - dev
      - tst
      - acc
      - main

jobs:
  deploy:
    runs-on: ubuntu-latest
    environment:
      name: ${{ github.ref_name == 'main' && 'production' || github.ref_name == 'acc' && 'acceptance' || github.ref_name == 'tst' && 'test' || 'development' }}
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Set up SSH key
        uses: webfactory/ssh-agent@v0.8.0
        with:
          ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}

      - name: Deploy via SSH
        run: |
          ssh -o StrictHostKeyChecking=no ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }} "cd ${{ secrets.DEPLOY_PATH }} && git pull origin ${{ github.ref_name }} && composer install --no-dev --optimize-autoloader"
```

> **Let op:** Pas de SSH commando's aan naar jouw situatie. Je kunt hier ook rsync, build scripts, of andere deployment tools gebruiken.

## 🗂️ .env per omgeving

Elke omgeving heeft een eigen `.env`-bestand op de server. Gebruik `.env.template` als basis. Zet deze NIET in git.

## 🚀 Deployment flow

- **Push naar branch** → GitHub Actions workflow start → Code wordt via SSH uitgerold naar de juiste server/directory.
- **Environments** zorgen voor veilige secrets en (optioneel) approvals.

## 🔍 Monitoring & Rollback

- Bekijk deployment logs in GitHub Actions (tab Actions).
- Rollback? Herstel een vorige commit en push opnieuw, of voer handmatig een rollback uit op de server.

## 📞 Support

- Problemen met deployment? Check de logs in GitHub Actions en op de server.
- Problemen met de app? Controleer het `.env`-bestand en de bestandspermissies.

---

**Let op:** commit nooit je `.env`-bestanden in git! Gebruik altijd een `.env.template` als voorbeeld. 