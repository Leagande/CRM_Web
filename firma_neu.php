<?php
// firma_neu.php - VERBESSERT MIT ZUGRIFFSKONTROLLE
require_once 'db_verbindung.php'; // WICHTIG: $pdo muss verfügbar sein!
include 'header.php';
require_once 'berechtigungen.php';

// Verarbeiten des Formulars
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $firmenname = trim($_POST['firmenname']);
    $strasse = trim($_POST['strasse']); 
    $plz = trim($_POST['plz']);
    $telefonnummer = trim($_POST['telefonnummer']); 
    $ort = trim($_POST['ort']);
    $land = trim($_POST['land']);
    $status = $_POST['status'];
    $notizen = trim($_POST['notizen']);

    // ðŸ”’ WICHTIG: Erstellt_von wird automatisch auf aktuellen Benutzer gesetzt!
    $erstellt_von = get_aktuelle_benutzer_id();

    $sql = "INSERT INTO Firmen (Firmenname, StraÃŸe, PLZ, Telefonnummer, Ort, Land, Status, Notizen_Firma, Erstellt_von) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $firmenname, 
            $strasse, 
            $plz, 
            $telefonnummer, 
            $ort, 
            $land, 
            $status, 
            $notizen,
            $erstellt_von  // <-- NEU: Benutzer-ID speichern
        ]);

        $_SESSION['success_message'] = "Firma \"$firmenname\" wurde erfolgreich erstellt!";
        header("Location: firmen.php");
        exit();

    } catch (PDOException $e) {
        error_log("Fehler beim Erstellen der Firma: " . $e->getMessage());
        $_SESSION['error_message'] = "Fehler beim Erstellen der Firma.";
    }
}
?>

<div class="container">
    <div class="card-title-container">
        <h1 class="card-title">
            <i class="fas fa-plus-circle"></i>
            Neue Firma anlegen
        </h1>
        <a href="firmen.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            ZurÃ¼ck zur Ãœbersicht
        </a>
    </div>

    <div class="card form-card">
        <form action="firma_neu.php" method="POST" class="modern-form">
            
            <!-- Stammdaten -->
            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-building"></i>
                    Stammdaten
                </h3>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="firmenname">
                            <i class="fas fa-signature"></i>
                            Firmenname *
                        </label>
                        <input type="text" class="form-control" id="firmenname" name="firmenname" placeholder="z.B. Musterfirma GmbH" required>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="status">
                            <i class="fas fa-flag"></i>
                            Status *
                        </label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="Lead" selected>ðŸŽ¯ Lead</option>
                            <option value="Kunde">ðŸ’¼ Kunde</option>
                            <option value="Partner">ðŸ¤ Partner</option>
                            <option value="Kontaktversuch">ðŸ“ž Kontaktversuch</option>
                            <option value="Verloren">âŒ Verloren</option>
                            <option value="Archiviert">ðŸ“¦ Archiviert</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Adresse -->
            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Adresse
                </h3>

                <div class="form-group">
                    <label for="strasse">
                        <i class="fas fa-road"></i>
                        StraÃŸe & Hausnummer
                    </label>
                    <input type="text" class="form-control" id="strasse" name="strasse" placeholder="z.B. MusterstraÃŸe 123">
                </div>

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="plz">
                            <i class="fas fa-hashtag"></i>
                            PLZ
                        </label>
                        <input type="text" class="form-control" id="plz" name="plz" placeholder="12345" maxlength="5">
                    </div>

                    <div class="form-group col-md-5">
                        <label for="ort">
                            <i class="fas fa-city"></i>
                            Ort *
                        </label>
                        <input type="text" class="form-control" id="ort" name="ort" placeholder="z.B. Berlin" required>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="land">
                            <i class="fas fa-globe"></i>
                            Land *
                        </label>
                        <input type="text" class="form-control" id="land" name="land" value="Deutschland" required>
                    </div>
                </div>
            </div>

            <!-- Kontaktdaten -->
            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-phone"></i>
                    Kontaktdaten
                </h3>

                <div class="form-group">
                    <label for="telefonnummer">
                        <i class="fas fa-phone-alt"></i>
                        Telefonnummer
                    </label>
                    <input type="text" class="form-control" id="telefonnummer" name="telefonnummer" placeholder="z.B. +49 30 12345678">
                </div>
            </div>

            <!-- Notizen -->
            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-sticky-note"></i>
                    Notizen
                </h3>

                <div class="form-group">
                    <label for="notizen">Interne Notizen</label>
                    <textarea class="form-control" id="notizen" name="notizen" rows="4" placeholder="ZusÃ¤tzliche Informationen, wichtige Details, GesprÃ¤chsnotizen..."></textarea>
                </div>
            </div>

            <!-- Buttons -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i>
                    Firma anlegen
                </button>
                <a href="firmen.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i>
                    Abbrechen
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
