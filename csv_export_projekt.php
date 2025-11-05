<?php
// csv_export_projekt.php - EXPORTIERT ALLE PROJEKTE MIT ZUGRIFFSKONTROLLE
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfen ob eingeloggt
if (!ist_eingeloggt()) {
    die("Zugriff verweigert.");
}

try {
    // Zugriffskontrolle
    $where_zugriff = get_where_zugriff('Zustaendig_ID');
    
    // SQL-Abfrage mit Zugriffskontrolle
    $sql = "SELECT 
                Projekt_ID,
                Projektname,
                Status,
                Startdatum,
                Enddatum_geplant,
                Budget,
                Notizen_Projekt
            FROM Projekte
            WHERE {$where_zugriff}
            ORDER BY Projektname ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $projekte = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // HTTP-Header für CSV-Download
    $dateiname = "Projekte_Export_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $dateiname . '"');
    
    // CSV erstellen
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM für Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Spaltenüberschriften
    $spalten = [
        'Projekt-ID',
        'Projektname',
        'Status',
        'Startdatum',
        'Enddatum (geplant)',
        'Budget',
        'Notizen'
    ];
    fputcsv($output, $spalten, ';');
    
    // Daten schreiben
    foreach ($projekte as $projekt) {
        $zeile = [
            $projekt['Projekt_ID'],
            $projekt['Projektname'],
            $projekt['Status'],
            $projekt['Startdatum'] ? date('d.m.Y', strtotime($projekt['Startdatum'])) : '',
            $projekt['Enddatum_geplant'] ? date('d.m.Y', strtotime($projekt['Enddatum_geplant'])) : '',
            $projekt['Budget'] ? number_format($projekt['Budget'], 2, ',', '.') . ' €' : '',
            $projekt['Notizen_Projekt'] ?? ''
        ];
        fputcsv($output, $zeile, ';');
    }
    
    fclose($output);
    exit();
    
} catch (PDOException $e) {
    error_log("Fehler beim CSV-Export: " . $e->getMessage());
    die("Fehler beim Erstellen des Exports.");
}
?>
