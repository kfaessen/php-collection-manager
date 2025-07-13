# Veilige Database Migratie

## Overzicht

Dit document beschrijft hoe de PHP Collection Manager veilige database migraties uitvoert zonder dataverlies.

## Probleem

De originele implementatie had een destructieve `dropAndRecreateCollectionItemsTable()` methode die de hele `collection_items` tabel verwijderde en opnieuw aanmaakte, wat tot permanent dataverlies leidde.

## Oplossing

### 1. Migratie Systeem

Het systeem gebruikt nu een versie-gebaseerd migratie systeem:

- **Database versie tracking**: De `database_migrations` tabel houdt bij welke migraties zijn uitgevoerd
- **Incrementele updates**: Migraties voegen alleen ontbrekende kolommen en indexen toe
- **Non-destructieve operaties**: Gebruikt `ADD COLUMN IF NOT EXISTS` en `CREATE INDEX IF NOT EXISTS`

### 2. Veilige Kolom Toevoeging

Voor de `user_id` kolom (die een NOT NULL constraint heeft) is speciale logica geïmplementeerd:

```php
private static function safelyAddUserIdColumn($tableName) 
{
    // Check if table has data
    $itemCount = getItemCount($tableName);
    
    if ($itemCount > 0) {
        // Table has data - assign to admin user
        $adminUserId = getOrCreateAdminUser();
        addColumnWithDefault($tableName, 'user_id', $adminUserId);
        updateExistingRecords($tableName, $adminUserId);
    } else {
        // Empty table - safe to add column
        addColumn($tableName, 'user_id');
    }
}
```

### 3. Migratie Stappen

1. **Versie Check**: Controleer huidige database versie
2. **Veilige Updates**: Voeg ontbrekende kolommen toe met `IF NOT EXISTS`
3. **Data Preservatie**: Behoud bestaande data tijdens structuurwijzigingen
4. **Foreign Key Handling**: Voeg constraints toe na kolom toevoeging
5. **Index Optimalisatie**: Voeg indexen toe voor performance

### 4. Gebruikte Technieken

#### Non-destructieve SQL Statements
```sql
-- Veilig kolommen toevoegen
ALTER TABLE collection_items ADD COLUMN IF NOT EXISTS user_id INT NOT NULL DEFAULT 1

-- Veilig indexen toevoegen  
CREATE INDEX IF NOT EXISTS idx_user_id ON collection_items (user_id)

-- Veilig foreign keys toevoegen
ALTER TABLE collection_items ADD CONSTRAINT fk_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```

#### Transactionele Migraties
```php
private static function executeMigration($version, $migration) 
{
    self::getConnection()->beginTransaction();
    try {
        // Execute migration steps
        foreach ($migration['sql'] as $sql) {
            self::query($sql);
        }
        // Record migration
        self::recordMigration($version, $migration['name']);
        self::getConnection()->commit();
    } catch (\Exception $e) {
        self::getConnection()->rollBack();
        throw $e;
    }
}
```

### 5. Migratie Geschiedenis

| Versie | Beschrijving | Veiligheid |
|--------|--------------|------------|
| 1 | Initiële database setup | ✅ Veilig |
| 2 | Collection items tabel toevoegen | ✅ Veilig |
| 3 | Ontbrekende kolommen toevoegen | ✅ Veilig |
| 4 | User_id kolom veilig toevoegen | ✅ Veilig |

### 6. Bestanden

- `includes/Database.php`: Hoofd migratie logica
- `update_database.php`: Veilige database update script
- `setup_database.php`: Initiële database setup
- `run_migrations.php`: Migratie uitvoering

### 7. Gebruik

```bash
# Veilige database update
php update_database.php

# Migratie uitvoering
php run_migrations.php

# Database status check
php check_db_status.php
```

### 8. Voordelen

- ✅ **Geen dataverlies**: Bestaande data wordt altijd behouden
- ✅ **Rollback mogelijk**: Migraties kunnen ongedaan worden gemaakt
- ✅ **Incrementeel**: Alleen wijzigingen worden toegepast
- ✅ **Veilig**: Gebruikt transactionele operaties
- ✅ **Traceerbaar**: Migratie geschiedenis wordt bijgehouden

### 9. Best Practices

1. **Altijd backup maken** voor productie migraties
2. **Test migraties** in development omgeving
3. **Controleer data integriteit** na migraties
4. **Documenteer wijzigingen** in migratie beschrijvingen
5. **Gebruik transactionele operaties** voor complexe migraties

## Conclusie

Het nieuwe migratie systeem elimineert het risico op dataverlies door destructieve database operaties te vervangen met veilige, incrementele updates. Alle bestaande functionaliteit wordt behouden terwijl de database structuur veilig wordt bijgewerkt. 