<?php
// 1. Datenbankverbindung herstellen
require_once 'db_verbindung.php'; // $pdo ist hier verfügbar

// 2. HTTP-Header setzen, um den Browser zum Download zu zwingen
$dateiname = "firmen_export_" . date("Y-m-d") . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $dateiname . '"');

// 3. Alle Spaltennamen (Überschriften)
// KORREKTUR: 'Strasse' -> 'Straße'
$spalten = [
    'Firma_ID',
    'Firmenname',
    'Straße', // HIER KORRIGIERT
    'PLZ',
    'Ort',
    'Land',
    'Telefonnummer',
    'Status',
    'Notizen_Firma',
    'Erstellt_am'
];

// 4. "php://output" ist ein spezieller Stream, der direkt an den Browser sendet
$output = fopen('php://output', 'w');

// 5. Schreibe die Überschriften-Zeile in die CSV (mit Semikolon für Excel)
fputcsv($output, $spalten, ';');

// 6. SQL-Abfrage, um alle Firmendaten zu holen
try {
    // KORREKTUR: 'Strasse' -> 'Straße'
    $sql = "SELECT Firma_ID, Firmenname, Straße, PLZ, Ort, Land, Telefonnummer, Status, Notizen_Firma, Erstellt_am 
            FROM Firmen 
            ORDER BY Firmenname ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // 7. Schleife durch alle Datenbank-Zeilen und schreibe sie in die CSV
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // KORREKTUR: 'Strasse' -> 'Straße'
        $csv_zeile = [
            $row['Firma_ID'],
            $row['Firmenname'],
            $row['Straße'], // HIER KORRIGIERT
            $row['PLZ'],
            $row['Ort'],
            $row['Land'],
            $row['Telefonnummer'],
            $row['Status'],
            $row['Notizen_Firma'],
            $row['Erstellt_am']
        ];
        fputcsv($output, $csv_zeile, ';');
    }

} catch (PDOException $e) {
    // Falls ein Fehler passiert, schreibe ihn in die CSV
    fputcsv($output, ['Fehler beim Export: ' . $e->getMessage()], ';');
}

// 8. Stream schließen und Skript beenden
fclose($output);
exit();

?>