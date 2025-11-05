<?php
// projekt_loeschen.php
require_once 'db_verbindung.php'; // $pdo ist hier verfügbar
session_start();

// 1. ID aus der URL holen und validieren
$projekt_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$projekt_id) {
    $_SESSION['error_message'] = "Ungültige Projekt-ID.";
    header("Location: projekte.php");
    exit();
}

try {
    // 2. Transaktion starten
    // Das stellt sicher, dass alle drei Löschvorgänge als eine Einheit behandelt werden.
    $pdo->beginTransaction();

    // 3. ZUERST alle zugehörigen Aktivitäten löschen
    // (Annahme: Tabelle heißt 'Aktivitäten', Spalte 'Projekt_ID')
    $sql_akt = "DELETE FROM Aktivitäten WHERE Projekt_ID = ?";
    $stmt_akt = $pdo->prepare($sql_akt);
    $stmt_akt->execute([$projekt_id]);
    
    // 4. DANN alle Firmen-Zuordnungen löschen
    // (Annahme: Tabelle 'Projekt_Firmen_Zuordnung', Spalte 'Projekt_ID')
    $sql_zuordnung = "DELETE FROM Projekt_Firmen_Zuordnung WHERE Projekt_ID = ?";
    $stmt_zuordnung = $pdo->prepare($sql_zuordnung);
    $stmt_zuordnung->execute([$projekt_id]);
    
    // 5. ZULETZT das Projekt selbst löschen
    // (Annahme: Tabelle 'Projekte', Spalte 'Projekt_ID')
    $sql_proj = "DELETE FROM Projekte WHERE Projekt_ID = ?";
    $stmt_proj = $pdo->prepare($sql_proj);
    $stmt_proj->execute([$projekt_id]);

    // 6. Wenn alles fehlerfrei war, Transaktion bestätigen
    $pdo->commit();

    $_SESSION['success_message'] = "Projekt und alle zugehörigen Aktivitäten wurden erfolgreich gelöscht.";

} catch (PDOException $e) {
    // 7. Wenn irgendein Schritt fehlgeschlagen ist, ALLES rückgängig machen
    $pdo->rollBack();
    $_SESSION['error_message'] = "Fehler beim Löschen des Projekts: " . $e->getMessage();
}

// Zurück zur Projektliste
header("Location: projekte.php");
exit();
?>