<?php
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ID aus der URL holen
$firma_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$firma_id) {
    $_SESSION['error_message'] = "Ungültige Firma-ID.";
    header("Location: firmen.php");
    exit();
}

try {
    // Firma laden
    $stmt = $pdo->prepare("SELECT * FROM Firmen WHERE Firma_ID = ?");
    $stmt->execute([$firma_id]);
    $firma = $stmt->fetch();

    if (!$firma) {
        $_SESSION['error_message'] = "Firma nicht gefunden.";
        header("Location: firmen.php");
        exit();
    }
    
    // ZUGRIFFSKONTROLLE!
    pruefe_zugriff_oder_redirect($firma['Erstellt_von'], 'firmen.php');
    
    // Firma löschen
    $stmt_delete = $pdo->prepare("DELETE FROM Firmen WHERE Firma_ID = ?");
    $stmt_delete->execute([$firma_id]);
    
    $_SESSION['success_message'] = "Firma erfolgreich gelöscht!";
    header("Location: firmen.php");
    exit();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Fehler beim Löschen der Firma: " . $e->getMessage();
    header("Location: firmen.php");
    exit();
}
?>
