<?php
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
include 'header.php';

// 1. ID aus der URL holen
$firma_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$firma_id) {
    $_SESSION['error_message'] = "Ungültige Firma-ID.";
    header("Location: firmen.php");
    exit();
}

// 3. Aktuelle Daten der Firma aus der DB laden
try {
    $stmt = $pdo->prepare("SELECT * FROM Firmen WHERE Firma_ID = ?");
    $stmt->execute([$firma_id]);
    $firma = $stmt->fetch();

    if (!$firma) {
        $_SESSION['error_message'] = "Firma nicht gefunden.";
        header("Location: firmen.php");
        exit();
    }
    
    // ZUGRIFFSKONTROLLE!
    pruefe_zugriff_oder_redirect($firma['Erstellt_von'], 'firmen.php');
    
} catch (PDOException $e) {
    die("Fehler beim Laden der Firmendaten: " . $e->getMessage());
}

// 2. Formular verarbeiten, wenn es gesendet wird
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Daten aus dem Formular abrufen
    $firmenname = $_POST['firmenname'];
    $strasse = $_POST['strasse'];
    $plz = $_POST['plz'];
    $telefonnummer = $_POST['telefonnummer'];
    $ort = $_POST['ort'];
    $land = $_POST['land'];
    $status = $_POST['status'];
    $notizen = $_POST['notizen'];

    // SQL-Befehl zum Aktualisieren der Firma
    $sql = "UPDATE Firmen SET 
                Firmenname = ?, 
                Strasse = ?, 
                PLZ = ?, 
                Telefonnummer = ?, 
                Ort = ?, 
                Land = ?, 
                Status = ?, 
                Notizen_Firma = ? 
            WHERE Firma_ID = ?";
    
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
            $firma_id
        ]);

        $_SESSION['success_message'] = "Firma erfolgreich aktualisiert!";
        header("Location: firma_details.php?id=" . $firma_id);
        exit();

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Fehler beim Aktualisieren der Firma: " . $e->getMessage();
    }
}

?>

<div class="container">
    <div class="card-title-container">
        <h1 class="card-title">Firma bearbeiten: <?php echo htmlspecialchars($firma['Firmenname']); ?></h1>
        <a href="firma_details.php?id=<?php echo $firma_id; ?>" class="btn btn-secondary btn-back">
            <i class="fas fa-arrow-left"></i>
            Zurück
        </a>
    </div>

    <div class="card card-full-width">
        <form action="firma_bearbeiten.php?id=<?php echo $firma_id; ?>" method="POST" class="needs-validation" novalidate>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="firmenname">Firmenname *</label>
                    <input type="text" class="form-control" id="firmenname" name="firmenname" value="<?php echo htmlspecialchars($firma['Firmenname']); ?>" required>
                </div>

                <div class="form-group col-md-6">
                    <label for="status">Status *</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="Lead" <?php echo ($firma['Status'] == 'Lead') ? 'selected' : ''; ?>>Lead</option>
                        <option value="Kunde" <?php echo ($firma['Status'] == 'Kunde') ? 'selected' : ''; ?>>Kunde</option>
                        <option value="Partner" <?php echo ($firma['Status'] == 'Partner') ? 'selected' : ''; ?>>Partner</option>
                        <option value="Verloren" <?php echo ($firma['Status'] == 'Verloren') ? 'selected' : ''; ?>>Verloren</option>
                        <option value="Archiviert" <?php echo ($firma['Status'] == 'Archiviert') ? 'selected' : ''; ?>>Archiviert</option>
                    </select>
                </div>
            </div>

            <hr>
            <h2 class="form-subtitle">Kontaktdetails</h2>

            <div class="form-group">
                <label for="telefonnummer">Telefonnummer</label>
                <input type="text" class="form-control" id="telefonnummer" name="telefonnummer" value="<?php echo htmlspecialchars($firma['Telefonnummer'] ?? ''); ?>" placeholder="z.B. +49 2561 123456">
            </div>

            <div class="form-group">
                <label for="strasse">Straße & Hausnummer</label>
                <input type="text" class="form-control" id="strasse" name="strasse" value="<?php echo htmlspecialchars($firma['Strasse'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="plz">PLZ</label>
                    <input type="text" class="form-control" id="plz" name="plz" value="<?php echo htmlspecialchars($firma['PLZ'] ?? ''); ?>">
                </div>
                <div class="form-group col-md-8">
                    <label for="ort">Ort</label>
                    <input type="text" class="form-control" id="ort" name="ort" value="<?php echo htmlspecialchars($firma['Ort'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="land">Land</label>
                <input type="text" class="form-control" id="land" name="land" value="<?php echo htmlspecialchars($firma['Land'] ?? 'Deutschland'); ?>">
            </div>
            
            <hr>

            <div class="form-group">
                <label for="notizen">Notizen (optional)</label>
                <textarea class="form-control" id="notizen" name="notizen" rows="4" placeholder="Interne Notizen zur Firma..."><?php echo htmlspecialchars($firma['Notizen_Firma'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Änderungen speichern
                </button>
                <a href="firma_details.php?id=<?php echo $firma_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Abbrechen
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.form-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
}
</style>

<?php include 'footer.php'; ?>
