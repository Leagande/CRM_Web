<?php
// ansprechpartner_bearbeiten.php
session_start();
require_once 'db_verbindung.php';
// header.php MUSS zuerst eingebunden werden, um $modus zu haben
require_once 'header.php';

// --- NEU: Modus-Prüfung ---
if ($modus !== 'aussendienst') {
    $_SESSION['error_message'] = "Diese Funktion ist nur im Außendienst-Modus verfügbar.";
    header("Location: " . add_modus_param('start.php'));
    exit();
}
// --- Ende Modus-Prüfung ---


$ansprechpartner_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     // Zusätzliche Sicherheit: Auch POST Requests im falschen Modus abfangen
     if (($_POST['modus'] ?? 'aussendienst') !== 'aussendienst') {
        header("Location: " . add_modus_param('start.php'));
        exit();
    }

    $id = filter_input(INPUT_POST, 'ansprechpartner_id', FILTER_VALIDATE_INT);
    $firma_id = filter_input(INPUT_POST, 'firma_id', FILTER_VALIDATE_INT);
    $vorname = trim($_POST['vorname']);
    $nachname = trim($_POST['nachname']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $position = trim($_POST['position']);

    $sql = "UPDATE Ansprechpartner SET
                Vorname = :vorname,
                Nachname = :nachname,
                Email = :email,
                Telefon = :telefon,
                Position = :position
            WHERE Ansprechpartner_ID = :id";

    $statement = $pdo->prepare($sql);
    $statement->execute([
        ':vorname' => $vorname,
        ':nachname' => $nachname,
        ':email' => $email,
        ':telefon' => $telefon,
        ':position' => $position,
        ':id' => $id
    ]);

    $_SESSION['success_message'] = "Änderungen an \"$vorname $nachname\" wurden erfolgreich gespeichert!";
    header("Location: " . add_modus_param('ansprechpartner.php?firma_id=' . $firma_id)); // Modus an Redirect anhängen
    exit();
} else {
    // ID für GET-Request prüfen
    if (!$ansprechpartner_id) {
        header("Location: " . add_modus_param('firmen.php'));
        exit();
    }
    $sql = "SELECT * FROM Ansprechpartner WHERE Ansprechpartner_ID = :id";
    $statement = $pdo->prepare($sql);
    $statement->execute([':id' => $ansprechpartner_id]);
    $ansprechpartner = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$ansprechpartner) {
        header("Location: " . add_modus_param('firmen.php'));
        exit();
    }
}

$page_title = "Ansprechpartner bearbeiten";
// require_once 'header.php'; // Bereits oben
?>

<header>
    <h1>Ansprechpartner bearbeiten</h1>
    <button onclick="goBack()" class="btn-back">Zurück</button>
</header>

<div class="card">
     <!-- Wichtig: Modus als hidden field hinzufügen -->
    <form action="<?= add_modus_param('ansprechpartner_bearbeiten.php?id='.$ansprechpartner_id) ?>" method="post">
        <input type="hidden" name="modus" value="<?= htmlspecialchars($modus) ?>">
        <input type="hidden" name="ansprechpartner_id" value="<?= $ansprechpartner['Ansprechpartner_ID'] ?>">
        <input type="hidden" name="firma_id" value="<?= $ansprechpartner['Firma_ID'] ?>">
        <div class="form-group">
            <label for="vorname">Vorname</label>
            <input type="text" id="vorname" name="vorname" value="<?= htmlspecialchars($ansprechpartner['Vorname'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="nachname">Nachname</label>
            <input type="text" id="nachname" name="nachname" value="<?= htmlspecialchars($ansprechpartner['Nachname']) ?>" required>
        </div>
        <div class="form-group">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($ansprechpartner['Email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="telefon">Telefon</label>
            <input type="tel" id="telefon" name="telefon" value="<?= htmlspecialchars($ansprechpartner['Telefon'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="position">Position</label>
            <input type="text" id="position" name="position" value="<?= htmlspecialchars($ansprechpartner['Position'] ?? '') ?>">
        </div>
        <button type="submit" class="btn">Änderungen speichern</button>
    </form>
</div>

<?php
require_once 'footer.php';
?>

