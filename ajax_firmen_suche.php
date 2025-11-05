<?php
// ajax_firmen_suche.php - MIT ZUGRIFFSKONTROLLE!
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfen ob eingeloggt
if (!ist_eingeloggt()) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// Suchbegriff holen
$query = $_GET['query'] ?? '';
if (strlen($query) < 2) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// Zugriffskontrolle
$where_zugriff = get_where_zugriff('Erstellt_von');

// SQL-Abfrage mit Zugriffskontrolle
$sql = "SELECT Firma_ID, Firmenname, Ort, PLZ, Status 
        FROM Firmen 
        WHERE {$where_zugriff}
        AND (Firmenname LIKE ? OR Ort LIKE ? OR PLZ LIKE ?)
        ORDER BY Firmenname ASC
        LIMIT 10";

try {
    $stmt = $pdo->prepare($sql);
    $search_param = "%$query%";
    $stmt->execute([$search_param, $search_param, $search_param]);
    $firmen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ergebnisse formatieren
    $results = [];
    foreach ($firmen as $firma) {
        $results[] = [
            'id' => $firma['Firma_ID'],
            'name' => $firma['Firmenname'],
            'ort' => $firma['PLZ'] . ' ' . $firma['Ort'],
            'status' => $firma['Status']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($results);
    
} catch (PDOException $e) {
    error_log("Fehler in ajax_firmen_suche.php: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler']);
}
?>
