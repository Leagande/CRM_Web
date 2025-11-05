-- ===================================================================
-- CRM DATENBANK - VOLLSTÄNDIGE STRUKTUR
-- Datenbank: vertriebscrm2
-- Version: 2.0
-- Erstellt für: VertriebsCRM 2 - Customer Relationship Management System
-- ===================================================================

-- Datenbank erstellen (falls nicht vorhanden)
CREATE DATABASE IF NOT EXISTS vertriebscrm2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vertriebscrm2;

-- ===================================================================
-- TABELLE: Rollen
-- Beschreibung: Speichert Benutzerrollen (Admin, Benutzer, etc.)
-- ===================================================================
CREATE TABLE IF NOT EXISTS Rollen (
    Rolle_ID INT AUTO_INCREMENT PRIMARY KEY,
    Rollenname VARCHAR(50) NOT NULL UNIQUE,
    Beschreibung VARCHAR(255),
    Erstellt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard-Rollen einfügen
INSERT INTO Rollen (Rolle_ID, Rollenname, Beschreibung) VALUES
(1, 'Admin', 'Administrator mit vollen Berechtigungen'),
(2, 'Benutzer', 'Standard-Benutzer mit eingeschränkten Rechten'),
(3, 'Manager', 'Manager mit erweiterten Berechtigungen')
ON DUPLICATE KEY UPDATE Rollenname = VALUES(Rollenname);

-- ===================================================================
-- TABELLE: Benutzer
-- Beschreibung: Speichert alle Benutzer des CRM-Systems
-- ===================================================================
CREATE TABLE IF NOT EXISTS Benutzer (
    Benutzer_ID INT AUTO_INCREMENT PRIMARY KEY,
    Benutzername VARCHAR(50) NOT NULL UNIQUE,
    Passwort_Hash VARCHAR(255) NOT NULL,
    Rolle_ID INT NOT NULL DEFAULT 2,
    Vorname VARCHAR(100),
    Nachname VARCHAR(100),
    Email VARCHAR(255),
    Telefon VARCHAR(50),
    Aktiv TINYINT(1) NOT NULL DEFAULT 1,
    Letzter_Login DATETIME,
    Erstellt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Geaendert_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (Rolle_ID) REFERENCES Rollen(Rolle_ID) ON DELETE RESTRICT,
    INDEX idx_benutzername (Benutzername),
    INDEX idx_rolle (Rolle_ID),
    INDEX idx_aktiv (Aktiv)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard-Admin-Benutzer erstellen
-- Benutzername: admin
-- Passwort: admin123 (WICHTIG: Nach dem ersten Login ändern!)
INSERT INTO Benutzer (Benutzername, Passwort_Hash, Rolle_ID, Vorname, Nachname, Email, Aktiv) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'System', 'Administrator', 'admin@example.com', 1)
ON DUPLICATE KEY UPDATE Benutzername = VALUES(Benutzername);

-- ===================================================================
-- TABELLE: Firmen
-- Beschreibung: Speichert alle Firmen/Kunden
-- ===================================================================
CREATE TABLE IF NOT EXISTS Firmen (
    Firma_ID INT AUTO_INCREMENT PRIMARY KEY,
    Firmenname VARCHAR(255) NOT NULL,
    Straße VARCHAR(255),
    PLZ VARCHAR(10),
    Ort VARCHAR(100) NOT NULL,
    Land VARCHAR(100) NOT NULL DEFAULT 'Deutschland',
    Telefonnummer VARCHAR(50),
    Email VARCHAR(255),
    Website VARCHAR(255),
    Status ENUM('Lead', 'Kunde', 'Partner', 'Kontaktversuch', 'Verloren', 'Archiviert') NOT NULL DEFAULT 'Lead',
    Notizen_Firma TEXT,
    Erstellt_von INT,
    Erstellt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Geaendert_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (Erstellt_von) REFERENCES Benutzer(Benutzer_ID) ON DELETE SET NULL,
    INDEX idx_firmenname (Firmenname),
    INDEX idx_status (Status),
    INDEX idx_ort (Ort),
    INDEX idx_erstellt_von (Erstellt_von),
    FULLTEXT INDEX ft_firmenname (Firmenname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- TABELLE: Ansprechpartner
-- Beschreibung: Speichert Kontaktpersonen für Firmen
-- ===================================================================
CREATE TABLE IF NOT EXISTS Ansprechpartner (
    Ansprechpartner_ID INT AUTO_INCREMENT PRIMARY KEY,
    Firma_ID INT NOT NULL,
    Vorname VARCHAR(100),
    Nachname VARCHAR(100) NOT NULL,
    Email VARCHAR(255),
    Telefon VARCHAR(50),
    Mobil VARCHAR(50),
    Position VARCHAR(100),
    Abteilung VARCHAR(100),
    Notizen TEXT,
    Erstellt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Geaendert_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (Firma_ID) REFERENCES Firmen(Firma_ID) ON DELETE CASCADE,
    INDEX idx_firma (Firma_ID),
    INDEX idx_nachname (Nachname),
    INDEX idx_email (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- TABELLE: Projekte
-- Beschreibung: Speichert Projekte/Aufträge
-- ===================================================================
CREATE TABLE IF NOT EXISTS Projekte (
    Projekt_ID INT AUTO_INCREMENT PRIMARY KEY,
    Projektname VARCHAR(255) NOT NULL,
    Beschreibung TEXT,
    Status ENUM('Planung', 'Aktiv', 'Pausiert', 'Abgeschlossen', 'Abgebrochen') NOT NULL DEFAULT 'Planung',
    Startdatum DATE NOT NULL,
    Enddatum_geplant DATE,
    Enddatum_tatsaechlich DATE,
    Budget DECIMAL(12, 2) DEFAULT 0.00,
    Kosten_aktuell DECIMAL(12, 2) DEFAULT 0.00,
    Notizen_Projekt TEXT,
    Zustaendig_ID INT,
    Erstellt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Geaendert_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (Zustaendig_ID) REFERENCES Benutzer(Benutzer_ID) ON DELETE SET NULL,
    INDEX idx_projektname (Projektname),
    INDEX idx_status (Status),
    INDEX idx_zustaendig (Zustaendig_ID),
    INDEX idx_startdatum (Startdatum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- TABELLE: Aktivitäten
-- Beschreibung: Speichert alle Aktivitäten (Anrufe, Meetings, Aufgaben, etc.)
-- ===================================================================
CREATE TABLE IF NOT EXISTS Aktivitäten (
    Aktivität_ID INT AUTO_INCREMENT PRIMARY KEY,
    Aktivitätstyp ENUM('Anruf', 'E-Mail', 'Meeting', 'Aufgabe', 'Notiz', 'Vertrag') NOT NULL,
    Datum DATETIME NOT NULL,
    Betreff VARCHAR(255) NOT NULL,
    Notiz TEXT,
    Firma_ID INT,
    Projekt_ID INT,
    Ansprechpartner_ID INT,
    Status ENUM('Offen', 'In Bearbeitung', 'Erledigt', 'Abgebrochen') NOT NULL DEFAULT 'Offen',
    Erledigt_Status TINYINT(1) NOT NULL DEFAULT 0,
    Faelligkeitsdatum DATE,
    Prioritaet ENUM('Normal', 'Hoch', 'Dringend') NOT NULL DEFAULT 'Normal',
    Zustaendig_ID INT,
    Erstellt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Geaendert_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (Firma_ID) REFERENCES Firmen(Firma_ID) ON DELETE SET NULL,
    FOREIGN KEY (Projekt_ID) REFERENCES Projekte(Projekt_ID) ON DELETE SET NULL,
    FOREIGN KEY (Ansprechpartner_ID) REFERENCES Ansprechpartner(Ansprechpartner_ID) ON DELETE SET NULL,
    FOREIGN KEY (Zustaendig_ID) REFERENCES Benutzer(Benutzer_ID) ON DELETE SET NULL,
    INDEX idx_aktivitaetstyp (Aktivitätstyp),
    INDEX idx_datum (Datum),
    INDEX idx_status (Status),
    INDEX idx_erledigt (Erledigt_Status),
    INDEX idx_faelligkeit (Faelligkeitsdatum),
    INDEX idx_prioritaet (Prioritaet),
    INDEX idx_zustaendig (Zustaendig_ID),
    INDEX idx_firma (Firma_ID),
    INDEX idx_projekt (Projekt_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- TABELLE: Dokumente (Optional - für zukünftige Erweiterung)
-- Beschreibung: Speichert Dokumente und Dateien
-- ===================================================================
CREATE TABLE IF NOT EXISTS Dokumente (
    Dokument_ID INT AUTO_INCREMENT PRIMARY KEY,
    Dateiname VARCHAR(255) NOT NULL,
    Dateipfad VARCHAR(500) NOT NULL,
    Dateityp VARCHAR(50),
    Dateigroesse INT,
    Beschreibung TEXT,
    Firma_ID INT,
    Projekt_ID INT,
    Hochgeladen_von INT,
    Hochgeladen_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (Firma_ID) REFERENCES Firmen(Firma_ID) ON DELETE CASCADE,
    FOREIGN KEY (Projekt_ID) REFERENCES Projekte(Projekt_ID) ON DELETE CASCADE,
    FOREIGN KEY (Hochgeladen_von) REFERENCES Benutzer(Benutzer_ID) ON DELETE SET NULL,
    INDEX idx_firma (Firma_ID),
    INDEX idx_projekt (Projekt_ID),
    INDEX idx_dateityp (Dateityp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- BEISPIELDATEN FÜR TESTZWECKE (Optional)
-- ===================================================================

-- Beispiel-Benutzer erstellen (Passwort: benutzer123)
INSERT INTO Benutzer (Benutzername, Passwort_Hash, Rolle_ID, Vorname, Nachname, Email, Aktiv) VALUES
('testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'Max', 'Mustermann', 'max.mustermann@example.com', 1)
ON DUPLICATE KEY UPDATE Benutzername = VALUES(Benutzername);

-- Beispiel-Firma
INSERT INTO Firmen (Firmenname, Straße, PLZ, Ort, Land, Telefonnummer, Status, Notizen_Firma, Erstellt_von) VALUES
('Musterfirma GmbH', 'Musterstraße 123', '10115', 'Berlin', 'Deutschland', '+49 30 12345678', 'Kunde', 'Wichtiger Kunde seit 2023', 1)
ON DUPLICATE KEY UPDATE Firmenname = VALUES(Firmenname);

-- Beispiel-Projekt
INSERT INTO Projekte (Projektname, Status, Startdatum, Enddatum_geplant, Budget, Notizen_Projekt, Zustaendig_ID) VALUES
('Website-Redesign', 'Aktiv', '2025-01-01', '2025-06-30', 15000.00, 'Komplette Überarbeitung der Firmenwebsite', 1)
ON DUPLICATE KEY UPDATE Projektname = VALUES(Projektname);

-- ===================================================================
-- VIEWS (Nützliche Ansichten für Berichte)
-- ===================================================================

-- View: Offene Aktivitäten mit allen Details
CREATE OR REPLACE VIEW v_offene_aktivitaeten AS
SELECT
    a.Aktivität_ID,
    a.Aktivitätstyp,
    a.Datum,
    a.Betreff,
    a.Notiz,
    a.Status,
    a.Faelligkeitsdatum,
    a.Prioritaet,
    f.Firmenname,
    p.Projektname,
    CONCAT(ap.Vorname, ' ', ap.Nachname) as Ansprechpartner_Name,
    CONCAT(b.Vorname, ' ', b.Nachname) as Zustaendig_Name,
    a.Erstellt_am
FROM Aktivitäten a
LEFT JOIN Firmen f ON a.Firma_ID = f.Firma_ID
LEFT JOIN Projekte p ON a.Projekt_ID = p.Projekt_ID
LEFT JOIN Ansprechpartner ap ON a.Ansprechpartner_ID = ap.Ansprechpartner_ID
LEFT JOIN Benutzer b ON a.Zustaendig_ID = b.Benutzer_ID
WHERE a.Erledigt_Status = 0
ORDER BY a.Faelligkeitsdatum ASC, a.Prioritaet DESC;

-- View: Firmen-Übersicht mit Ansprechpartner-Anzahl
CREATE OR REPLACE VIEW v_firmen_uebersicht AS
SELECT
    f.Firma_ID,
    f.Firmenname,
    f.Ort,
    f.Status,
    COUNT(DISTINCT ap.Ansprechpartner_ID) as Anzahl_Ansprechpartner,
    COUNT(DISTINCT a.Aktivität_ID) as Anzahl_Aktivitaeten,
    CONCAT(b.Vorname, ' ', b.Nachname) as Erstellt_von_Name,
    f.Erstellt_am
FROM Firmen f
LEFT JOIN Ansprechpartner ap ON f.Firma_ID = ap.Firma_ID
LEFT JOIN Aktivitäten a ON f.Firma_ID = a.Firma_ID
LEFT JOIN Benutzer b ON f.Erstellt_von = b.Benutzer_ID
GROUP BY f.Firma_ID;

-- View: Projekt-Übersicht
CREATE OR REPLACE VIEW v_projekte_uebersicht AS
SELECT
    p.Projekt_ID,
    p.Projektname,
    p.Status,
    p.Startdatum,
    p.Enddatum_geplant,
    p.Budget,
    p.Kosten_aktuell,
    (p.Budget - p.Kosten_aktuell) as Budget_Verbleibend,
    COUNT(a.Aktivität_ID) as Anzahl_Aktivitaeten,
    CONCAT(b.Vorname, ' ', b.Nachname) as Zustaendig_Name,
    DATEDIFF(p.Enddatum_geplant, CURDATE()) as Tage_bis_Ende
FROM Projekte p
LEFT JOIN Aktivitäten a ON p.Projekt_ID = a.Projekt_ID
LEFT JOIN Benutzer b ON p.Zustaendig_ID = b.Benutzer_ID
GROUP BY p.Projekt_ID;

-- ===================================================================
-- FERTIG!
-- ===================================================================

-- Zusammenfassung anzeigen
SELECT
    'VertriebsCRM 2 - Datenbank erfolgreich erstellt!' as Status,
    (SELECT COUNT(*) FROM Rollen) as Anzahl_Rollen,
    (SELECT COUNT(*) FROM Benutzer) as Anzahl_Benutzer,
    (SELECT COUNT(*) FROM Firmen) as Anzahl_Firmen,
    (SELECT COUNT(*) FROM Projekte) as Anzahl_Projekte,
    (SELECT COUNT(*) FROM Aktivitäten) as Anzahl_Aktivitaeten;

-- Admin-Zugangsdaten zur Erinnerung
SELECT
    '=== WICHTIG: ADMIN-ZUGANGSDATEN ===' as Info,
    'Benutzername: admin' as Zugang1,
    'Passwort: admin123' as Zugang2,
    'BITTE NACH DEM ERSTEN LOGIN ÄNDERN!' as Hinweis;
