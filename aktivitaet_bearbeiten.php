<?php
// aktivitaet_bearbeiten.php
session_start(); // Session starten, um eine Nachricht setzen zu können
require_once 'db_verbindung.php';
// header.php MUSS zuerst eingebunden werden, um $modus zu haben
require_once 'header.php';

$aktivitaet_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Obwohl Bearbeiten in beiden Modi geht, prüfen wir hier nicht explizit auf $modus,
// da die Seite ja über Links mit dem korrekten Modus aufgerufen wird.
// Eine Prüfung wäre nur nötig, wenn eine Funktion komplett gesperrt werden soll.

if (!$aktivitaet_id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . add_modus_param('index.php'));
    exit();
}

try {
    $firmen = $pdo->query("SELECT Firma_ID, Firmenname FROM Firmen ORDER BY Firmenname")->fetchAll(PDO::FETCH_ASSOC);
    $projekte = $pdo->query("SELECT Projekt_ID, Projektname FROM Projekte ORDER BY Projektname")->fetchAll(PDO::FETCH_ASSOC);
    $ansprechpartner = $pdo->query("SELECT Ansprechpartner_ID, CONCAT(Vorname, ' ', Nachname) as Name FROM Ansprechpartner ORDER BY Nachname")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("Fehler beim Laden der Formulardaten: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'aktivitaet_id', FILTER_VALIDATE_INT);
    $aktivitaetstyp = trim($_POST['aktivitaetstyp']);
    $datum = trim($_POST['datum']);
    $betreff = trim($_POST['betreff']);
    $notiz = trim($_POST['notiz']);
    $firma_id = !empty($_POST['firma_id']) ? filter_input(INPUT_POST, 'firma_id', FILTER_VALIDATE_INT) : null;
    $projekt_id = !empty($_POST['projekt_id']) ? filter_input(INPUT_POST, 'projekt_id', FILTER_VALIDATE_INT) : null;
    $ansprechpartner_id = !empty($_POST['ansprechpartner_id']) ? filter_input(INPUT_POST, 'ansprechpartner_id', FILTER_VALIDATE_INT) : null;
    $faelligkeitsdatum = !empty($_POST['faelligkeitsdatum']) ? trim($_POST['faelligkeitsdatum']) : null;
    $prioritaet = trim($_POST['prioritaet']);

    $sql = "UPDATE Aktivitäten SET
                Aktivitätstyp = :typ,
                Datum = :datum,
                Betreff = :betreff,
                Notiz = :notiz,
                Firma_ID = :firma_id,
                Projekt_ID = :projekt_id,
                Ansprechpartner_ID = :ansprechpartner_id,
                Faelligkeitsdatum = :faelligkeit,
                Prioritaet = :prio
            WHERE Aktivität_ID = :id";

    $statement = $pdo->prepare($sql);
    $statement->execute([
        ':typ' => $aktivitaetstyp,
        ':datum' => $datum,
        ':betreff' => $betreff,
        ':notiz' => $notiz,
        ':firma_id' => $firma_id,
        ':projekt_id' => $projekt_id,
        ':ansprechpartner_id' => $ansprechpartner_id,
        ':faelligkeit' => $faelligkeitsdatum,
        ':prio' => $prioritaet,
        ':id' => $id
    ]);

    // Setze die Erfolgsmeldung
    $_SESSION['success_message'] = "Änderungen an Aktivität \"$betreff\" wurden erfolgreich gespeichert!";
    // Beim Redirect den Modus mitgeben
    header("Location: " . add_modus_param('index.php'));
    exit();
} else {
    // ID für GET-Request prüfen
    if (!$aktivitaet_id) {
        header("Location: " . add_modus_param('index.php')); // Modus hinzufügen
        exit();
    }
    $sql = "SELECT * FROM Aktivitäten WHERE Aktivität_ID = :id";
    $statement = $pdo->prepare($sql);
    $statement->execute([':id' => $aktivitaet_id]);
    $aktivitaet = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$aktivitaet) {
        header("Location: " . add_modus_param('index.php')); // Modus hinzufügen
        exit();
    }
}

$page_title = "Aktivität bearbeiten";
// require_once 'header.php'; // Bereits oben
?>

<header>
    <h1>Aktivität bearbeiten</h1>
    <button onclick="goBack()" class="btn-back">Zurück</button>
</header>

<div class="card">
     <!-- Wichtig: Modus als hidden field hinzufügen -->
    <form action="<?= add_modus_param('aktivitaet_bearbeiten.php?id='.$aktivitaet_id) ?>" method="post">
         <input type="hidden" name="modus" value="<?= htmlspecialchars($modus) ?>">
        <input type="hidden" name="aktivitaet_id" value="<?= $aktivitaet['Aktivität_ID'] ?>">

        <div class="form-group">
            <label for="aktivitaetstyp">Aktivitätstyp</label>
            <select id="aktivitaetstyp" name="aktivitaetstyp" required>
                <option value="Aufgabe" <?= $aktivitaet['Aktivitätstyp'] == 'Aufgabe' ? 'selected' : '' ?>>Aufgabe</option>
                <option value="Anruf" <?= $aktivitaet['Aktivitätstyp'] == 'Anruf' ? 'selected' : '' ?>>Anruf</option>
                <option value="E-Mail" <?= $aktivitaet['Aktivitätstyp'] == 'E-Mail' ? 'selected' : '' ?>>E-Mail</option>
                <option value="Meeting" <?= $aktivitaet['Aktivitätstyp'] == 'Meeting' ? 'selected' : '' ?>>Meeting</option>
            </select>
        </div>

        <div class="form-group">
            <label for="datum">Datum & Uhrzeit</label>
            <input type="datetime-local" id="datum" name="datum" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($aktivitaet['Datum']))) ?>" required>
        </div>

        <div class="form-group">
            <label for="betreff">Betreff</label>
            <input type="text" id="betreff" name="betreff" value="<?= htmlspecialchars($aktivitaet['Betreff']) ?>" required>
        </div>

        <div class="form-group">
            <label for="faelligkeitsdatum">Fällig bis (optional)</label>
            <input type="date" id="faelligkeitsdatum" name="faelligkeitsdatum" value="<?= htmlspecialchars($aktivitaet['Faelligkeitsdatum'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Priorität</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="prioritaet" value="Normal" <?= ($aktivitaet['Prioritaet'] ?? 'Normal') == 'Normal' ? 'checked' : '' ?>> Normal
                </label>
                <label class="radio-label">
                    <input type="radio" name="prioritaet" value="Hoch" <?= $aktivitaet['Prioritaet'] == 'Hoch' ? 'checked' : '' ?>> Hoch
                </label>
                <label class="radio-label">
                    <input type="radio" name="prioritaet" value="Dringend" <?= $aktivitaet['Prioritaet'] == 'Dringend' ? 'checked' : '' ?>> Dringend
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="firma_id">Zugehörige Firma (optional)</label>
            <select id="firma_id" name="firma_id">
                <option value="">-- Bitte wählen --</option>
                <?php foreach ($firmen as $firma): ?>
                    <option value="<?= $firma['Firma_ID'] ?>" <?= $aktivitaet['Firma_ID'] == $firma['Firma_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($firma['Firmenname']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="projekt_id">Zugehöriges Projekt (optional)</label>
            <select id="projekt_id" name="projekt_id">
                <option value="">-- Bitte wählen --</option>
                <?php foreach ($projekte as $projekt): ?>
                    <option value="<?= $projekt['Projekt_ID'] ?>" <?= $aktivitaet['Projekt_ID'] == $projekt['Projekt_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($projekt['Projektname']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="ansprechpartner_id">Zugehöriger Ansprechpartner (optional)</label>
            <select id="ansprechpartner_id" name="ansprechpartner_id">
                <option value="">-- Bitte wählen --</option>
                <?php foreach ($ansprechpartner as $kontakt): ?>
                    <option value="<?= $kontakt['Ansprechpartner_ID'] ?>" <?= $aktivitaet['Ansprechpartner_ID'] == $kontakt['Ansprechpartner_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($kontakt['Name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="notiz">Notiz</label>
            <textarea id="notiz" name="notiz" rows="5"><?= htmlspecialchars($aktivitaet['Notiz'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn">Änderungen speichern</button>
    </form>
</div>

<?php
require_once 'footer.php';
?>

