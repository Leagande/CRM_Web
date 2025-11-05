<?php
// NEU: Logik für den Vorlagen-Download
// Wenn ?vorlage=1 an die URL angehängt wird, wird diese Logik ausgeführt
if (isset($_GET['vorlage'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="firmen_import_vorlage.csv"');
    $output = fopen('php://output', 'w');
    
    // Das sind die 8 Spalten, die der Import-Code erwartet
    $spalten = [
        'Firmenname', // A
        'Straße',     // B (mit ß)
        'PLZ',        // C
        'Telefonnummer', // D
        'Ort',        // E
        'Land',       // F
        'Status',     // G
        'Notizen_Firma' // H
    ];
    // Schreibe die Überschrift in die Vorlage
    fputcsv($output, $spalten, ';');
    
    // Füge eine Beispiel-Zeile hinzu, damit man das Format sieht
    fputcsv($output, [
        'Beispiel GmbH', 'Musterstraße 1', '12345', '+49 30 123456', 'Musterstadt', 'Deutschland', 'Lead', 'Erster Kontakt auf der Messe...'
    ], ';');
    fclose($output);
    exit();
}

// Ab hier beginnt die normale Seiten-Logik
include 'header.php'; // $pdo ist hier verfügbar
$page_title = "CSV Import";

$upload_feedback = null; // Für Erfolgs- oder Fehlermeldungen
$error_details = []; // Sammelt detaillierte Fehler

// --- Verarbeitet die hochgeladene Datei ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_datei"])) {
    
    if ($_FILES["csv_datei"]["error"] !== UPLOAD_ERR_OK) {
        $upload_feedback = ['success' => false, 'message' => 'Fehler beim Hochladen der Datei. (Code: ' . $_FILES["csv_datei"]["error"] . ')'];
    } else {
        $dateiname = $_FILES["csv_datei"]["tmp_name"];
        
        $mime_type = mime_content_type($dateiname);
        if ($mime_type != 'text/plain' && $mime_type != 'text/csv' && $mime_type != 'application/csv') {
             $upload_feedback = ['success' => false, 'message' => 'Fehler: Es sind nur CSV-Dateien erlaubt. (Erkannter Typ: ' . $mime_type . ')'];
        } else {
            
            $importierte_zeilen = 0;
            $fehlgeschlagene_zeilen = 0;
            $zeilennummer = 0;
            
            $pdo->beginTransaction();
            
            // Der korrekte SQL-Befehl (mit Straße und Notizen_Firma)
            $sql = "INSERT INTO Firmen (Firmenname, Straße, PLZ, Telefonnummer, Ort, Land, Status, Notizen_Firma) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            if (($handle = fopen($dateiname, "r")) !== FALSE) {
                
                $trennzeichen = ';';
                
                // Überschriften-Zeile überspringen
                $header = fgetcsv($handle, 1000, $trennzeichen);
                $zeilennummer++;
                
                // Zeilen lesen
                while (($data = fgetcsv($handle, 1000, $trennzeichen)) !== FALSE) {
                    $zeilennummer++;
                    
                    // Erwarte 8 Spalten laut unserer Anleitung
                    if (count($data) >= 8) {
                        try {
                            $stmt->execute([
                                $data[0], // A: Firmenname
                                $data[1], // B: Straße
                                $data[2], // C: PLZ
                                $data[3], // D: Telefonnummer
                                $data[4], // E: Ort
                                $data[5], // F: Land
                                $data[6], // G: Status
                                $data[7]  // H: Notizen_Firma
                            ]);
                            $importierte_zeilen++;
                        } catch (PDOException $e) {
                            $fehlgeschlagene_zeilen++;
                            if (count($error_details) < 10) { 
                                $error_details[] = "Fehler in Zeile $zeilennummer: " . $e->getMessage();
                            }
                        }
                    } else {
                         $fehlgeschlagene_zeilen++;
                         if (count($error_details) < 10) {
                            $error_details[] = "Fehler in Zeile $zeilennummer: Falsche Spaltenanzahl (erwartet: 8, gefunden: " . count($data) . ").";
                         }
                    }
                }
                fclose($handle);

                // Transaktion abschließen
                $pdo->commit();
                
                $upload_feedback = [
                    'success' => ($fehlgeschlagene_zeilen == 0), 
                    'message' => "Import abgeschlossen! $importierte_zeilen Firmen erfolgreich importiert. $fehlgeschlagene_zeilen Zeilen konnten nicht importiert werden."
                ];

            } else {
                 $upload_feedback = ['success' => false, 'message' => 'Fehler: Die Datei konnte nicht gelesen werden.'];
            }
        }
    }
}
?>

<div class="container">
    <div class="card-title-container">
        <h1 class="card-title">CSV-Import für Firmen</h1>
        <a href="firmen.php" class="btn btn-secondary btn-back">
            <i class="fas fa-arrow-left"></i>
            Zurück zur Firmenliste
        </a>
    </div>

    <div class="card card-full-width">
        <form action="csv_import.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="form-group">
                <label for="csv_datei">CSV-Datei auswählen</label>
                <input type="file" class="form-control" id="csv_datei" name="csv_datei" accept=".csv, text/csv" required>
                <div class="invalid-feedback">
                    Bitte wählen Sie eine Datei aus.
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i>
                Datei jetzt importieren
            </button>
        </form>
    </div>

    <?php if ($upload_feedback): ?>
        <div class="card card-full-width <?php echo $upload_feedback['success'] ? 'toast-success' : 'toast-error'; ?>" style="border-left-width: 5px; padding: 1.5rem; opacity: 1; transform: none; margin-top: 1.5rem;">
            <p style="margin: 0; font-weight: 600;"><?php echo htmlspecialchars($upload_feedback['message']); ?></p>
            
            <?php if (!empty($error_details)): ?>
                <hr style="margin: 1rem 0;">
                <strong>Fehler-Details (max. 10):</strong>
                <ul style="margin-top: 0.5rem; padding-left: 1.5rem; font-size: 0.9rem;">
                    <?php foreach ($error_details as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>


    <div class="card card-full-width" style="margin-top: 1.5rem;">
        <h2 class="card-subtitle">Anleitung für den Import</h2>
        <p>Um Fehler zu vermeiden, laden Sie am besten unsere Vorlage herunter, füllen Sie sie in Excel (oder einem Texteditor) aus und laden Sie sie hier hoch.</p>
        
        <a href="csv_import.php?vorlage=1" class="btn btn-secondary" style="margin-bottom: 1.5rem; display: inline-block; width: auto;">
            <i class="fas fa-file-csv"></i>
            Import-Vorlage herunterladen
        </a>

        <p><strong>WICHTIG:</strong> Das Trennzeichen muss ein **Semikolon ( ; )** sein. Die Spalten müssen exakt diese Reihenfolge haben:</p>
        
        <pre style="background-color: #f8f9fa; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; font-family: monospace; white-space: pre-wrap; word-break: break-all;">
Firmenname;Straße;PLZ;Telefonnummer;Ort;Land;Status;Notizen_Firma
Beispiel GmbH;Musterstraße 1;12345;+49 30 123456;Musterstadt;Deutschland;Lead;Erster Kontakt...
Nochne Firma AG;Hauptweg 5;54321;+49 40 654321;Hansestadt;Deutschland;Kunde;...
        </pre>
        
        <p style="margin-top: 1rem; font-weight: 600; color: #D9463A;">
            <i class="fas fa-exclamation-triangle"></i>
            Achtung: Die Datei, die Sie **exportieren**, enthält 10 Spalten (mit `Firma_ID` und `Erstellt_am`). Diese Export-Datei kann **nicht** direkt wieder importiert werden.
        </p>
    </div>

</div>

<?php include 'footer.php'; ?>