<?php
// auth_check.php - Prüft ob Benutzer eingeloggt ist
// Diese Datei wird am Anfang jeder geschützten Seite eingebunden

// Session starten, falls noch nicht gestartet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Nicht eingeloggt -> zur Login-Seite weiterleiten
    header("Location: login.php");
    exit;
}

// Optional: Session-Timeout prüfen (z.B. nach 2 Stunden Inaktivität)
$timeout_duration = 7200; // 2 Stunden in Sekunden

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Session abgelaufen
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}

// Letzte Aktivität aktualisieren
$_SESSION['last_activity'] = time();

// Optional: Session-Regeneration für erhöhte Sicherheit (alle 30 Minuten)
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    // Session ID neu generieren
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
?>