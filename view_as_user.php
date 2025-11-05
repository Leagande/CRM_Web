<?php
/**
 * view_as_user.php - Aktiviert/Deaktiviert "Als Benutzer ansehen"-Modus
 * Nur für Admins!
 */

require_once 'db_verbindung.php';
require_once 'berechtigungen.php';

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nur Admin darf das
nur_admin_zugriff('index.php');

// Aktion bestimmen
$action = $_GET['action'] ?? '';

if ($action === 'set') {
    // Als Benutzer ansehen aktivieren
    $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    
    if (!$user_id) {
        $_SESSION['error_message'] = "Ungültige Benutzer-ID.";
        header("Location: benutzerverwaltung.php");
        exit();
    }
    
    // Prüfen ob Benutzer existiert
    try {
        $stmt = $pdo->prepare("SELECT Benutzername FROM Benutzer WHERE Benutzer_ID = ?");
        $stmt->execute([$user_id]);
        $username = $stmt->fetchColumn();
        
        if (!$username) {
            $_SESSION['error_message'] = "Benutzer nicht gefunden.";
            header("Location: benutzerverwaltung.php");
            exit();
        }
        
        // View-Modus aktivieren
        set_view_as_user($user_id);
        $_SESSION['success_message'] = "Sie sehen jetzt die Daten von: " . htmlspecialchars($username);
        
        // Zur Startseite weiterleiten
        header("Location: start.php");
        exit();
        
    } catch (PDOException $e) {
        error_log("Fehler in view_as_user.php: " . $e->getMessage());
        $_SESSION['error_message'] = "Datenbankfehler.";
        header("Location: benutzerverwaltung.php");
        exit();
    }
    
} elseif ($action === 'clear') {
    // View-Modus beenden
    clear_view_as_user();
    $_SESSION['success_message'] = "Sie sehen jetzt wieder Ihre eigenen Daten.";
    header("Location: start.php");
    exit();
    
} else {
    // Ungültige Aktion
    $_SESSION['error_message'] = "Ungültige Aktion.";
    header("Location: benutzerverwaltung.php");
    exit();
}
?>
