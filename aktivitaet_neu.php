<?php
// aktivitaet_neu.php - MIT AUTO-ZUWEISUNG und GEFIXT!
session_start();
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
require_once 'header.php';

// Daten für Dropdowns abrufen
try {
    $where_firmen = get_where_zugriff('Erstellt_von');
    $where_projekte = get_where_zugriff('Zustaendig_ID');
    
    // Firmen
    $sql_firmen = "SELECT Firma_ID, Firmenname FROM Firmen WHERE {$where_firmen} ORDER BY Firmenname";
    $firmen = $pdo->query($sql_firmen)->fetchAll(PDO::FETCH_ASSOC);
    
    // Projekte
    $sql_projekte = "SELECT Projekt_ID, Projektname FROM Projekte WHERE {$where_projekte} ORDER BY Projektname";
    $projekte = $pdo->query($sql_projekte)->fetchAll(PDO::FETCH_ASSOC);
    
    // Ansprechpartner (von eigenen Firmen)
    $sql_ansprechpartner = "SELECT a.Ansprechpartner_ID, CONCAT(a.Vorname, ' ', a.Nachname) as Name, f.Firmenname
                            FROM Ansprechpartner a 
                            LEFT JOIN Firmen f ON a.Firma_ID = f.Firma_ID
                            WHERE {$where_firmen}
                            ORDER BY a.Nachname";
    $ansprechpartner = $pdo->query($sql_ansprechpartner)->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Fehler beim Laden der Formulardaten: " . $e->getMessage());
    $firmen = [];
    $projekte = [];
    $ansprechpartner = [];
}

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aktivitaetstyp = trim($_POST['aktivitaetstyp']);
    $datum = trim($_POST['datum']);
    $betreff = trim($_POST['betreff']);
    $notiz = trim($_POST['notiz']);
    $firma_id = !empty($_POST['firma_id']) ? filter_input(INPUT_POST, 'firma_id', FILTER_VALIDATE_INT) : null;
    $projekt_id = !empty($_POST['projekt_id']) ? filter_input(INPUT_POST, 'projekt_id', FILTER_VALIDATE_INT) : null;
    $ansprechpartner_id = !empty($_POST['ansprechpartner_id']) ? filter_input(INPUT_POST, 'ansprechpartner_id', FILTER_VALIDATE_INT) : null;
    $faelligkeitsdatum = !empty($_POST['faelligkeitsdatum']) ? trim($_POST['faelligkeitsdatum']) : null;
    $prioritaet = trim($_POST['prioritaet']);

    // AUTO-ZUWEISUNG: Zustaendig_ID
    $zustaendig_id = get_effektive_benutzer_id();

    $sql = "INSERT INTO Aktivitäten (Aktivitätstyp, Datum, Betreff, Notiz, Firma_ID, Projekt_ID, Ansprechpartner_ID, Status, Faelligkeitsdatum, Prioritaet, Zustaendig_ID)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Offen', ?, ?, ?)";

    try {
        $statement = $pdo->prepare($sql);
        $statement->execute([
            $aktivitaetstyp,
            $datum,
            $betreff,
            $notiz,
            $firma_id,
            $projekt_id,
            $ansprechpartner_id,
            $faelligkeitsdatum,
            $prioritaet,
            $zustaendig_id
        ]);

        $_SESSION['success_message'] = "Aktivität \"$betreff\" wurde erfolgreich angelegt!";
        header("Location: index.php");
        exit();
        
    } catch (PDOException $e) {
        error_log("Fehler beim Erstellen der Aktivität: " . $e->getMessage());
        $_SESSION['error_message'] = "Fehler beim Erstellen der Aktivität.";
    }
}

$jetzt = date('Y-m-d\TH:i');
?>

<div class="container">
    <div class="card-title-container">
        <h1 class="card-title">
            <i class="fas fa-plus-circle"></i>
            Neue Aktivität anlegen
        </h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Zurück
        </a>
    </div>

    <div class="card">
        <form method="POST" action="aktivitaet_neu.php">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="aktivitaetstyp">Aktivitätstyp *</label>
                    <select class="form-control" id="aktivitaetstyp" name="aktivitaetstyp" required>
                        <option value="">Bitte wählen...</option>
                        <option value="Anruf">Anruf</option>
                        <option value="E-Mail">E-Mail</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Aufgabe">Aufgabe</option>
                        <option value="Notiz">Notiz</option>
                        <option value="Vertrag">Vertrag</option>
                    </select>
                </div>

                <div class="form-group col-md-6">
                    <label for="datum">Datum & Uhrzeit *</label>
                    <input type="datetime-local" class="form-control" id="datum" name="datum" value="<?= $jetzt ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="betreff">Betreff *</label>
                <input type="text" class="form-control" id="betreff" name="betreff" required placeholder="z.B. Telefonat mit Kunden">
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="firma_id">Firma</label>
                    <select class="form-control" id="firma_id" name="firma_id">
                        <option value="">Keine Zuordnung</option>
                        <?php foreach ($firmen as $f): ?>
                            <option value="<?= $f['Firma_ID'] ?>"><?= htmlspecialchars($f['Firmenname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label for="projekt_id">Projekt</label>
                    <select class="form-control" id="projekt_id" name="projekt_id">
                        <option value="">Keine Zuordnung</option>
                        <?php foreach ($projekte as $p): ?>
                            <option value="<?= $p['Projekt_ID'] ?>"><?= htmlspecialchars($p['Projektname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label for="ansprechpartner_id">Ansprechpartner</label>
                    <select class="form-control" id="ansprechpartner_id" name="ansprechpartner_id">
                        <option value="">Keine Zuordnung</option>
                        <?php foreach ($ansprechpartner as $ap): ?>
                            <option value="<?= $ap['Ansprechpartner_ID'] ?>">
                                <?= htmlspecialchars($ap['Name']) ?> (<?= htmlspecialchars($ap['Firmenname']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="faelligkeitsdatum">Fälligkeitsdatum</label>
                    <input type="date" class="form-control" id="faelligkeitsdatum" name="faelligkeitsdatum">
                </div>

                <div class="form-group col-md-6">
                    <label for="prioritaet">Priorität</label>
                    <select class="form-control" id="prioritaet" name="prioritaet">
                        <option value="Normal" selected>Normal</option>
                        <option value="Hoch">Hoch</option>
                        <option value="Dringend">Dringend</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="notiz">Notizen</label>
                <textarea class="form-control" id="notiz" name="notiz" rows="4" placeholder="Detaillierte Notizen zur Aktivität..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Aktivität speichern
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
