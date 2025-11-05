# MeinCRM - Customer Relationship Management System

Ein vollständiges CRM-System für kleine und mittelständische Unternehmen, entwickelt mit PHP und MySQL.

## Funktionen

- **Firmenverwaltung**: Verwaltung von Kunden, Leads und Partnern
- **Projektmanagement**: Projektverfolgung mit Budget- und Statusverwaltung
- **Aktivitätenverfolgung**: Aufgaben, Anrufe, Meetings und E-Mails organisieren
- **Ansprechpartner**: Kontaktpersonen für Firmen verwalten
- **Benutzerverwaltung**: Mehrbenutzersystem mit Rollenberechtigungen
- **Dashboard**: Übersicht über offene Aktivitäten und KPIs
- **Such- und Filterfunktionen**: Schnelles Finden von Daten
- **CSV Import/Export**: Daten importieren und exportieren
- **Berechtigungssystem**: Admin und Benutzer-Rollen mit Zugriffskontrolle

## Systemanforderungen

- **Webserver**: Apache 2.4+ oder Nginx
- **PHP**: Version 7.4 oder höher (empfohlen: PHP 8.0+)
- **MySQL**: Version 5.7+ oder MariaDB 10.3+
- **PHP-Erweiterungen**:
  - PDO
  - PDO_MySQL
  - mbstring
  - JSON

## Installation

### 1. XAMPP/WAMP/MAMP installieren (für lokale Entwicklung)

Für Windows-Benutzer empfehlen wir [XAMPP](https://www.apachefriends.org/de/index.html):
- XAMPP herunterladen und installieren
- Apache und MySQL starten

### 2. Dateien kopieren

```bash
# Repository klonen oder Dateien in das htdocs-Verzeichnis kopieren
# Bei XAMPP: C:\xampp\htdocs\CRM_Web\
# Bei MAMP: /Applications/MAMP/htdocs/CRM_Web/
```

### 3. Datenbank einrichten

#### Option A: Mit phpMyAdmin (einfach)

1. Öffnen Sie phpMyAdmin: `http://localhost/phpmyadmin`
2. Klicken Sie auf "Importieren"
3. Wählen Sie die Datei `database_setup.sql` aus
4. Klicken Sie auf "OK"

#### Option B: Mit MySQL Kommandozeile

```bash
# MySQL-Kommandozeile öffnen
mysql -u root -p

# SQL-Datei importieren
source /pfad/zu/database_setup.sql;

# Oder direkt:
mysql -u root -p < database_setup.sql
```

### 4. Datenbankverbindung konfigurieren

Die Datenbankverbindung ist bereits in `db_verbindung.php` konfiguriert:

```php
$host = 'localhost';      // MySQL Server
$db   = 'vertriebs_crm'; // Datenbankname
$user = 'root';           // MySQL Benutzername
$pass = '';               // MySQL Passwort (bei XAMPP standardmäßig leer)
```

**Wichtig**: Für Produktionsumgebungen ändern Sie diese Zugangsdaten!

### 5. Anwendung aufrufen

Öffnen Sie in Ihrem Browser:
```
http://localhost/CRM_Web/
```

### 6. Erster Login

Verwenden Sie die Standard-Admin-Zugangsdaten:
- **Benutzername**: `admin`
- **Passwort**: `admin123`

**WICHTIG**: Ändern Sie das Passwort nach dem ersten Login!

## Datenbankstruktur

Das System verwendet folgende Haupttabellen:

- **Benutzer**: Systembenutzer mit Rollen
- **Rollen**: Berechtigungsrollen (Admin, Benutzer, Manager)
- **Firmen**: Kunden und Leads
- **Ansprechpartner**: Kontaktpersonen bei Firmen
- **Projekte**: Projekte und Aufträge
- **Aktivitäten**: Aufgaben, Anrufe, Meetings, etc.
- **Dokumente**: Datei-Upload (optional)

Detaillierte Struktur siehe `database_setup.sql`

## Benutzerrollen

### Administrator (Rolle_ID = 1)
- Vollständiger Zugriff auf alle Daten
- Benutzerverwaltung
- Kann als andere Benutzer ansehen
- Systemeinstellungen

### Benutzer (Rolle_ID = 2)
- Zugriff nur auf eigene Datensätze
- Firmen und Projekte erstellen
- Aktivitäten verwalten

### Manager (Rolle_ID = 3)
- Erweiterte Berechtigungen
- Team-Übersicht

## Konfiguration

### Datenbankverbindung anpassen

Bearbeiten Sie `db_verbindung.php`:

```php
$host = 'ihr-mysql-server';
$db   = 'ihre-datenbank';
$user = 'ihr-benutzer';
$pass = 'ihr-passwort';
```

### Zeitzone einstellen

In `header.php` oder `db_verbindung.php`:

```php
date_default_timezone_set('Europe/Berlin');
```

## Sicherheitshinweise

**Für Produktionsumgebungen:**

1. **Datenbankpasswort ändern**
   ```sql
   ALTER USER 'root'@'localhost' IDENTIFIED BY 'sicheres_passwort';
   ```

2. **Admin-Passwort sofort ändern**
   - Nach dem ersten Login → Benutzerverwaltung → Passwort ändern

3. **db_verbindung.php schützen**
   - Dateiberechtigungen: `chmod 600 db_verbindung.php`
   - Nie in öffentliche Verzeichnisse hochladen

4. **HTTPS verwenden**
   - SSL-Zertifikat installieren
   - Alle Verbindungen verschlüsseln

5. **Error Reporting deaktivieren**
   In Produktionsumgebungen in `index.php` ändern:
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

6. **Regelmäßige Backups**
   ```bash
   mysqldump -u root -p vertriebs_crm > backup_$(date +%Y%m%d).sql
   ```

## Verwendung

### Neue Firma anlegen

1. Navigation → "Firmen" → "Neue Firma"
2. Firmendaten eingeben
3. Status auswählen (Lead, Kunde, Partner, etc.)
4. Speichern

### Projekt erstellen

1. Navigation → "Projekte" → "Neues Projekt"
2. Projektname und Details eingeben
3. Budget und Zeitraum festlegen
4. Projekt wird automatisch dem aktuellen Benutzer zugewiesen

### Aktivität anlegen

1. Dashboard → "Neue Aktivität"
2. Aktivitätstyp wählen (Anruf, Meeting, Aufgabe, etc.)
3. Firma und Projekt verknüpfen (optional)
4. Fälligkeit und Priorität festlegen
5. Speichern

### Benutzer verwalten (nur Admin)

1. Navigation → "Benutzerverwaltung"
2. "Neuer Benutzer" → Daten eingeben
3. Rolle zuweisen
4. Speichern

## Troubleshooting

### Problem: "Access denied for user 'root'@'localhost'"

**Lösung**: MySQL-Passwort in `db_verbindung.php` überprüfen

### Problem: "Database does not exist"

**Lösung**: `database_setup.sql` importieren

### Problem: Encoding-Probleme (Umlaute werden nicht korrekt angezeigt)

**Lösung**:
1. Datenbankzeichensatz überprüfen: `utf8mb4`
2. In `db_verbindung.php` ist `charset = 'utf8mb4'` gesetzt

### Problem: Session-Fehler

**Lösung**:
```php
// In php.ini prüfen:
session.save_path = "/tmp"
// Verzeichnis muss beschreibbar sein
```

### Problem: "Headers already sent"

**Lösung**: Keine Ausgaben vor `header()` Aufrufen, keine Leerzeichen vor `<?php`

## Backup und Wiederherstellung

### Backup erstellen

```bash
# Komplettes Backup
mysqldump -u root -p vertriebs_crm > crm_backup.sql

# Mit Zeitstempel
mysqldump -u root -p vertriebs_crm > crm_backup_$(date +%Y%m%d_%H%M%S).sql

# Nur Struktur
mysqldump -u root -p --no-data vertriebs_crm > crm_structure.sql

# Nur Daten
mysqldump -u root -p --no-create-info vertriebs_crm > crm_data.sql
```

### Backup wiederherstellen

```bash
mysql -u root -p vertriebs_crm < crm_backup.sql
```

## Lizenz

Dieses Projekt ist für den internen Gebrauch bestimmt.

## Support

Bei Fragen oder Problemen:
- Überprüfen Sie die Troubleshooting-Sektion
- Prüfen Sie die PHP-Error-Logs
- Konsultieren Sie die Datenbankstruktur in `database_setup.sql`

## Version

- **Version**: 1.0
- **Letztes Update**: 2025-11-05
- **Entwickelt für**: PHP 7.4+, MySQL 5.7+

## Changelog

### Version 1.0 (2025-11-05)
- Initiale Version
- Firmen-, Projekt- und Aktivitätenverwaltung
- Benutzerverwaltung mit Rollen
- Dashboard mit KPIs
- CSV Import/Export
- Vollständige Datenbankstruktur
