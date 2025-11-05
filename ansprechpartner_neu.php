<?php
// ansprechpartner_neu.php
session_start();
require_once 'db_verbindung.php';

// Fall 1: Das Formular wird abgeschickt (POST-Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firma_id = filter_input(INPUT_POST, 'firma_id', FILTER_VALIDATE_INT);
    $vorname = trim($_POST['vorname']);
    $nachname = trim($_POST['nachname']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $position = trim($_POST['position']);

    // Nur fortfahren, wenn eine firma_id vorhanden ist
    if ($firma_id) {
        $sql = "INSERT INTO Ansprechpartner (Firma_ID, Vorname, Nachname, Email, Telefon, Position)
                VALUES (:firma_id, :vorname, :nachname, :email, :telefon, :position)";
        $statement = $pdo->prepare($sql);
        $statement->execute([
            ':firma_id' => $firma_id,
            ':vorname' => $vorname,
            ':nachname' => $nachname,
            ':email' => $email,
            ':telefon' => $telefon,
            ':position' => $position
        ]);

        $_SESSION['success_message'] = "Ansprechpartner \"$vorname $nachname\" wurde erfolgreich angelegt!";
        header("Location: ansprechpartner.php?firma_id=" . $firma_id);
        exit();
    } else {
        // Sollte nie passieren, wenn das Formular korrekt ist
        die("Fehler: Keine Firma ID übermittelt.");
    }
}

// Fall 2: Die Seite wird normal aufgerufen (GET-Request)
$firma_id = filter_input(INPUT_GET, 'firma_id', FILTER_VALIDATE_INT);
if (!$firma_id) {
    // Wenn keine ID in der URL ist, können wir keinen Ansprechpartner zuordnen.
    header("Location: firmen.php");
    exit();
}

$page_title = "Neuer Ansprechpartner";
require_once 'header.php';
?>

<header>
    <h1>Neuer Ansprechpartner</h1>
    <!-- GEÄNDERT: Ruft jetzt die JavaScript-Funktion auf -->
    <button onclick="goBack()" class="btn-back">Zurück</button>
</header>

<div class="card">
    <form action="ansprechpartner_neu.php" method="post">
        <input type="hidden" name="firma_id" value="<?= $firma_id ?>">
        <div class="form-group">
            <label for="vorname">Vorname</label>
            <input type="text" id="vorname" name="vorname">
        </div>
        <div class="form-group">
            <label for="nachname">Nachname</label>
            <input type="text" id="nachname" name="nachname" required>
        </div>
        <div class="form-group">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email">
        </div>
        <div class="form-group">
            <label for="telefon">Telefon</label>
            <input type="tel" id="telefon" name="telefon">
        </div>
        <div class="form-group">
            <label for="position">Position</label>
            <input type="text" id="position" name="position">
        </div>
        <button type="submit" class="btn">Ansprechpartner speichern</button>
    </form>
</div>

<?php
require_once 'footer.php';
?>

