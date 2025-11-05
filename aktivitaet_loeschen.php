<?php
// aktivitaet_loeschen.php
session_start(); // Session starten, um eine Nachricht setzen zu können
require_once 'db_verbindung.php';

$aktivitaet_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($aktivitaet_id) {
    try {
        // Betreff für die Nachricht holen
        $sql_name = "SELECT Betreff FROM Aktivitäten WHERE Aktivität_ID = :id";
        $stmt_name = $pdo->prepare($sql_name);
        $stmt_name->execute([':id' => $aktivitaet_id]);
        $aktivitaet = $stmt_name->fetch();
        $betreff = $aktivitaet ? $aktivitaet['Betreff'] : 'Unbekannte Aktivität';

        // Aktivität löschen
        $sql_delete = "DELETE FROM Aktivitäten WHERE Aktivität_ID = :id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([':id' => $aktivitaet_id]);

        // Setze die Erfolgsmeldung
        $_SESSION['success_message'] = "Aktivität \"$betreff\" wurde erfolgreich gelöscht!";

    } catch (\PDOException $e) {
        die("Fehler beim Löschen der Aktivität: " . $e->getMessage());
    }
}

// Leite zum Dashboard weiter
header("Location: index.php");
exit();

