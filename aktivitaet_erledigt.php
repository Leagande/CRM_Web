<?php
// aktivitaet_erledigt.php
session_start();
require_once 'db_verbindung.php';

$aktivitaet_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($aktivitaet_id) {
    try {
        // SQL-Anweisung, um den Erledigt_Status auf 1 zu setzen
        $sql = "UPDATE Aktivitäten SET Erledigt_Status = 1 WHERE Aktivität_ID = :id";
        $statement = $pdo->prepare($sql);
        $statement->execute([':id' => $aktivitaet_id]);

        // Optional: Erfolgsmeldung setzen
        // $_SESSION['success_message'] = "Aktivität als erledigt markiert!";

    } catch (\PDOException $e) {
        die("Fehler beim Markieren als erledigt: " . $e->getMessage());
    }
}

// Leite zurück zum Dashboard
header("Location: index.php");
exit();
?>
