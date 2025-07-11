# Groepsdetail- en Bewerkpagina Functionaliteit

## Overzicht
De groepsdetail- en bewerkpagina (`group.php`) biedt een complete interface voor het beheren van groepen, hun leden en rechten. Deze functionaliteit is geïntegreerd in de bestaande admin interface.

## Toegang
- **URL**: `group.php?id=GROEP_ID`
- **Rechten**: `manage_groups` vereist
- **Toegankelijk via**: Admin interface → Groepen tab → Klik op groepsnaam of bewerk-knop

## Functionaliteiten

### 1. Groepsgegevens Bewerken
- **Naam wijzigen**: Bewerk de groepsnaam
- **Beschrijving wijzigen**: Bewerk de groepsbeschrijving
- **Metadata**: Toon aanmaak- en wijzigingsdatum
- **Verwijderen**: Verwijder groep (niet mogelijk voor standaardgroepen: admin, user, moderator)

### 2. Ledenbeheer
- **Overzicht**: Toon alle leden van de groep met naam, gebruikersnaam en e-mail
- **Leden toevoegen**: Dropdown met alle gebruikers die nog niet in de groep zitten
- **Leden verwijderen**: Verwijder leden uit de groep met één klik
- **Aantal leden**: Toon het totale aantal leden

### 3. Rechtenbeheer
- **Overzicht**: Toon alle rechten van de groep met naam en beschrijving
- **Rechten toevoegen**: Dropdown met alle rechten die de groep nog niet heeft
- **Rechten verwijderen**: Trek rechten in met één klik
- **Aantal rechten**: Toon het totale aantal rechten

## Backend Methodes (UserManager)

### Nieuwe Methodes
- `getGroupById($groupId)`: Haal groep op basis van ID op
- `getGroupUsers($groupId)`: Haal alle gebruikers van een groep op

### Bestaande Methodes
- `updateGroup($groupId, $name, $description)`: Bewerk groepsgegevens
- `deleteGroup($groupId)`: Verwijder groep
- `addUserToGroup($userId, $groupId)`: Voeg gebruiker toe aan groep
- `removeUserFromGroup($userId, $groupId)`: Verwijder gebruiker uit groep
- `addPermissionToGroup($groupId, $permissionId)`: Voeg recht toe aan groep
- `removePermissionFromGroup($groupId, $permissionId)`: Verwijder recht uit groep

## Integratie

### Admin Interface Verbeteringen
- **Groepenlijst**: Klikbare groepsnamen en bewerk-knoppen
- **Gebruikerslijst**: Klikbare gebruikersnamen en bewerk-knoppen
- **Nieuwe groep**: Modal voor het aanmaken van nieuwe groepen
- **Nieuwe gebruiker**: Modal voor het aanmaken van nieuwe gebruikers
- **Feedback**: Succesmeldingen bij acties

### Navigatie
- **Terug-knop**: Terug naar admin interface groepen tab
- **Directe links**: Van admin interface naar detailpagina's
- **Redirects**: Na aanmaken nieuwe groep/gebruiker naar detailpagina

## Veiligheid

### Rechtencontrole
- Alleen gebruikers met `manage_groups` recht kunnen de pagina gebruiken
- Standaardgroepen kunnen niet verwijderd worden
- Alle invoer wordt gevalideerd en ontsmet

### Validatie
- Groep ID wordt gevalideerd
- Alle formulierdata wordt ontsmet met `Utils::sanitize()`
- Bevestiging vereist bij verwijderen van groepen

## Gebruikerservaring

### Interface
- **Responsive design**: Werkt op desktop en mobiel
- **Bootstrap 5**: Moderne, consistente styling
- **Bootstrap Icons**: Duidelijke iconen voor acties
- **Feedback**: Duidelijke meldingen bij alle acties

### Workflow
1. **Toegang**: Via admin interface → Groepen tab
2. **Bewerken**: Klik op groepsnaam of bewerk-knop
3. **Wijzigingen**: Bewerk gegevens, leden of rechten
4. **Opslaan**: Wijzigingen worden direct toegepast
5. **Terug**: Terug naar overzicht via terug-knop

## Bestanden

### Nieuwe Bestanden
- `public/group.php`: Groepsdetail- en bewerkpagina

### Aangepaste Bestanden
- `public/admin.php`: Verbeterde admin interface met links en modals
- `includes/UserManager.php`: Nieuwe methodes voor groepsbeheer

## Toekomstige Uitbreidingen
- **Bulk acties**: Meerdere gebruikers tegelijk toevoegen/verwijderen
- **Zoeken**: Zoeken in ledenlijst
- **Filters**: Filteren op rechten of gebruikers
- **Audit log**: Logboek van wijzigingen
- **Export**: Export van groepsgegevens 