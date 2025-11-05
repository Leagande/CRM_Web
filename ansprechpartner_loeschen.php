<?php
// ansprechpartner_loeschen.php
session_start();
require_once 'db_verbindung.php';
// Modus holen
$modus = $_GET['modus'] ?? 'aussendienst';

// --- NEU: Modus-Prüfung ---
if ($modus !== 'aussendienst') {
    $_SESSION['error_message'] = "Diese Funktion ist nur im Außendienst-Modus verfügbar.";
    $redirect_url = 'start.php?modus=' . urlencode($modus);
    header("Location: $redirect_url");
    exit();
}
// --- Ende Modus-Prüfung ---


$ansprechpartner_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$firma_id = filter_input(INPUT_GET, 'firma_id', FILTER_VALIDATE_INT);

if ($ansprechpartner_id && $firma_id) {
    try {
        // Name für die Nachricht holen
        $sql_name = "SELECT Vorname, Nachname FROM Ansprechpartner WHERE Ansprechpartner_ID = :id";
        $stmt_name = $pdo->prepare($sql_name);
        $stmt_name->execute([':id' => $ansprechpartner_id]);
        $kontakt = $stmt_name->fetch();
        $kontaktname = $kontakt ? trim($kontakt['Vorname'] . ' ' . $kontakt['Nachname']) : 'Unbekannter Kontakt';

        // Kontakt löschen
        $sql_delete = "DELETE FROM Ansprechpartner WHERE Ansprechpartner_ID = :id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([':id' => $ansprechpartner_id]);

        $_SESSION['success_message'] = "Ansprechpartner \"$kontaktname\" wurde erfolgreich gelöscht!";

    } catch (\PDOException $e) {
        die("Fehler beim Löschen des Ansprechpartners: " . $e->getMessage());
    }
}

// Leite zur Ansprechpartner-Übersicht dieser Firma weiter
// Modus an Redirect anhängen
$redirect_url = 'ansprechpartner.php?firma_id=' . $firma_id . '&modus=' . urlencode($modus);
header("Location: $redirect_url");
exit();
?>

