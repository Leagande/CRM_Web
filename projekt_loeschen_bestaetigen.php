<?php
include 'header.php'; // $pdo ist hier verfügbar

// 1. ID aus der URL holen und validieren
$projekt_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$projekt_id) {
    $_SESSION['error_message'] = "Ungültige Projekt-ID.";
    header("Location: projekte.php");
    exit();
}

// 2. Projekt-Namen holen, um ihn anzuzeigen
try {
    $stmt = $pdo->prepare("SELECT Projektname FROM Projekte WHERE Projekt_ID = ?");
    $stmt->execute([$projekt_id]);
    $projektname = $stmt->fetchColumn();

    if (!$projektname) {
        $_SESSION['error_message'] = "Projekt nicht gefunden.";
        header("Location: projekte.php");
        exit();
    }
} catch (PDOException $e) {
    die("Fehler beim Laden der Projektdaten: " . $e->getMessage());
}

$page_title = "Löschen bestätigen";
?>

<div class="container">
    <div class="card card-full-width" style="max-width: 800px; margin: 2rem auto;">
        <h1 class="card-title" style="color: #D9463A; text-align: center;">
            <i class="fas fa-exclamation-triangle"></i>
            Aktion bestätigen
        </h1>
        
        <p style="text-align: center; font-size: 1.1rem; line-height: 1.6;">
            Sind Sie sicher, dass Sie das Projekt "<strong><?php echo htmlspecialchars($projektname); ?></strong>" endgültig löschen möchten?
        </p>
        <p style="text-align: center; color: var(--text-color-light);">
            Diese Aktion kann nicht rückgängig gemacht werden. Alle zugehörigen Aktivitäten und Firmen-Zuordnungen werden ebenfalls dauerhaft entfernt (wie in Schritt 2 implementiert).
        </p>

        <hr>

        <h2 class="card-subtitle" style="text-align: center;">Optional: Zusammenfassung herunterladen</h2>
        <p style="text-align: center; color: var(--text-color-light);">
            Laden Sie eine CSV-Zusammenfassung aller Firmen und Umsätze herunter, die zu diesem Projekt gehören, *bevor* Sie es löschen.
        </p>
        
        <a href="csv_export_projekt.php?id=<?php echo $projekt_id; ?>" class="btn btn-secondary" style="display: block; width: 80%; margin: 0.5rem auto; text-align: center;">
            <i class="fas fa-download"></i>
            Zusammenfassung herunterladen (CSV)
        </a>

        <hr style="margin-top: 2rem;">

        <div class="action-buttons" style="justify-content: center; gap: 1rem; margin-top: 1rem;">
            
             <a href="projekte.php" class="btn btn-secondary">
                 <i class="fas fa-times"></i>
                Abbrechen
            </a>
            
            <a href="projekt_loeschen.php?id=<?php echo $projekt_id; ?>" class="btn btn-danger" onclick="return confirm('Letzte Warnung: Wirklich endgültig löschen?');">
                <i class="fas fa-trash"></i>
                Ja, jetzt endgültig löschen
            </a>
        </div>
        
    </div>
</div>

<style>
.btn-danger {
    background: #D9463A; /* Rot */
    color: #fff;
    box-shadow: 0 4px 6px rgba(217, 70, 58, 0.3);
}
.btn-danger:hover {
    background: #b92c1f;
    box-shadow: 0 6px 20px rgba(217, 70, 58, 0.4);
}
</style>

<?php include 'footer.php'; ?>