<?php
// db_verbindung.php
// Diese Datei stellt die Verbindung zur MySQL-Datenbank her.
// Sie wird von anderen PHP-Dateien per 'require_once' eingebunden.

// --- Konfiguration der Zugangsdaten ---
$host = 'localhost';      // Der Server, auf dem die Datenbank läuft (bei XAMPP immer 'localhost')
$db   = 'vertriebscrm2';  // Der Name der Datenbank (VertriebsCRM 2)
$user = 'root';           // Der Standard-Benutzername für die Datenbank in XAMPP
$pass = '';               // Das Standard-Passwort für XAMPP ist leer
$charset = 'utf8mb4';     // Stellt sicher, dass Umlaute (ä, ö, ü) und Sonderzeichen korrekt behandelt werden

// --- DSN (Data Source Name) erstellen ---
// Das ist eine Zeichenkette, die alle Informationen für die Verbindung enthält.
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// --- Optionen für die PDO-Verbindung ---
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Wirft Fehler als "Exceptions", was sauberer ist
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Holt Daten als assoziatives Array (z.B. ['name' => 'Testfirma'])
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Nutzt "echte" Prepared Statements für mehr Sicherheit
];

// --- Die eigentliche Verbindung herstellen ---
try {
    // Versucht, ein neues PDO-Objekt (die Datenbankverbindung) zu erstellen
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Wenn die Verbindung fehlschlägt (z.B. falsches Passwort), wird eine Fehlermeldung ausgegeben und das Skript beendet.
    // Das ist wichtig, damit keine sensiblen Daten preisgegeben werden.
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Das $pdo-Objekt ist jetzt unsere aktive Verbindung zur Datenbank.
// Jede Datei, die 'db_verbindung.php' einbindet, kann $pdo benutzen.
?>

